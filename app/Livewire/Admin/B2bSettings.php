<?php

namespace App\Livewire\Admin;

use App\Assistant\BusinessContext;
use App\Models\User;
use App\Pricing\PriceAccess;
use App\Services\AuditLogger;
use App\Services\Settings;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Admin → B2B settings: who sees prices, how customers gain access, image protection. */
class B2bSettings extends Component
{
    /** Form field => settings key. */
    private const KEYS = [
        'enabled' => 'b2b.enabled',
        'guest_prices' => 'b2b.guest_prices',
        'require_registration' => 'b2b.require_registration',
        'require_email_verification' => 'b2b.require_email_verification',
        'require_approval' => 'b2b.require_approval',
        'guest_inquiries' => 'b2b.guest_inquiries',
        'watermark_enabled' => 'images.watermark_enabled',
        'watermark_mode' => 'images.watermark_mode',
        'watermark_text' => 'images.watermark_text',
        'watermark_opacity' => 'images.watermark_opacity',
        'watermark_personalized' => 'images.watermark_personalized',
        'watermark_identifier' => 'images.watermark_identifier',
    ];

    public array $s = [];

    public function mount(Settings $settings): void
    {
        $all = $settings->all();
        foreach (self::KEYS as $field => $key) {
            $this->s[$field] = $all[$key];
        }
        $this->s['watermark_text'] = (string) $this->s['watermark_text'];
        $this->s['watermark_opacity'] = (int) $this->s['watermark_opacity'];
    }

    public function save(Settings $settings): void
    {
        $this->validate([
            's.enabled' => ['boolean'], 's.guest_prices' => ['boolean'], 's.require_registration' => ['boolean'],
            's.require_email_verification' => ['boolean'], 's.require_approval' => ['boolean'], 's.guest_inquiries' => ['boolean'],
            's.watermark_enabled' => ['boolean'], 's.watermark_personalized' => ['boolean'],
            's.watermark_text' => ['nullable', 'string', 'max:60'],
            's.watermark_opacity' => ['required', 'integer', 'min:5', 'max:60'],
            's.watermark_identifier' => ['required', Rule::in(['email', 'id'])],
            's.watermark_mode' => ['required', Rule::in(['embedded', 'overlay'])],
        ]);

        $values = [];
        foreach (self::KEYS as $field => $key) {
            $values[$key] = match ($field) {
                'watermark_text' => trim((string) $this->s[$field]) ?: null,
                'watermark_opacity' => (int) $this->s[$field],
                'watermark_identifier', 'watermark_mode' => $this->s[$field],
                default => (bool) $this->s[$field],
            };
        }

        $before = $settings->all();
        $changed = collect($values)->filter(fn ($v, $k) => ($before[$k] ?? null) !== $v)
            ->map(fn ($v, $k) => ['from' => $before[$k] ?? null, 'to' => $v])->all();
        $settings->set($values);
        BusinessContext::forget(); // the chatbot only quotes prices anonymous visitors may see
        AuditLogger::log('settings.b2b_updated', null, 'B2B pricing and image protection settings updated.', $changed);
        session()->flash('status', 'B2B settings saved.');
    }

    public function render()
    {
        $s = $this->s;
        $gated = $s['require_registration'];
        $method = match (true) {
            $gated && $s['require_email_verification'] && $s['require_approval'] => 'verify_approval',
            $gated && $s['require_email_verification'] => 'verify',
            $gated && $s['require_approval'] => 'approval',
            default => 'instant',
        };

        return view('livewire.admin.b2b-settings', [
            'method' => PriceAccess::METHODS[$method],
            'pending' => User::query()->role('customer')->where('approval_status', 'pending')->count(),
            'brandName' => setting('brand.name'),
        ])->layout('components.layouts.admin', ['title' => 'B2B settings']);
    }
}
