<x-guest-layout>
    <div class="ui-page flex min-h-screen max-w-none items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div class="ui-panel w-full max-w-md">
            <h1 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Forgot Password') }}</h1>
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
            </p>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="ui-control mt-1" type="email" name="email" :value="old('email')" required autofocus />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button class="ui-button-primary">
                        {{ __('Email Password Reset Link') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
