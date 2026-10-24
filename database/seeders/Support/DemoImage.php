<?php

namespace Database\Seeders\Support;

/**
 * Generates clearly-labelled SVG placeholder product images for demo data.
 * These are illustrations, not photographs, and carry a visible "Demo image" label.
 */
final class DemoImage
{
    private const SHAPES = [
        'tote' => '<path d="M230 400 L570 400 L620 820 Q620 850 590 850 L210 850 Q180 850 180 820 Z"/><path fill="none" stroke-width="18" d="M300 410 Q300 250 400 250 Q500 250 500 410"/>',
        'backpack' => '<rect x="230" y="300" width="340" height="540" rx="70"/><path d="M230 420 Q400 470 570 420 L570 360 Q400 300 230 360 Z" opacity=".85"/><rect x="330" y="560" width="140" height="120" rx="14" opacity=".8"/><path fill="none" stroke-width="16" d="M330 300 Q330 220 400 220 Q470 220 470 300"/>',
        'briefcase' => '<rect x="130" y="380" width="540" height="400" rx="28"/><path d="M130 420 L670 420 L670 560 Q400 600 130 560 Z" opacity=".85"/><path fill="none" stroke-width="18" d="M330 380 L330 320 Q330 300 350 300 L450 300 Q470 300 470 320 L470 380"/><rect x="380" y="560" width="40" height="40" rx="6" fill="#C9A45C"/>',
        'duffel' => '<path d="M120 480 Q120 400 200 400 L600 400 Q680 400 680 480 L680 720 Q680 780 620 780 L180 780 Q120 780 120 720 Z"/><ellipse cx="160" cy="590" rx="40" ry="150" opacity=".75"/><ellipse cx="640" cy="590" rx="40" ry="150" opacity=".75"/><path fill="none" stroke-width="16" d="M300 410 Q300 300 400 300 Q500 300 500 410"/>',
        'wallet' => '<rect x="190" y="380" width="420" height="300" rx="22"/><line x1="400" y1="380" x2="400" y2="680" stroke-width="6" opacity=".35"/>',
        'cardholder' => '<rect x="250" y="400" width="300" height="200" rx="16"/><path fill="none" stroke-width="4" opacity=".4" d="M270 450 L530 450 M270 490 L530 490"/>',
        'moneyclip' => '<rect x="240" y="440" width="320" height="150" rx="14"/><rect x="240" y="470" width="320" height="34" rx="6" fill="#C9A45C"/>',
        'belt' => '<rect x="80" y="520" width="640" height="70" rx="12"/><rect x="560" y="500" width="110" height="110" rx="10" fill="none" stroke="#C9A45C" stroke-width="14"/><line x1="610" y1="510" x2="610" y2="600" stroke="#C9A45C" stroke-width="10"/><circle cx="200" cy="555" r="7" fill="#0006"/><circle cx="260" cy="555" r="7" fill="#0006"/><circle cx="320" cy="555" r="7" fill="#0006"/>',
        'keyholder' => '<path d="M330 420 L470 420 L470 640 Q400 700 330 640 Z"/><circle cx="400" cy="360" r="50" fill="none" stroke="#C9A45C" stroke-width="12"/>',
        'passport' => '<rect x="270" y="330" width="260" height="360" rx="18"/><rect x="330" y="420" width="140" height="90" rx="8" fill="none" stroke="#C9A45C" stroke-width="5" opacity=".7"/>',
    ];

    /** Landscape (4:3) banner illustration for homepage slides; labelled as a demo image. */
    public static function banner(string $shape, string $color, string $title, string $tone = 'warm'): string
    {
        $body = self::SHAPES[$shape] ?? self::SHAPES['tote'];
        [$top, $bottom, $arch] = match ($tone) {
            'cool' => ['#e9e6e1', '#cfc8bd', '#ddd6cb'],
            'deep' => ['#3a2a20', '#20160f', '#4a3628'],
            default => ['#efe3cf', '#d6bf9c', '#e6d4b8'],
        };
        $label = $tone === 'deep' ? '#c9b393' : '#7a6652';
        $title = htmlspecialchars($title, ENT_XML1);
        $stitch = '<g fill="none" stroke="#f4e6c8" stroke-width="3" stroke-dasharray="10 8" opacity=".5" transform="translate(400 600) scale(0.93) translate(-400 -600)">'
            .preg_replace('/fill="[^"]*"/', '', $body).'</g>';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 900" role="img" aria-label="Demo illustration: {$title}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{$top}"/><stop offset="1" stop-color="{$bottom}"/></linearGradient>
    <linearGradient id="sheen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".18"/><stop offset=".5" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".22"/></linearGradient>
  </defs>
  <rect width="1200" height="900" fill="url(#bg)"/>
  <path d="M330 900 V420 a270 270 0 0 1 540 0 V900 Z" fill="{$arch}" opacity=".7"/>
  <rect y="720" width="1200" height="180" fill="#000" opacity=".08"/>
  <ellipse cx="600" cy="745" rx="300" ry="26" fill="#000" opacity=".16"/>
  <g transform="translate(200 -110)">
    <g fill="{$color}" stroke="{$color}">{$body}</g>
    <g fill="url(#sheen)" stroke="none">{$body}</g>
    {$stitch}
  </g>
  <text x="600" y="860" text-anchor="middle" font-family="Georgia, serif" font-size="22" letter-spacing="4" fill="{$label}">DEMO IMAGE</text>
</svg>
SVG;
    }

    public static function svg(string $shape, string $color, string $view, string $title): string
    {
        $body = self::SHAPES[$shape] ?? self::SHAPES['tote'];
        $transform = match ($view) {
            'side' => 'translate(400 600) scale(0.6 1) translate(-400 -600)',
            'back' => 'translate(800 0) scale(-1 1)',
            'detail' => 'translate(400 560) scale(2.1) translate(-400 -560)',
            'lifestyle' => 'translate(400 640) scale(0.78) translate(-400 -560)',
            default => '',
        };
        $bg = $view === 'lifestyle' ? ['#E9DCC6', '#CDB592'] : ['#F6F0E6', '#E8DDCB'];
        $title = htmlspecialchars($title, ENT_XML1);
        $viewLabel = strtoupper($view);
        $stitch = '<g fill="none" stroke="#F4E6C8" stroke-width="3" stroke-dasharray="10 8" opacity=".55" transform="translate(400 600) scale(0.93) translate(-400 -600)">'
            .preg_replace('/fill="[^"]*"/', '', $body).'</g>';
        $surface = $view === 'lifestyle'
            ? '<rect x="0" y="820" width="800" height="180" fill="#8B6A4A" opacity=".35"/>'
            : '';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 1000" role="img" aria-label="Demo illustration: {$title}, {$view} view">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{$bg[0]}"/><stop offset="1" stop-color="{$bg[1]}"/></linearGradient>
    <linearGradient id="sheen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".18"/><stop offset=".5" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".22"/></linearGradient>
  </defs>
  <rect width="800" height="1000" fill="url(#bg)"/>
  {$surface}
  <ellipse cx="400" cy="870" rx="260" ry="26" fill="#000" opacity=".12"/>
  <g transform="{$transform}">
    <g fill="{$color}" stroke="{$color}">{$body}</g>
    <g fill="url(#sheen)" stroke="none">{$body}</g>
    {$stitch}
  </g>
  <text x="400" y="960" text-anchor="middle" font-family="Georgia, serif" font-size="22" letter-spacing="4" fill="#7A6652">DEMO IMAGE · {$viewLabel}</text>
</svg>
SVG;
    }
}
