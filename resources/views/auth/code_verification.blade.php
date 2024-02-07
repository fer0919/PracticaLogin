<x-guest-layout>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('validateCode') }}">
            @csrf
            <div class="mt-4">
            <x-input-label for="code" :value="__('Code')" />

            <x-text-input id="code" class="block mt-1 w-full"
                            type="int"
                            name="code"
                            />

            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>
            <div class="mt-4">
                <x-primary-button>
                    {{ __('Verify code') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>