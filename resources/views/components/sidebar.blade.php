<div class="min-h-[85dvh] max-h-dvh w-72 bg-white flex flex-col px-3 py-1 border-r border-gray-200">
    <nav class="flex-1 mt-4 space-y-1 h-[65dvh] max-h-[75dvh] overflow-y-auto thin-scrollbar p-3 bg-gray-50 rounded-sm">
        @foreach ($groups as $group)

            <!-- {{-- Group: parent expands only, never navigates, no icon --}} -->
            <div x-data="{ open: @js($group['hasActiveChild']) }">
                <button type="button"
                        @click="open = !open"
                        class="w-full flex items-center justify-between uppercase px-3 py-2 text-xs  tracking-wide rounded-md transition-colors duration-150
                                {{ $group['hasActiveChild']
                                    ? 'text-gray-800'
                                    : 'text-gray-500  hover:text-gray-500' }}">
                    <span>{{ $group['name'] }}</span>
                    <i data-lucide="chevron-right"
                        class="w-3.5 h-3.5 transition-transform duration-150"
                        :class="open ? 'rotate-90' : ''"></i>
                </button>

                <ul x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="mt-1 space-y-1 ml-4"
                    @if(! $group['hasActiveChild']) style="display:none" @endif>
                    @foreach ($group['children'] as $child)
                        <li>
                            <a href="{{ $child['href'] }}"
                                class="flex items-center px-3 py-2 rounded-md transition-colors duration-150 text-sm
                                        {{ $child['active']
                                            ? 'bg-gray-100 text-gray-500 font-semibold'
                                            : 'text-gray-500 hover:bg-gray-100/60 hover:text-gray-700' }}">
                                <i data-lucide="{{ $child['icon'] }}" class="w-4 h-4 mr-2 shrink-0"></i>
                                <span>{{ $child['name'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

        @endforeach
    </nav>

    <p class="text-center text-xxs text-gray-400 my-2 uppercase tracking-wider">current Version: 2.0.0</p>
</div>