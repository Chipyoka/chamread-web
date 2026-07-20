<x-app-layout>
    <div class="p-6 space-y-6">

          <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'System'
                ],
                [
                    'label'=>'Meter Reading Codes'
                ]
            ]"/>
        </x-slot:breadcrumb>

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">Meter Reading Codes</h1>
                <p class="text-sm text-gray-500">Configure and functionally define ERP codes</p>
            </div>
        </div>

        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            <div class="overflow-x-auto rounded-sm">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="codesTableBody" class="bg-white divide-y divide-gray-100">
                        @foreach($codes as $code)
                        <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $code->id }}">
                            <td class="px-4 py-3">
                                <input
                                    type="text"
                                    class="code-input w-16 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs"
                                    value="{{ $code->code }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3">
                                <input
                                    type="text"
                                    class="name-input w-48 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs"
                                    value="{{ $code->name }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3">
                                <input
                                    type="text"
                                    class="description-input w-64 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs"
                                    value="{{ $code->description }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3">
                                <select class="type-select px-2 py-1 border border-gray-200 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white" disabled>
                                    <option value="reading" {{ $code->type === 'reading' ? 'selected' : '' }}>Reading</option>
                                    <option value="explanation" {{ $code->type === 'explanation' ? 'selected' : '' }}>Explanation</option>
                                    <option value="billing" {{ $code->type === 'billing' ? 'selected' : '' }}>Billing</option>
                                    <option value="other" {{ $code->type === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="status-toggle sr-only peer" {{ $code->status === 'active' ? 'checked' : '' }}>
                                        <div class="w-9 h-5 bg-gray-300 rounded-full peer peer-checked:bg-green-600 transition-colors"></div>
                                        <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-4"></div>
                                    </label>
                                    <span class="status-label inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $code->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ ucfirst($code->status) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <button class="edit-row inline-flex items-center gap-1 text-gray-500 hover:text-gray-900 transition-colors text-xs font-medium">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        Edit
                                    </button>
                                    <button class="save-row hidden inline-flex items-center gap-1 text-green-600 hover:text-green-800 transition-colors text-xs font-medium">
                                        <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
                                        Save
                                    </button>
                                    <button class="cancel-row hidden inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 transition-colors text-xs font-medium">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                        Cancel
                                    </button>

                                    <!-- Check if user is admin -->
                                    @if(Auth::user()->role === 'ADMIN')
                                        <button class="delete-row inline-flex items-center gap-1 text-red-600 hover:text-red-800 transition-colors text-xs font-medium">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 gap-4 flex justify-end">
                <button
                    id="addNewRow"
                    class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
                >
                    <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                    New Code
                </button>
                <button
                    id="saveAllChanges"
                    class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
                >
                    <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                    Save All Changes
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tableBody = document.getElementById('codesTableBody');

                // Initialize Notyf with your existing configuration
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

                // Helper to get row data
                function getRowData(row) {
                    const id = row.dataset.id;
                    const code = row.querySelector('.code-input').value;
                    const name = row.querySelector('.name-input').value;
                    const description = row.querySelector('.description-input').value;
                    const type = row.querySelector('.type-select').value;
                    const status = row.querySelector('.status-toggle').checked ? 'active' : 'inactive';
                    return { id, code, name, description, type, status };
                }

                function setStatusLabel(row, isActive) {
                    const statusLabel = row.querySelector('.status-label');
                    statusLabel.textContent = isActive ? 'Active' : 'Inactive';
                    statusLabel.className = `status-label inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`;
                }

                // Show validation errors
                function showValidationErrors(errors) {
                    const errorMessages = Object.values(errors).flat().join('\n');
                    notyf.error(errorMessages);
                }

                // Toggle status
                tableBody.addEventListener('change', function(e) {
                    if (e.target.classList.contains('status-toggle')) {
                        const row = e.target.closest('tr');
                        setStatusLabel(row, e.target.checked);
                    }
                });

                // Edit row
                tableBody.addEventListener('click', function(e) {
                    if (e.target.closest('.edit-row')) {
                        const row = e.target.closest('tr');
                        const inputs = row.querySelectorAll('.code-input, .name-input, .description-input');
                        const select = row.querySelector('.type-select');

                        inputs.forEach(input => input.removeAttribute('readonly'));
                        select.disabled = false;

                        row.querySelector('.edit-row').classList.add('hidden');
                        row.querySelector('.save-row').classList.remove('hidden');
                        row.querySelector('.cancel-row').classList.remove('hidden');

                        // Store original values for cancel
                        row.dataset.originalCode = row.querySelector('.code-input').value;
                        row.dataset.originalName = row.querySelector('.name-input').value;
                        row.dataset.originalDescription = row.querySelector('.description-input').value;
                        row.dataset.originalType = select.value;
                        row.dataset.originalStatus = row.querySelector('.status-toggle').checked ? 'active' : 'inactive';
                    }
                });

                // Cancel edit
                tableBody.addEventListener('click', function(e) {
                    if (e.target.closest('.cancel-row')) {
                        const row = e.target.closest('tr');
                        const inputs = row.querySelectorAll('.code-input, .name-input, .description-input');
                        const select = row.querySelector('.type-select');

                        row.querySelector('.code-input').value = row.dataset.originalCode;
                        row.querySelector('.name-input').value = row.dataset.originalName;
                        row.querySelector('.description-input').value = row.dataset.originalDescription;
                        select.value = row.dataset.originalType;
                        const isActive = row.dataset.originalStatus === 'active';
                        row.querySelector('.status-toggle').checked = isActive;
                        setStatusLabel(row, isActive);

                        inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                        select.disabled = true;

                        row.querySelector('.edit-row').classList.remove('hidden');
                        row.querySelector('.save-row').classList.add('hidden');
                        row.querySelector('.cancel-row').classList.add('hidden');
                    }
                });

                // Save single row
                tableBody.addEventListener('click', async function(e) {
                    if (e.target.closest('.save-row')) {
                        const row = e.target.closest('tr');
                        const data = getRowData(row);

                        if (!data.id) return;

                        try {
                            const response = await fetch(`/systems/mrc/${data.id}`, {
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

                            const inputs = row.querySelectorAll('.code-input, .name-input, .description-input');
                            const select = row.querySelector('.type-select');

                            inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                            select.disabled = true;

                            row.querySelector('.edit-row').classList.remove('hidden');
                            row.querySelector('.save-row').classList.add('hidden');
                            row.querySelector('.cancel-row').classList.add('hidden');

                            row.dataset.originalCode = data.code;
                            row.dataset.originalName = data.name;
                            row.dataset.originalDescription = data.description;
                            row.dataset.originalType = data.type;
                            row.dataset.originalStatus = data.status;

                            notyf.success(result.message || 'Code updated successfully!');

                        } catch (error) {
                            console.error(error);
                            notyf.error(error.message || 'Error updating code');
                        }
                    }
                });

                // Delete row
                tableBody.addEventListener('click', function(e) {
                    if (e.target.closest('.delete-row')) {
                        const row = e.target.closest('tr');
                        const id = row.dataset.id;

                        if (!id) {
                            row.remove();
                            notyf.success('Row removed');
                            return;
                        }

                        if (confirm('Are you sure you want to delete this code?')) {
                            fetch(`/systems/mrc/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            })
                            .then(response => response.json())
                            .then(result => {
                                row.remove();
                                notyf.success('Code deleted successfully!');
                            })
                            .catch(error => {
                                notyf.error('Error deleting code');
                                console.error(error);
                            });
                        }
                    }
                });

                // Add new row
                document.getElementById('addNewRow').addEventListener('click', function() {
                    const tbody = document.getElementById('codesTableBody');
                    const newRow = document.createElement('tr');
                    newRow.className = 'hover:bg-gray-50 transition-colors bg-gray-50';
                    newRow.innerHTML = `
                        <td class="px-4 py-3">
                            <input type="text" class="code-input w-16 px-2 py-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Code">
                        </td>
                        <td class="px-4 py-3">
                            <input type="text" class="name-input w-48 px-2 py-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Name">
                        </td>
                        <td class="px-4 py-3">
                            <input type="text" class="description-input w-64 px-2 py-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Description">
                        </td>
                        <td class="px-4 py-3">
                            <select class="type-select px-2 py-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white">
                                <option value="reading">Reading</option>
                                <option value="explanation">Explanation</option>
                                <option value="billing">Billing</option>
                                <option value="other">Other</option>
                            </select>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="status-toggle sr-only peer" checked>
                                    <div class="w-9 h-5 bg-gray-300 rounded-full peer peer-checked:bg-green-600 transition-colors"></div>
                                    <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-4"></div>
                                </label>
                                <span class="status-label inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <button class="save-new-row inline-flex items-center gap-1 text-green-600 hover:text-green-800 transition-colors text-xs font-medium">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    Save
                                </button>
                                <button class="cancel-new-row inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 transition-colors text-xs font-medium">
                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    Cancel
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(newRow);
                    refreshIcons();
                });

                // Save new row
                tableBody.addEventListener('click', function(e) {
                    if (e.target.closest('.save-new-row')) {
                        const row = e.target.closest('tr');
                        const data = getRowData(row);

                        fetch('/systems/mrc/', {
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

                            row.dataset.id = result.code.id;
                            row.className = 'hover:bg-gray-50 transition-colors';

                            const inputs = row.querySelectorAll('.code-input, .name-input, .description-input');
                            const select = row.querySelector('.type-select');

                            inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                            select.disabled = true;

                            row.querySelector('.save-new-row').outerHTML = `
                                <button class="edit-row inline-flex items-center gap-1 text-gray-500 hover:text-gray-900 transition-colors text-xs font-medium">
                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    Edit
                                </button>`;
                            row.querySelector('.cancel-new-row').outerHTML = `
                                <button class="delete-row inline-flex items-center gap-1 text-red-600 hover:text-red-800 transition-colors text-xs font-medium">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    Delete
                                </button>`;

                            refreshIcons();
                            notyf.success('Code created successfully!');
                        })
                        .catch(error => {
                            notyf.error('Error creating code');
                            console.error(error);
                        });
                    }
                });

                // Cancel new row
                tableBody.addEventListener('click', function(e) {
                    if (e.target.closest('.cancel-new-row')) {
                        const row = e.target.closest('tr');
                        row.remove();
                        notyf.success('Row cancelled');
                    }
                });

                // Save all changes
                document.getElementById('saveAllChanges').addEventListener('click', function() {
                    const rows = document.querySelectorAll('#codesTableBody tr[data-id]');
                    const updates = [];

                    rows.forEach(row => {
                        const data = getRowData(row);
                        updates.push({
                            id: data.id,
                            type: data.type,
                            status: data.status
                        });
                    });

                    if (updates.length === 0) {
                        notyf.error('No rows to update');
                        return;
                    }

                    fetch('/systems/mrc/bulk-update', {
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

</x-app-layout>