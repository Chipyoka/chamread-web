<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">CSA Details</h1>
                <p class="text-sm text-gray-500">View CSA profile and assignments</p>
            </div>

            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.csas.edit', $csa) }}"
                   class="inline-flex items-center px-4 py-2 bg-yellow-50 text-yellow-700 text-sm font-medium rounded hover:bg-yellow-100 transition">
                    <i data-lucide="edit" class="w-4 h-4 mr-2"></i> Edit
                </a>

                <a href="{{ route('admin.csas.assign', $csa) }}"
                   class="inline-flex items-center px-4 py-2 bg-purple-50 text-purple-700 text-sm font-medium rounded hover:bg-purple-100 transition">
                    <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i> Assign
                </a>

                <form action="{{ route('admin.csas.destroy', $csa) }}" method="POST" onsubmit="return confirm('Delete this CSA?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-50 text-red-600 text-sm font-medium rounded hover:bg-red-100 transition">
                        <i data-lucide="trash" class="w-4 h-4 mr-2"></i> Delete
                    </button>
                </form>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="px-4 py-3 bg-green-50 text-green-700 text-sm rounded-md">
                {{ session('success') }}
            </div>
        @endif

        <!-- CSA Info Card -->
        <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h2 class="text-gray-600 text-sm font-medium">Name</h2>
                    <p class="text-gray-800 font-semibold">{{ $csa->name }}</p>
                </div>

                <div>
                    <h2 class="text-gray-600 text-sm font-medium">Username</h2>
                    <p class="text-gray-800 font-semibold">{{ $csa->username }}</p>
                </div>

                <div>
                    <h2 class="text-gray-600 text-sm font-medium">Email</h2>
                    <p class="text-gray-800 font-semibold">{{ $csa->email ?? '-' }}</p>
                </div>

                <div>
                    <h2 class="text-gray-600 text-sm font-medium">Zone</h2>
                    <p class="text-gray-800 font-semibold">{{ $csa->zone?->name ?? '-' }}</p>
                </div>

                <div>
                    <h2 class="text-gray-600 text-sm font-medium">Last Login</h2>
                    <p class="text-gray-800 font-semibold">
                        {{ $csa->last_login_at ? $csa->last_login_at->diffForHumans() : 'Never' }}
                    </p>
                </div>

                <div>
                    <h2 class="text-gray-600 text-sm font-medium">Status</h2>
                    @if($csa->last_login_at && $csa->last_login_at->gt(now()->subDays(7)))
                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">
                            Active
                        </span>
                    @else
                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">
                            Inactive
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- CSA Assignments -->
        <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-800">Assignments</h3>

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
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($assignments as $assignment)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $assignment->zone->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $assignment->dma?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $assignment->billingCycle->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $assignment->target ?? '0' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $assignment->assignment_type ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800 capitalize">{{ $assignment->status }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->assigned_at?->format('Y-m-d') ?? '-' }}</td>
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
</x-app-layout>