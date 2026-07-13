<x-app-layout>
    <div x-data="billingCycleForm()" class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-600">Billing Cycles</h1>
                <p class="text-sm text-gray-500">Manage Customer Accounts</p>
            </div>

            <!-- allow only admins -->
            @if(Auth::user()->role === 'ADMIN')
                <button
                    type="button"
                    x-on:click="$dispatch('open-modal', 'confirm-happy')"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition"
                >
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                    Add Cycle
                </button>
            @endif
        </div>

        <!-- 
        ==================================================================
        MODAL REGISTRATIONS
        -->

        <x-modal name="confirm-happy" max-width="md" :closable="false">
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
                            disabled
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
  
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('billingCycleForm', () => ({
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
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>