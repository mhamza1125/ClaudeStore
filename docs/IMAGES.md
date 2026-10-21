# Product images

## Workflow

1. **Upload** originals in Admin → Products → (product) → Images (JPG/PNG/WebP, ≥ 300 px,
   ≤ 15 MB). Choose the view type (front, back, side, detail, lifestyle, model, packaging) and
   optionally a variant.
2. The **original is stored privately** on the `local` disk (`storage/app/private/product-originals`)
   and never modified.
3. A queued job (`OptimizeProductImage`) writes a resized (≤ 2000 px) **WebP** to the `public` disk.
   Until the worker runs, the image shows as "processing".
4. Only images with status **approved** appear on the storefront. Uploads are approved by default;
   tick "Upload as draft for review" for retouched or AI-generated images.
5. Staff can approve, reject, reorder, re-assign to a variant, edit alt text, or delete. Deleting
   an uploaded image also deletes its private original.

## Provider interface

`App\Images\Contracts\ImageProvider` is provider-independent:

- `isConfigured()`, `capabilities()`
- `removeBackground(binary, mime): ProviderResult`
- `generateView(referenceBinaries, viewType, instructions): ProviderResult`

Bundled implementations:

| `IMAGE_PROVIDER` | Class | Notes |
| --- | --- | --- |
| `null` (default) | `NullImageProvider` | Never produces images. UI shows the manual workflow. |
| `removebg` | `RemoveBgProvider` | Background removal via the remove.bg HTTP API. Needs `REMOVEBG_API_KEY`. |

Provider output is **always a new draft** (`status = pending_review`, `source = processed`,
`parent_id` → the source image). The original stays untouched and the draft must be approved by a
person.

**Verification status:** `RemoveBgProvider` is implemented against remove.bg's documented API
(`POST https://api.remove.bg/v1.0/removebg`, header `X-Api-Key`, field `image_file`) and is tested
with faked HTTP responses only. Test it with a real key before relying on it.

No AI image-generation provider is bundled. The application never claims an image was generated
when no provider is configured.

### Adding a provider

1. Implement `ImageProvider` in `app/Images/Providers/YourProvider.php`.
2. Register it in `App\Images\ImageProviderManager::make()`.
3. Add its env keys to `config/store.php` and docs/ENV.md.
4. If it supports generation, add a UI action that, like `removeBackground`, creates a draft and
   dispatches a job.

## Accuracy policy

Generated or edited images must not silently change logos, hardware, stitching, leather texture or
colour. Treat alternate views generated from incomplete references as drafts, compare them
against the original before approving (the admin card links each draft to its source), and reject
anything inaccurate.

## Manual fallback (no credentials)

Edit or generate images in an external tool, then upload them with "Upload as draft for review",
check them against the originals, and approve them.

## Demo images

Seeded demo products use generated **SVG illustrations** labelled "DEMO IMAGE" (source `demo`).
Replace them with real photography before launch.
