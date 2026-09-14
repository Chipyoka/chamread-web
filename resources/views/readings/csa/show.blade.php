<x-app-layout>
    <div  x-data="" class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-500">CSA Details</h1>
                <p class="text-sm text-gray-500">View CSA profile and assignments</p>
            </div>

            <div class="flex items-center space-x-2">
                <div class="flex mr-4 px-2 space-x-2">

    
                    <x-micro-button
                       color="blue"
                       icon="list-todo"
                       size="md"
                       href="{{ route('readings.csas.readings', $csa) }}"

                   >
                       View Readings
                   </x-micro-button>

                    <x-micro-button
                       color="slate"
                       icon="file-text"
                       size="md"
                       href="{{ route('readings.csas.accounts', $csa) }}"
                   >
                       View Accounts
                   </x-micro-button>
                    <x-micro-button
                       color="purple"
                       type="button"
                       icon="map-pin"
                       size="md"

                       x-on:click="$dispatch('open-modal', 'create-cycle')"
                   >
                       Assign
                   </x-micro-button>
    
                   @if(in_array(Auth::user()->role, ['ADMIN', 'IT']))
                        <form action="{{ route('readings.csas.destroy', $csa) }}" class="delete-form" method="POST" onsubmit="return confirm('Delete this CSA?')">
                            @csrf
                            @method('DELETE')
                                <x-micro-button
                            variant="delete"
                            icon="trash"
                            size="md"
                            type="submit"
                        >
                            Delete
                        </x-micro-button>
                        </form>
                   @endif
                </div>
           

                 <!-- back to list -->
                <x-micro-button
                    variant="edit"
                    href="{{ route('readings.csas.index') }}"
                    icon="arrow-left"
                    size="md"
                >
                    Back to CSAs
                </x-micro-button>
            </div>
        </div>

        <!-- CSA Info Card -->
        <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Name</h2>
                    <p class="text-gray-500 font-semibold">{{ $csa->name }}</p>
                </div>

                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Username</h2>
                    <p class="text-gray-500 font-semibold">{{ $csa->username }}</p>
                </div>

                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Email</h2>
                    <p class="text-gray-500 font-semibold">{{ $csa->email ?? '-' }}</p>
                </div>

                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Zone</h2>
                    <p class="text-gray-500 font-semibold">{{ $csa->activeAssignment->zone->name ?? '-' }}</p>
                </div>

                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Device</h2>
                   
                    <p class="text-gray-500 font-semibold truncate" title="{{ $csa->device->name ?? '-' }} - {{ $csa->device->model ?? '-' }} - {{ $csa->device->serial_number ?? '-' }}">
                        {{ $csa->device->name ?? '-' }} {{ $csa->device->model ?? '-' }} - {{ $csa->device->serial_number ?? '-' }}
                    </p>
                </div>
                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Last Login</h2>
                    <p class="text-gray-500 font-semibold">
                        {{ $csa->last_login_at ? $csa->last_login_at->diffForHumans() : 'Never' }}
                    </p>
                </div>

                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Activity Status</h2>
                    @if($csa->last_login_at && $csa->last_login_at->gt(now()->subDays(7)))
                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">
                            Active
                        </span>
                    @else
                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-500 rounded">
                            Inactive
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- CSA Assignments -->
        <div class="bg-white border border-gray-200 rounded-md p-6 space-y-4">
            <h3 class="text-gray-400 text-xs uppercase my-2">Assignments</h3>

           
            <!-- METRIC CARDS -->
            <div class=" grid grid-cols-4 gap-x-4 gap-y-8">

                <!-- card total assigned accounts within current cycle-->
                <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-primary">{{ $target  ?? 0}}</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Accounts Assigned</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-blue-100/70 rounded-full">
                        <i data-lucide="list" class="w-7 h-7 text-primary"></i>
                    </div>
                </div>

                <!-- card total read -->
                <div 
                    class="hover-sweep  flex items-center justify-between bg-gray-50/70 border-t-8 border-green-500 rounded-sm px-4 py-4 hover:shadow-md transition-all duration-300 ease-in-out ">
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
                    class="hover-sweep flex items-center justify-between  bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4  hover:shadow-md transition-all duration-300 ease-in-out ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-amber-400">{{ $totalPending ?? 0 }}</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Pending</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                        <i data-lucide="clock" class="w-7 h-7 text-amber-400"></i>
                    </div>
                </div>

                <!-- card percentage -->
                 @php
                    $total = $totalRead + $totalPending;
                    $completionRate = $total > 0 ? round(($totalRead / $total) * 100, 2) : 0;
                @endphp
                <div        
                    class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-slate-400 rounded-sm px-4 py-4  hover:shadow-md transition-all duration-300 ease-in-out ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-slate-400">{{ $completionRate }}%</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Completion Rate</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-slate-100/70 rounded-full">
                        <i data-lucide="circle-percent" class="w-7 h-7 text-slate-400"></i>
                    </div>
                </div>
            </div>

            @if($assignments->count() > 0)
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Zone</th>
                            <th class="px-6 py-3">Billing Cycle</th>
                            <th class="px-6 py-3">Target</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Assigned At</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($assignments as $assignment)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $assignment->zone->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $assignment->billingCycle->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $assignment->target ?? '0' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $assignment->assignment_type ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 capitalize">{{ $assignment->status }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $assignment->assigned_at?->format('Y-m-d') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $assignments->links() }}
                </div>
            @else
                <p class="text-gray-500 text-sm">No assignments found for this CSA.</p>
            @endif
        </div>

    </div>


       <!-- Create assign modal -->
        <x-modal name="create-cycle" max-width="lg" :closable="false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">Assign Device and Zone to CSA ({{ $csa->activeAssignment->zone->name ?? '-' }})</h2>
                 <!-- Assignment Form -->
                <form action="{{ route('readings.csas.assign.store', $csa) }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Zone -->
                    <div>
                        <x-input-label for="zone_id" :value="__('Zone')" />
                        <select id="zone_id" name="zone_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50" required>
                            <option value="">-- Select Zone --</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}">{{ $zone->name }} - ({{ $zone->customer_accounts_count }} accounts)</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('zone_id')" class="mt-2" />
                    </div>

                    

                    <!-- Billing Cycle -->
                    <div>
                        <x-input-label for="billing_cycle_id" :value="__('Billing Cycle')" />
                        <select id="billing_cycle_id" name="billing_cycle_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50" required>
                            <option value="">-- Select Billing Cycle --</option>
                            @foreach($cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->name }} </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('billing_cycle_id')" class="mt-2" />
                    </div>

                    <!-- Devices -->
                    <div>
                        <x-input-label for="device_id" :value="__('Device')" />
                        <select id="device_id" name="device_id" class="mt-1 block w-full border-gray-300 shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50">
                            <option value="">-- Select Device--</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">{{ $device->name }} {{ $device->model }} - {{ $device->serial_number }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('device_id')" class="mt-2" />
                    </div>

                    <!-- Status -->
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50">
                            <option value="active">Active</option>
                            <option value="reassigned">Reassigned</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-primary text-white  hover:bg-primary/90 transition">
                            Assign
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
</x-app-layout>