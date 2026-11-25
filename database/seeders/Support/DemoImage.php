<?php

namespace Database\Seeders\Support;

/**
 * Art-directed SVG placeholder images for demo data: one consistent studio look across the
 * site (warm seamless backdrop, soft top-left light, leather grain, saddle stitching, antique
 * brass hardware, pale kraft and cream props). They are illustrations, not
 * photographs, and always carry a visible "Demo image" label. See docs/DESIGN.md → Imagery.
 */
final class DemoImage
{
    private const BRASS = '#c9a35f';

    /**
     * Shapes are drawn in an 800×1000 frame centred near (400, 580). "body" paths are filled
     * leather (and receive grain + light); "line" paths are stroked straps and handles; "trim"
     * is drawn on top (darker panels); "hardware" is brass; "stitch" overrides the stitch path.
     */
    private const SHAPES = [
        'tote' => [
            'body' => '<path d="M214 430 L586 430 L634 812 Q637 842 606 842 L194 842 Q163 842 166 812 Z"/>',
            'line' => '<path d="M296 436 Q296 262 400 262 Q504 262 504 436" stroke-width="20"/>',
            'trim' => '<path d="M214 430 L586 430 L589 462 L211 462 Z" opacity=".22"/><rect x="284" y="430" width="26" height="54" rx="5" opacity=".3"/><rect x="490" y="430" width="26" height="54" rx="5" opacity=".3"/>',
            'hardware' => '<circle cx="297" cy="470" r="6"/><circle cx="503" cy="470" r="6"/>',
        ],
        'backpack' => [
            'body' => '<rect x="232" y="318" width="336" height="526" rx="84"/>',
            'line' => '<path d="M346 322 Q346 246 400 246 Q454 246 454 322" stroke-width="16"/>',
            'trim' => '<path d="M232 470 Q400 520 568 470 L568 400 Q568 318 486 318 L314 318 Q232 318 232 400 Z" opacity=".24"/><rect x="306" y="600" width="188" height="178" rx="22" opacity=".18"/><rect x="330" y="486" width="20" height="90" rx="6" opacity=".35"/><rect x="450" y="486" width="20" height="90" rx="6" opacity=".35"/>',
            'hardware' => '<rect x="324" y="548" width="32" height="22" rx="4"/><rect x="444" y="548" width="32" height="22" rx="4"/>',
        ],
        'briefcase' => [
            'body' => '<rect x="140" y="402" width="520" height="384" rx="28"/>',
            'line' => '<path d="M338 404 V350 Q338 326 362 326 H438 Q462 326 462 350 V404" stroke-width="18"/>',
            'trim' => '<path d="M140 430 Q140 402 168 402 H632 Q660 402 660 430 V556 Q400 600 140 556 Z" opacity=".2"/>',
            'hardware' => '<rect x="378" y="548" width="44" height="50" rx="7"/><rect x="390" y="562" width="20" height="8" rx="2" fill="#000" opacity=".25"/>',
        ],
        'duffel' => [
            'body' => '<path d="M128 506 Q128 424 210 424 L590 424 Q672 424 672 506 L672 742 Q672 792 622 792 L178 792 Q128 792 128 742 Z"/>',
            'line' => '<path d="M300 430 Q300 312 400 312 Q500 312 500 430" stroke-width="18"/>',
            'trim' => '<ellipse cx="170" cy="608" rx="42" ry="168" opacity=".22"/><ellipse cx="630" cy="608" rx="42" ry="168" opacity=".22"/><rect x="250" y="424" width="300" height="20" opacity=".18"/>',
            'hardware' => '<rect x="530" y="428" width="34" height="14" rx="4"/><circle cx="300" cy="446" r="7"/><circle cx="500" cy="446" r="7"/>',
        ],
        'wallet' => [
            'body' => '<rect x="190" y="402" width="420" height="300" rx="24"/>',
            'trim' => '<rect x="396" y="402" width="8" height="300" opacity=".18"/><path d="M404 402 H586 Q610 402 610 426 V702 H404 Z" opacity=".08"/>',
        ],
        'cardholder' => [
            'body' => '<rect x="262" y="430" width="276" height="190" rx="18"/>',
            'trim' => '<path d="M262 478 H538" stroke="#000" stroke-width="3" opacity=".22"/>',
            'prop' => '<rect x="292" y="392" width="216" height="74" rx="8" fill="#efe3d0"/><rect x="312" y="410" width="60" height="8" rx="4" fill="#fff" opacity=".7"/>',
        ],
        'moneyclip' => [
            'body' => '<rect x="236" y="436" width="328" height="160" rx="16"/>',
            'hardware' => '<rect x="236" y="470" width="328" height="36" rx="6"/><rect x="236" y="470" width="328" height="10" rx="4" fill="#fff" opacity=".3"/>',
        ],
        'belt' => [
            'body' => '<path d="M120 522 H594 V586 H120 Q78 586 78 554 Q78 522 120 522 Z"/>',
            'trim' => '<rect x="500" y="514" width="22" height="80" rx="4" opacity=".35"/>',
            'hardware' => '<path fill-rule="evenodd" d="M590 500 H690 Q704 500 704 514 V594 Q704 608 690 608 H590 Q576 608 576 594 V514 Q576 500 590 500 Z M596 518 V590 H684 V518 Z"/><rect x="626" y="510" width="10" height="88" rx="4"/>',
            'holes' => '<circle cx="176" cy="554" r="7"/><circle cx="226" cy="554" r="7"/><circle cx="276" cy="554" r="7"/><circle cx="326" cy="554" r="7"/>',
        ],
        'keyholder' => [
            'body' => '<path d="M334 432 H466 V652 Q466 690 400 712 Q334 690 334 652 Z"/>',
            'hardware' => '<path fill="none" stroke-width="13" d="M400 432 V392"/><circle cx="400" cy="350" r="50" fill="none" stroke-width="12"/>',
        ],
        'passport' => [
            'body' => '<rect x="272" y="332" width="256" height="364" rx="20"/>',
            'trim' => '<rect x="330" y="420" width="140" height="92" rx="10" fill="none" stroke="#000" stroke-width="4" opacity=".22"/>',
            'hardware' => '<rect x="380" y="604" width="40" height="6" rx="3"/>',
        ],
    ];

