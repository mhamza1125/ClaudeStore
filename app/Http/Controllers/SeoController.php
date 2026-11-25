<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/** XML sitemap and robots.txt (both generated, so they always match the live catalogue and URL). */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('seo.sitemap', 3600, function () {
            $urls = [
                [route('home'), null, '1.0'],
                [route('catalog'), null, '0.9'],
                [route('pages.show', 'custom-manufacturing'), null, '0.8'],
                [route('pages.show', 'about'), null, '0.6'],
                [route('contact'), null, '0.6'],
            ];
            foreach (array_keys(PageController::POLICIES) as $slug) {
                $urls[] = [route('pages.show', $slug), null, '0.3'];
            }
            Category::active()->orderBy('sort_order')->get(['id', 'slug', 'updated_at'])
                ->each(function ($c) use (&$urls) {
                    $urls[] = [route('catalog.category', $c), $c->updated_at, '0.8'];
                });
            Product::published()->orderBy('id')->get(['id', 'slug', 'updated_at'])
                ->each(function ($p) use (&$urls) {
                    $urls[] = [route('products.show', $p), $p->updated_at, '0.7'];
                });

            $body = collect($urls)->map(fn ($u) => '  <url><loc>'.e($u[0]).'</loc>'
                .($u[1] ? '<lastmod>'.$u[1]->toAtomString().'</lastmod>' : '')
                .'<priority>'.$u[2].'</priority></url>')->implode("\n");

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$body."\n</urlset>\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        // The same rules in every environment, so a misconfigured APP_ENV can never de-index the site.
        $lines = [
            'User-agent: *',
            // Private, transactional and duplicate-content areas (pages also send noindex).
            'Disallow: /admin',
            'Disallow: /account',
            'Disallow: /q/',
            'Disallow: /quote/submitted',
            'Disallow: /search/',
            'Disallow: /assistant/',
            '',
            'Sitemap: '.route('seo.sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
