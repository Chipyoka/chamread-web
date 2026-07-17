<div >

    <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

        <div class="overflow-x-auto rounded-sm">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Applies To</th>
                        <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">System</th>
                        <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="flagsTableBody" class="bg-white divide-y divide-gray-100">
                    @foreach($flags as $flag)
                    <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $flag->id }}" data-flag-id="{{ $flag->id }}">
                        <td class="px-4 py-3">
                            <input 
                                type="text" 
                                class="flag-name-input w-30 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs"
                                value="{{ $flag->name }}"
                                readonly
                            >
                        </td>
                        <td class="px-4 py-3 w-18">
                            <select class="flag-applies-to-select min-w-full px-2 py-1 border border-gray-200 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white" disabled>
                                <option value="account" {{ $flag->applies_to === 'account' ? 'selected' : '' }}>Accounts</option>
                                <option value="reading" {{ $flag->applies_to === 'reading' ? 'selected' : '' }}>Readings</option>
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $flag->is_system ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $flag->is_system ? 'System' : 'Custom' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="flag-status-toggle sr-only peer" {{ $flag->active ? 'checked' : '' }} {{ $flag->is_system ? 'disabled' : '' }}>
                                    <div class="w-9 h-5 bg-gray-300 rounded-full peer peer-checked:bg-green-600 transition-colors {{ $flag->is_system ? 'opacity-50' : '' }}"></div>
                                    <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-4"></div>
                                </label>
                                <span class="flag-status-label inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $flag->active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $flag->active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <button class="edit-flag inline-flex items-center gap-1 text-gray-500 hover:text-gray-900 transition-colors text-xs font-medium {{ $flag->is_system ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $flag->is_system ? 'disabled' : '' }}>
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    Edit
                                </button>
                                <button class="save-flag hidden inline-flex items-center gap-1 text-green-600 hover:text-green-800 transition-colors text-xs font-medium">
                                    <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
                                    Save
                                </button>
                                <button class="cancel-flag hidden inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 transition-colors text-xs font-medium">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    Cancel
                                </button>
                                <button class="delete-flag inline-flex items-center gap-1 text-red-600 hover:text-red-800 transition-colors text-xs font-medium {{ $flag->is_system ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $flag->is_system ? 'disabled' : '' }}>
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

       
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const flagsTableBody = document.getElementById('flagsTableBody');

            // Initialize Notyf
            const notyf = new Notyf({
                duration: 5000,
                position: {
                    x: 'right',
                    y: 'bottom',
                },
                dismissible: true,
                ripple: false,
                className: 'notyf-custom',
            });

            // Re-render any lucide icons added dynamically
            function refreshIcons() {
                if (window.lucide) {
                    lucide.createIcons();
                }
            }

            function getFlagRowData(row) {
                return {
                    id: row.dataset.flagId,
                    name: row.querySelector('.flag-name-input').value,
                    applies_to: row.querySelector('.flag-applies-to-select').value,
                    active: row.querySelector('.flag-status-toggle').checked ? 1 : 0,
                };
            }

            function setFlagStatusLabel(row, isActive) {
                const label = row.querySelector('.flag-status-label');
                label.textContent = isActive ? 'Active' : 'Inactive';
                label.className = `flag-status-label inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`;
            }

            // Show validation errors
            function showValidationErrors(errors) {
                const errorMessages = Object.values(errors).flat().join('\n');
                notyf.error(errorMessages);
            }

            // Toggle flag status
            flagsTableBody.addEventListener('change', function(e) {
                if (e.target.classList.contains('flag-status-toggle')) {
                    const row = e.target.closest('tr');
                    setFlagStatusLabel(row, e.target.checked);
                }
            });

            // Edit flag
            flagsTableBody.addEventListener('click', function(e) {
                const editBtn = e.target.closest('.edit-flag');
                if (editBtn) {
                    const row = e.target.closest('tr');
                    const inputs = row.querySelectorAll('.flag-name-input');
                    const selects = row.querySelectorAll('.flag-applies-to-select');

                    inputs.forEach(input => input.removeAttribute('readonly'));
                    selects.forEach(select => select.disabled = false);

                    row.querySelector('.edit-flag').classList.add('hidden');
                    row.querySelector('.save-flag').classList.remove('hidden');
                    row.querySelector('.cancel-flag').classList.remove('hidden');

                    // Store original values
                    row.dataset.originalName = row.querySelector('.flag-name-input').value;
                    row.dataset.originalAppliesTo = row.querySelector('.flag-applies-to-select').value;
                    row.dataset.originalStatus = row.querySelector('.flag-status-toggle').checked ? '1' : '0';

                    refreshIcons();
                }
            });

            // Cancel flag edit
            flagsTableBody.addEventListener('click', function(e) {
                if (e.target.closest('.cancel-flag')) {
                    const row = e.target.closest('tr');
                    const inputs = row.querySelectorAll('.flag-name-input');
                    const selects = row.querySelectorAll('.flag-applies-to-select');

                    row.querySelector('.flag-name-input').value = row.dataset.originalName;
                    row.querySelector('.flag-applies-to-select').value = row.dataset.originalAppliesTo;
                    const isActive = row.dataset.originalStatus === '1';
                    row.querySelector('.flag-status-toggle').checked = isActive;
                    setFlagStatusLabel(row, isActive);

                    inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                    selects.forEach(select => select.disabled = true);

                    row.querySelector('.edit-flag').classList.remove('hidden');
                    row.querySelector('.save-flag').classList.add('hidden');
                    row.querySelector('.cancel-flag').classList.add('hidden');

                    refreshIcons();
                }
            });

            // Save single flag
            flagsTableBody.addEventListener('click', async function(e) {
                if (e.target.closest('.save-flag')) {
                    const row = e.target.closest('tr');
                    const data = getFlagRowData(row);

                    if (!data.id) return;

                    try {
                        const response = await fetch(`/systems/flags/${data.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(data)
                        });

                        const result = await response.json();

                        if (!response.ok) {
                            if (response.status === 403) {
                                notyf.error(result.error || 'Insufficient permissions.');
                                return;
                            }

                            if (response.status === 422) {
                                showValidationErrors(result.errors);
                                return;
                            }

                            throw new Error(result.error || 'Update failed.');
                        }

                        const inputs = row.querySelectorAll('.flag-name-input');
                        const selects = row.querySelectorAll('.flag-applies-to-select');

                        inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                        selects.forEach(select => select.disabled = true);

                        row.querySelector('.edit-flag').classList.remove('hidden');
                        row.querySelector('.save-flag').classList.add('hidden');
                        row.querySelector('.cancel-flag').classList.add('hidden');

                        row.dataset.originalName = data.name;
                        row.dataset.originalAppliesTo = data.applies_to;
                        row.dataset.originalStatus = data.active;

                        notyf.success(result.message || 'Flag updated successfully!');
                        refreshIcons();

                    } catch (error) {
                        console.error(error);
                        notyf.error(error.message || 'Error updating flag');
                    }
                }
            });

            // Delete flag
            flagsTableBody.addEventListener('click', function(e) {
                if (e.target.closest('.delete-flag')) {
                    const row = e.target.closest('tr');
                    const id = row.dataset.flagId;

                    if (!id) {
                        row.remove();
                        notyf.success('Row removed');
                        return;
                    }

                    if (confirm('Are you sure you want to delete this flag?')) {
                        fetch(`/systems/flags/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => response.json())
                        .then(result => {
                            row.remove();
                            notyf.success('Flag deleted successfully!');
                        })
                        .catch(error => {
                            notyf.error('Error deleting flag');
                            console.error(error);
                        });
                    }
                }
            });

            // Add new flag
            document.getElementById('addNewFlag').addEventListener('click', function() {
                const tbody = document.getElementById('flagsTableBody');
                const newRow = document.createElement('tr');
                newRow.className = 'hover:bg-gray-50 transition-colors bg-gray-50';
                newRow.innerHTML = `
                    <td class="px-4 py-3">
                        <input type="text" class="flag-name-input w-48 px-2 py-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Name">
                    </td>
                    <td class="px-4 py-3 w-18">
                        <select class="flag-applies-to-select min-w-full px-2 py-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white">
                            <option value="account">Accounts</option>
                            <option value="reading">Readings</option>
                        </select>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Custom</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="flag-status-toggle sr-only peer" checked>
                                <div class="w-9 h-5 bg-gray-300 rounded-full peer peer-checked:bg-green-600 transition-colors"></div>
                                <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-4"></div>
                            </label>
                            <span class="flag-status-label inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <button class="save-new-flag inline-flex items-center gap-1 text-green-600 hover:text-green-800 transition-colors text-xs font-medium">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                Save
                            </button>
                            <button class="cancel-new-flag inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 transition-colors text-xs font-medium">
                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                Cancel
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(newRow);
                refreshIcons();
            });

            // Save new flag
            flagsTableBody.addEventListener('click', function(e) {
                if (e.target.closest('.save-new-flag')) {
                    const row = e.target.closest('tr');
                    const data = getFlagRowData(row);

                    fetch('/systems/flags', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.errors) {
                            showValidationErrors(result.errors);
                            return;
                        }

                        row.dataset.flagId = result.flag.id;
                        row.dataset.id = result.flag.id;
                        row.className = 'hover:bg-gray-50 transition-colors';

                        const inputs = row.querySelectorAll('.flag-name-input');
                        const selects = row.querySelectorAll('.flag-applies-to-select');

                        inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                        selects.forEach(select => select.disabled = true);

                        row.querySelector('.save-new-flag').outerHTML = `
                            <button class="edit-flag inline-flex items-center gap-1 text-gray-500 hover:text-gray-900 transition-colors text-xs font-medium">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                Edit
                            </button>`;
                        row.querySelector('.cancel-new-flag').outerHTML = `
                            <button class="delete-flag inline-flex items-center gap-1 text-red-600 hover:text-red-800 transition-colors text-xs font-medium">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                Delete
                            </button>`;

                        refreshIcons();
                        notyf.success('Flag created successfully!');
                    })
                    .catch(error => {
                        notyf.error('Error creating flag');
                        console.error(error);
                    });
                }
            });

            // Cancel new flag
            flagsTableBody.addEventListener('click', function(e) {
                if (e.target.closest('.cancel-new-flag')) {
                    const row = e.target.closest('tr');
                    row.remove();
                }
            });

            // Save all flags
            document.getElementById('saveAllFlags').addEventListener('click', function() {
                const rows = document.querySelectorAll('#flagsTableBody tr[data-id]');
                const updates = [];

                rows.forEach(row => {
                    const data = getFlagRowData(row);
                    updates.push({
                        id: data.id,
                        applies_to: data.applies_to,
                        active: data.active
                    });
                });

                if (updates.length === 0) {
                    notyf.error('No rows to update');
                    return;
                }

                fetch('/systems/flags/bulk-update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ updates })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.errors) {
                        showValidationErrors(result.errors);
                        return;
                    }
                    notyf.success('All changes saved successfully!');
                })
                .catch(error => {
                    notyf.error('Error saving changes');
                    console.error(error);
                });
            });

            // Initial icon render
            refreshIcons();
        });
    </script>
@endpush