<x-guest-layout>
    <div class="ui-page flex min-h-screen max-w-none items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div class="ui-panel w-full max-w-md">
            <h1 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Confirm Password') }}</h1>
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
            </p>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="password" :value="__('Password')" />

                    <x-text-input id="password" class="ui-control mt-1"
                                  type="password"
                                  name="password"
                                  required autocomplete="current-password" />

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button class="ui-button-primary">
                        {{ __('Confirm') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
