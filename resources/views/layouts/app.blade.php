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

            <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet/dist/leaflet.css"
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
    let loaderTimeout = null;

    function showLoader() {
        // Clear any existing timeout
        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
        }
        
        // Show loader
        loader.classList.remove('opacity-0', 'pointer-events-none');
        
        // Set timeout to hide loader after 6 seconds
        loaderTimeout = setTimeout(() => {
            console.warn('Loader timeout: Force hiding loader after 6 seconds');
            hideLoader();
        }, 5000);
    }

    function hideLoader() {
        // Clear timeout if exists
        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
            loaderTimeout = null;
        }
        
        // Hide loader
        loader.classList.add('opacity-0', 'pointer-events-none');
    }

    // Hide loader on full page load
    window.addEventListener('load', hideLoader);

    // Handle link navigation safely
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        
        if (!link) return;

        /*
        |--------------------------------------------------------------------------
        | Ignore UI/internal anchors
        |--------------------------------------------------------------------------
        */

        // Leaflet popup controls
        if (link.closest('.leaflet-container')) {
            return;
        }

        // Empty/hash links
        if (
            link.getAttribute('href') === '#' ||
            link.getAttribute('href')?.startsWith('#')
        ) {
            return;
        }

        // Javascript links
        if (
            link.getAttribute('href')?.startsWith('javascript:')
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Valid navigation
        |--------------------------------------------------------------------------
        */

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
            // Check if form has confirmed or prevent default scenarios
            const isConfirmed = !form.hasAttribute('data-confirm') || 
                               confirm(form.getAttribute('data-confirm') || 'Are you sure?');
            
            if (isConfirmed) {
                showLoader();
            }
        }
    });

    // Handle browser back/forward cache restore
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            hideLoader();
        }
    });
    
    // Handle form confirmations that might be cancelled
    document.addEventListener('click', function(e) {
        const button = e.target.closest('[data-confirm]');
        if (button && button.type === 'submit') {
            const confirmMessage = button.getAttribute('data-confirm') || 
                                  button.closest('form')?.getAttribute('data-confirm');
            
            if (confirmMessage && !confirm(confirmMessage)) {
                e.preventDefault();
                // Ensure loader is hidden if confirmation is cancelled
                hideLoader();
                return false;
            }
        }
    });
    
    // Handle beforeunload to ensure loader doesn't hang
    window.addEventListener('beforeunload', function() {
        hideLoader();
    });
    
    // Optional: Handle AJAX/Fetch requests
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        showLoader();
        return originalFetch.apply(this, args)
            .finally(() => {
                // Don't hide immediately for AJAX that might update UI without navigation
                // You can adjust this timeout as needed
                setTimeout(() => {
                    if (!document.querySelector('.loading-active')) {
                        hideLoader();
                    }
                }, 300);
            });
    };
</script>

        @stack('scripts')
    </body>
</html>