    /** Bounding box of each shape's drawing: [minX, minY, maxX, maxY] (used to fit and stand it). */
    private const BOX = [
        'tote' => [166, 262, 634, 842], 'backpack' => [232, 246, 568, 844], 'briefcase' => [140, 326, 660, 786],
        'duffel' => [128, 312, 672, 792], 'wallet' => [190, 402, 610, 702], 'cardholder' => [262, 392, 538, 620],
        'moneyclip' => [236, 436, 564, 596], 'belt' => [78, 500, 704, 608], 'keyholder' => [334, 300, 466, 712],
        'passport' => [272, 332, 528, 696],
    ];

    /** 4:5 product image. Views: front, side, back, detail, lifestyle. */
    public static function svg(string $shape, string $color, string $view, string $title): string
    {
        $title = htmlspecialchars($title, ENT_XML1);
        $life = $view === 'lifestyle';
        [$k, $w] = self::scale($shape, $life ? 400 : 500, $life ? 460 : 560);
        [$x0, $y0, $x1, $y1] = self::BOX[$shape] ?? self::BOX['tote'];
        // Tall goods stand on the floor line; flat goods (belts, clips) are centred optically.
        $bottom = $life ? 845 : (int) round(min(812, 560 + ($y1 - $y0) * $k / 2));
        [$cx, $cy] = [($x0 + $x1) / 2, ($y0 + $y1) / 2];
        $transform = match ($view) {
            'side' => self::place($shape, 400, $bottom, $k * 0.62, $k),
            'back' => self::place($shape, 400, $bottom, -$k, $k),
            'detail' => 'translate(400 500) scale('.round($k * 2.1, 3).") translate(-{$cx} -{$cy})",
            default => self::place($shape, 400, $bottom, $k, $k),
        };
        $scene = $life ? self::table(800, 1000, 760) : '';
        $prop = $life ? '<rect x="70" y="700" width="300" height="190" rx="6" fill="#e9d8c2" opacity=".9" transform="rotate(-8 220 795)"/>' : '';
        $shadow = $view === 'detail' ? '' : self::shadow(400, $bottom + 6, (int) round($w * ($view === 'side' ? 0.36 : 0.56)));
        $object = self::object('p', $shape, $color, $transform);
        $label = strtoupper($view);

        return self::wrap(800, 1000, "Demo illustration: {$title}, {$view} view", self::backdrop('p', 800, 1000, 'warm').$scene.$prop.$shadow.$object, "DEMO IMAGE · {$label}");
    }

