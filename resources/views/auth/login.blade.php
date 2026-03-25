<x-guest-layout>
    <!-- Full Screen Fixed Background -->
    <div class="fixed inset-0 -z-10">
        <img src="{{ asset('images/bg.png') }}" alt="Background" class="w-full h-full object-cover opacity-80">
    </div>

    <!-- Login Form Container -->
    <div class="min-h-[90dvh] flex flex-col items-center justify-center ">
        <div class="w-full max-w-md px-6 py-8 bg-white/90 backdrop-blur-md rounded-sm shadow-lg">
            <div class="flex items-center justify-center">
                  <img src="{{ asset('images/app_logo.png') }}" alt="logo" class="h-14">
            </div>
            <div class="mb-6 mt-2 text-center">
                <!-- <h2 class="text-gray-600 text-xl font-semibold">Dashboard Login</h2> -->
                <p class="text-gray-500 text-sm">Provide your credentials below to proceed.</p>
            </div>
            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div class="mt-4">
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Remember Me -->
                <div class="block mt-4">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-(--color-primary) shadow-sm focus:ring-(--color-primary)" name="remember">
                        <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                    </label>
                </div>

                <div class="flex items-center mt-4"> 

                    <x-primary-button class="w-full justify-center">
                        {{ __('Log in') }}
                    </x-primary-button>
                </div>
                <p class="text-xs text-gray-400 mt-3 cursor-default hover:text-gray-500 transition-all duration-200 border-e-slate-300">Having trouble? Contact IT Department.</p>
            </form>
        </div>
        <div class="text-gray-200 text-xs pt-2 px-4 border-t border-blue-300 mt-6 text-center">
           Chamread Management Dashboard © 2026 ChWSSCL.
        </div>
    </div>
</x-guest-layout>