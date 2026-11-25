<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Super-admin only (staff.manage). */
class StaffManager extends Component
{
    public array $form = ['name' => '', 'email' => '', 'role' => 'sales-rep'];

    public ?string $createdPassword = null;

    public function create(): void
    {
        $this->validate([
            'form.name' => ['required', 'string', 'max:120'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'form.role' => ['required', Rule::in(User::STAFF_ROLES)],
        ]);
        $password = Str::password(16, symbols: false);
        $user = User::create(['name' => $this->form['name'], 'email' => strtolower($this->form['email']), 'password' => $password]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole($this->form['role']);
        AuditLogger::log('staff.created', $user, "Staff account {$user->email} created as {$this->form['role']}.");
        $this->createdPassword = $password;
        $this->reset('form');
    }

    public function setRole(int $userId, string $role): void
    {
        abort_unless(in_array($role, [...User::STAFF_ROLES, 'customer'], true), 422);
        $user = User::query()->findOrFail($userId);
        abort_if($user->id === auth()->id(), 422, 'You cannot change your own role.');
        $from = $user->getRoleNames()->implode(',');
        $user->syncRoles([$role]);
        AuditLogger::log('staff.role_changed', $user, "Role {$from} → {$role}.", ['role' => ['from' => $from, 'to' => $role]]);
    }

    public function toggleActive(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        abort_if($user->id === auth()->id(), 422);
        $user->update(['is_active' => ! $user->is_active]);
        AuditLogger::log($user->is_active ? 'staff.activated' : 'staff.deactivated', $user, $user->email);
    }

    public function render()
    {
        return view('livewire.admin.staff-manager', [
            'staff' => User::staff()->with('roles')->orderBy('name')->get(),
            'roles' => collect(User::STAFF_ROLES)->mapWithKeys(fn ($r) => [$r => Str::headline($r)])->all(),
        ])->layout('components.layouts.admin', ['title' => 'Staff']);
    }
}
