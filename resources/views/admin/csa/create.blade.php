<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-600">Add New CSA</h1>
                <p class="text-sm text-gray-500">Create a Customer Service Agent account</p>
            </div>

            <a href="{{ route('admin.csas.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                Back to List
            </a>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.csas.store') }}" method="POST" class="space-y-6 bg-white p-6 rounded-lg shadow-sm">
            @csrf

            <!-- Name -->
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Username -->
            <div>
                <x-input-label for="username" :value="__('Username')" />
                <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" value="{{ old('username') }}" required />
                <x-input-error :messages="$errors->get('username')" class="mt-2" />
            </div>

            <!-- Email -->
            <div>
                <x-input-label for="email" :value="__('Email (optional)')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email') }}" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Auto-generated Password -->
            <div>
                <x-input-label :value="__('Password (auto-generated)')" />
                <div class="flex items-center space-x-2 mt-1">
                    <input id="passwordDisplay" type="text" class="block w-full px-3 py-2 border rounded-md bg-gray-100 text-gray-700 cursor-not-allowed" readonly>
                    <button type="button" id="copyBtn" class="px-3 py-2 bg-primary text-white rounded hover:bg-primary/90 transition text-sm">
                        Copy
                    </button>
                </div>
                <p class="text-gray-500 text-sm mt-1">Default password format: <code>[username]1234</code></p>
            </div>

            <!-- Hidden password input -->
            <input type="hidden" name="password" id="passwordInput" value="">

            <!-- Zone -->
            <div>
                <x-input-label for="zone_id" :value="__('Zone (optional)')" />
                <select name="zone_id" id="zone_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-primary focus:ring-opacity-50">
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
                    Create CSA
                </button>
            </div>
        </form>
    </div>

    <!-- Scripts -->
    <script>
        const usernameInput = document.getElementById('username');
        const passwordDisplay = document.getElementById('passwordDisplay');
        const passwordInput = document.getElementById('passwordInput');
        const copyBtn = document.getElementById('copyBtn');

        // Generate password dynamically
        usernameInput.addEventListener('input', function() {
            const username = usernameInput.value.trim();
            const password = username ? username + '1234' : '';
            passwordDisplay.value = password;
            passwordInput.value = password;
        });

        // Copy to clipboard
        copyBtn.addEventListener('click', function() {
            if (!passwordDisplay.value) return;
            passwordDisplay.select();
            passwordDisplay.setSelectionRange(0, 99999); // for mobile devices
            document.execCommand('copy');

            // Feedback
            copyBtn.textContent = 'Copied!';
            setTimeout(() => { copyBtn.textContent = 'Copy'; }, 2000);
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            const event = new Event('input');
            usernameInput.dispatchEvent(event);
        });
    </script>

</x-app-layout>