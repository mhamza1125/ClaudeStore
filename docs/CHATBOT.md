# Website chatbot

A floating assistant in the bottom-right corner of the storefront and customer portal. It answers
questions about the business, its products and services, and guides visitors toward requesting a
quote. It is powered by Claude through the official Anthropic PHP SDK (`anthropic-ai/sdk`).

## How it works

```
browser (Alpine widget) ──POST /assistant/messages (CSRF, rate limited)──▶ AssistantController
        ▲                                                                       │
        └────────────────────── {reply} ◀── ChatProvider (Anthropic SDK) ◀──────┘
                                              system prompt = BusinessContext (public data only)
```

- **Server-side only.** The browser talks to our own endpoint. `ANTHROPIC_API_KEY` is read from
  `.env` and is never rendered into pages or JavaScript (covered by a test).
- **Business knowledge** (`app/Assistant/BusinessContext.php`): brand and contact settings, active
  categories, up to 150 published products (description, material, options, MOQ, availability,
  lead time, **guest-visible prices only**), published policies, and the "Additional knowledge"
  text from Admin → Settings. Trade-only, customer-group and negotiated prices are never included,
  even for signed-in buyers, so they cannot leak through the chatbot. The prompt is cached for five
  minutes, rebuilt when settings are saved, and sent with prompt caching enabled.
- **Behaviour:** short plain-text answers. It states only the facts it is given, says when it
  doesn't know and suggests a quote request or contact, never offers discounts, can't see
  accounts or orders, and links to pages such as `/quote`.
- **Conversation storage:** the last 20 messages live in the visitor's server session (not the
  database) and are sent with each new question. "New chat" clears them.
- **Safety:** messages are capped at 1,000 characters; the endpoint is limited per IP
  (8/minute and 80/day by default). Server-side refusal fallback is enabled. Replies are rendered
  as text (no HTML); only site paths and WhatsApp links become clickable. Visitor message content
  is not written to logs.

## Configuration

**Admin → Settings → Chatbot** (stored in the settings table):

| Setting | Effect |
| --- | --- |
| Show the chatbot | On/off, independent of the WhatsApp button (off by default) |
| Assistant name | Shown in the widget (defaults to "<brand> assistant") |
| Introductory message | Teaser bubble and first message in the chat |
| Pop up the introductory message / delay | Whether and after how many seconds the teaser appears (once per browser session after dismissal) |
| Additional knowledge | Extra facts and FAQs the assistant may use (shipping regions, sampling, certifications…) |

The settings page shows whether the AI provider is connected.

**Environment (`.env`)**

| Variable | Default | Notes |
| --- | --- | --- |
| `ANTHROPIC_API_KEY` | — | **Required for live answers.** Create one at console.anthropic.com |
| `ASSISTANT_PROVIDER` | anthropic | `null` forces offline mode |
| `ASSISTANT_MODEL` | claude-opus-5-5 | Any Claude model ID. For lower cost per message, `claude-haiku-5-5` or `claude-sonnet-5-5` |
| `ASSISTANT_EFFORT` | low | `low`/`medium`/`high`; low suits short chat answers |
| `ASSISTANT_MAX_TOKENS` | 2048 | Upper bound per reply |
| `ASSISTANT_TIMEOUT` | 30 | Seconds; one retry on transient errors |
| `ASSISTANT_RATE_PER_MINUTE` / `ASSISTANT_RATE_PER_DAY` | 8 / 80 | Per IP |

After changing `.env` in production run `php artisan config:cache`.

## Without credentials

If the chatbot is enabled but no key is set, the launcher shows a grey "offline" dot and opens a
panel with links to the quote form, email and WhatsApp (if enabled). It never imitates an AI
answer.

## Costs and privacy

Each visitor message is one Messages API call (system prompt mostly served from the prompt cache,
plus recent history). Monitor usage in the Anthropic Console and adjust the model, effort or rate
limits. Visitor messages are sent to Anthropic for processing; mention this in your privacy
policy. The widget tells visitors not to share personal or payment details.

## Verification status

- Automated tests cover the endpoint, session history, validation, rate limiting, offline mode,
  admin settings, the business-context privacy rules, and the SDK request shape (model, cached
  system prompt, effort, fallback header, error mapping) using a mocked HTTP transport.
- A browser run exercised the complete path (widget → endpoint → Anthropic SDK) against a local
  stand-in API. **No call was made to the live Anthropic API** in this environment, because no key
  was available. Send a few test questions after adding your key, and review the answers before
  enabling the chatbot publicly.

## Extending

Implement `App\Assistant\Contracts\ChatProvider` for another provider and register it in
`App\Assistant\AssistantManager::make()`.
