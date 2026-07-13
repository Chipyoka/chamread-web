@props([
    'name',
    'maxWidth' => '2xl',
    'closeable' => true,
])

@php
$maxWidthClass = [
    'sm'  => 'sm:max-w-sm',
    'md'  => 'sm:max-w-md',
    'lg'  => 'sm:max-w-lg',
    'xl'  => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
    '3xl' => 'sm:max-w-3xl',
    '4xl' => 'sm:max-w-4xl',
][$maxWidth] ?? 'sm:max-w-2xl';
@endphp

<div
    x-data="{
        show: false,
        name: '{{ $name }}',
        closeable: {{ $closeable ? 'true' : 'false' }},

        close() {
            if (!this.closeable) return;
            this.show = false;
            document.body.style.overflow = '';
        },

        forceClose() {
            this.show = false;
            document.body.style.overflow = '';
        },

        openModal() {
            this.show = true;
            document.body.style.overflow = 'hidden';
        }
    }"
    x-on:open-modal.window="if ($event.detail === name) openModal()"
    x-on:close-modal.window="if (!$event.detail || $event.detail === name) forceClose()"
    x-on:keydown.escape.window="show && close()"
    x-show="show"
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="transition-opacity duration-300 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-200 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm"
    ></div>

    {{-- Panel wrapper (centers content) --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="show"
            x-transition:enter="transition duration-300 ease-out"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-on:click.stop
            class="relative w-full {{ $maxWidthClass }} bg-white rounded-xl shadow-2xl"
        >
            {{-- Close button (only if closeable) --}}
            @if($closeable)
                <button
                    type="button"
                    x-on:click="close()"
                    class="absolute top-4 right-4 p-1 rounded-full hover:bg-gray-100 transition-colors duration-200 z-10"
                >
                    <i data-lucide="x" class="h-5 w-5 text-gray-500"></i>
                </button>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>