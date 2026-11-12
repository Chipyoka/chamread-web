
<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-500">unmetered Details</h1>
                <p class="text-sm text-gray-500">View unmetered information </p>
               
            </div>

            <div class="flex items-center space-x-2">

                 <!-- back to list -->
                <x-micro-button
                    variant="edit"
                    href="{{ route('readings.unmetered.index') }}"
                    icon="arrow-left"
                    size="md"
                >
                    Back to list
                </x-micro-button>
            </div>
        </div>

         <!-- Reading Info Card -->
        <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
         <h3 class="text-gray-400 text-xs uppercase my-2">Details</h3>
         <div class="flex justify-start items-start gap-x-4">
            <div class="w-1/2">
                @if(!$unmetered->photo_path)
                    <div class="flex flex-col gap-4 items-center justify-center  border border-gray-100 rounded-sm bg-gray-50/70 min-h-[380px]">
                        <i data-lucide="image" class="w-8 h-8 text-gray-300"></i>
                        <p class="text-gray-400 text-xs">This unmetered has no image</p>
                    </div>
                @else
                    <div class="h-[380px] max-h-[380px] max-w-[500px] overflow-hidden">
                        <img src="{{ asset('storage/' . $unmetered->photo_path) }}" class="h-full object-cover" alt="unmetered Photo">
                    </div>
                @endif
            </div>
            <div class="grid grid-cols-2  w-full gap-4">
              
                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Meter Reading Code</h2>
                    <p class="text-gray-500 font-semibold"> {{ $unmetered->code->code ?? '-' }} - {{ $unmetered->code->name ?? '-' }}</p>
                </div>
                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Comment</h2>
                    <p class="text-gray-500 font-semibold">
                        @php
                            $value = $unmetered->comment;
                        @endphp
                        {{ $value?? 'Not provided' }}
                    </p>
                </div>
                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Reading Time</h2>
                    <p class="text-gray-500 font-semibold"> {{ $unmetered->captured_at->format('Y-m-d H:i:s') ?? '-' }}</p>
                </div>
                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Synced At</h2>
                    <p class="text-gray-500 font-semibold"> {{ $unmetered->synced_at->format('Y M d H:i:s') ?? '-' }}</p>
                </div>
               

                 <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Recorded by</h2>
                    <h2 class="text-lg font-medium text-gray-500">
                        <span class="text-secondary">
                            @php
                                $assignedCsa = $unmetered->csa;
                            @endphp
                            <a href="{{ route('readings.csas.show', $assignedCsa) }}" class="hover:underline">
                                {{ $unmetered->csa->name ?? 'Unknown' }}
                            </a>
                        </span>
                    </h2>
                </div>
             
            </div>

           

             
            </div>
         </div>
        </div>

      <div class="p-6 space-y-6">

        <!-- Customer Info Card -->
        <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-gray-400 text-xs uppercase my-2">Customer Account Information</h3>
                <a href="{{ route('readings.accounts.show', $unmetered->account) }}" class="text-primary text-sm hover:underline">View Account</a>

            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Account #</h2>
                    <p class="text-gray-500 font-semibold">{{ $unmetered->account->account_number ?? 'N/A' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Meter</h2>
                    <p class="text-gray-500 font-semibold">{{ $unmetered->account->meter_number ?? 'N/A' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Customer Name</h2>
                    <p class="text-gray-500 font-semibold">{{ $unmetered->account->customer_name ?? '-' }}</p>
                </div>


                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Phone</h2>
                    <p class="text-gray-500 font-semibold">{{ $unmetered->account->phone ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Address</h2>
                    <p class="text-gray-500 font-semibold">{{ $unmetered->account->address ?? '-' }}</p>
                </div>

                <div class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Zone</h2>
                    <p class="text-gray-500 font-semibold">{{ $unmetered->account->zone?->name ?? '-' }}</p>
                </div>
            </div>
        </div>

    


    </div>
    </div>
</x-app-layout>