<x-layouts.admin title="Import / export">
    <x-page-header title="Product import / export" eyebrow="Catalogue"/>
    @if (session('import_errors'))
        <div class="mb-6 panel text-sm"><p class="mb-2 font-medium text-danger">Problems found:</p><ul class="list-disc space-y-1 pl-5">@foreach (session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="panel">
            <h2 class="text-2xl">Export</h2>
            <p class="mt-2 text-sm text-muted">Download all products (including drafts and archived) with their standard price tiers.</p>
            <a href="{{ route('admin.csv.export') }}" class="btn btn-primary mt-4"><x-icon name="download" class="h-4 w-4"/> Download CSV</a>
        </section>
        <form method="POST" action="{{ route('admin.csv.import') }}" enctype="multipart/form-data" class="panel space-y-4">
            @csrf
            <h2 class="text-2xl">Import</h2>
            <p class="text-sm text-muted">Rows are matched by SKU: existing products are updated, new SKUs are created as drafts unless <code>status</code> says otherwise. Empty cells leave existing values unchanged. If any row is invalid, nothing is saved.</p>
            <div><label for="file" class="field-label">CSV file (UTF-8, max 5 MB)</label><input id="file" type="file" name="file" accept=".csv,text/csv" required class="input">@error('file')<p class="field-error">{{ $message }}</p>@enderror</div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="dry_run" value="1" class="checkbox" checked> Dry run (validate only)</label>
            <button class="btn btn-primary"><x-icon name="upload" class="h-4 w-4"/> Upload</button>
        </form>
    </div>
    <section class="panel mt-6 text-sm">
        <h2 class="mb-2 text-2xl">Columns</h2>
        <p class="text-muted"><code>{{ implode(', ', $columns) }}</code></p>
        <p class="mt-2 text-muted">Booleans: 1/0, true/false or yes/no. Prices: decimals like <code>18.00</code>. <code>price_tiers</code>: <code>1:18.00|25:15.00|100:12.50</code> (replaces standard product tiers; leave the column out to keep existing tiers). Variants, images and group/negotiated prices are managed in the admin UI.</p>
    </section>
</x-layouts.admin>
