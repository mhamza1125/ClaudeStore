<?php

namespace App\Livewire\Admin;

use App\Models\ContactMessage;
use App\Services\AuditLogger;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Admin → Messages: Contact Us submissions. */
class ContactMessages extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: false)]
    public bool $unread = false;

    public ?int $openId = null;

    public function updating(): void
    {
        $this->resetPage();
    }

    public function open(int $id): void
    {
        $this->openId = $this->openId === $id ? null : $id;
        if ($this->openId) {
            ContactMessage::query()->whereKey($id)->whereNull('read_at')->update(['read_at' => now()]);
        }
    }

    public function markUnread(int $id): void
    {
        ContactMessage::query()->whereKey($id)->update(['read_at' => null]);
        $this->openId = null;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('customers.manage'), 403);
        $message = ContactMessage::query()->findOrFail($id);
        AuditLogger::log('contact_message.deleted', null, "Contact message #{$message->id} from {$message->email} deleted.");
        $message->delete();
        $this->openId = null;
    }

    public function render()
    {
        return view('livewire.admin.contact-messages', [
            'messages' => ContactMessage::query()
                ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', like_term($this->search))
                    ->orWhere('email', 'like', like_term($this->search))->orWhere('company', 'like', like_term($this->search))
                    ->orWhere('subject', 'like', like_term($this->search))))
                ->when($this->unread, fn ($q) => $q->whereNull('read_at'))
                ->latest('id')->paginate(25),
            'canManage' => auth()->user()->can('customers.manage'),
        ])->layout('components.layouts.admin', ['title' => 'Messages']);
    }
}
