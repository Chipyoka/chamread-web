<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
            rel="stylesheet"
        />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased mx-auto">
        <!-- Global Loader Overlay -->
        <div
            id="page-loader"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/60 backdrop-blur-sm transition-opacity duration-300"
        >
            <div class="loader"></div>
        </div>

        <div class="hidden lg:block min-h-[80dvh] bg-slate-50">
            <div class="min-h-4 bg-primary"></div>

            @include('layouts.navigation')

            <!-- Page Content -->
            <main class="flex items-start justify-between">
                <!-- Sidebar Navigation -->
                <x-sidebar />

                <!-- Main Content -->
                <div class="w-full overflow-y-auto h-full max-h-[80dvh]">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <!-- Small Screen Notice -->
        <div class="lg:hidden h-screen flex flex-col items-center justify-center space-y-4">
            <div class="flex items-center justify-center gap-2 h-fit w-fit px-4 py-3 bg-amber-50 rounded-sm">
                <i data-lucide="circle-alert" class="w-6 h-6 text-amber-600"></i>

                <p class="text-amber-600">
                    You need to use a laptop.
                </p>
            </div>

            <p class="text-xs text-center text-gray-400 max-w-[70%]">
                Dashboard cannot be loaded using a smaller screen.
            </p>
        </div>

        <!-- Toast Notifications -->
        <x-toast />


        <script>
                const loader = document.getElementById('page-loader');

                function showLoader() {
                    loader.classList.remove('opacity-0', 'pointer-events-none');
                }

                function hideLoader() {
                    loader.classList.add('opacity-0', 'pointer-events-none');
                }

                // Hide loader on full page load
                window.addEventListener('load', hideLoader);

                // Handle link navigation safely
                document.addEventListener('click', function (e) {
                    const link = e.target.closest('a');

                    if (!link) return;

                    const isValidNavigation =
                        link.href &&
                        !link.target &&
                        !link.hasAttribute('download') &&
                        link.origin === window.location.origin;

                    if (isValidNavigation) {
                        showLoader();
                    }
                });

                // Handle form submissions
                document.addEventListener('submit', function (e) {
                    const form = e.target;

                    if (form.tagName === 'FORM') {
                        showLoader();
                    }
                });

                // Handle browser back/forward cache restore
                window.addEventListener('pageshow', function (event) {
                    if (event.persisted) {
                        hideLoader();
                    }
                });
        </script>

        @stack('scripts')
    </body>
</html>