<x-app-layout>
    <div class="p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">Devices</h1>
                <p class="text-sm text-gray-500">Manage all devices for assignment to meter readers</p>
            </div>
        </div>

        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">
            <div class="bg-white rounded-md p-4 space-y-4 overflow-x-auto  thin-scrollbar ">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Model</th>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Serial #</th>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs  text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="devicesTableBody" class="bg-white divide-y divide-gray-100">
                        @foreach($devices as $device)
                        <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $device->id }}">
                            <td class="px-4 py-3">
                                <input 
                                    type="text" 
                                    class="name-input w-40 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs"
                                    value="{{ $device->name }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3 w-24">
                                <select class="type-select w-20 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white" disabled>
                                    <option value="phone" {{ $device->type === 'phone' ? 'selected' : '' }}>Phone</option>
                                    <option value="tablet" {{ $device->type === 'tablet' ? 'selected' : '' }}>Tablet</option>
                                    <option value="laptop" {{ $device->type === 'laptop' ? 'selected' : '' }}>Laptop</option>
                                    <option value="desktop" {{ $device->type === 'desktop' ? 'selected' : '' }}>Desktop</option>
                                    <option value="other" {{ $device->type === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="text" 
                                    class="model-input w-32 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs"
                                    value="{{ $device->model }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="text" 
                                    class="serial-input w-32 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs"
                                    value="{{ $device->serial_number }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <select class="status-select px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white" disabled>
                                        <option value="active" {{ $device->status === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $device->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        <option value="lost" {{ $device->status === 'lost' ? 'selected' : '' }}>Lost</option>
                                        <option value="damaged" {{ $device->status === 'damaged' ? 'selected' : '' }}>Damaged</option>
                                        <option value="returned" {{ $device->status === 'returned' ? 'selected' : '' }}>Returned</option>
                                    </select>
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
                        New Device
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
            const tableBody = document.getElementById('devicesTableBody');

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

            // Helper to get row data
            function getRowData(row) {
                const id = row.dataset.id;
                const name = row.querySelector('.name-input').value;
                const type = row.querySelector('.type-select').value;
                const model = row.querySelector('.model-input').value;
                const serial_number = row.querySelector('.serial-input').value;
                const status = row.querySelector('.status-select').value;
                return { id, name, type, model, serial_number, status };
            }

            // Show validation errors
            function showValidationErrors(errors) {
                const errorMessages = Object.values(errors).flat().join('\n');
                notyf.error(errorMessages);
            }

            // Edit row
            tableBody.addEventListener('click', function(e) {
                if (e.target.closest('.edit-row')) {
                    const row = e.target.closest('tr');
                    const inputs = row.querySelectorAll('.name-input, .model-input, .serial-input');
                    const selects = row.querySelectorAll('.type-select, .status-select');

                    inputs.forEach(input => input.removeAttribute('readonly'));
                    selects.forEach(select => select.disabled = false);

                    row.querySelector('.edit-row').classList.add('hidden');
                    row.querySelector('.save-row').classList.remove('hidden');
                    row.querySelector('.cancel-row').classList.remove('hidden');

                    // Store original values for cancel
                    row.dataset.originalName = row.querySelector('.name-input').value;
                    row.dataset.originalType = row.querySelector('.type-select').value;
                    row.dataset.originalModel = row.querySelector('.model-input').value;
                    row.dataset.originalSerial = row.querySelector('.serial-input').value;
                    row.dataset.originalStatus = row.querySelector('.status-select').value;
                }
            });

            // Cancel edit
            tableBody.addEventListener('click', function(e) {
                if (e.target.closest('.cancel-row')) {
                    const row = e.target.closest('tr');
                    const inputs = row.querySelectorAll('.name-input, .model-input, .serial-input');
                    const selects = row.querySelectorAll('.type-select, .status-select');

                    row.querySelector('.name-input').value = row.dataset.originalName;
                    row.querySelector('.type-select').value = row.dataset.originalType;
                    row.querySelector('.model-input').value = row.dataset.originalModel;
                    row.querySelector('.serial-input').value = row.dataset.originalSerial;
                    row.querySelector('.status-select').value = row.dataset.originalStatus;

                    inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                    selects.forEach(select => select.disabled = true);

                    row.querySelector('.edit-row').classList.remove('hidden');
                    row.querySelector('.save-row').classList.add('hidden');
                    row.querySelector('.cancel-row').classList.add('hidden');
                }
            });

            // Save single row
            tableBody.addEventListener('click', function(e) {
                if (e.target.closest('.save-row')) {
                    const row = e.target.closest('tr');
                    const data = getRowData(row);

                    if (data.id) {
                        fetch(`/systems/devices/${data.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(data)
                        })
                        .then(response => {
                            if (response.status === 403) {
                                return response.json().then(data => {
                                    throw new Error(data.error || 'Insufficient permissions');
                                });
                            }
                            return response.json();
                        })
                        .then(result => {
                            if (result.errors) {
                                showValidationErrors(result.errors);
                                return;
                            }

                            const inputs = row.querySelectorAll('.name-input, .model-input, .serial-input');
                            const selects = row.querySelectorAll('.type-select, .status-select');

                            inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                            selects.forEach(select => select.disabled = true);

                            row.querySelector('.edit-row').classList.remove('hidden');
                            row.querySelector('.save-row').classList.add('hidden');
                            row.querySelector('.cancel-row').classList.add('hidden');

                            // Update stored data
                            row.dataset.originalName = data.name;
                            row.dataset.originalType = data.type;
                            row.dataset.originalModel = data.model;
                            row.dataset.originalSerial = data.serial_number;
                            row.dataset.originalStatus = data.status;

                            notyf.success('Device updated successfully!');
                        })
                        .catch(error => {
                            notyf.error(error.message || 'Error updating device');
                            console.error(error);
                        });
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

                    if (confirm('Are you sure you want to delete this device?')) {
                        fetch(`/systems/devices/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(response => {
                            if (response.status === 403) {
                                return response.json().then(data => {
                                    throw new Error(data.error || 'Insufficient permissions');
                                });
                            }
                            return response.json();
                        })
                        .then(result => {
                            row.remove();
                            notyf.success('Device deleted successfully!');
                        })
                        .catch(error => {
                            notyf.error(error.message || 'Error deleting device');
                            console.error(error);
                        });
                    }
                }
            });

            // Add new row
            document.getElementById('addNewRow').addEventListener('click', function() {
                const tbody = document.getElementById('devicesTableBody');
                const newRow = document.createElement('tr');
                newRow.className = 'hover:bg-gray-50 transition-colors bg-gray-50';
                newRow.innerHTML = `
                    <td class="px-4 py-3">
                        <input type="text" class="name-input w-40 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Name">
                    </td>
                    <td class="px-4 py-3">
                        <select class="type-select px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white">
                            <option value="phone">Phone</option>
                            <option value="tablet">Tablet</option>
                            <option value="laptop">Laptop</option>
                            <option value="desktop">Desktop</option>
                            <option value="other">Other</option>
                        </select>
                    </td>
                    <td class="px-4 py-3">
                        <input type="text" class="model-input w-32 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Model">
                    </td>
                    <td class="px-4 py-3">
                        <input type="text" class="serial-input w-32 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Serial #">
                    </td>
                    <td class="px-4 py-3">
                        <select class="status-select px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="lost">Lost</option>
                            <option value="damaged">Damaged</option>
                            <option value="returned">Returned</option>
                        </select>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <button class="save-new-row text-green-600 hover:text-green-800 transition-colors text-xs font-medium">Save</button>
                            <button class="cancel-new-row text-gray-500 hover:text-gray-700 transition-colors text-xs font-medium">Cancel</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(newRow);
            });

            // Save new row
            tableBody.addEventListener('click', function(e) {
                if (e.target.closest('.save-new-row')) {
                    const row = e.target.closest('tr');
                    const data = getRowData(row);

                    fetch('/systems/devices', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        if (response.status === 403) {
                            return response.json().then(data => {
                                throw new Error(data.error || 'Insufficient permissions');
                            });
                        }
                        return response.json();
                    })
                    .then(result => {
                        if (result.errors) {
                            showValidationErrors(result.errors);
                            return;
                        }

                        row.dataset.id = result.device.id;
                        row.className = 'hover:bg-gray-50 transition-colors';

                        const inputs = row.querySelectorAll('.name-input, .model-input, .serial-input');
                        const selects = row.querySelectorAll('.type-select, .status-select');

                        inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                        selects.forEach(select => select.disabled = true);

                        row.querySelector('.save-new-row').outerHTML = `
                            <button class="edit-row text-gray-500 hover:text-gray-900 transition-colors text-xs font-medium">Edit</button>`;
                        row.querySelector('.cancel-new-row').outerHTML = `
                            <button class="delete-row text-red-600 hover:text-red-800 transition-colors text-xs font-medium">Delete</button>`;

                        notyf.success('Device created successfully!');
                    })
                    .catch(error => {
                        notyf.error(error.message || 'Error creating device');
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
                const rows = document.querySelectorAll('#devicesTableBody tr[data-id]');
                const updates = [];

                rows.forEach(row => {
                    const data = getRowData(row);
                    updates.push({
                        id: data.id,
                        status: data.status
                    });
                });

                if (updates.length === 0) {
                    notyf.error('No rows to update');
                    return;
                }

                fetch('/systems/devices/bulk-update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ updates })
                })
                .then(response => {
                    if (response.status === 403) {
                        return response.json().then(data => {
                            throw new Error(data.error || 'Insufficient permissions');
                        });
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.errors) {
                        showValidationErrors(result.errors);
                        return;
                    }
                    notyf.success('All changes saved successfully!');
                })
                .catch(error => {
                    notyf.error(error.message || 'Error saving changes');
                    console.error(error);
                });
            });
        });
    </script>
    @endpush

</x-app-layout>