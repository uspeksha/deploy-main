<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ __('Profile') }}
            </h2>
            <a href="{{ route('dashboard') }}" class="ui-button-secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Kembali ke dashboard
            </a>
        </div>
    </x-slot>

    <div class="ui-page">
            <div class="ui-panel">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="ui-panel">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="ui-panel border-rose-200 dark:border-rose-900/70">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
    </div>
</x-app-layout>
