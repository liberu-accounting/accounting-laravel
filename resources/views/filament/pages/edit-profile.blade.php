<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Profile Information</x-slot>
        <x-slot name="description">Update your account's name and email address.</x-slot>

        <form wire:submit="submit">
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button type="submit">
                    Save
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Two-Factor Authentication</x-slot>
        <x-slot name="description">
            Add additional security to your account using two-factor authentication.
        </x-slot>

        @if (! $this->user->two_factor_secret)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                You have not enabled two-factor authentication.
            </p>

            <div class="mt-6">
                <x-filament::button wire:click="enableTwoFactor">
                    Enable
                </x-filament::button>
            </div>
        @elseif (! $this->user->hasEnabledTwoFactorAuthentication())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Scan the QR code with your authenticator app, then enter a generated code to confirm.
            </p>

            <div class="mt-4 inline-block rounded-lg bg-white p-4">
                {!! $this->user->twoFactorQrCodeSvg() !!}
            </div>

            <div class="mt-4">
                <p class="text-sm font-medium text-gray-950 dark:text-white">Recovery codes</p>
                <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                    Store these somewhere safe. Each can be used once to recover access if you lose your device.
                </p>
                <div class="grid gap-1 font-mono text-sm text-gray-950 dark:text-white">
                    @foreach ($this->user->recoveryCodes() as $code)
                        <span>{{ $code }}</span>
                    @endforeach
                </div>
            </div>

            <form wire:submit="confirmTwoFactor" class="mt-6 flex flex-wrap items-end gap-3">
                <div class="w-40">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="twoFactorCode"
                            placeholder="Code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                        />
                    </x-filament::input.wrapper>
                </div>

                <x-filament::button type="submit">
                    Confirm
                </x-filament::button>

                <x-filament::button type="button" color="danger" wire:click="disableTwoFactor">
                    Cancel
                </x-filament::button>
            </form>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Two-factor authentication is
                <span class="font-semibold text-success-600 dark:text-success-400">enabled</span>.
            </p>

            <div class="mt-6">
                <x-filament::button color="danger" wire:click="disableTwoFactor">
                    Disable
                </x-filament::button>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
