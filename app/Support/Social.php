<?php

namespace App\Support;

/**
 * Social profiles (Admin → Settings → Social profiles). Each network has a URL setting
 * (social.{key}) and a visibility switch (social.{key}_show).
 *
 * A profile is shown when it is switched on AND has a URL. Outside production, a switched-on
 * profile without a URL links to "#" so the layout can be previewed; the live site never shows
 * a dead link.
 */
final class Social
{
    /** key => [label, 24×24 icon path data (filled)] */
    public const NETWORKS = [
        'instagram' => ['Instagram', 'M12 7.2a4.8 4.8 0 1 0 0 9.6 4.8 4.8 0 0 0 0-9.6Zm0 7.9a3.1 3.1 0 1 1 0-6.2 3.1 3.1 0 0 1 0 6.2Zm6.1-8.1a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0ZM21.3 8c-.1-1.5-.4-2.8-1.5-3.9S17.5 2.7 16 2.6c-1.5-.1-6.5-.1-8 0-1.5.1-2.8.4-3.9 1.5S2.7 6.5 2.6 8c-.1 1.5-.1 6.5 0 8 .1 1.5.4 2.8 1.5 3.9s2.4 1.4 3.9 1.5c1.5.1 6.5.1 8 0 1.5-.1 2.8-.4 3.9-1.5s1.4-2.4 1.5-3.9c.1-1.5.1-6.5 0-8Zm-2.1 9.6a3.1 3.1 0 0 1-1.8 1.8c-1.2.5-4.1.4-5.4.4s-4.2.1-5.4-.4a3.1 3.1 0 0 1-1.8-1.8c-.5-1.2-.4-4.1-.4-5.4s-.1-4.2.4-5.4a3.1 3.1 0 0 1 1.8-1.8c1.2-.5 4.1-.4 5.4-.4s4.2-.1 5.4.4a3.1 3.1 0 0 1 1.8 1.8c.5 1.2.4 4.1.4 5.4s.1 4.2-.4 5.4Z'],
        'facebook' => ['Facebook', 'M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.3v7A10 10 0 0 0 22 12Z'],
        'linkedin' => ['LinkedIn', 'M20.4 20.5h-3.6v-5.6c0-1.3 0-3-1.8-3s-2.1 1.4-2.1 2.9v5.7H9.3V9h3.4v1.6h.1a3.8 3.8 0 0 1 3.4-1.9c3.6 0 4.3 2.4 4.3 5.5v6.3ZM5.3 7.4a2.1 2.1 0 1 1 0-4.2 2.1 2.1 0 0 1 0 4.2ZM7.1 20.5H3.5V9h3.6v11.5ZM22.2 0H1.8C.8 0 0 .8 0 1.7v20.6c0 .9.8 1.7 1.8 1.7h20.4c1 0 1.8-.8 1.8-1.7V1.7C24 .8 23.2 0 22.2 0Z'],
        'youtube' => ['YouTube', 'M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.2 3.6-6.2 3.6Z'],
        'tiktok' => ['TikTok', 'M19.6 6.7a4.8 4.8 0 0 1-3.8-4.2V2h-3.4v13.7a2.9 2.9 0 1 1-2-2.8V9.4a6.3 6.3 0 1 0 5.4 6.3V8.7a8.2 8.2 0 0 0 4.8 1.5V6.8l-1-.1Z'],
        'pinterest' => ['Pinterest', 'M12 0a12 12 0 0 0-4.4 23.2c-.1-.9-.2-2.4 0-3.4l1.4-6s-.4-.7-.4-1.8c0-1.7 1-2.9 2.2-2.9 1 0 1.5.8 1.5 1.7 0 1-.7 2.6-1 4-.3 1.2.6 2.2 1.8 2.2 2.1 0 3.8-2.3 3.8-5.5 0-2.9-2.1-4.9-5-4.9-3.4 0-5.4 2.6-5.4 5.2 0 1 .4 2.1.9 2.7.1.1.1.2.1.4l-.3 1.4c0 .2-.2.3-.4.2-1.5-.7-2.4-2.9-2.4-4.6 0-3.8 2.7-7.2 7.9-7.2 4.1 0 7.3 3 7.3 6.9 0 4.1-2.6 7.4-6.2 7.4-1.2 0-2.3-.6-2.7-1.4l-.7 2.8c-.3 1-1 2.3-1.5 3.1A12 12 0 1 0 12 0Z'],
    ];

    /** Profiles to display: list of ['key', 'label', 'url', 'icon']. */
    public static function visible(): array
    {
        $out = [];
        foreach (self::NETWORKS as $key => [$label, $icon]) {
            if (! setting("social.{$key}_show", true)) {
                continue;
            }
            $url = trim((string) setting("social.{$key}"));
            if ($url === '') {
                if (app()->isProduction()) {
                    continue;
                }
                $url = '#';
            }
            $out[] = compact('key', 'label', 'url', 'icon');
        }

        return $out;
    }

    /** Real profile URLs only (for structured data "sameAs"). */
    public static function profileUrls(): array
    {
        return collect(self::visible())->pluck('url')->reject(fn ($u) => $u === '#')->values()->all();
    }
}
