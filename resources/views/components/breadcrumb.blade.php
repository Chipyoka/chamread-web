<nav class="flex items-center text-xxs px-8">

    <ol class="flex items-center gap-2">

        @foreach($items as $item)

            <li class="flex items-center">

                @if(!$loop->first)
                    <span class=" text-gray-400">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </span>
                @endif


                @if(isset($item['url']) && !$loop->last)

                    <a 
                        href="{{ $item['url'] }}"
                        class="text-gray-500 hover:text-primary transition"
                    >
                        {{ $item['label'] }}
                    </a>

                @else

                    <span class="font-medium text-gray-500/70">
                        {{ $item['label'] }}
                    </span>

                @endif

            </li>

        @endforeach

    </ol>

</nav>