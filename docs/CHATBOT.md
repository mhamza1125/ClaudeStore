# Website chatbot

A floating assistant in the bottom-right corner of the storefront and customer portal. It answers
questions about the business, its products and services, and guides visitors toward requesting a
quote. The AI provider is pluggable: **Cloudflare Workers AI is the default**
(`AI_DEFAULT_PROVIDER=cloudflare`, REST API via Laravel's HTTP client); Anthropic Claude (official
`anthropic-ai/sdk`) remains available with `AI_DEFAULT_PROVIDER=anthropic`.

## How it works

```
browser (Alpine widget) ──POST /assistant/messages (CSRF, rate limited)──▶ AssistantController
        ▲                                                                       │
        └────────────────────── {reply} ◀── ChatProvider (Cloudflare | Anthropic) ◀┘
                                              system prompt = BusinessContext (public data only)
```

- **Server-side only.** The browser talks to our own endpoint. `CLOUDFLARE_API_TOKEN`,
  `CLOUDFLARE_ACCOUNT_ID` and `ANTHROPIC_API_KEY` are read from `.env` (via `config/services.php`)
  and are never rendered into pages, JavaScript, JSON responses or logs (covered by tests).
- **Business knowledge** (`app/Assistant/BusinessContext.php`): the company introduction and story
  (Admin → Settings, else the brand description), location, time zone and contact routes; the
  website's own service, private-label and trade-term copy (`config/store.php` → `services`,
  `manufacturing`, `trade_terms`, the same lists the homepage and /custom-manufacturing show);
  the ordering steps, quote validity, accepted attachment types and the current price-visibility
  rule (from Admin → B2B settings); active categories **with published product counts** (empty
  ones are marked "no products listed yet"); up to 150 published products (description, material,
  options, MOQ, availability, lead time, raw-leather flag, **guest-visible prices only**);
  published policies; and the "Additional knowledge" text from Admin → Settings. Trade-only, customer-group and negotiated prices are never included,
  even for signed-in buyers, so they cannot leak through the chatbot. The prompt is cached for five
  minutes and rebuilt when settings are saved (the Anthropic provider also uses prompt caching).
- **Behaviour:** a B2B sales assistant. It answers first, asks one or two qualifying questions
  when useful (business type, products, quantities, branding, destination, timing), routes the
  visitor to catalogue pages, /custom-manufacturing or raw leather, and once the need is clear
  offers a short brief to paste into the /quote form. It does not collect contact details in the
  chat. It states only the facts it is given and explicitly does not invent capacity,
  certifications, years in business, sample fees, lead times, shipping costs or client names; it
  says empty categories have no listed styles and can be quoted as custom work; it never offers
  discounts, can't see accounts or orders, replies in the visitor's language, and links only to
  paths in its business information.
- **Conversation storage:** the last 20 messages live in the visitor's server session (not the
  database) and are sent with each new question. "New chat" clears them.
- **Safety:** empty and control-character-only messages are rejected; messages are capped at 1,000
  characters; the endpoint is limited per IP (8/minute and 80/day by default). Only one AI request
  per visitor session runs at a time (a cache lock; a second one gets HTTP 409), and resubmitting
  the same text within 15 seconds returns the previous answer without a new API call. The widget
  also disables sending while a reply is pending. Replies are rendered
  as text (no HTML); only site paths and WhatsApp links become clickable. Visitor message content
  is not written to logs.

## Configuration

**Admin → Settings → Chatbot** (stored in the settings table):

| Setting | Effect |
| --- | --- |
| Show the chatbot | On/off, independent of the WhatsApp button (off by default) |
| Assistant name | Shown in the widget (defaults to "<brand> assistant") |
| Introductory message | First message at the top of the chat window |
| Short prompt above the closed button / prompt text / delay | A one-line pill above the launcher (default "Wholesale or custom order? Ask us", max 48 characters). It appears after the delay at most once per browser session and hides itself after 10 seconds, or when dismissed or the chat opens |
| Additional knowledge | Extra facts and FAQs the assistant may use. **This is the only way to give it facts the website does not state** (countries served, sample fees and timing, typical lead times, capacity, certifications). Without them it says it doesn't know |

