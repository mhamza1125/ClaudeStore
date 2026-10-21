<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public const POLICIES = [
        'terms' => 'Terms of trade',
        'privacy' => 'Privacy policy',
        'shipping' => 'Shipping & delivery',
        'returns' => 'Returns & claims',
    ];

    public function show(string $page)
    {
        if (array_key_exists($page, self::POLICIES)) {
            return view('store.pages.policy', [
                'title' => self::POLICIES[$page],
                'content' => setting('policies.'.$page),
            ]);
        }

        abort_unless(in_array($page, ['about', 'custom-manufacturing'], true), 404);

        return view('store.pages.'.$page);
    }
}
