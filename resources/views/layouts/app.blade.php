<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased mx-auto">
        <div class="hidden lg:block min-h-[80dvh] bg-slate-50">
            <div class="min-h-4 bg-primary"></div>
            @include('layouts.navigation')

            
            <!-- Page Content -->
            <main class="flex items-start justify-between">
                <!-- sidebar navigation -->
                <x-sidebar />

                <!-- main content -->
                <div class="w-full overflow-y-auto h-full max-h-[80dvh]">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <div class="lg:hidden h-screen flex flex-col  items-center justify-center space-y-4">
            <div class="flex items-center justify-center gap-2 h-fit w-fit px-4 py-3 bg-amber-50 rounded-sm">
                <i data-lucide="circle-alert" class="w-6 h-6 text-amber-600"></i>
                <p class="text-amber-600">You need to use a laptop.</p>
            </div>
            <p class="text-xs text-center text-gray-400 max-w-[70%]">Dashboard cannot be loaded using a smaller screen.</p>
        </div>
    </body>
</html>