    /** Scale that fits a shape into maxH × maxW (capped so small goods aren't blown up). Returns [scale, drawn width]. */
    private static function scale(string $shape, int $maxH, int $maxW, float $cap = 1.6): array
    {
        [$x0, $y0, $x1, $y1] = self::BOX[$shape] ?? self::BOX['tote'];
        $k = min($maxH / ($y1 - $y0), $maxW / ($x1 - $x0), $cap);

        return [$k, ($x1 - $x0) * $k];
    }

    /** Transform that stands a shape with its base centred on (x, bottom). */
    private static function place(string $shape, float $x, float $bottom, float $kx, float $ky): string
    {
        [$x0, , $x1, $y1] = self::BOX[$shape] ?? self::BOX['tote'];
        $cx = ($x0 + $x1) / 2;

        return 'translate('.round($x, 1).' '.round($bottom, 1).') scale('.round($kx, 3).' '.round($ky, 3).") translate(-{$cx} -{$y1})";
    }

    /** Category still lifes: slug => [[main shape, supporting shape], [colours]]. */
    private const CATEGORY_ART = [
        'leather-bags' => [['briefcase', 'duffel'], ['#7b3f22', '#3e2619']],
        'bags' => [['briefcase', 'duffel'], ['#7b3f22', '#3e2619']],
        'leather-belts' => [['belt', 'belt'], ['#1e1a18', '#9a4e1f']],
        'belts' => [['belt', 'belt'], ['#1e1a18', '#9a4e1f']],
        'leather-wallets' => [['wallet', 'cardholder'], ['#3e2619', '#a0673a']],
        'wallets' => [['wallet', 'cardholder'], ['#3e2619', '#a0673a']],
        'leather-accessories' => [['passport', 'keyholder'], ['#5e1f1e', '#a0673a']],
        'accessories' => [['passport', 'keyholder'], ['#5e1f1e', '#a0673a']],
        'custom' => [['wallet', 'passport'], ['#7b3f22', '#23304a']],
    ];

    /** Category artwork by slug, falling back to a single shape. */
    public static function forCategory(string $slug, string $shape, string $title): string
    {
        [$shapes, $colors] = self::CATEGORY_ART[$slug] ?? [[$shape, $shape], ['#7b3f22', '#3e2619']];

        return self::category($shapes, $colors, $title);
    }

    /** Category tile (1200×1000): two related items in one still life, centred high so a label card can sit below. */
    public static function category(array $shapes, array $colors, string $title): string
    {
        $title = htmlspecialchars($title, ENT_XML1);
        [$a, $b] = [$shapes[0], $shapes[1] ?? $shapes[0]];
        [$ca, $cb] = [$colors[0], $colors[1] ?? $colors[0]];
        $stack = $a === $b; // two of the same (e.g. belts): stack them instead of side by side
        [$ka, $wa] = self::scale($a, 380, $stack ? 620 : 470);
        [$kb, $wb] = self::scale($b, 290, $stack ? 560 : 330);
        $objects = $stack
            ? self::object('c2', $b, $cb, self::place($b, 640, 470, $kb, $kb)).self::object('c1', $a, $ca, self::place($a, 560, 620, $ka, $ka))
            : self::object('c2', $b, $cb, self::place($b, 800, 640, $kb, $kb)).self::object('c1', $a, $ca, self::place($a, 500, 650, $ka, $ka));
        $body = self::backdrop('c', 1200, 1000, 'warm')
            .'<circle cx="790" cy="330" r="250" fill="#e4d1b6" opacity=".55"/>'
            .self::shadow(600, 656, (int) round(($stack ? $wa : $wa + $wb) * 0.5))
            .$objects;

        return self::wrap(1200, 1000, "Demo illustration: {$title}", $body, 'DEMO IMAGE');
    }

