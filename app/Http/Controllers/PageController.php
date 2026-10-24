<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public const POLICIES = [
        'terms' => 'Terms of trade',
        'privacy' => 'Privacy policy',
        'shipping' => 'Shipping & delivery',
        'returns' => 'Returns & claims',
    ];

    public const CONTENT_PAGES = ['about', 'custom-manufacturing'];

    /** Slugs served at the site root (/about, /terms …). Route constraints use this list. */
    public static function slugs(): array
    {
        return [...self::CONTENT_PAGES, ...array_keys(self::POLICIES)];
    }

    public function show(string $page)
    {
        $canonical = route('pages.show', $page);

        if (array_key_exists($page, self::POLICIES)) {
            return view('store.pages.policy', [
                'title' => self::POLICIES[$page],
                'content' => setting('policies.'.$page),
                'canonical' => $canonical,
            ]);
        }

        abort_unless(in_array($page, self::CONTENT_PAGES, true), 404);

        return view('store.pages.'.$page, ['canonical' => $canonical]);
    }

    /** Old /pages/{slug} URLs → /{slug} (301), keeping any query string. */
    public function legacy(Request $request, string $page)
    {
        $query = $request->getQueryString();

        return redirect()->to(route('pages.show', $page).($query ? '?'.$query : ''), 301);
    }
}
