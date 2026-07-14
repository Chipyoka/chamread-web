<x-app-layout>
    <div x-data="billingCycleForm()" class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-600">Billing Cycles</h1>
                <p class="text-sm text-gray-500">Create a billing cycle to initialize it</p>
            </div>

            <!-- allow only admins -->
            @if(Auth::user()->role === 'ADMIN')
                <button
                    type="button"
                    x-on:click="$dispatch('open-modal', 'create-cycle')"
                    class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition"
                >
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                    New Cycle
                </button>
            @endif
        </div>


        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            @if($billingCycles->count() > 0)

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xxs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Start-Date</th>
                            <th class="px-6 py-3">End-Date</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Records</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">

                        @foreach($billingCycles as $billingCycle)
                            <tr class="hover:bg-gray-50 transition">

                                <!-- billingCycle Name -->
                                <td class="px-6 py-4 text-xs text-gray-600 font-medium uppercase">
                                    {{ $billingCycle->name }}
                                </td>

                                <!-- Start Date -->
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    {{ $billingCycle->start_date->format('D d M Y') ?? '-' }}
                                </td>

                                <!-- End Date -->
                                <td class="px-6 py-4 text-xs text-gray-600 font-medium">
                                    {{ $billingCycle->end_date->format('D d M Y') ?? '-' }}
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $billingCycle->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $billingCycle->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $billingCycle->status === 'locked' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $billingCycle->status === 'closed' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ ucfirst($billingCycle->status ?? '-') }}
                                    </span>
                                </td>
                                

                                <!-- Records -->
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    {{ $billingCycle->readings->count() ?? '0' }}
                                </td>


                                

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right text-xs">
                                    <div class="flex items-center justify-end space-x-2">
                                        
                                        <!-- Toggle Download -->
                                        <form method="POST" action="{{ route('management.cycles.toggle-download', $billingCycle) }}" 
                                            class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="inline-flex items-center px-2.5 py-1.5 rounded-sm text-xs font-medium transition
                                                    {{ !$billingCycle->can_download 
                                                        ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                                                        : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                                <i data-lucide="download" class="w-3.5 h-3.5 mr-1"></i>
                                                {{ !$billingCycle->can_download ? 'Allow download' : 'Disable download' }}
                                            </button>
                                        </form>

                                        <!-- Toggle Upload -->
                                        <form method="POST" action="{{ route('management.cycles.toggle-upload', $billingCycle) }}" 
                                            class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    class="inline-flex items-center px-2.5 py-1.5 rounded-sm text-xs font-medium transition
                                                    {{ !$billingCycle->can_upload 
                                                        ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' 
                                                        : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                                <i data-lucide="upload" class="w-3.5 h-3.5 mr-1"></i>
                                                {{ !$billingCycle->can_upload ? 'Allow upload' : 'Disable upload' }}
                                            </button>
                                        </form>

                                        <!-- Edit -->
                                        <button type="button" 
                                         x-on:click="$dispatch('open-modal', 'edit-cycle'); selectCycle(@js(array_merge($billingCycle->toArray(), [
                                                'end_date' => $billingCycle->end_date->format('Y-m-d'),
                                         ])))"
                                                class="inline-flex items-center px-2.5 py-1.5 rounded-sm text-xs font-medium transition
                                                bg-gray-200/80 text-gray-700 hover:bg-gray-200">
                                            <i data-lucide="edit" class="w-3.5 h-3.5 mr-1"></i>
                                            Edit
                                        </button>

                                     

                                      

                                    </div>
                                </td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="p-4">
                    {{ $billingCycles->links() }}
                </div>

            @else

                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="calendar" class="w-10 h-10 text-gray-300"></i>
                        <p class="text-gray-500 text-xs">Create a billing cycle to initialize it.</p>

                    </div>
                </div>

            @endif

        </div>

        <div class="p-2">
            <p class="text-xs text-gray-400">To upload the <strong>READINGS_FILE</strong> navigate to the ERP Section.</p>
        </div>

        <!-- 
        ==================================================================
        MODAL REGISTRATIONS
        -->

        <!-- Create cycle modal -->
        <x-modal name="create-cycle" max-width="md" :closable="false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">Create Billing Cycle</h2>
                <form method="POST" action="{{ route('management.cycles.store') }}" class="space-y-4">
                    @csrf

                    {{-- Name (Auto-generated, disabled) --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            x-model="cycleName"
                             readonly
                            class="mt-1 block w-full uppercase rounded-sm text-gray-400 border-gray-300 bg-gray-100 shadow-sm sm:text-sm cursor-not-allowed"
                        >
                        <p class="mt-1 text-xs text-gray-400">Auto-generated from start date</p>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Start Date --}}
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input 
                            type="date" 
                            name="start_date" 
                            id="start_date" 
                            x-model="startDate"
                            x-on:change="generateCycleName()"
                            value="{{ old('start_date') }}"
                            class="mt-1 block w-full rounded-sm border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm @error('start_date') border-red-500 @enderror"
                        >
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- End Date --}}
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input 
                            type="date" 
                            name="end_date" 
                            id="end_date" 
                            value="{{ old('end_date') }}"
                            class="mt-1 block w-full rounded-sm border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm @error('end_date') border-red-500 @enderror"
                        >
                        <input 
                            type="hidden" 
                            name="deadline" 
                            id="deadline" 
                            value="{{ old('end_date') }}"
                            class="mt-1 block w-full rounded-sm border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm @error('end_date') border-red-500 @enderror"
                        >
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <div class="pt-2 flex justify-end">
                        <button type="submit"
                            class="w-fit rounded-sm bg-primary px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            Save Cycle
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>


        <!-- edit cycle modal -->
        <x-modal name="edit-cycle" max-width="md" :closable="false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">Edit Billing Cycle ( <span class="text-gray-500 uppercase" x-text="selectedBillingCycle ? selectedBillingCycle.name : ''"></span> )</h2>
                <p class="text-xxs my-1 text-gray-500 ">Skip fields you do not wish to change</p>
                <form method="POST" x-bind:action="editFormAction" x-ref="editForm"
                      x-on:submit.prevent="submitEditForm()" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <div class="my-4 border-t pt-2 border-gray-100 text-gray-500 flex items-center">
                            <i data-lucide="info" class="mr-2 w-3 h-3"></i>
                            <p class="text-xxs ">To close a billing cycle simply select CLOSED.</p>
                        </div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Select status</label>

                        <select name="status" id="status" x-model="editStatus"
                            class="mt-1 block w-full rounded-sm border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm @error('end_date') border-red-500 @enderror"

                        >
                            <option value="active">Active</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    {{-- End Date --}}
                    <div>
                        <div class="my-4 border-t pt-2 border-gray-200 text-gray-500 flex items-center">
                            <i data-lucide="info" class="mr-2 w-3 h-3"></i>
                            <p class="text-xxs ">To extended the billing cycle, simply select a future date</p>
                        </div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">Extend End Date</label>
                        <input 
                            type="date" 
                            name="end_date" 
                            id="end_date" 
                            x-model="editEndDate"
                            x-bind:class="editEndDateError ? 'border-red-500' : 'border-gray-300'"
                            class="mt-1 block w-full rounded-sm shadow-sm focus:border-primary focus:ring-primary sm:text-sm @error('end_date') border-red-500 @enderror"
                        >
                        <p class="mt-1 text-xxs text-red-600" x-show="editEndDateError" x-text="editEndDateError" x-cloak></p>

                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <div class="pt-2 flex justify-end">
                        <button type="submit"
                            x-bind:disabled="!!editEndDateError"
                            x-bind:class="editEndDateError ? 'opacity-50 cursor-not-allowed' : ''"
                            class="w-fit rounded-sm bg-primary px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
  
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('billingCycleForm', () => ({
                // ----- Create cycle form -----
                startDate: '',
                cycleName: '',

                generateCycleName() {
                    if (!this.startDate) {
                        this.cycleName = '';
                        return;
                    }

                    const date = new Date(this.startDate);
                    const month = date.toLocaleString('default', { month: 'long' });
                    const year = date.getFullYear();

                    this.cycleName = `${month}-${year}`;
                },

                // ----- Edit cycle form -----
                selectedBillingCycle: null,
                editStatus: 'active',
                editEndDate: '',

                // Route with a placeholder id that we swap out client-side,
                // since the update route needs a specific cycle's id.
                actionTemplate: "{{ route('management.cycles.update', ['billingCycle' => 'BILLING_CYCLE_ID']) }}",

                // Converts a Carbon-serialized ISO string (e.g. "2026-07-29T22:00:00.000000Z")
                // back to the correct local "YYYY-MM-DD" for a <input type="date">.
                // IMPORTANT: uses local getters (getFullYear/getMonth/getDate), NOT
                // getUTCFullYear/etc — that's what undoes the UTC shift Carbon introduces
                // when it JSON-serializes a date-only value.
                toDateInputValue(isoString) {
                    if (!isoString) return '';
                    const d = new Date(isoString);
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                },

                selectCycle(cycle) {
                    this.selectedBillingCycle = cycle;
                    this.editStatus = cycle.status ?? 'active';
                    this.editEndDate = this.toDateInputValue(cycle.end_date);
                },

                get editFormAction() {
                    if (!this.selectedBillingCycle) {
                        return '#';
                    }
                    return this.actionTemplate.replace('BILLING_CYCLE_ID', this.selectedBillingCycle.id);
                },

                // ----- End date extension validation -----
                get currentEndDate() {
                    return this.selectedBillingCycle
                        ? this.toDateInputValue(this.selectedBillingCycle.end_date)
                        : '';
                },

                get editEndDateError() {
                    if (!this.editEndDate || !this.currentEndDate) {
                        return '';
                    }
                    // Extending must move the date forward, not backward or same
                    if (this.editEndDate < this.currentEndDate) {
                        return `Extension date must be after the current end date (${this.currentEndDate}).`;
                    }
                    return '';
                },

                submitEditForm() {
                    if (this.editEndDateError) {
                        return; // blocked, error shown inline
                    }
                    this.$refs.editForm.submit();
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>