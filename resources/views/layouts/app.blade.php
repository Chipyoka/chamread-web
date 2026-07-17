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

        <style>
            #page-loader-track {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                z-index: 9999;
                pointer-events: none;
                background: transparent;
                opacity: 0;
            }

            #page-loader-track.is-active {
                opacity: 1;
            }

            #page-loader-bar {
                height: 100%;
                width: 0%;
                background-color: #f2f2f2; /* brown */
                /* box-shadow: 0 0 8px rgba(139, 90, 43, 0.6); */
                transition: width 0.25s ease-out;
            }
        </style>
    </head>

    <body class="font-sans antialiased mx-auto">
        <!-- Top Progress Bar (replaces old full-screen loader overlay) -->
        <div id="page-loader-track">
            <div id="page-loader-bar"></div>
        </div>

        <div class="hidden lg:block min-h-[80dvh] bg-slate-50">
            <div class="min-h-4 bg-primary"></div>

            @include('layouts.navigation')

            <!-- Page Content -->
            <main class="flex items-start justify-between">
                <!-- Sidebar Navigation -->
                <x-sidebar />

                <!-- Main Content -->
                <div class="w-full overflow-y-auto h-full max-h-[84dvh]">
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

        <!-- Idle timeout -->
        <x-idle-logout-modal />

        <script>
            const loaderTrack = document.getElementById('page-loader-track');
            const loaderBar = document.getElementById('page-loader-bar');

            let loaderTimeout = null;
            let loaderCreepInterval = null;
            let loaderFadeTimeout = null;
            let activeLoaders = 0; // handles overlapping fetch/navigation calls safely

            function showLoader() {
                activeLoaders++;

                // Cancel any pending fade-out/reset from a previous cycle
                if (loaderFadeTimeout) {
                    clearTimeout(loaderFadeTimeout);
                    loaderFadeTimeout = null;
                }

                if (activeLoaders > 1) {
                    // Already running, just extend the safety timeout
                    if (loaderTimeout) clearTimeout(loaderTimeout);
                    loaderTimeout = setTimeout(forceHideLoader, 5000);
                    return;
                }

                // Start fresh
                loaderTrack.classList.add('is-active');
                loaderBar.style.transition = 'none';
                loaderBar.style.width = '0%';

                // Force reflow so the transition re-applies cleanly
                void loaderBar.offsetWidth;
                loaderBar.style.transition = 'width 0.25s ease-out';
                loaderBar.style.width = '20%';

                // Creep forward gradually while the request/navigation is in flight
                let progress = 20;
                loaderCreepInterval = setInterval(() => {
                    // Slow down as it approaches 90%, never completes on its own
                    const remaining = 90 - progress;
                    progress += remaining * 0.1;
                    loaderBar.style.width = Math.min(progress, 90) + '%';
                }, 300);

                // Safety net in case something never resolves
                loaderTimeout = setTimeout(forceHideLoader, 5000);
            }

            function hideLoader() {
                activeLoaders = Math.max(activeLoaders - 1, 0);
                if (activeLoaders > 0) return; // wait for all in-flight loaders to finish

                completeLoader();
            }

            function forceHideLoader() {
                console.warn('Loader timeout: Force hiding loader after 5 seconds');
                activeLoaders = 0;
                completeLoader();
            }

            function completeLoader() {
                if (loaderCreepInterval) {
                    clearInterval(loaderCreepInterval);
                    loaderCreepInterval = null;
                }
                if (loaderTimeout) {
                    clearTimeout(loaderTimeout);
                    loaderTimeout = null;
                }

                // Finish the bar, then fade the track out and reset
                loaderBar.style.transition = 'width 0.2s ease-out';
                loaderBar.style.width = '100%';

                loaderFadeTimeout = setTimeout(() => {
                    loaderTrack.classList.remove('is-active');

                    // Reset width after the fade completes so next run starts clean
                    loaderFadeTimeout = setTimeout(() => {
                        loaderBar.style.transition = 'none';
                        loaderBar.style.width = '0%';
                    }, 250);
                }, 200);
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
                    forceHideLoader();
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
            
            // NOTE: intentionally NOT hiding/completing the loader on beforeunload.
            // beforeunload fires the instant the browser commits to navigating away,
            // long before the new page has actually finished loading/rendering.
            // Forcing the bar to 100% here made it look "done" while the user was
            // still waiting. Since this whole document (and its JS state) gets
            // discarded on navigation anyway, we just let the bar keep visually
            // running until the swap actually happens — nothing to clean up.
            
            // Optional: Handle AJAX/Fetch requests
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                showLoader();
                return originalFetch.apply(this, args)
                    .finally(() => {
                        hideLoader();
                    });
            };
        </script>

        @stack('scripts')
    </body>
</html>