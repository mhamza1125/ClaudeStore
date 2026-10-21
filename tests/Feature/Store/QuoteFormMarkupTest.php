<?php

namespace Tests\Feature\Store;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteFormMarkupTest extends TestCase
{
    use RefreshDatabase;

    /** Regression: Livewire morph markers must never end up inside textarea content (they get submitted). */
    public function test_textareas_contain_no_livewire_markers(): void
    {
        $html = $this->get('/quote')->assertOk()->getContent();
        preg_match_all('#<textarea[^>]*>(.*?)</textarea>#s', $html, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $content) {
            $this->assertStringNotContainsString('<!--', html_entity_decode($content));
            $this->assertSame('', trim($content));
        }
    }
}
