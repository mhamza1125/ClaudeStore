# Product images

## Workflow

1. **Upload** originals in Admin → Products → (product) → Images (JPG/PNG/WebP, ≥ 300 px,
   ≤ 15 MB). Choose the view type (front, back, side, detail, lifestyle, model, packaging) and
   optionally a variant.
2. The **original is stored privately** on the `local` disk (`storage/app/private/product-originals`)
   and never modified.
3. `OptimizeProductImage` writes a resized (≤ 2000 px) **WebP display master**, also on the private
   disk (`product-display/`). Visitors never receive it directly (see Protection below).
4. Only images with status **approved** appear on the storefront. Uploads are approved by default;
   tick "Upload as draft for review" for retouched or AI-generated images.
5. Staff can approve, reject, reorder, re-assign to a variant, edit alt text, or delete. Deleting
   an uploaded image also deletes its private original.

## Protection

Settings: Admin → B2B settings → Image watermarking (docs/B2B.md).

**Server-side controls** (these actually restrict access):

- Originals and display masters live on the private disk and have no public URL.
- Images are served by `GET /media/products/{image}/{thumb|card|large}/{signature}`
  (`ProductImageController`, `App\Images\ProtectedImages`). The signature is an HMAC of the image
  ID and size, so URLs can't be guessed or enumerated, and each size needs its own signature.
- Only approved images of published products are served; drafts, rejected images and unpublished
  products return 404 except to catalogue staff. Staff screens use `?raw=1` for unwatermarked
  copies, which is ignored for everyone else.
- Responses are resized renditions: 320 px (thumbnails, search, quote list), 800 px (cards,
  gallery) and 1600 px (zoom, retina gallery, homepage banner). The 2000 px master and the
  original are never sent.
- **One centred watermark** (the business name, about 40% of the image width, semi-transparent
  white with a faint shadow) plus, if enabled, the signed-in customer's email or
  "Customer #ID" in small type underneath. Two modes (Admin → B2B settings):
  - **embedded** (default): drawn into the resized copy that is served. The original upload and
    the private master are never modified. The mark stays on saved or shared files.
    Personalised copies are sent with `Cache-Control: private` and per-viewer URLs.
  - **overlay**: copies are served unmarked and `<x-image-watermark>` lays the same centred mark
    over each image with CSS (cards, gallery, thumbnails, zoom, search, quote list, banner).
    This is a deterrent only: the mark is not in the downloaded file and can be removed with
    developer tools.
- The route is rate-limited to 300 requests per minute per IP, which slows bulk scraping.

**Deterrents** (they discourage casual copying but can't stop a determined user):

- Right-click and drag-to-save are blocked on product images, with no iOS long-press
  "Save image" and no selection.
- Images load inside the page rather than as obvious files.

Screenshots, screen recording and browser developer tools can't be prevented by any website. A
viewer can always capture what is on their screen. That is why the watermark (and the
personalised identifier) is the main protection: copies stay attributable.

Renditions are cached in `storage/app/private/image-cache/{image id}/`, keyed by file, size and
watermark. They are deleted with the image, and `images:prune-cache` (scheduled daily) removes
files older than 14 days. Changing watermark settings changes the URLs, so browsers fetch new
copies. Demo SVG illustrations get the same watermark as an SVG overlay, served with a
restrictive Content-Security-Policy. The watermark font is DejaVu Sans Bold
(`resources/fonts`, free licence, from dompdf).

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
