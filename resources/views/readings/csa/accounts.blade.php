<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">Assigned accounts: <span class="text-secondary">{{ $csa->name ?? 'CSA'}} </span></h1>
                <p class="text-sm text-gray-500">{{ $csa->activeAssignment->zone->name ?? "" }} accounts assigned in the current billing cycle</p>
            </div>

           <div class="flex items-center space-x-2">
           
                 <x-micro-button
                    color="purple"
                    href="{{ route('readings.csas.readings', $csa) }}"
                    icon="list-todo"
                    size="md"
                >
                    View Readings
                </x-micro-button>
                 <x-micro-button
                    variant="view"
                    href="{{ route('readings.csas.show', $csa) }}"
                    icon="user"
                    size="md"
                >
                    View Profile
                </x-micro-button>

                 <!-- back to list -->
                <x-micro-button
                    variant="edit"
                    href="{{ route('readings.csas.index') }}"
                    icon="arrow-left"
                    size="md"
                >
                    Back to list
                </x-micro-button>
            </div>
        </div>

        <!-- Overview -->
        <div class=" bg-white border rounded-md border-gray-200  px-6 py-4">
            <div class="text-gray-500 mb-6">
                <p class="text-gray-400 text-xs uppercase my-2">key performance metrics</p>
            </div>
            <!-- METRIC CARDS -->
            <div class=" grid grid-cols-3 gap-x-4 gap-y-8">

                <!-- card total assigned accounts within current cycle-->
                <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-primary">{{ $totalAssigned  ?? 0}}</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Accounts Assigned</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-blue-100/70 rounded-full">
                        <i data-lucide="list" class="w-7 h-7 text-primary"></i>
                    </div>
                </div>

                <!-- card total read -->
                <div 
                onclick="window.location.href='{{ request()->fullUrlWithQuery(['status' => request('status') === 'read' ? '' : 'read']) }}'"
                class="hover-sweep cursor-pointer flex items-center justify-between bg-gray-50/70 border-t-8 border-green-500 rounded-sm px-4 py-4 hover:shadow-md transition-all duration-300 ease-in-out ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-green-500">{{ $totalRead ?? 0 }}</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Marked Read</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-green-100/70 rounded-full">
                        <i data-lucide="circle-check" class="w-7 h-7 text-green-500"></i>
                    </div>
                </div>

                <!-- card pending -->
                <div 
                onclick="window.location.href='{{ request()->fullUrlWithQuery(['status' => request('status') === 'not-read' ? '' : 'not-read']) }}'"
                class="hover-sweep flex items-center justify-between cursor-pointer bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4  hover:shadow-md transition-all duration-300 ease-in-out ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-amber-400">{{ $totalPending ?? 0 }}</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Pending</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                        <i data-lucide="clock" class="w-7 h-7 text-amber-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            @if($accounts->count() > 0)

                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Account #</th>
                            <th class="px-6 py-3">Meter #</th>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Phone</th>
                            <th class="px-6 py-3  hover:text-gray-700 transition-colors">
                                <div class="flex items-center space-x-1">
                                    <span>Reading Status</span>
                                    @if(request('status') === 'not-read')
                                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">Filtered</span>
                                        <i data-lucide="x" class="w-4 h-4 ml-1"></i>
                                    @else
                                          <i data-lucide="filter" class="w-4 h-4 ml-1"></i>
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">

                        @foreach($accounts as $account)
                            <tr class="hover:bg-gray-50 transition">

                                  <!-- Account Number -->
                                <td class="px-6 py-4 text-sm text-gray-500 font-medium">
                                    {{ $account->account_number }}
                                </td>

                                <!-- Meter Number -->
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $account->meter_number ?? '-' }}
                                </td>

                                <!-- Name -->
                                <td class="px-6 py-4 text-sm text-gray-500 font-medium">
                                    {{ $account->customer_name }}
                                </td>

                                <!-- Phone -->
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $account->phone ?? '-' }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <span class="
                                        px-2 py-1 text-xs rounded uppercase
                                        @if($account->read_status === 'read')
                                            bg-green-100 text-green-700
                                        @else
                                            bg-red-100 text-red-700
                                        @endif
                                    ">
                                        {{ $account->read_status === 'read' ? 'Read' : 'Not read' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right text-sm space-x-2">

                                    <!-- View -->
                                    <x-micro-button
                                        href="{{ route('readings.accounts.show', $account) }}"
                                        color="blue"
                                        icon="user"
                                        size="sm"
                                    >
                                        Details
                                    </x-micro-button>


                                  
                                </td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="p-4">
                    {{ $accounts->links() }}
                </div>

            @else

                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="users" class="w-10 h-10 text-gray-300"></i>
                        <p class="text-gray-500 text-sm">No Accounts found.</p>
                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>