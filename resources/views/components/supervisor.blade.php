<div x-data="supervisorDashboard()" class="max-w-7xl mx-auto sm:px-6 lg:px-4 text-gray-500 space-y-6">

    <!-- Top card row  -->
    <div class="bg-white border rounded-md border-gray-200 px-6 py-4">
        <div class="text-gray-500 mb-6">
            <p class="text-gray-400 text-xs uppercase my-2">key performance metrics</p>
        </div>

        <!-- METRIC CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-x-4 gap-y-8">

            <!-- total accounts loaded -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-slate-500 rounded-sm px-4 py-4 transition-all duration-300 ease-in-out">
                <div>
                    <h2 class="text-3xl font-bold text-slate-500">{{ $overviewData['totalAccountsLoaded'] ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Total Accounts</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-slate-100/70 rounded-full">
                    <i data-lucide="file-text" class="w-7 h-7 text-slate-500"></i>
                </div>
            </div>
            <!-- Read -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-green-500 rounded-sm px-4 py-4 transition-all duration-300 ease-in-out">
                <div>
                    <h2 class="text-3xl font-bold text-green-500">{{ $counts['read'] ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Read</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-green-100/70 rounded-full">
                    <i data-lucide="circle-check" class="w-7 h-7 text-green-500"></i>
                </div>
            </div>

               <!-- Pending -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4 transition-all duration-300 ease-in-out">
                <div>
                    <h2 class="text-3xl font-bold text-amber-400">{{ $counts['pending'] ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Pending</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                    <i data-lucide="clock-arrow-down" class="w-7 h-7 text-amber-400"></i>
                </div>
            </div>

            
            <!-- Flagged -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-red-500 rounded-sm px-4 py-4 transition-all duration-300 ease-in-out">
                <div>
                    <h2 class="text-3xl font-bold text-red-500">{{ $counts['flagged'] ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Flagged</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-red-100/70 rounded-full">
                    <i data-lucide="flag" class="w-7 h-7 text-red-500"></i>
                </div>
            </div>

            <!-- Auto -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-blue-500 rounded-sm px-4 py-4 transition-all duration-300 ease-in-out">
                <div>
                    <h2 class="text-3xl font-bold text-blue-500">{{ $counts['auto'] ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Auto</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-blue-100/70 rounded-full">
                    <i data-lucide="zap" class="w-7 h-7 text-blue-500"></i>
                </div>
            </div>

            <!-- Manual -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-purple-500 rounded-sm px-4 py-4 transition-all duration-300 ease-in-out">
                <div>
                    <h2 class="text-3xl font-bold text-purple-500">{{ $counts['manual'] ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Manual</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-purple-100/70 rounded-full">
                    <i data-lucide="user-check" class="w-7 h-7 text-purple-500"></i>
                </div>
            </div>


         

        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between">
            <p class="text-gray-400 text-xs uppercase mt-2">Showing {{ $counts[$filter] ?? 0 }} Readings of 
                <span 
                class="font-bold
                @if ($filter === 'read') text-green-500
                @elseif ($filter === 'auto') text-blue-500
                @elseif ($filter === 'manual') text-purple-500
                @elseif ($filter === 'flagged') text-red-500
                @elseif ($filter === 'pending') text-amber-400
                    
                @endif
                "
                >{{ $filter }}</span></p>

            <form method="GET" class="mt-2">
                <select name="q" onchange="this.form.submit()"
                    class="rounded-sm border-gray-300 shadow-sm text-xs focus:border-primary focus:ring-primary">
                    <option value="read"    {{ $filter === 'read'    ? 'selected' : '' }}>Read</option>
                    <option value="auto"    {{ $filter === 'auto'    ? 'selected' : '' }}>Auto</option>
                    <option value="manual"  {{ $filter === 'manual'  ? 'selected' : '' }}>Manual</option>
                    <option value="flagged" {{ $filter === 'flagged' ? 'selected' : '' }}>Flagged</option>
                    <option value="pending" {{ $filter === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </form>
        </div>

        @if ($filter === 'pending')

            {{-- Accounts with no reading yet this cycle --}}
            @if(count($accountPendingList) > 0)
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xxs text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Account</th>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Address</th>
                            <th class="px-6 py-3">Zone</th>
                            <th class="px-6 py-3">CSA</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($accountPendingList as $account)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">{{ $account->account_number ?? "-" }}</td>
                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">{{ $account->customer_name ?? "-" }}</td>
                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">{{ $account->address ?? "-" }}</td>
                                <td class="px-6 py-4 text-xs text-gray-500">{{ $account->zone->name ?? "-" }}</td>
                                <td class="px-6 py-4 text-xs text-gray-500">{{ $account->assignedCsa?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-right text-xs space-x-2 flex items-center justify-end">
                                    <x-micro-button href="{{ route('readings.accounts.show', $account) }}" color="blue" icon="eye" size="sm">
                                        View
                                    </x-micro-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4">{{ $accountPendingList->withQueryString()->links() }}</div>
            @else
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="list-todo" class="w-10 h-10 text-gray-300"></i>
                        <p class="text-gray-500 text-xs">No pending accounts.</p>
                    </div>
                </div>
            @endif

        @else

            {{-- Readings: read / auto / manual / flagged --}}
            @if(count($readings) > 0)
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xxs text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Account</th>
                            <th class="px-6 py-3">CSA</th>
                            <th class="px-6 py-3">Current</th>
                            <th class="px-6 py-3">Consump...</th>
                            <th class="px-6 py-3">Reading Time</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($readings as $reading)
                            <tr class="hover:bg-gray-50 transition">

                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                    {{ $reading->account->account_number ?? '-' }}
                                </td>

                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                    {{ $reading->csa->name ?? "-" }}
                                </td>

                                <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                    {{ number_format((float) ($reading->current_reading ?: 0)) }}
                                </td>

                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $reading->consumption ?? (($reading->current_reading ?? 0) - ($reading->previous_reading ?? 0)) }}
                                </td>

                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $reading->reading_time?->format('Y-m-d H:i:s') ?? '-' }}
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right text-xs space-x-2 flex items-center justify-end">
                                    @if($reading->flags->isNotEmpty())
                                        <span class="inline-flex justify-center items-center w-6 h-6 bg-amber-400 rounded-full animate-pulse">
                                            <i data-lucide="flag" class="w-3 h-3 text-white"></i>
                                        </span>
                                    @endif

                                    @php
                                        $viewPayload = [
                                            'account_number'  => $reading->account->account_number ?? '-',
                                            'csa_name'        => $reading->csa->name ?? '-',
                                            'previous_reading'=> $reading->previous_reading,
                                            'current_reading' => $reading->current_reading,
                                            'consumption'     => $reading->consumption,
                                            'reading_date'    => optional($reading->reading_date)->format('Y-m-d'),
                                            'reading_time'    => optional($reading->reading_time)->format('Y-m-d H:i:s'),
                                            'this_month_code' => $reading->code?->code . ' ' .($reading->code->name ?? ''),
                                            'meter_status'    => $reading->meter_status,
                                            'comment'         => $reading->comment,
                                            'verification_status' => $reading->verification_status,
                                            'photo_url'       => $reading->photo_path ? \Illuminate\Support\Facades\Storage::url($reading->photo_path) : null,
                                            'flags'           => $reading->flags->map(fn ($f) => [
                                                'name'        => $f->name,
                                                'description' => $f->description,
                                            ])->values(),
                                        ];
                                    @endphp

                                    <!-- View (modal, no navigation) -->
                                    <x-micro-button
                                        type="button"
                                        color="blue"
                                        icon="eye"
                                        size="sm"
                                        x-on:click="selectView({{ \Illuminate\Support\Js::from($viewPayload) }}); $dispatch('open-modal', 'view-reading')"
                                    >
                                        View
                                    </x-micro-button>

                                    <!-- Re-read -->
                                    @php
                                        $readingPayload = [
                                            'id' => $reading->id,
                                            'account_id' => $reading->account_id,
                                            'account_number' => $reading->account->account_number ?? null,
                                        ];
                                    @endphp
                                    @if(!$reading->pendingReread)
                                        <x-micro-button
                                            type="button"
                                            color="amber"
                                            icon="notebook-pen"
                                            size="sm"
                                            x-on:click="selectReading({{ \Illuminate\Support\Js::from($readingPayload) }}); $dispatch('open-modal', 're-read')"
                                        >
                                            Re-read
                                        </x-micro-button>
                                    @endif

                                    @if($reading->pendingReread)
                                        <x-micro-button
                                            href="{{ route('readings.meter-readings.re-read.complete', $reading) }}"
                                            color="green"
                                            icon="circle-check-big"
                                            size="sm"
                                        >Mark Complete</x-micro-button>
                                    @endif

                                    <!-- Verify (manual verification) -->
                                    @if($reading->verification_status === 'flagged')
                                        <form method="POST" action="{{ route('readings.meter-readings.verify', $reading) }}" class="inline-flex" onsubmit="return confirm('Are you sure you want to verify this reading? This action cannot be undone.')">
                                            @csrf
                                            <x-micro-button type="submit" color="green" icon="shield-check" size="sm" >
                                                Verify
                                            </x-micro-button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="p-4">
                    {{ $readings->withQueryString()->links() }}
                </div>

            @else
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="list-todo" class="w-10 h-10 text-gray-300"></i>
                        <p class="text-gray-500 text-xs">No readings found.</p>
                    </div>
                </div>
            @endif

        @endif
    </div>


    <!--
    ==================================================================
    MODAL REGISTRATIONS
    -->

    <!-- View reading modal: photo + essential info, no navigation -->
    <x-modal name="view-reading" max-width="3xl" :closable="true">
        <div class="p-6" x-show="selectedView">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                Reading — <span x-text="selectedView?.account_number"></span>
            </h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <template x-if="selectedView?.photo_url">
                        <img :src="selectedView.photo_url" alt="Meter photo"
                            class="w-full max-h-80 object-contain rounded-sm border border-gray-200 bg-gray-50/70 mb-4">
                    </template>

                    <template x-if="!selectedView?.photo_url">
                        <div class="w-full h-40 flex items-center justify-center bg-gray-50 rounded-sm border border-dashed border-gray-200 mb-4">
                            <p class="text-gray-400 text-xs">No photo attached</p>
                        </div>
                    </template>
                </div>

                <!-- details -->
                <div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                        <div>
                            <dt class="text-gray-400 uppercase text-xxs">CSA</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView?.csa_name"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 uppercase text-xxs">Status</dt>
                            <dd class="text-gray-700 font-medium capitalize" x-text="selectedView?.verification_status"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 uppercase text-xxs">Previous Reading</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView?.previous_reading"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 uppercase text-xxs">Current Reading</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView?.current_reading"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 uppercase text-xxs">Consumption</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView?.consumption"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 uppercase text-xxs">Meter Code</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView?.this_month_code"></dd>
                        </div>
                    
                        <div>
                            <dt class="text-gray-400 uppercase text-xxs">Reading Time</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView?.reading_time"></dd>
                        </div>
                        <div x-show="selectedView?.comment">
                            <dt class="text-gray-400 uppercase text-xxs">Comment</dt>
                            <dd class="text-gray-700" x-text="selectedView?.comment"></dd>
                        </div>
                    </dl>

                    <template x-if="selectedView?.flags?.length">
                        <div class="mt-4 border-t pt-3 border-gray-100">
                            <p class="text-gray-400 uppercase text-xxs mb-2">Flag Reasons</p>
                            <ul class="space-y-1">
                                <template x-for="flag in selectedView.flags" :key="flag.name">
                                    <li class="flex items-center text-xs text-amber-600">
                                        <i data-lucide="flag" class="w-3 h-3 mr-2"></i>
                                        <span x-text="flag.description"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                </div>

            </div>

   

          
        </div>
    </x-modal>

    <!-- Re-read request modal -->
    <x-modal name="re-read" max-width="md" :closable="false">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900">Request Re-read</h2>
            <form method="POST"
                  x-bind:action="reReadFormAction"
                  x-ref="reReadForm"
                  class="space-y-4">
                @csrf

                <div class="my-4 border-t pt-2 border-gray-100 text-gray-500 flex items-center">
                    <i data-lucide="info" class="mr-2 w-3 h-3"></i>
                    <p class="text-xxs ">Select reason for re-reading account <span class="text-sm ml-2 font-semibold" x-text="selectedReading.account_number"></span></p>
                </div>

                {{-- Reason --}}
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">Reason:</label>
                      <select
                            id="reason"
                            name="reason"
                            required
                            class="mt-1 block w-full rounded-sm border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm @error('end_date') border-red-500 @enderror"
                        >
                            <option value="">Select a reason...</option>
                            <option value="Reading appears high">Reading appears high</option>
                            <option value="Reading appears low">Reading appears low</option>
                            <option value="Meter photo is unclear or missing">Meter photo is unclear or missing</option>
                            <option value="Meter digits are not visible">Meter digits are not visible</option>
                            <option value="Supervisor quality assurance check">Supervisor quality assurance check</option>
                            <option value="Possible incorrect meter reading">Possible incorrect meter reading</option>
                            <option value="Customer complaint received">Customer complaint received</option>
                            <option value="Meter access requires verification">Meter access requires verification</option>
                            <option value="Suspected meter fault">Suspected meter fault</option>
                            <option value="Consumption pattern requires verification">Consumption pattern requires verification</option>
                        </select>

                    @error('reason')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <div class="pt-2 flex justify-end">
                    <button type="submit"
                        class="w-fit rounded-sm bg-primary px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('supervisorDashboard', () => ({
                // ----- Re-read request form -----
                reason: '',
                selectedReading: null,

                actionTemplate: "{{ route('readings.meter-readings.re-read', ['reading' => 'READING_ID']) }}",

                selectReading(reading) {
                    this.selectedReading = reading;
                },

                get reReadFormAction() {
                    if (!this.selectedReading) {
                        return '#';
                    }
                    return this.actionTemplate.replace('READING_ID', this.selectedReading.id);
                },

                submitReReadForm() {
                    this.$refs.reReadForm.submit();
                },

                // ----- View reading modal -----
                selectedView: null,

                selectView(reading) {
                    this.selectedView = reading;
                },
            }));
        });
    </script>
@endpush