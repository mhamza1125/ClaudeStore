<?php

namespace Tests\Feature\Store;

use App\Livewire\Admin\SettingsForm;
use App\Services\Settings;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppButtonTest extends TestCase
{
    use RefreshDatabase;

    private const DEFAULT_LINK = 'https://api.whatsapp.com/send/?phone=923314657496&text=Hello%21+I+want+to+know+more+about+your+services.&type=phone_number&app_absent=0';

    public function test_default_configuration_produces_the_specified_link(): void
    {
        $this->assertSame(self::DEFAULT_LINK, WhatsApp::configuredLink());
        $this->get('/')->assertOk()->assertSee('data-testid="whatsapp-button"', false)
            ->assertSee('href="'.e(self::DEFAULT_LINK).'"', false);
    }

    public function test_link_encoding_and_phone_normalisation(): void
    {
        $this->assertSame('923110012233', WhatsApp::normalizePhone('+92 311-001 2233'));
        $this->assertTrue(WhatsApp::isValidPhone('+92 311 0012233'));
        $this->assertFalse(WhatsApp::isValidPhone('12345'));
        $this->assertFalse(WhatsApp::isValidPhone('0092 311 0012233 999'));

        $link = WhatsApp::link('+44 20 7946 0000', "Quote for 50 wallets & 20 belts? Café #1\nThanks");
        $this->assertSame('https://api.whatsapp.com/send/?phone=442079460000&text=Quote+for+50+wallets+%26+20+belts%3F+Caf%C3%A9+%231%0AThanks&type=phone_number&app_absent=0', $link);
        parse_str(parse_url($link, PHP_URL_QUERY), $q);
        $this->assertSame("Quote for 50 wallets & 20 belts? Café #1\nThanks", $q['text']);
    }

    public function test_admin_can_configure_number_preset_and_custom_message(): void
    {
        $this->actingAs($this->staff());
        Livewire::test(SettingsForm::class)
            ->assertSet('s.whatsapp_enabled', true)->assertSet('s.whatsapp_preset', 'services')
            ->assertSee(self::DEFAULT_LINK)
            ->set('s.whatsapp_phone', '+44 20 7946 0000')->set('s.whatsapp_preset', 'wholesale')
            ->call('save')->assertHasNoErrors();
        app(Settings::class)->flush();
        $this->assertSame('442079460000', setting('whatsapp.phone'));
        $this->assertStringContainsString('text=Hello%21+I+would+like+to+know+more+about+wholesale', WhatsApp::configuredLink());

        Livewire::test(SettingsForm::class)->set('s.whatsapp_preset', 'custom')->set('s.whatsapp_custom', '')
            ->call('save')->assertHasErrors('s.whatsapp_custom')
            ->set('s.whatsapp_custom', 'Hi Atelier & team!')->call('save')->assertHasNoErrors();
        app(Settings::class)->flush();
        $this->assertStringContainsString('text=Hi+Atelier+%26+team%21', WhatsApp::configuredLink());
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    }

    public function test_invalid_numbers_and_unknown_presets_are_rejected(): void
    {
        $this->actingAs($this->staff());
        Livewire::test(SettingsForm::class)->set('s.whatsapp_phone', '12-34')->call('save')->assertHasErrors('s.whatsapp_phone');
        Livewire::test(SettingsForm::class)->set('s.whatsapp_preset', 'evil')->call('save')->assertHasErrors('s.whatsapp_preset');
        // A number is optional only while the button is disabled.
        Livewire::test(SettingsForm::class)->set('s.whatsapp_phone', '')->call('save')->assertHasErrors('s.whatsapp_phone')
            ->set('s.whatsapp_enabled', false)->call('save')->assertHasNoErrors();
    }

    public function test_disabled_button_is_not_rendered_anywhere(): void
    {
        app(Settings::class)->set(['whatsapp.enabled' => false]);
        $this->get('/')->assertDontSee('data-testid="whatsapp-button"', false);
        $this->get('/products')->assertDontSee('api.whatsapp.com');
    }

    public function test_button_appears_on_store_and_portal_but_not_admin(): void
    {
        $this->get('/quote')->assertSee('data-testid="whatsapp-button"', false);
        $this->actingAs($this->customer())->get('/account')->assertSee('data-testid="whatsapp-button"', false);
        $this->actingAs($this->staff())->get('/admin')->assertDontSee('data-testid="whatsapp-button"', false);
    }
}