    /** Landscape (4:3) homepage banner: a product on a plinth. Tones: warm, deep, cool. */
    public static function banner(string $shape, string $color, string $title, string $tone = 'warm'): string
    {
        $title = htmlspecialchars($title, ENT_XML1);
        $plinth = match ($tone) {
            'deep' => ['#3a2a20', '#2a1d16'],
            'cool' => ['#e9e1d4', '#d9cdbb'],
            default => ['#e7d9c3', '#d2bf9f'],
        };
        $panel = match ($tone) {
            'deep' => '<rect x="640" y="120" width="420" height="560" rx="210" fill="#d6a175" opacity=".14"/><rect x="664" y="144" width="372" height="512" rx="186" fill="none" stroke="#d6a175" stroke-width="2.5" stroke-dasharray="12 9" opacity=".55"/>',
            'cool' => '<rect x="600" y="90" width="470" height="640" rx="235" fill="#e2cdb0" opacity=".8"/>',
            default => '<rect x="610" y="100" width="450" height="600" rx="225" fill="#fff" opacity=".45"/>',
        };
        $body = self::backdrop('b', 1200, 900, $tone)
            .$panel
            .'<rect x="560" y="690" width="560" height="70" rx="12" fill="'.$plinth[0].'"/><rect x="560" y="740" width="560" height="160" fill="'.$plinth[1].'"/>'
            .self::shadow(840, 696, (int) round(self::scale($shape, 400, 440)[1] * 0.55))
            .self::object('b', $shape, $color, self::place($shape, 840, 690, self::scale($shape, 400, 440)[0], self::scale($shape, 400, 440)[0]));

        return self::wrap(1200, 900, "Demo illustration: {$title}", $body, 'DEMO IMAGE', $tone === 'deep' ? '#c9b393' : '#8a7562');
    }

    // ---------------------------------------------------------------- building blocks

