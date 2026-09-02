{{--
    resources/views/components/charts/underperformers-list.blade.php

    Ranked list for "below average by readings" - deliberately NOT a bar
    chart, since a bar's height goes to zero for the worst offenders
    (zero readings), making them the least visible instead of the most.
    Here the count is always shown as text, and zero-reading rows get an
    explicit "No readings yet" badge instead of an invisible bar.

    Props:
      title           string      (optional, rendered above the list)
      rows            iterable    each item needs: rank, name, total_readings,
                                   gap, percent_of_average
      averageReadings number      shown in the header as context
      unitLabel       string      (optional, defaults to "readings")
--}}
@props([
    'title' => null,
    'rows' => [],
    'averageCompletionRate' => 0,
    'unitLabel' => 'readings',
])

<div class="bg-white">
    @if(count($rows) === 0)
        <div class="flex flex-col gap-4 items-center justify-center w-full border border-gray-100 rounded-sm bg-gray-50/70 min-h-60">
            <i data-lucide="thumbs-up" class="w-8 h-8 text-gray-300"></i>
            <p class="text-gray-400 text-xs">Nobody is below average right now</p>
        </div>
    @else
        <div class="border border-amber-100 rounded-sm divide-y divide-amber-100 bg-amber-50/30 max-h-[420px] overflow-y-auto thin-scrollbar">
            @foreach($rows as $row)
                <div class="flex items-center gap-3 px-4 py-3 hover:bg-amber-50/60 transition">

                    {{-- Rank badge --}}
                    <div class="flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-600 text-xxs font-bold shrink-0">
                        {{ $row->rank }}
                    </div>

                    {{-- Name + progress bar --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-600 truncate">{{ $row->name ?? 'Unknown' }}</p>

                       
                    </div>

                    {{-- Count / severity --}}
                    <div class="text-right shrink-0">
                        @if($row->total_readings == 0)
                            <span class="inline-flex items-center px-2 py-1 rounded-sm bg-amber-500 text-white text-xxs font-semibold uppercase">
                                No {{ $unitLabel }}
                            </span>
                        @else
                            <p class="text-sm font-bold text-amber-500">{{ $row->total_readings }} Readings</p>
                        <p class="text-xxs text-gray-400">
                            {{ number_format($row->completion_rate, 1) }}% complete 
                            <span class="text-red-500">({{ number_format($row->gap, 1) }}% behind average)</span>
                        </p>
                        @endif
                    </div>

                </div>
            @endforeach
        </div>
    @endif

      <p class="text-xs text-gray-400 mt-4">
        Cycle average: <span class="font-semibold text-gray-500">{{ $averageCompletionRate }}%</span>
      </p>
</div>