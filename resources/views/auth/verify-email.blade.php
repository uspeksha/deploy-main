<x-guest-layout>
    <div class="ui-page flex min-h-screen max-w-none items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div class="ui-panel w-full max-w-xl">
            <h1 class="mb-3 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Verify Email Address') }}</h1>
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </div>
            @endif

            <div class="flex flex-col-reverse items-stretch justify-between gap-3 sm:flex-row sm:items-center">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf

                    <x-primary-button class="ui-button-primary">
                        {{ __('Resend Verification Email') }}
                    </x-primary-button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="ui-button-secondary w-full sm:w-auto">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
