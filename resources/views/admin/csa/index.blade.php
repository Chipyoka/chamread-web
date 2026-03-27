<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-600">CSA Management</h1>
                <p class="text-sm text-gray-500">Manage Customer Service Agents</p>
            </div>

            <a href="{{ route('admin.csas.create') }}"
               class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                Add CSA
            </a>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="px-4 py-3 bg-green-50 text-green-700 text-sm rounded-md">
                {{ session('success') }}
            </div>
        @endif

        <!-- Table -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">

            @if($csas->count() > 0)

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Username</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Last Login</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">

                        @foreach($csas as $csa)
                            <tr class="hover:bg-gray-50 transition">

                                <!-- Name -->
                                <td class="px-6 py-4 text-sm text-gray-800 font-medium">
                                    {{ $csa->name }}
                                </td>

                                <!-- Username -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $csa->username }}
                                </td>

                                <!-- Email -->
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $csa->email }}
                                </td>

                                <!-- Last Login -->
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $csa->last_login_at 
                                        ? $csa->last_login_at->diffForHumans() 
                                        : 'Never' }}
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 uppercase">
                                    @if($csa->status === 'ACTIVE')
                                    
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">
                                            Active
                                        </span>
                                    @elseif ($csa->status === 'SUSPENDED')
                                        <span class="px-2 py-1 text-xs font-medium bg-amber-50 text-amber-600 rounded">
                                            suspended
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right text-sm space-x-2">

                                    <!-- View -->
                                    <a href="{{ route('admin.csas.show', $csa) }}"
                                       class="inline-flex items-center px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded hover:bg-blue-100">
                                        View
                                    </a>

                                    <!-- Edit -->
                                    <a href="{{ route('admin.csas.edit', $csa) }}"
                                       class="inline-flex items-center px-2 py-1 text-xs bg-yellow-50 text-yellow-700 rounded hover:bg-yellow-100">
                                        Edit
                                    </a>

                                    <!-- Assign -->
                                    <a href="{{ route('admin.csas.assign', $csa) }}"
                                       class="inline-flex items-center px-2 py-1 text-xs bg-purple-50 text-purple-700 rounded hover:bg-purple-100">
                                        Assign
                                    </a>

                                    <!-- Delete -->
                                    <form action="{{ route('admin.csas.destroy', $csa) }}"
                                          method="POST"
                                          class="inline-block"
                                          onsubmit="return confirm('Delete this CSA?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="px-2 py-1 text-xs bg-red-50 text-red-600 rounded hover:bg-red-100">
                                            Delete
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="p-4">
                    {{ $csas->links() }}
                </div>

            @else

                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="users" class="w-10 h-10 text-gray-300"></i>
                        <p class="text-gray-500 text-sm">No CSAs found.</p>

                        <a href="{{ route('admin.csas.create') }}"
                           class="text-primary text-sm hover:underline">
                            Create your first CSA
                        </a>
                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>