<x-layouts.guest>
    <x-slot name="title">
        Two-factor authentication
    </x-slot>

    <div class="text-sm text-gray-600 mb-4">
        Please confirm access to your account by entering the authentication code provided by your authenticator application.
    </div>

    <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-6">
        @csrf

        <!-- Code -->
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700">
                Authentication Code
            </label>
            <div class="mt-1">
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                       autofocus
                       pattern="[0-9]{6}"
                       placeholder="000000"
                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 text-center text-2xl tracking-widest sm:text-sm @error('code') border-red-300 @enderror">
            </div>
            @error('code')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                Verify
            </button>
        </div>
    </form>

    <!-- Divider -->
    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">Or use a recovery code</span>
            </div>
        </div>
    </div>

    <!-- Recovery Code Form -->
    <form method="POST" action="{{ url('/two-factor-challenge') }}" class="mt-6 space-y-6">
        @csrf

        <!-- Recovery Code -->
        <div>
            <label for="recovery_code" class="block text-sm font-medium text-gray-700">
                Recovery Code
            </label>
            <div class="mt-1">
                <input id="recovery_code" name="recovery_code" type="text" autocomplete="off"
                       class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm @error('recovery_code') border-red-300 @enderror">
            </div>
            @error('recovery_code')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                Use Recovery Code
            </button>
        </div>
    </form>
</x-layouts.guest>


