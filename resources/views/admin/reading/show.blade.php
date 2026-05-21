
<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-600">Reading Details</h1>
                <p class="text-sm text-gray-500">View reading information </p>
               
            </div>

            <div class="flex items-center space-x-2">
           
            
                 <!-- back to list -->
           


                 <!-- back to list -->
                <x-micro-button
                    variant="edit"
                    href="{{ route('admin.readings.index') }}"
                    icon="arrow-left"
                    size="md"
                >
                    Back to readings
                </x-micro-button>
            </div>
        </div>

     


         <!-- reading Info Card -->
        <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
         <h3 class="text-gray-400 text-xs uppercase my-2">Reading Status</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              

            <!-- Reading status -->
            <div class="
                flex items-center justify-center uppercase col-span-2
                @if($reading->status === 'read')
                    bg-green-100 text-green-700
                @else
                    bg-red-100 text-red-700
                @endif">
                <p class="text-lg font-semibold text-center"> {{ $reading->status === 'read' ? 'Read' : 'Not read' }}</p>
            </div>

              <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Recorded by</h2>
                    <h2 class="text-lg font-medium text-gray-600">
                        <span class="text-secondary">
                            @php
                                $assignedCsa = $reading->csa;
                            @endphp
                            <a href="{{ route('admin.csas.show', $assignedCsa) }}" class="hover:underline">
                                {{ $reading->csa->name ?? 'Unknown' }}
                            </a>
                        </span>
                    </h2>
                </div>
             
            </div>
        </div>
         <!-- reading Info Card -->
        <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
         <h3 class="text-gray-400 text-xs uppercase my-2">Readings Information</h3>
         <div class="flex justify-start items-start gap-x-4">
            <div class="w-1/2">
                @if(!$reading->photo_path)
                    <div class="flex flex-col gap-4 items-center justify-center  border border-gray-100 rounded-sm bg-gray-50/70 min-h-[380px]">
                        <i data-lucide="image" class="w-8 h-8 text-gray-300"></i>
                        <p class="text-gray-400 text-xs">This reading has no image</p>
                    </div>
                @else
                    <div class="h-[380px] max-h-[380px] max-w-[500px] overflow-hidden">
                        <img src="{{ asset('storage/' . $reading->photo_path) }}" class="h-full object-cover" alt="Reading Photo">
                    </div>
                @endif
            </div>
            <div class="grid grid-cols-1 md:grid-cols-1 w-full gap-4">
                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Previous Reading</h2>
                    <p class="text-gray-600 font-semibold">{{ number_format((float) ($reading->previous_reading ?: 0), 3) }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Current Reading</h2>
                    <p class="text-gray-600 font-semibold">{{ number_format((float) ($reading->current_reading ?: 0), 3) }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Consumption</h2>
                    <p class="text-gray-600 font-semibold">
                         @php
                            $consumption = (($reading->current_reading ?? 0) - ($reading->previous_reading ?? 0));
                        @endphp

                        {{ number_format($consumption, 3, '.', '') }}
                    </p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">{{$reading->status === 'read' ? "Comment" : "Reason"}}</h2>
                    <p class="text-gray-600 font-semibold">
                        @php
                            $value = $reading->status === 'read' ? $reading->comment : $reading->reason->name;
                        @endphp
                        {{ $value?? 'Not provided' }}
                    </p>
                </div>
                  <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Reading Time</h2>
                    <p class="text-gray-600 font-semibold"> {{ $reading->reading_time->format('Y-m-d H:i:s') ?? '-' }}</p>
                </div>

           

             
            </div>
         </div>
        </div>

        <!-- Customer Info Card -->
        <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
            
         <h3 class="text-gray-400 text-xs uppercase my-2">Customer Account Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Account #</h2>
                    <p class="text-gray-600 font-semibold">{{ $reading->account->account_number ?? 'N/A' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Meter</h2>
                    <p class="text-gray-600 font-semibold">{{ $reading->account->meter_number ?? 'N/A' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Customer Name</h2>
                    <p class="text-gray-600 font-semibold">{{ $reading->account->name ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Billing Area</h2>
                    <p class="text-gray-600 font-semibold">{{ $reading->account->billing_area ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Phone</h2>
                    <p class="text-gray-600 font-semibold">{{ $reading->account->phone ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Address</h2>
                    <p class="text-gray-600 font-semibold">{{ $reading->account->address ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Zone</h2>
                    <p class="text-gray-600 font-semibold">{{ $reading->zone?->name ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">DMA</h2>
                    <p class="text-gray-600 font-semibold">{{ $reading->dma?->name ?? '-' }}</p>
                </div>

           

             
            </div>
        </div>


    </div>
</x-app-layout>