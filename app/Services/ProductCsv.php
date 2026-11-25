<?php

namespace App\Services;

use App\Enums\Availability;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Product CSV import/export. Rows are matched by SKU (update) or created.
 * price_tiers format: "1:18.00|25:15.00|100:12.50" (standard product-level tiers).
 * Import is all-or-nothing: any invalid row aborts the whole file.
 */
class ProductCsv
{
    public const COLUMNS = [
        'sku', 'name', 'slug', 'category_slug', 'status', 'short_description', 'description', 'material', 'leather_type',
        'dimensions', 'hardware', 'finishing', 'packaging', 'lead_time', 'base_price', 'moq', 'order_multiple',
        'availability', 'stock_quantity', 'quote_enabled', 'public_pricing', 'price_on_request', 'is_custom',
        'customizable', 'is_featured', 'is_bestseller', 'is_raw_leather', 'price_tiers',
    ];

    private const BOOLEANS = ['quote_enabled', 'public_pricing', 'price_on_request', 'is_custom', 'customizable', 'is_featured', 'is_bestseller', 'is_raw_leather'];

    /** @param resource $out */
    public function export($out): void
    {
        fputcsv($out, self::COLUMNS);
        Product::query()->with(['category', 'priceTiers' => fn ($q) => $q->whereNull('variant_id')->whereNull('customer_group_id')])
            ->orderBy('id')->chunk(200, function ($products) use ($out) {
                foreach ($products as $p) {
                    $row = [
                        $p->sku, $p->name, $p->slug, $p->category?->slug, $p->status, $p->short_description, $p->description,
                        $p->material, $p->leather_type, $p->dimensions, $p->hardware, $p->finishing, $p->packaging, $p->lead_time,
                        Money::toDecimalString($p->base_price_cents), $p->moq, $p->order_multiple, $p->availability->value,
                        $p->stock_quantity, ...array_map(fn ($b) => $p->{$b} ? 1 : 0, self::BOOLEANS),
                        $p->priceTiers->map(fn ($t) => $t->min_qty.':'.Money::toDecimalString($t->unit_price_cents))->implode('|'),
                    ];
                    fputcsv($out, array_map([$this, 'escapeCell'], $row));
                }
            });
    }