    private static function wrap(int $w, int $h, string $label, string $body, string $caption, string $captionColor = '#8a7562'): string
    {
        $cy = $h - 34;

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$w} {$h}" role="img" aria-label="{$label}">
  <defs>
    <filter id="grain" x="0" y="0" width="100%" height="100%"><feTurbulence type="fractalNoise" baseFrequency="1.15" numOctaves="2" seed="7"/><feColorMatrix values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 .32 0"/></filter>
    <filter id="soft" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="14"/></filter>
    <linearGradient id="light" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".26"/><stop offset=".45" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".3"/></linearGradient>
    <linearGradient id="brass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ecd29a"/><stop offset=".5" stop-color="#c9a35f"/><stop offset="1" stop-color="#8f6d33"/></linearGradient>
  </defs>
  {$body}
  <text x="{$w}" y="{$cy}" dx="-36" text-anchor="end" font-family="Helvetica, Arial, sans-serif" font-size="17" font-weight="600" letter-spacing="3" fill="{$captionColor}" opacity=".75">{$caption}</text>
</svg>
SVG;
    }

    /** Seamless studio backdrop with a soft light pool (top left) and a floor fade. */
    private static function backdrop(string $id, int $w, int $h, string $tone): string
    {
        [$top, $bottom, $glow] = match ($tone) {
            'deep' => ['#2e2019', '#1c130e', '#4a372a'],
            'cool' => ['#f3eee6', '#e2d8c9', '#ffffff'],
            default => ['#f2eadd', '#e1d2bc', '#fbf6ee'],
        };
        $gx = round($w * 0.3);
        $gy = round($h * 0.22);
        $gr = round($w * 0.75);

        return <<<SVG
<linearGradient id="{$id}bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{$top}"/><stop offset="1" stop-color="{$bottom}"/></linearGradient>
  <radialGradient id="{$id}glow" cx="{$gx}" cy="{$gy}" r="{$gr}" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="{$glow}" stop-opacity=".9"/><stop offset="1" stop-color="{$glow}" stop-opacity="0"/></radialGradient>
  <rect width="{$w}" height="{$h}" fill="url(#{$id}bg)"/><rect width="{$w}" height="{$h}" fill="url(#{$id}glow)"/>
SVG;
    }

    /** Walnut table top for lifestyle shots. */
    private static function table(int $w, int $h, int $y): string
    {
        return '<rect x="0" y="'.$y.'" width="'.$w.'" height="'.($h - $y).'" fill="#8a5f3f"/><rect x="0" y="'.$y.'" width="'.$w.'" height="'.($h - $y).'" filter="url(#grain)" opacity=".9"/><rect x="0" y="'.$y.'" width="'.$w.'" height="10" fill="#000" opacity=".12"/>';
    }

    private static function shadow(int $cx, int $cy, int $rx): string
    {
        return '<ellipse cx="'.$cx.'" cy="'.$cy.'" rx="'.$rx.'" ry="24" fill="#2a1a10" opacity=".28" filter="url(#soft)"/>';
    }

    /** One leather object: fill, grain and light clipped to the body, trim, hardware and stitching. */
    private static function object(string $id, string $shape, string $color, string $transform): string
    {
        $s = self::SHAPES[$shape] ?? self::SHAPES['tote'];
        $body = $s['body'];
        $line = $s['line'] ?? '';
        $trim = $s['trim'] ?? '';
        $prop = $s['prop'] ?? '';
        $holes = $s['holes'] ?? '';
        $hardware = isset($s['hardware']) ? '<g fill="url(#brass)" stroke="url(#brass)">'.$s['hardware'].'</g>' : '';
        // Stitching: the body outline inset by ~6%, dashed, in a pale thread colour.
        $stitchPath = preg_replace('/\s(fill|opacity)="[^"]*"/', '', $body);
        [$x0, $y0, $x1, $y1] = self::BOX[$shape] ?? self::BOX['tote'];
        [$cx, $cy] = [($x0 + $x1) / 2, ($y0 + $y1) / 2];
        // Inset the outline by ~12 units on every side (non-uniform, so long straps keep their length).
        [$sx, $sy] = [round(1 - 24 / ($x1 - $x0), 3), round(1 - 24 / ($y1 - $y0), 3)];
        $stitch = '<g fill="none" stroke="#f1dfc4" stroke-width="2.6" stroke-dasharray="9 7" opacity=".7" transform="translate('.$cx.' '.$cy.') scale('.$sx.' '.$sy.') translate(-'.$cx.' -'.$cy.')">'.$stitchPath.'</g>';
        $lines = $line ? '<g fill="none" stroke="'.$color.'" stroke-linecap="round">'.$line.'</g><g fill="none" stroke="url(#light)" stroke-linecap="round">'.$line.'</g>' : '';

        return <<<SVG
<g transform="{$transform}">
    <clipPath id="{$id}clip">{$body}</clipPath>
    {$prop}{$lines}
    <g fill="{$color}">{$body}</g>
    <g clip-path="url(#{$id}clip)"><rect x="0" y="0" width="800" height="1000" filter="url(#grain)"/><rect x="0" y="0" width="800" height="1000" fill="url(#light)"/><g fill="#000">{$trim}</g></g>
    {$stitch}
    <g fill="#000" opacity=".45">{$holes}</g>
    {$hardware}
  </g>
SVG;
    }
}
