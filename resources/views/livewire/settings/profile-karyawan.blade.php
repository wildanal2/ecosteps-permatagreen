<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app-header')]
    class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $photo;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'image|max:2048',
        ]);
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->photo) {
            if ($user->profile_photo) {
                Storage::disk('s3')->delete($user->profile_photo);
            }
            $path = $this->photo->storeAs(
                'profile/' . $user->email,
                time() . '.' . $this->photo->extension(),
                's3'
            );
            $user->profile_photo = $path;
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div>
                <flux:label>Foto Profil</flux:label>
                <div class="mt-2 flex items-center gap-4">
                    @if (auth()->user()->profile_photo)
                        <img src="{{ Storage::disk('s3')->url(auth()->user()->profile_photo) }}" class="h-20 w-20 rounded-lg object-cover" alt="Profile">
                    @else
                        <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xl font-semibold">
                            {{ auth()->user()->initials() }}
                        </div>
                    @endif
                    <div>
                        <label for="photo-upload" class="cursor-pointer inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
                            Pilih Foto
                        </label>
                        <input id="photo-upload" type="file" wire:model="photo" accept="image/*" class="hidden">
                        @if ($photo)
                            <p class="text-sm text-green-600 mt-1 font-medium">{{ $photo->getClientOriginalName() }}</p>
                        @endif
                        <p class="text-xs text-gray-500 mt-1">Maksimal 2MB (JPG, PNG, GIF)</p>
                    </div>
                </div>
                @error('photo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                <div wire:loading wire:target="photo" class="text-sm text-blue-600 mt-1">Mengunggah foto...</div>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        {{-- <livewire:settings.delete-user-form /> --}}
    </x-settings.layout>
</section>
