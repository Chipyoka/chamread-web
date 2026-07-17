<!-- We will plug in notifications as a prop from parent -->

@props(['isOpen' => false])

@php
    $notifications = [];
@endphp

<div 
    x-data="{ 
        open: {{ $isOpen ? 'true' : 'false' }},
        close() {
            this.open = false;
            document.body.style.overflow = '';
        },
        openPanel() {
            this.open = true;
            document.body.style.overflow = 'hidden';
        }
    }"
    @notification-toggle.window="open = !open; if(open) document.body.style.overflow = 'hidden'; else document.body.style.overflow = ''"
    x-init="() => { if(open) document.body.style.overflow = 'hidden'; }"
>
    {{-- Backdrop --}}
    <div 
        x-show="open"
        x-transition:enter="transition-opacity duration-300 ease-in-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-300 ease-in-out"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="close()"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50"
        style="display: none;"
    ></div>

    {{-- Slide Panel --}}
    <div 
        x-show="open"
        x-transition:enter="transform transition-transform duration-300 ease-in-out"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition-transform duration-300 ease-in-out"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl z-50 overflow-y-auto"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <i data-lucide="bell" class="h-6 w-6 text-gray-500"></i>
                    <span class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 rounded-full text-[10px] text-white flex items-center justify-center">
                        {{ count(array_filter($notifications, fn($n) => !$n->read)) }}
                    </span>
                </div>
                <h2 class="text-lg font-semibold text-gray-500">Notifications</h2>
            </div>
            <button 
                @click="close()"
                class="p-1 hover:bg-gray-100 rounded-full transition-colors duration-200"
            >
                <i data-lucide="x" class="h-5 w-5 text-gray-500"></i>
            </button>
        </div>

        {{-- Notifications List --}}
        <div class="divide-y divide-gray-100">
            @forelse($notifications as $notification)
                <div class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200 cursor-pointer {{ !$notification->read ? 'bg-blue-50/30' : '' }}">
                    <div class="flex items-start gap-3">
                        {{-- Icon --}}
                        <div class="flex-shrink-0">
                            <div class="p-2 rounded-full {{ !$notification->read ? 'bg-primary/10' : 'bg-gray-100' }}">
                                <i data-lucide="bell" class="h-4 w-4 {{ !$notification->read ? 'text-primary' : 'text-gray-500' }}"></i>
                            </div>
                        </div>
                        
                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium {{ !$notification->read ? 'text-gray-900' : 'text-gray-500' }}">
                                    {{ $notification->title }}
                                </p>
                                <span class="text-xs text-gray-400 whitespace-nowrap">{{ $notification->time }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $notification->message }}</p>
                        
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-50 rounded-full mb-4">
                        <i data-lucide="bell-off" class="h-8 w-8 text-gray-300"></i>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">You're all caught up!</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        @if(count($notifications) > 0)
            <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4">
                <button class="w-full text-center text-sm text-primary hover:text-primary/80 font-medium transition-colors duration-200">
                    Mark all as read
                </button>
            </div>
        @endif
    </div>
</div>