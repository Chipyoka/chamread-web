<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-600">CSA Details</h1>
                <p class="text-sm text-gray-500">View CSA profile and assignments</p>
            </div>

            <div class="flex items-center space-x-2">
                <div class="flex mr-4 px-2 space-x-2">

                    <x-micro-button
                       variant="edit"
                       href="{{ route('admin.csas.edit', $csa) }}"
                       icon="edit"
                       size="md"
                   >
                       Edit CSA
                   </x-micro-button>
    
                    <x-micro-button
                       color="purple"
                       href="{{ route('admin.csas.assign', $csa) }}"
                       icon="map-pin"
                       size="md"
                   >
                       Assign
                   </x-micro-button>
    
                   <form action="{{ route('admin.csas.destroy', $csa) }}" class="delete-form" method="POST" onsubmit="return confirm('Delete this CSA?')">
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
                </div>
           

                 <!-- back to list -->
                <x-micro-button
                    variant="edit"
                    href="{{ route('admin.csas.index') }}"
                    icon="arrow-left"
                    size="md"
                >
                    Back to list
                </x-micro-button>
            </div>
        </div>

        <!-- CSA Info Card -->
        <div class="bg-white rounded-md p-6 space-y-4 border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Name</h2>
                    <p class="text-gray-600 font-semibold">{{ $csa->name }}</p>
                </div>

                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Username</h2>
                    <p class="text-gray-600 font-semibold">{{ $csa->username }}</p>
                </div>

                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Email</h2>
                    <p class="text-gray-600 font-semibold">{{ $csa->email ?? '-' }}</p>
                </div>

                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Zone</h2>
                    <p class="text-gray-600 font-semibold">{{ $csa->zone?->name ?? '-' }}</p>
                </div>

                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Last Login</h2>
                    <p class="text-gray-600 font-semibold">
                        {{ $csa->last_login_at ? $csa->last_login_at->diffForHumans() : 'Never' }}
                    </p>
                </div>

                <div  class="px-2 py-1.5 bg-gray-50 rounded-sm">
                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Status</h2>
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
        <div class="bg-white border border-gray-200 rounded-md p-6 space-y-4">
            <h3 class="text-gray-400 text-xs uppercase my-2">Assignments</h3>

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
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->zone->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->dma?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->billingCycle->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->target ?? '0' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $assignment->assignment_type ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 capitalize">{{ $assignment->status }}</td>
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