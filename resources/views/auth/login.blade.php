
<x-guest-layout>
    <!-- Full Screen Fixed Background -->
    <div class="fixed inset-0 -z-10">
        <img src="{{ asset('images/bg.png') }}" alt="Background" class="w-full h-full object-cover opacity-80">
    </div>

    <!-- Login Form Container -->
    <div class="max-h-[90dvh] w-full">
        <div class="px-6 py-4 bg-white/90 backdrop-blur-md rounded-sm shadow-lg w-6xl ">
            <div class="flex items-center justify-between mb-6 gap-6">

                <img src="{{ asset('images/logo.png') }}" alt="logo" class="h-10">
                <img src="{{ asset('images/app_logo.png') }}" alt="logo" class="h-8">

            </div>

            <div class="flex gap-6 justify-between">

                <!-- Summary -->
                <div class="border px-6 w-1/2 py-6 mb-4 rounded-md">
                    <div class="flex justify-between gap-4 items-start ">
                        
                        
                        <div class="rounded-md border border-gray-200 px-4 py-3 w-full bg-white/40 ">
                            <p class="text-xxs uppercase tracking-wide text-gray-500">Current billing cycle</p>
                            <h1 class="text-2xl font-medium text-gray-500">{{ $currentCycle->name ?? "-"}}</h1>
                        </div>
     
                        <div class="w-1/2">
                            <div class="mb-2 bg-white/40 border-gray-200 border rounded-md flex items-center justify-between py-1.5 px-3  text-sm">
                                <p class="text-xxs text-gray-500">Readings</p>
                                <p class="">{{$read ?? 0}}</p>
                            </div>
                            <div class=" bg-white/40 border-gray-200 border rounded-md flex items-center justify-between py-1.5 px-3  text-sm">
                                <p class=" text-xxs text-gray-500">Pending</p>
                                <p class="">{{$pending ?? 0}}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-center mt-2 bg-white/40 p-4 rounded-md">

                    @if($read < 1 && $pending < 1)
                        <div class="flex flex-col gap-4 items-center justify-center w-full border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
                            <i data-lucide="chart-no-axes-column" class="w-8 h-8 text-gray-300"></i>
                            <p class="text-gray-400 text-xs">No data available yet</p>
                        </div>

                    @else
                        <div class=" ">
                            <x-charts.reading-donut-chart
                                    :read="$read"
                                    :pending="$pending"
                                />
                        </div>
                    @endif
                    </div>
                   
                </div>

                <!-- Login form -->
                <div class="border px-6 w-1/2 py-6 mb-4 rounded-md bg-white/40">
                    <div class="mb-6 mt-2">
                        <h2 class="text-gray-700 text-2xl font-semibold">Login</h2>
                        <p class="text-gray-500 text-sm">Provide your credentials below to proceed.</p>
                    </div>
                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4" :status="session('status')" />
        
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
        
                        <!-- Email Address -->
                        <div>
                            <x-input-label for="login" :value="__('Email')" />
                            <x-text-input id="login" class="block mt-1 w-full" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" />
                            <x-input-error :messages="$errors->get('login')" class="mt-2" />
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
                                <span class="ml-2 text-sm text-gray-500">{{ __('Remember me') }}</span>
                            </label>
                        </div>
        
                        <div class="flex items-center mt-4"> 
        
                            <x-primary-button class="w-full justify-center">
                                {{ __('Login') }}
                            </x-primary-button>
                        </div>
                        <p class="text-xxs text-gray-400 mt-3 cursor-default hover:text-gray-500 transition-all duration-200 border-e-slate-300">Having trouble? Contact IT Department.</p>
                    </form>
                </div>
            </div>
            <div class="text-gray-500 text-xxs  text-center">
               Chamread Management Dashboard © 2026 ChWSSCL.
            </div>
        </div>
    </div>
</x-guest-layout>