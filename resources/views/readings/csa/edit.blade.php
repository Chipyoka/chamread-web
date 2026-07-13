<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-600">Edit CSA</h1>
                <p class="text-sm text-gray-500">Update Customer Service Agent details</p>
            </div>

            <a href="{{ route('readings.csas.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                Back to List
            </a>
        </div>

        <!-- Form -->
        <form action="{{ route('readings.csas.update', $csa) }}" method="POST" class="space-y-6 bg-white p-6 rounded-lg shadow-sm">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $csa->name) }}" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Username -->
            <div>
                <x-input-label for="username" :value="__('Username')" />
                <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" value="{{ old('username', $csa->username) }}" required />
                <x-input-error :messages="$errors->get('username')" class="mt-2" />
            </div>

            <!-- Email -->
            <div>
                <x-input-label for="email" :value="__('Email (optional)')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $csa->email) }}" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Optional Password Reset -->
            <div>
                <x-input-label for="password" :value="__('Reset Password (optional)')" />
                <x-text-input id="password" name="password" type="text" class="mt-1 block w-full" placeholder="Leave blank to keep current password" />
                <p class="text-gray-500 text-sm mt-1">
                    Default password format if resetting: <code>[username]1234</code>. Leave blank to retain existing password.
                </p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- status -->
            <div>
                <x-input-label for="status" :value="__('Status')" />
                <select required name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="ACTIVE" {{ old('status', $csa->status) == 'ACTIVE' ? 'selected' : '' }}>Active</option>
                    <option value="SUSPENDED" {{ old('status', $csa->status) == 'SUSPENDED' ? 'selected' : '' }}>Suspended</option>
                    <option value="INACTIVE" {{ old('status', $csa->status) == 'INACTIVE' ? 'selected' : '' }}>Inactive</option>
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

   
            <!-- Zone -->
            <div>
                <x-input-label for="zone_id" :value="__('Zone (optional)')" />
                <select required name="zone_id" id="zone_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50">
                    <option value="">-- Select Zone --</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}" {{ old('zone_id', $csa->zone_id ?? '') == $zone->id ? 'selected' : '' }}>
                            {{ $zone->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('zone_id')" class="mt-2" />
            </div>

            <!-- Submit -->
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition">
                    Update CSA
                </button>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script>
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');

        // Optional: auto-suggest password if admin types something in username field
        usernameInput.addEventListener('input', function() {
            if(!passwordInput.value) {
                passwordInput.placeholder = usernameInput.value ? usernameInput.value + '1234' : 'Leave blank to keep password';
            }
        });
    </script>
</x-app-layout>