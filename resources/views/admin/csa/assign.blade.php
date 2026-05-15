<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-600">Assign Zones/DMA to CSA</h1>
                <p class="text-sm text-gray-500">Manage assignments for {{ $csa->name }} ({{ $csa->username }})</p>
            </div>

            <a href="{{ route('admin.csas.show', $csa) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded hover:bg-gray-200 transition">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to Details
            </a>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="px-4 py-3 bg-green-50 text-green-700 text-sm rounded-md">
                {{ session('success') }}
            </div>
        @endif

        <!-- Assignment Form -->
        <form action="{{ route('admin.csas.assign.store', $csa) }}" method="POST" class="space-y-6 bg-white p-6 rounded-lg shadow-sm">
            @csrf

              <!-- Target -->
            <div>
                <x-input-label for="target" :value="__('Target')" />
                <x-text-input id="target" name="target" type="number" class="mt-1 block w-full" value="{{ old('name') }}" required autofocus />
                <x-input-error :messages="$errors->get('target')" class="mt-2" />
            </div>


            <!-- Zone -->
            <div>
                <x-input-label for="zone_id" :value="__('Zone')" />
                <select id="zone_id" name="zone_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50" required>
                    <option value="">-- Select Zone --</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('zone_id')" class="mt-2" />
            </div>

            <!-- DMA -->
            <div>
                <x-input-label for="dma_id" :value="__('DMA (optional)')" />
                <select id="dma_id" name="dma_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">-- Select DMA --</option>
                </select>
                <x-input-error :messages="$errors->get('dma_id')" class="mt-2" />
            </div>

            <!-- Billing Cycle -->
            <div>
                <x-input-label for="billing_cycle_id" :value="__('Billing Cycle')" />
                <select id="billing_cycle_id" name="billing_cycle_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50" required>
                    <option value="">-- Select Billing Cycle --</option>
                    @foreach($cycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->name }} </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('billing_cycle_id')" class="mt-2" />
            </div>

            <!-- Status -->
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="active">Active</option>
                    <option value="reassigned">Reassigned</option>
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition">
                    Assign
                </button>
            </div>
        </form>

        <!-- Current Assignments -->
        <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-600">Current Assignments</h3>

            @if($assignments->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Zone</th>
                            <th class="px-6 py-3">DMA</th>
                            <th class="px-6 py-3">Billing Cycle</th>
                            <th class="px-6 py-3">Target</th>
                            <th class="px-6 py-3">Type</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Assigned At</th>
                            <th class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($assignments as $assignment)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->zone->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->dma?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->billingCycle->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->target ?? '0' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->assignment_type ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 capitalize">{{ $assignment->status }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->assigned_at?->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    
                                </td>
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

    <!-- Scripts -->
    <script>
        const zoneSelect = document.getElementById('zone_id');
        const dmaSelect = document.getElementById('dma_id');

        // Fetch DMAs dynamically when zone changes
        zoneSelect.addEventListener('change', function() {
            const zoneId = this.value;
            dmaSelect.innerHTML = '<option value="">-- Select DMA --</option>'; // reset

            if (!zoneId) return;

            fetch(`/admin/zones/${zoneId}/dmas`)
                .then(res => res.json())
                .then(data => {
                    data.forEach(dma => {
                        const option = document.createElement('option');
                        option.value = dma.id;
                        option.textContent = dma.name;
                        dmaSelect.appendChild(option);
                    });
                })
                .catch(err => console.error('Failed to load DMAs:', err));
        });
    </script>
</x-app-layout>