The settings page shows whether the AI provider is connected.

**Environment (`.env`)**

| Variable | Default | Notes |
| --- | --- | --- |
| `AI_DEFAULT_PROVIDER` | cloudflare | `cloudflare`, `anthropic` or `null` (forces offline mode) |
| `CLOUDFLARE_ACCOUNT_ID` | — | **Required for Cloudflare.** Shown in the Cloudflare dashboard (account home / Workers AI) |
| `CLOUDFLARE_API_TOKEN` | — | **Required for Cloudflare.** Create under My Profile → API Tokens with the *Workers AI* permission for this account |
| `CLOUDFLARE_DEFAULT_MODEL` | @cf/meta/llama-3.3-70b-instruct-fp8-fast | Any Workers AI text-generation model ID |
| `CLOUDFLARE_AI_TEMPERATURE` | 0.3 | |
| `ANTHROPIC_API_KEY` | — | Required only for the Anthropic provider |
| `ASSISTANT_MODEL` / `ASSISTANT_EFFORT` | claude-opus-5-5 / low | Anthropic provider only |
| `ASSISTANT_MAX_TOKENS` | 1024 | Upper bound per reply |
| `ASSISTANT_TIMEOUT` | 30 | Seconds; one retry on network errors and 5xx responses |
| `ASSISTANT_RATE_PER_MINUTE` / `ASSISTANT_RATE_PER_DAY` | 8 / 80 | Per IP |

> **Model ID.** `@cf/meta/llama-3.3-70b` (without a suffix) does not match Cloudflare's published
> model name as far as we know; the documented ID is `@cf/meta/llama-3.3-70b-instruct-fp8-fast`,
> which is the default. Check the Workers AI model catalogue before changing it — an unknown model
> returns an error that `php artisan assistant:check` reports.

### Activating Cloudflare Workers AI

1. Set `CLOUDFLARE_ACCOUNT_ID`, `CLOUDFLARE_API_TOKEN` (and optionally `CLOUDFLARE_DEFAULT_MODEL`)
   in the server's `.env` or secret store — never in the repository.
2. `php artisan config:cache`
3. `php artisan assistant:check` — sends one test message and prints the provider, model, latency
   and the first words of the reply, or the HTTP status with a hint (401/403 token or permission,
   404 account or model, 429 rate limit). Exit code 0 means a live answer was received.
4. Admin → Settings → Chatbot → *Show the chatbot*. The settings page shows the provider and model.

After changing `.env` in production run `php artisan config:cache`.

## Without credentials

If the chatbot is enabled but the provider's credentials are missing, the launcher shows a grey "offline" dot and opens a
panel with links to the quote form, email and WhatsApp (if enabled). It never imitates an AI
answer.

## Costs and privacy

Each visitor message is one provider call (system prompt plus recent history; the system prompt is
roughly 2–10k tokens depending on catalogue size). Cloudflare bills Workers AI in "neurons" with a
daily free allocation; monitor usage in the Cloudflare dashboard and adjust the model or rate
limits. Visitor messages are sent to the configured provider for processing; mention this in your
privacy policy. The widget tells visitors not to share personal or payment details.

## Verification status

- Automated tests cover the endpoint, session history, validation, rate limiting, the per-session
  lock and duplicate guard, offline mode, admin settings and the business-context privacy rules.
- Cloudflare provider tests (`tests/Feature/Assistant/CloudflareChatProviderTest.php`, faked HTTP)
  check the URL, Bearer header, payload, both response shapes, retries, error mapping, model
  validation and that the token is not logged or rendered.
- A browser run exercised widget → endpoint → Cloudflare provider against a **local stand-in** for
  the Workers AI endpoint. **No call was made to the live Cloudflare API**: no credentials were
  available and outbound access to `api.cloudflare.com` is blocked in the build environment. Run
  `php artisan assistant:check` after configuring, and review some answers before enabling the
  chatbot publicly. Llama models may follow the system prompt less strictly than Claude; test
  questions about prices and discounts in particular.

## Extending

Implement `App\Assistant\Contracts\ChatProvider` for another provider and register it in
`App\Assistant\AssistantManager::make()`.
