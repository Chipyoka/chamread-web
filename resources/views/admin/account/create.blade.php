<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">Add New Customer Account</h1>
                <p class="text-sm text-gray-500">Create a Customer account</p>
            </div>

            <a href="{{ route('admin.accounts.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                Back to List
            </a>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.accounts.store') }}" method="POST" class="space-y-6 bg-white p-6 rounded-lg shadow-sm">
            @csrf

            <!-- Account Number -->
            <div>
                <x-input-label for="account_number" :value="__('Account Number')" />
                <x-text-input id="account_number" name="account_number" type="text" class="mt-1 block w-full" value="{{ old('account_number') }}" required />
                <x-input-error :messages="$errors->get('account_number')" class="mt-2" />
            </div>

            <!-- Meter Number -->
            <div>
                <x-input-label for="meter_number" :value="__('Meter Number')" />
                <x-text-input id="meter_number" name="meter_number" type="text" class="mt-1 block w-full" value="{{ old('meter_number') }}" required />
                <x-input-error :messages="$errors->get('meter_number')" class="mt-2" />
            </div>

            <!-- Name -->
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Phone -->
            <div>
                <x-input-label for="phone" :value="__('Phone')" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" value="{{ old('phone') }}" required />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>

            <!-- Address -->
            <div>
                <x-input-label for="address" :value="__('Address')" />
                <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" value="{{ old('address') }}" required />
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>

            <!-- Billing Area -->
            <div>
                <x-input-label for="billing_area" :value="__('Billing Area')" />
                <x-text-input id="billing_area" name="billing_area" type="text" class="mt-1 block w-full" value="{{ old('billing_area') }}" required />
                <x-input-error :messages="$errors->get('billing_area')" class="mt-2" />
            </div>

            <!-- dma -->
            <div>
                <x-input-label for="dma_id" :value="__('DMA (optional)')" />
                <select name="dma_id" id="dma_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">-- Select DMA --</option>
                    @foreach($dmas as $dma)
                        <option value="{{ $dma->id }}" {{ old('dma_id') == $dma->id ? 'selected' : '' }}>
                            {{ $dma->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('dma_id')" class="mt-2" />
            </div>

            <!-- Zone -->
            <div>
                <x-input-label for="zone_id" :value="__('Zone (optional)')" />
                <select name="zone_id" id="zone_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">-- Select Zone --</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>
                            {{ $zone->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('zone_id')" class="mt-2" />
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition">
                    Save Account
                </button>
            </div>
        </form>
    </div>

</x-app-layout>