<div x-data="reReadForm()" class="max-w-7xl mx-auto sm:px-6 lg:px-4 text-gray-500 space-y-6">

    <!-- Top card row  -->
    <div class=" bg-white border rounded-md border-gray-200  px-6 py-4">
        <div class="text-gray-500 mb-6">
            <p class="text-gray-400 text-xs uppercase my-2">key performance metrics</p>
        </div>

        <!-- METRIC CARDS -->
        <div class=" grid grid-cols-3 gap-x-4 gap-y-8">
            <div class="flex items-center justify-between bg-blue-50/40 border-t-8 border-blue-100/70 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                <div class="">
                    <h2 class="text-3xl font-bold text-primary">{{ $completionRate ?? '00'}}%</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Completion rate (%)</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-blue-50 rounded-full">
                    <i data-lucide="pie-chart" class="w-7 h-7 text-primary"></i>
                </div>
            </div>

            <!-- card total read -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-green-500 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                <div class="">
                    <h2 class="text-3xl font-bold text-green-500">{{ $accountsRead ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Marked Read</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-green-100/70 rounded-full">
                    <i data-lucide="circle-check" class="w-7 h-7 text-green-500"></i>
                </div>
            </div>

            <!-- card abnormal -->
            <div class="hover-sweep flex items-center justify-between bg-gray-50/70 border-t-8 border-amber-400 rounded-sm px-4 py-4 cursor-default hover:shadow-md transition-all duration-300 ease-in-out ">
                <div class="">
                    <h2 class="text-3xl font-bold text-amber-400">{{ $pending ?? 0 }}</h2>
                    <p class="text-gray-500 text-xs uppercase mt-2">Pending</p>
                </div>
                <div class="flex items-center justify-center p-4 bg-amber-100/70 rounded-full">
                    <i data-lucide="clock-arrow-down" class="w-7 h-7 text-amber-400"></i>
                </div>
            </div>

        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

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

                            <!-- Account Number -->
                            <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                {{ $reading->account->account_number }}
                            </td>

                            <!-- meter reader (CSA) -->
                            <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                {{ $reading->csa->name ?? "-" }}
                            </td>

                            <!-- current reading -->
                            <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                {{ number_format((float) ($reading->current_reading ?: 0), 3) }}
                            </td>

                            <!-- Consumption (hot loading) -->
                            <td class="px-6 py-4 text-xs text-gray-500">
                                @php
                                    $consumption = (($reading->current_reading ?? 0) - ($reading->previous_reading ?? 0));
                                @endphp

                                {{ number_format($consumption, 3, '.', '') }}
                            </td>


                            <!-- Reading time -->
                            <td class="px-6 py-4 text-xs text-gray-500">
                                {{ $reading->reading_time?->format('Y-m-d H:i:s') ?? '-' }}
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right text-xs space-x-2">

                                <!-- View -->
                                <x-micro-button
                                    href="{{ route('readings.meter-readings.show', $reading) }}"
                                    color="blue"
                                    icon="eye"
                                    size="sm"
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
                                <x-micro-button
                                    type="button"
                                    color="amber"
                                    icon="notebook-pen"
                                    size="sm"
                                    x-on:click="selectReading({{ \Illuminate\Support\Js::from($readingPayload) }}); $dispatch('open-modal', 're-read')"
                                >
                                    Re-read
                                </x-micro-button>
                                
                                @if($reading->pendingReread)
                                    <x-micro-button
                                        href="{{ route('readings.meter-readings.re-read.complete', $reading) }}"
                                        color="green"
                                        icon="circle-check-big"
                                        size="sm"
                                    />
                                @endif

                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>

            <!-- Pagination -->
            <div class="p-4">
                {{ $readings->links() }}
            </div>

        @else

            <!-- Empty State -->
            <div class="p-10 text-center">
                <div class="flex flex-col items-center space-y-3">
                    <i data-lucide="list-todo" class="w-10 h-10 text-gray-300"></i>
                    <p class="text-gray-500 text-xs">No readings found.</p>
                </div>
            </div>

        @endif

    </div>


    <!--
    ==================================================================
    MODAL REGISTRATIONS
    -->

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

                            <option value="Reading appears high">
                                Reading appears high
                            </option>

                            <option value="Reading appears low">
                                Reading appears low
                            </option>

                            <option value="Meter photo is unclear or missing">
                                Meter photo is unclear or missing
                            </option>

                            <option value="Meter digits are not visible">
                                Meter digits are not visible
                            </option>

                            <option value="Supervisor quality assurance check">
                                Supervisor quality assurance check
                            </option>

                            <option value="Possible incorrect meter reading">
                                Possible incorrect meter reading
                            </option>

                            <option value="Customer complaint received">
                                Customer complaint received
                            </option>

                            <option value="Meter access requires verification">
                                Meter access requires verification
                            </option>

                            <option value="Suspected meter fault">
                                Suspected meter fault
                            </option>

                            <option value="Consumption pattern requires verification">
                                Consumption pattern requires verification
                            </option>
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
            Alpine.data('reReadForm', () => ({
                // ----- Re-read request form -----
                reason: '',
                selectedReading: null,

                // Route with a placeholder id that we swap out client-side,
                // since the update route needs a specific reading's id.
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
                }
            }));
        });
    </script>
@endpush