<x-app-layout>
    <div x-data="userManagementForm()" class="p-6 space-y-6">

        <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Systems'
                ],
                [
                    'label'=>'Users'
                ]
            ]"/>
        </x-slot:breadcrumb>

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">User Management</h1>
                <p class="text-sm text-gray-500">Manage system users and their permissions</p>
            </div>

            <!-- allow only admins and IT -->
            @if(in_array(Auth::user()->role, ['ADMIN', 'IT']))
                <button
                    type="button"
                    x-on:click="$dispatch('open-modal', 'create-user')"
                    class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition"
                >
                    <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i>
                    Add User
                </button>
            @endif
        </div>

        <!-- Filters & Search -->
        <div class="bg-white rounded-md p-4 border border-gray-200">
            <form method="GET" action="{{ route('systems.users.index') }}" class="flex flex-wrap items-center justify-between gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            id="search" 
                            value="{{ request('search') }}"
                            placeholder="Search by name, email, or username..."
                            class="pl-9 w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                        >
                    </div>
                </div>

                <!-- Role Filter -->
                <div class="w-48">
                    <select 
                        name="role" 
                        id="role" 
                        class="w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                    >
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>
                                {{ ucfirst(strtolower($role)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-48">
                    <select 
                        name="status" 
                        id="status" 
                        class="w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                    >
                        <option value="">All Statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                {{ ucfirst(strtolower($status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-sm hover:bg-primary/90 transition">
                        <i data-lucide="filter" class="w-4 h-4 mr-2"></i>
                        Apply Filters
                    </button>
                    
                    @if(request()->hasAny(['search', 'role', 'status']))
                        <a href="{{ route('systems.users.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-sm hover:bg-gray-200 transition">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            @if($users->count() > 0)

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xxs font-semibold text-gray-500 uppercase tracking-wide">
                                <th class="px-6 py-3">User</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Last Login</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">

                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50 transition">
                                    <!-- User Info -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                                @if($user->photo_url)
                                                    <img src="{{ $user->photo_url }}" alt="{{ $user->name }}" class="h-8 w-8 rounded-full object-cover">
                                                @else
                                                    <span class="text-xs font-medium text-gray-600">
                                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                                <p class="text-xxs text-gray-500">Joined {{ $user->created_at->format('M d, Y') }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Email -->
                                    <td class="px-6 py-4 ">
                                        <a href="mailto:{{ $user->email }}" class="text-xs text-primary hover:underline">
                                            {{ $user->email }}
                                        </a>
                                        @if($user->email_verified_at)
                                            <span class="ml-1 inline-flex items-center text-green-600">
                                                <i data-lucide="badge-check" class="w-3 h-3"></i>
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Role -->
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $user->role === 'ADMIN' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $user->role === 'IT' ? 'bg-purple-100 text-purple-800' : '' }}
                                            {{ $user->role === 'SUPERVISOR' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $user->role === 'MD' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                            {{ $user->role === 'FINANCE' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $user->role === 'COMMERCIAL' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $user->role === 'HR' ? 'bg-pink-100 text-pink-800' : '' }}
                                            {{ $user->role === 'TECHNICAL' ? 'bg-orange-100 text-orange-800' : '' }}
                                            {{ !in_array($user->role, ['ADMIN','IT','SUPERVISOR','MD','FINANCE','COMMERCIAL','HR','TECHNICAL']) ? 'bg-gray-100 text-gray-800' : '' }}
                                        ">
                                            {{ ucfirst(strtolower($user->role)) }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $user->status === 'ACTIVE' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $user->status === 'SUSPENDED' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $user->status === 'INACTIVE' ? 'bg-red-100 text-red-800' : '' }}
                                        ">
                                            {{ ucfirst(strtolower($user->status)) }}
                                        </span>
                                    </td>

                                    <!-- Last Login -->
                                    <td class="px-6 py-4">
                                        @if($user->last_login_at)
                                            <span class="text-xs text-gray-500">
                                                {{ $user->last_login_at->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">Never</span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 text-right text-xs">
                                        <div class="flex items-center justify-end space-x-2">
                                            <!-- View -->
                                            <button type="button" 
                                                x-on:click="$dispatch('open-modal', 'view-user'); selectUserForView(@js(array_merge($user->toArray(), [
                                                    'created_at' => $user->created_at->format('M d, Y h:i A'),
                                                    'updated_at' => $user->updated_at->format('M d, Y h:i A'),
                                                    'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->format('M d, Y h:i A') : null,
                                                    'last_login_at' => $user->last_login_at ? $user->last_login_at->format('M d, Y h:i A') : null,
                                                    'device_name' => $user->device ? $user->device->name : null,
                                                ])))"
                                                class="inline-flex items-center px-2.5 py-1.5 rounded-sm text-xs font-medium transition
                                                bg-blue-50 text-blue-600 hover:bg-blue-100">
                                                <i data-lucide="eye" class="w-3.5 h-3.5 mr-1"></i>
                                                View
                                            </button>

                                            <!-- Edit -->
                                            @if(in_array(Auth::user()->role, ['ADMIN', 'IT']))
                                                <button type="button" 
                                                    x-on:click="$dispatch('open-modal', 'edit-user'); selectUserForEdit(@js(array_merge($user->toArray(), [
                                                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                                                        'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
                                                    ])))"
                                                    class="inline-flex items-center px-2.5 py-1.5 rounded-sm text-xs font-medium transition
                                                    bg-gray-200/60 text-gray-700 hover:bg-gray-200">
                                                    <i data-lucide="edit" class="w-3.5 h-3.5 mr-1"></i>
                                                    Edit
                                                </button>

                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 py-2">
                    {{ $users->links() }}
                </div>

            @else

                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="users" class="w-10 h-10 text-gray-300"></i>
                        <p class="text-gray-500 text-sm">No users found.</p>
                        @if(request()->hasAny(['search', 'role', 'status']))
                            <p class="text-xs text-gray-400">Try adjusting your filters or clear them.</p>
                        @elseif(in_array(Auth::user()->role, ['ADMIN', 'IT']))
                            <p class="text-xs text-gray-400">Click "Add User" to create your first user.</p>
                        @endif
                    </div>
                </div>

            @endif

        </div>

        <!-- 
        ==================================================================
        MODALS
        -->

        <!-- Create User Modal -->
        <x-modal name="create-user" max-width="xl" :closable="false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">Add New User</h2>
                <p class="text-sm text-gray-500 mt-1">Create a new system user account</p>
                
                <form method="POST" action="{{ route('systems.users.store') }}" class="mt-6 space-y-4">
                    @csrf

                    <!-- Name -->
                    <div>
                        <label for="create_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="create_name" 
                            value="{{ old('name') }}"
                            required
                            class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm @error('name') border-red-500 @enderror"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Username & Email (2 columns) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="create_username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input 
                                type="text" 
                                name="username" 
                                id="create_username" 
                                value="{{ old('username') }}"
                                required
                                class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm @error('username') border-red-500 @enderror"
                            >
                            @error('username')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="create_email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input 
                                type="email" 
                                name="email" 
                                id="create_email" 
                                value="{{ old('email') }}"
                                required
                                class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm @error('email') border-red-500 @enderror"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Role & Status (2 columns) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="create_role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select 
                                name="role" 
                                id="create_role" 
                                required
                                class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm @error('role') border-red-500 @enderror"
                            >
                                <option value="">Select Role</option>
                                <option value="ADMIN" {{ old('role') == 'ADMIN' ? 'selected' : '' }}>Admin</option>
                                <option value="IT" {{ old('role') == 'IT' ? 'selected' : '' }}>IT</option>
                                <option value="SUPERVISOR" {{ old('role') == 'SUPERVISOR' ? 'selected' : '' }}>Supervisor</option>
                                <option value="MD" {{ old('role') == 'MD' ? 'selected' : '' }}>Managing Director</option>
                                <option value="FINANCE" {{ old('role') == 'FINANCE' ? 'selected' : '' }}>Finance</option>
                                <option value="COMMERCIAL" {{ old('role') == 'COMMERCIAL' ? 'selected' : '' }}>Commercial</option>
                                <option value="HR" {{ old('role') == 'HR' ? 'selected' : '' }}>Human Resources</option>
                                <option value="TECHNICAL" {{ old('role') == 'TECHNICAL' ? 'selected' : '' }}>Technical</option>
                                <option value="OTHER" {{ old('role') == 'OTHER' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="create_status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select 
                                name="status" 
                                id="create_status" 
                                required
                                class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm @error('status') border-red-500 @enderror"
                            >
                                <option value="ACTIVE" {{ old('status') == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                                <option value="SUSPENDED" {{ old('status') == 'SUSPENDED' ? 'selected' : '' }}>Suspended</option>
                                <option value="INACTIVE" {{ old('status') == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="create_password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input 
                                type="password" 
                                name="password" 
                                id="create_password" 
                                required
                                class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm @error('password') border-red-500 @enderror"
                            >
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="create_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input 
                                type="password" 
                                name="password_confirmation" 
                                id="create_password_confirmation" 
                                required
                                class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                            >
                        </div>
                    </div>

                    <!-- Device -->
                    <div>
                        <label for="create_device_id" class="block text-sm font-medium text-gray-700">Assign Device (Optional)</label>
                        <select 
                            name="device_id" 
                            id="create_device_id" 
                            class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm @error('device_id') border-red-500 @enderror"
                        >
                            <option value="">No Device Assigned</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}" {{ old('device_id') == $device->id ? 'selected' : '' }}>
                                    {{ $device->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('device_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <div class="pt-2 flex justify-end space-x-3">
                        <button type="button" 
                            x-on:click="$dispatch('close-modal', 'create-user')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-sm hover:bg-gray-200 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2 bg-primary text-white text-sm font-medium rounded-sm systems.users hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            <i data-lucide="user-plus" class="w-4 h-4 inline mr-2"></i>
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>

        <!-- View User Modal -->
        <x-modal name="view-user" max-width="2xl" :closable="false">
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">User Details</h2>
                </div>

                <template x-if="viewUser">
                    <div class="mt-6 space-y-6">
                        <!-- User Profile Header -->
                        <div class="flex items-center space-x-4 pb-6 border-b border-gray-200">
                            <div class="flex-shrink-0 h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                <template x-if="viewUser.photo_url">
                                    <img :src="viewUser.photo_url" :alt="viewUser.name" class="h-16 w-16 rounded-full object-cover">
                                </template>
                                <template x-if="!viewUser.photo_url">
                                    <span class="text-lg font-medium text-gray-600" x-text="viewUser.name ? viewUser.name.substring(0, 2).toUpperCase() : 'U'"></span>
                                </template>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900" x-text="viewUser.name"></h3>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="text-xs text-gray-500" x-text="'@' + viewUser.username"></span>
                                    <span class="text-gray-300">|</span>
                                    <span class="text-xs text-gray-500" x-text="viewUser.role"></span>
                                    <span class="text-gray-300">|</span>
                                    <span class="text-xs text-gray-500" x-text="viewUser.created_at"></span>
                                    
                                </div>
                            </div>
                        </div>

                        <!-- User Information Grid -->
                        <div class="grid grid-cols-2 gap-6 mt-2">
                                <!-- email -->
                                <div class="bg-gray-50 px-4 py-2 border border-gray-100">
                                    <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Email Address</label>
                                    <div class="mt-1 flex items-center space-x-2">
                                        <span class="text-sm text-gray-900" x-text="viewUser.email"></span>
                                    </div>
                                    <p class="text-xxs text-gray-400 mt-1" x-text="viewUser.email_verified_at ? 'Verified on ' + viewUser.email_verified_at : 'Not verified'"></p>
                                </div>

                                <!-- role -->
                                <div class="bg-gray-50 px-4 py-2 border border-gray-100">
                                    <label class="block text-xxs font-medium text-gray-400 tracking-wide uppercase">Role</label>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-red-100 text-red-800': viewUser.role === 'ADMIN',
                                                'bg-purple-100 text-purple-800': viewUser.role === 'IT',
                                                'bg-blue-100 text-blue-800': viewUser.role === 'SUPERVISOR',
                                                'bg-indigo-100 text-indigo-800': viewUser.role === 'MD',
                                                'bg-green-100 text-green-800': viewUser.role === 'FINANCE',
                                                'bg-yellow-100 text-yellow-800': viewUser.role === 'COMMERCIAL',
                                                'bg-pink-100 text-pink-800': viewUser.role === 'HR',
                                                'bg-orange-100 text-orange-800': viewUser.role === 'TECHNICAL',
                                                'bg-gray-100 text-gray-800': !['ADMIN','IT','SUPERVISOR','MD','FINANCE','COMMERCIAL','HR','TECHNICAL'].includes(viewUser.role)
                                            }"
                                            x-text="viewUser.role"
                                        ></span>
                                    </div>
                                </div>

                                <!-- device -->
                                <div class="bg-gray-50 px-4 py-2 border border-gray-100">
                                    <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Device Assignment</label>
                                    <div class="mt-1">
                                        <template x-if="viewUser.device_name">
                                            <span class="text-sm text-gray-900 flex items-center space-x-2">
                                                <i data-lucide="smartphone" class="w-4 h-4 text-gray-400"></i>
                                                <span x-text="viewUser.device_name"></span>
                                            </span>
                                        </template>
                                        <template x-if="!viewUser.device_name">
                                            <span class="text-sm text-gray-400">No device assigned</span>
                                        </template>
                                    </div>
                                </div>

                                <!-- account status -->
                                <div class="bg-gray-50 px-4 py-2 border border-gray-100"">
                                    <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Account Status</label>
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-green-100 text-green-800': viewUser.status === 'ACTIVE',
                                                'bg-yellow-100 text-yellow-800': viewUser.status === 'SUSPENDED',
                                                'bg-red-100 text-red-800': viewUser.status === 'INACTIVE'
                                            }"
                                            x-text="viewUser.status"
                                        ></span>
                                    </div>
                                </div>

                                <!-- last login -->
                                <div class="bg-gray-50 px-4 py-2 border border-gray-100">
                                    <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Last Login</label>
                                    <div class="mt-1">
                                        <template x-if="viewUser.last_login_at">
                                            <span class="text-sm text-gray-900 flex items-center space-x-2">
                                                <span x-text="viewUser.last_login_at"></span>
                                            </span>
                                        </template>
                                        <template x-if="!viewUser.last_login_at">
                                            <span class="text-sm text-gray-400">Never logged in</span>
                                        </template>
                                    </div>
                                </div>

                                <!-- last updated -->
                                <div class="bg-gray-50 px-4 py-2 border border-gray-100">
                                    <label class="block text-xxs font-medium text-gray-400 uppercase tracking-wide">Last Updated</label>
                                    <div class="mt-1">
                                        <span class="text-sm text-gray-900 flex items-center space-x-2">
                                            <span x-text="viewUser.updated_at"></span>
                                        </span>
                                    </div>
                                </div>
                        </div>


                        <!-- Action Buttons -->
                        <div  x-show="viewUser.id != {{ auth()->id() }}" class="pt-4 border-t border-gray-100 flex justify-between items-center">
                            <div class="flex items-center space-x-3 gap-4">
                                <!-- Toggle Status Button -->
                                @if(in_array(Auth::user()->role, ['ADMIN', 'IT']))
                                    <template x-if="viewUser.status !== 'INACTIVE'">
                                        <form method="POST" 
                                            :action="toggleStatusAction" 
                                            class="inline-block"
                                            x-on:submit.prevent="confirmToggleStatus($event, viewUser.name, viewUser.status)"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                class="inline-flex items-center px-3 py-2 rounded-sm text-sm font-medium transition"
                                                :class="viewUser.status === 'ACTIVE' 
                                                    ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' 
                                                    : 'bg-green-100 text-green-700 hover:bg-green-200'">
                                                <span x-text="viewUser.status === 'ACTIVE' ? 'Suspend User' : 'Activate User'"></span>
                                            </button>
                                        </form>
                                    </template>
                                    <template x-if="viewUser.status === 'INACTIVE'">
                                        <span class="text-xs text-gray-400 flex items-center space-x-1">
                                            <i data-lucide="info" class="w-4 h-4"></i>
                                            <span>Cannot toggle inactive users</span>
                                        </span>
                                    </template>
                                @endif

                                <!-- Edit Button -->
                                @if(in_array(Auth::user()->role, ['ADMIN', 'IT']))
                                    <button type="button" 
                                        x-on:click="$dispatch('close-modal', 'view-user'); $dispatch('open-modal', 'edit-user'); selectUserForEdit(viewUser)"
                                        class="inline-flex items-center px-3 py-2 rounded-sm text-sm font-medium transition
                                        bg-gray-200/80 text-gray-700 hover:bg-gray-200">
                                        Edit User
                                    </button>
                                @endif
                            </div>

                            <!-- Delete Button -->
                            @if(in_array(Auth::user()->role, ['ADMIN', 'IT']))
                                <button type="button"
                                    x-on:click="$dispatch('close-modal', 'view-user'); $dispatch('open-modal', 'delete-user'); selectUserForDelete(viewUser)"
                                    class="inline-flex items-center px-3 py-2 rounded-sm text-sm font-medium transition
                                    bg-red-50 text-red-600 hover:bg-red-100">
                                    Delete User
                                </button>
                            @endif
                        </div>
                    </div>
                </template>

            </div>
        </x-modal>

        <!-- Edit User Modal -->
        <x-modal name="edit-user" max-width="2xl" :closable="false">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">Edit User <span class="font-medium" x-text="editUser ? editUser.name : ''"></h2>
                
                <form method="POST" x-bind:action="editFormAction" x-ref="editForm"
                      x-on:submit.prevent="submitEditForm()" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Name -->
                    <div>
                        <label for="edit_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="edit_name" 
                            x-model="editName"
                            required
                            class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                        >
                    </div>

                    <!-- Username & Email (2 columns) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input 
                                type="text" 
                                name="username" 
                                id="edit_username" 
                                x-model="editUsername"
                                required
                                class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                            >
                        </div>

                        <div>
                            <label for="edit_email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input 
                                type="email" 
                                name="email" 
                                id="edit_email" 
                                x-model="editEmail"
                                required
                                class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                            >
                        </div>
                    </div>

                    <!-- Role & Status (2 columns) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select 
                                name="role" 
                                id="edit_role" 
                                x-model="editRole"
                                required
                                class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                            >
                                <option value="ADMIN">Admin</option>
                                <option value="IT">IT</option>
                                <option value="SUPERVISOR">Supervisor</option>
                                <option value="MD">Managing Director</option>
                                <option value="FINANCE">Finance</option>
                                <option value="COMMERCIAL">Commercial</option>
                                <option value="HR">Human Resources</option>
                                <option value="TECHNICAL">Technical</option>
                                <option value="CSA">CSA</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit_status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select 
                                name="status" 
                                id="edit_status" 
                                x-model="editStatus"
                                required
                                class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                            >
                                <option value="ACTIVE">Active</option>
                                <option value="SUSPENDED">Suspended</option>
                                <option value="INACTIVE">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Password (Optional) -->
                    <div class="border-t border-gray-100 pt-4 mt-2">
                        <div class="flex items-center space-x-2 mb-3">
                            <i data-lucide="lock" class="w-3 h-3 text-gray-500"></i>
                            <p class="text-xs text-gray-500">Change Password (Leave blank to keep current)</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="edit_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="edit_password" 
                                    x-model="editPassword"
                                    class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                                >
                            </div>

                            <div>
                                <label for="edit_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                <input 
                                    type="password" 
                                    name="password_confirmation" 
                                    id="edit_password_confirmation" 
                                    x-model="editPasswordConfirmation"
                                    class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Device -->
                    <div>
                        <label for="edit_device_id" class="block text-sm font-medium text-gray-700">Assign Device (Optional)</label>
                        <select 
                            name="device_id" 
                            id="edit_device_id" 
                            x-model="editDeviceId"
                            class="mt-1 block w-full rounded-sm border-gray-200 systems.users focus:border-primary focus:ring-primary sm:text-sm"
                        >
                            <option value="">No Device Assigned</option>
                            @foreach($devices as $device)
                                <option value="{{ $device->id }}">
                                    {{ $device->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Submit -->
                    <div class="pt-2 flex justify-end space-x-3">
                        <button type="button" 
                            x-on:click="$dispatch('close-modal', 'edit-user')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-sm hover:bg-gray-200 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-6 py-2 bg-primary text-white text-sm font-medium rounded-sm systems.users hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                            <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>

        <!-- Delete User Modal -->
        <x-modal name="delete-user" max-width="md" :closable="false">
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="h-5 w-5 text-red-600"></i>
                        </div>
                    </div>
                    <div >
                        <h2 class="text-lg font-semibold text-gray-900">Delete User</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Are you sure you want to delete <span class="font-medium text-gray-700" x-text="deleteUserName"></span>?
                            This action cannot be undone.
                        </p>
                        
                        <form method="POST" x-bind:action="deleteFormAction" class="mt-6">
                            @csrf
                            @method('DELETE')
                            
                            <div class="flex justify-end space-x-3">
                                <button type="button" 
                                    x-on:click="$dispatch('close-modal', 'delete-user')"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-sm hover:bg-gray-200 transition">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-6 py-2 bg-red-600 text-white text-sm font-medium rounded-sm systems.users hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    <i data-lucide="trash-2" class="w-4 h-4 inline mr-2"></i>
                                    Delete User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </x-modal>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('userManagementForm', () => ({
                    // ----- View user state -----
                    viewUser: null,

                    // ----- Edit form state -----
                    editUser: null,
                    editName: '',
                    editUsername: '',
                    editEmail: '',
                    editRole: 'CSA',
                    editStatus: 'ACTIVE',
                    editDeviceId: '',
                    editPassword: '',
                    editPasswordConfirmation: '',

                    // Route templates
                    editActionTemplate: "{{ route('systems.users.update', ['user' => 'USER_ID']) }}",
                    toggleStatusTemplate: "{{ route('systems.users.toggle-status', ['user' => 'USER_ID']) }}",
                    deleteActionTemplate: "{{ route('systems.users.destroy', ['user' => 'USER_ID']) }}",

                    // ----- Delete form state -----
                    deleteUser: null,
                    deleteUserName: '',

                    // ----- Select user for viewing -----
                    selectUserForView(user) {
                        this.viewUser = user;
                        // Re-initialize Lucide icons after view update
                        this.$nextTick(() => {
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        });
                    },

                    // ----- Select user for editing -----
                    selectUserForEdit(user) {
                        this.editUser = user;
                        this.editName = user.name || '';
                        this.editUsername = user.username || '';
                        this.editEmail = user.email || '';
                        this.editRole = user.role || 'CSA';
                        this.editStatus = user.status || 'ACTIVE';
                        this.editDeviceId = user.device_id || '';
                        this.editPassword = '';
                        this.editPasswordConfirmation = '';
                    },

                    // ----- Edit form action -----
                    get editFormAction() {
                        if (!this.editUser) {
                            return '#';
                        }
                        return this.editActionTemplate.replace('USER_ID', this.editUser.id);
                    },

                    // ----- Toggle status action -----
                    get toggleStatusAction() {
                        if (!this.viewUser) {
                            return '#';
                        }
                        return this.toggleStatusTemplate.replace('USER_ID', this.viewUser.id);
                    },

                    // ----- Submit edit form -----
                    submitEditForm() {
                        // If password fields are filled, ensure they match
                        if (this.editPassword || this.editPasswordConfirmation) {
                            if (this.editPassword !== this.editPasswordConfirmation) {
                                alert('Passwords do not match.');
                                return;
                            }
                            if (this.editPassword.length < 8) {
                                alert('Password must be at least 8 characters.');
                                return;
                            }
                        }
                        this.$refs.editForm.submit();
                    },

                    // ----- Select user for deletion -----
                    selectUserForDelete(user) {
                        this.deleteUser = user;
                        this.deleteUserName = user.name || '';
                    },

                    // ----- Delete form action -----
                    get deleteFormAction() {
                        if (!this.deleteUser) {
                            return '#';
                        }
                        return this.deleteActionTemplate.replace('USER_ID', this.deleteUser.id);
                    },

                    // ----- Confirm status toggle -----
                    confirmToggleStatus(event, userName, currentStatus) {
                        const action = currentStatus === 'ACTIVE' ? 'suspend' : 'activate';
                        if (confirm(`Are you sure you want to ${action} "${userName}"?`)) {
                            event.target.closest('form').submit();
                        } else {
                            event.preventDefault();
                        }
                    }
                }));
            });
        </script>
    @endpush
</x-app-layout>