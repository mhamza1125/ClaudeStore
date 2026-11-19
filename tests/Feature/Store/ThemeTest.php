<?php

namespace Tests\Feature\Store;

use App\Livewire\Admin\SettingsForm;
use App\Services\Settings;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_render_on_html_with_switcher(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('data-theme-default="white" data-theme-light="white" data-theme-switch="1"', false)
            ->assertSee('aria-label="Colour theme"', false)
            ->assertSee('role="menuitemradio"', false);
    }

    public function test_visitors_and_users_can_choose_each_theme(): void
    {
        $user = $this->customer();
        $this->actingAs($user);
        foreach (['white', 'mid', 'dark', 'system'] as $choice) {
            $this->putJson('/preferences/theme', ['theme' => $choice])->assertNoContent();
            $this->assertSame($choice, $user->fresh()->theme);
        }
        $this->putJson('/preferences/theme', ['theme' => 'light'])->assertUnprocessable();
        $user->forceFill(['theme' => 'white'])->save();
        $this->get('/')->assertSee('data-user-theme="white"', false)->assertSee('data-theme-url=', false);
    }

    public function test_legacy_light_preference_maps_to_light_palette(): void
    {
        $this->assertSame('white', Theme::normalize('light'));
        $this->assertNull(Theme::normalize('neon'));
        $user = $this->customer();
        $user->forceFill(['theme' => 'light'])->save();
        $this->actingAs($user)->get('/')->assertSee('data-user-theme="white"', false);
    }

    public function test_admin_sets_default_and_can_lock_the_theme(): void
    {
        $this->actingAs($this->staff());
        Livewire::test(SettingsForm::class)
            ->set('s.theme_default', 'neon')->call('save')->assertHasErrors('s.theme_default')
            ->set('s.theme_default', 'white')->set('s.theme_system_light', 'white')->set('s.theme_allow_switch', false)
            ->call('save')->assertHasNoErrors();
        app(Settings::class)->flush();

        auth()->logout();
        $this->get('/')->assertSee('data-theme-default="white" data-theme-light="white" data-theme-switch="0"', false)
            ->assertDontSee('aria-label="Colour theme"', false);

        // Staff keep a switcher in the admin panel.
        $this->actingAs($this->staff())->get('/admin')->assertSee('data-theme-switch="1"', false)->assertSee('aria-label="Colour theme"', false);
    }

    public function test_head_script_runs_before_styles(): void
    {
        $html = $this->get('/')->getContent();
        $this->assertLessThan(strpos($html, 'build/assets'), strpos($html, 'ds.palette = palette'));
    }
}