    /** Neutralise spreadsheet formula injection. */
    public function escapeCell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * @return array{created:int, updated:int, errors:array<int,string>}
     */
    public function import(string $path, bool $dryRun = false): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        if (! $header) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['The file is empty.']];
        }
        $header = array_map(fn ($h) => strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', $h))), $header);
        $missing = array_diff(['sku', 'name'], $header);
        if ($missing) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['Missing required columns: '.implode(', ', $missing)]];
        }

        $rows = [];
        $errors = [];
        $line = 1;
        $seen = [];
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if ($data === [null] || count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            if ($line > 5001) {
                $errors[] = 'Files are limited to 5,000 rows.';
                break;
            }
            $row = array_combine($header, array_pad(array_slice($data, 0, count($header)), count($header), null));
            $row = array_map(fn ($v) => is_string($v) ? trim(ltrim($v, "'")) : $v, $row);
            try {
                $parsed = $this->parseRow($row);
            } catch (InvalidArgumentException $e) {
                $errors[] = "Row {$line}: ".$e->getMessage();

                continue;
            }
            if (isset($seen[$parsed['sku']])) {
                $errors[] = "Row {$line}: duplicate SKU {$parsed['sku']} in file.";

                continue;
            }
            $seen[$parsed['sku']] = true;
            $rows[$line] = $parsed;
        }
        fclose($handle);

        if ($errors) {
            return ['created' => 0, 'updated' => 0, 'errors' => $errors];
        }

        $created = $updated = 0;
        DB::beginTransaction();
        try {
            foreach ($rows as $line => $r) {
                $product = Product::query()->where('sku', $r['sku'])->first();
                $attrs = $r['attributes'];
                if (! $product) {
                    $attrs['slug'] ??= $this->uniqueSlug($r['attributes']['name']);
                    $attrs['published_at'] = ($attrs['status'] ?? 'draft') === 'published' ? now() : null;
                    $product = Product::create(['sku' => $r['sku']] + $attrs + ['status' => 'draft']);
                    $created++;
                } else {
                    if (isset($attrs['slug']) && Product::query()->where('slug', $attrs['slug'])->whereKeyNot($product->id)->exists()) {
                        throw new InvalidArgumentException("Row {$line}: slug {$attrs['slug']} is used by another product.");
                    }
                    if (($attrs['status'] ?? null) === 'published' && ! $product->published_at) {
                        $attrs['published_at'] = now();
                    }
                    $product->update($attrs);
                    $updated++;
                }
                if ($r['tiers'] !== null) {
                    // Product-level standard tiers only, updated in place (audit F-04).
                    app(PriceTierSync::class)->sync(
                        $product,
                        collect($r['tiers'])->map(fn ($cents, $min) => ['variant_id' => null, 'customer_group_id' => null, 'min_qty' => (int) $min, 'unit_price_cents' => $cents])->values()->all(),
                        fn ($q) => $q->whereNull('variant_id')->whereNull('customer_group_id'),
                    );
                }
            }
            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
                AuditLogger::log('product.csv_import', null, "CSV import: {$created} created, {$updated} updated.");
            }
        } catch (InvalidArgumentException $e) {
            DB::rollBack();

            return ['created' => 0, 'updated' => 0, 'errors' => [$e->getMessage()]];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => []];
    }

    private function parseRow(array $row): array
    {
        $v = Validator::make($row, [
            'sku' => ['required', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/'],
            'name' => ['required', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255'],
            'category_slug' => ['nullable', Rule::exists('categories', 'slug')],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'short_description' => ['nullable', 'max:500'],
            'base_price' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'moq' => ['nullable', 'integer', 'min:1'],
            'order_multiple' => ['nullable', 'integer', 'min:1'],
            'availability' => ['nullable', Rule::enum(Availability::class)],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            ...array_fill_keys(self::BOOLEANS, ['nullable', Rule::in(['0', '1', 'true', 'false', 'yes', 'no'])]),
        ]);
        if ($v->fails()) {
            throw new InvalidArgumentException($v->errors()->first());
        }
        if (ProductVariant::query()->where('sku', $row['sku'])->exists()) {
            throw new InvalidArgumentException("SKU {$row['sku']} is used by a variant.");
        }

        $attributes = ['name' => $row['name']];
        foreach (['slug', 'status', 'short_description', 'description', 'material', 'leather_type', 'dimensions', 'hardware', 'finishing', 'packaging', 'lead_time', 'availability'] as $f) {
            if (array_key_exists($f, $row) && $row[$f] !== null && $row[$f] !== '') {
                $attributes[$f] = $row[$f];
            }
        }
        if (filled($row['category_slug'] ?? null)) {
            $attributes['category_id'] = Category::query()->where('slug', $row['category_slug'])->value('id');
        }
        if (filled($row['base_price'] ?? null)) {
            $attributes['base_price_cents'] = Money::parse($row['base_price']);
        }
        foreach (['moq', 'order_multiple', 'stock_quantity'] as $f) {
            if (filled($row[$f] ?? null)) {
                $attributes[$f] = (int) $row[$f];
            }
        }
        foreach (self::BOOLEANS as $b) {
            if (filled($row[$b] ?? null)) {
                $attributes[$b] = in_array(strtolower((string) $row[$b]), ['1', 'true', 'yes'], true);
            }
        }

        $tiers = null;
        if (array_key_exists('price_tiers', $row)) {
            $tiers = [];
            foreach (array_filter(explode('|', (string) $row['price_tiers'])) as $pair) {
                if (! preg_match('/^\s*(\d+)\s*:\s*(\d{1,9}(?:\.\d{1,2})?)\s*$/', $pair, $m) || (int) $m[1] < 1) {
                    throw new InvalidArgumentException("Invalid price tier \"{$pair}\" (use qty:price, e.g. 25:15.00).");
                }
                $tiers[(int) $m[1]] = Money::parse($m[2]);
            }
            ksort($tiers);
        }

        return ['sku' => $row['sku'], 'attributes' => $attributes, 'tiers' => $tiers];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 2;
        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
