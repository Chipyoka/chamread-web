<x-app-layout>
          <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Management'
                ],
                [
                    'label'=>'Zones'
                ]
            ]"/>
        </x-slot:breadcrumb>

    <div class="p-6 space-y-6">
          <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">Zones</h1>
                <p class="text-sm text-gray-500">Manage all zones</p>
            </div>

             <div class="flex items-center gap-3">
                <!-- Import Button -->
                <button 
                    id="importButton"
                    class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
                >
                     <i data-lucide="file-up" class="w-4 h-4 mr-2"></i>
                    Import Excel
                </button>
                
                <!-- Download Template Button -->
                <a 
                    href="{{ route('management.zones.download-template') }}"
                    class="inline-flex items-center px-3 py-2.5 bg-gray-200 text-gray-500 text-xs font-medium rounded-md hover:bg-primary/90 transition"
                >
                    <i data-lucide="file-down" class="w-4 h-4 mr-2"></i>
                    Download Template
                </a>
                
               
            </div>
        </div>

        <!-- Import Modal -->
        <div id="importModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border border-gray-100 rounded-lg shadow-lg w-[28rem] bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Import Zones</h3>
                    <button id="closeImportModal" class="text-gray-500 hover:text-gray-700">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <form id="importForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-700 mb-2">
                            Excel File 
                        </label>
                        <input 
                            type="file" 
                            name="file" 
                            id="fileInput"
                            accept=".xlsx,.xls,.csv"
                            class="w-full px-3 py-2 border border-gray-200 rounded-sm text-xs focus:outline-none focus:ring-1 focus:ring-gray-500"
                            required
                        >
                        <p class="my-2 text-xxs text-gray-500">.xlsx, .xls, .csv allowed | Maximum file size: 5MB</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button 
                            type="button" 
                            id="cancelImport"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-sm hover:bg-gray-300 transition-colors text-xs font-medium"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            id="submitImport"
                            class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-sm hover:bg-primary/90 transition"
                        >
                            Start Import
                        </button>
                    </div>
                </form>
                <div id="importProgress" class="hidden mt-4">
                    <div class="text-xs text-gray-500">Importing...</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div id="progressBar" class="bg-gray-700 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
                <div id="importResult" class="hidden mt-4"></div>
            </div>
        </div>
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto  rounded-sm">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Province</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="zonesTableBody" class="bg-white divide-y divide-gray-100">
                        @foreach($zones as $zone)
                        <tr class="hover:bg-gray-50 transition-colors" data-id="{{ $zone->id }}">
                            <td class="px-4 py-3">
                                <input 
                                    type="text" 
                                    class="code-input w-16 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs uppercase"
                                    value="{{ $zone->code }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="text" 
                                    class="district-input w-48 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs uppercase"
                                    value="{{ $zone->district }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="text" 
                                    class="province-input w-48 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs uppercase"
                                    value="{{ $zone->province }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if($zone->assignments_count == 0)
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input  type="checkbox" class="status-toggle sr-only peer" {{ $zone->status === 'active' ? 'checked' : '' }}>
                                        <div class="w-9 h-5 bg-gray-300 rounded-full peer peer-checked:bg-green-600 transition-colors"></div>
                                        <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-4"></div>
                                    </label>
                                    @endif
                                    <span class="status-label inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $zone->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ ucfirst($zone->status) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($zone->assignments_count == 0)
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
                                        @if(in_array(Auth::user()->role, ['ADMIN', 'IT']))
                                    

                                            <button class="delete-row inline-flex items-center gap-1 text-red-600 hover:text-red-800 transition-colors text-xs font-medium">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                Delete
                                            </button>
                                            
                                            @endif
                                            @else
                                                <span class="text-xs italic text-gray-500">Locked</span>
                                        @endif
                                    </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="my-2">
                <p class="text-xs text-amber-500">*Zones with active assignments are locked.</p>
            </div>

            <div class="mt-6 gap-4 flex justify-end">
                <button 
                        id="addNewRow"
                        class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
                    >
                        + Add New Zone
                    </button>

                <button 
                    id="saveAllChanges"
                    class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
                >
                    Save All Changes
                </button>
            </div>
        </div>
    </div>

 

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tableBody = document.getElementById('zonesTableBody');
                const importModal = document.getElementById('importModal');
                const importForm = document.getElementById('importForm');

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
                    const code = row.querySelector('.code-input').value;
                    const district = row.querySelector('.district-input').value;
                    const province = row.querySelector('.province-input').value;
                    const status = row.querySelector('.status-toggle').checked ? 'active' : 'inactive';
                    return { id, code, name, district, province, status };
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
                        const inputs = row.querySelectorAll('.code-input, .district-input, .province-input');

                        inputs.forEach(input => input.removeAttribute('readonly'));

                        row.querySelector('.edit-row').classList.add('hidden');
                        row.querySelector('.save-row').classList.remove('hidden');
                        row.querySelector('.cancel-row').classList.remove('hidden');

                        // Store original values for cancel
                        row.dataset.originalCode = row.querySelector('.code-input').value;
                        row.dataset.originalDistrict = row.querySelector('.district-input').value;
                        row.dataset.originalProvince = row.querySelector('.province-input').value;
                        row.dataset.originalStatus = row.querySelector('.status-toggle').checked ? 'active' : 'inactive';
                    }
                });

                // Cancel edit
                tableBody.addEventListener('click', function(e) {
                    if (e.target.closest('.cancel-row')) {
                        const row = e.target.closest('tr');
                        const inputs = row.querySelectorAll('.code-input, .district-input, .province-input');

                        row.querySelector('.code-input').value = row.dataset.originalCode;
                        row.querySelector('.district-input').value = row.dataset.originalDistrict;
                        row.querySelector('.province-input').value = row.dataset.originalProvince;
                        const isActive = row.dataset.originalStatus === 'active';
                        row.querySelector('.status-toggle').checked = isActive;
                        setStatusLabel(row, isActive);

                        inputs.forEach(input => input.setAttribute('readonly', 'readonly'));

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
                            fetch(`/management/zones/${data.id}`, {
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

                                const inputs = row.querySelectorAll('.code-input, .district-input, .province-input');

                                inputs.forEach(input => input.setAttribute('readonly', 'readonly'));

                                row.querySelector('.edit-row').classList.remove('hidden');
                                row.querySelector('.save-row').classList.add('hidden');
                                row.querySelector('.cancel-row').classList.add('hidden');

                                // Update stored data
                                row.dataset.originalCode = data.code;
                                row.dataset.originalName = data.name;
                                row.dataset.originalDistrict = data.district;
                                row.dataset.originalProvince = data.province;
                                row.dataset.originalStatus = data.status;

                                notyf.success('Zone updated successfully!');
                            })
                            .catch(error => {
                                notyf.error(error.message || 'Error updating zone');
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

                        if (confirm('Are you sure you want to delete this zone?')) {
                            fetch(`/management/zones/${id}`, {
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
                                notyf.success('Zone deleted successfully!');
                            })
                            .catch(error => {
                                notyf.error(error.message || 'Error deleting zone');
                                console.error(error);
                            });
                        }
                    }
                });

                // Add new row
                document.getElementById('addNewRow').addEventListener('click', function() {
                    const tbody = document.getElementById('zonesTableBody');
                    const newRow = document.createElement('tr');
                    newRow.className = 'hover:bg-gray-50 transition-colors bg-gray-50';
                    newRow.innerHTML = `
                        <td class="px-4 py-3">
                            <input type="text" class="code-input w-20 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Code">
                        </td>
                        <td class="px-4 py-3">
                            <input type="text" class="district-input w-32 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="District">
                        </td>
                        <td class="px-4 py-3">
                            <input type="text" class="province-input w-32 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Province">
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

                        fetch('/management/zones', {
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

                            row.dataset.id = result.zone.id;
                            row.className = 'hover:bg-gray-50 transition-colors';

                            const inputs = row.querySelectorAll('.code-input, .district-input, .province-input');

                            inputs.forEach(input => input.setAttribute('readonly', 'readonly'));

                            row.querySelector('.save-new-row').outerHTML = `
                                <button class="edit-row text-gray-500 hover:text-gray-900 transition-colors text-xs font-medium">Edit</button>`;
                            row.querySelector('.cancel-new-row').outerHTML = `
                                <button class="delete-row text-red-600 hover:text-red-800 transition-colors text-xs font-medium">Delete</button>`;

                            notyf.success('Zone created successfully!');
                        })
                        .catch(error => {
                            notyf.error(error.message || 'Error creating zone');
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
                    const rows = document.querySelectorAll('#zonesTableBody tr[data-id]');
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

                    fetch('/management/zones/bulk-update', {
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

                // Import Modal
                document.getElementById('importButton').addEventListener('click', function() {
                    importModal.classList.remove('hidden');
                    document.getElementById('importResult').classList.add('hidden');
                    document.getElementById('importProgress').classList.add('hidden');
                    document.getElementById('fileInput').value = '';
                });

                document.getElementById('closeImportModal').addEventListener('click', function() {
                    importModal.classList.add('hidden');
                });

                document.getElementById('cancelImport').addEventListener('click', function() {
                    importModal.classList.add('hidden');
                });

                // Close modal on outside click
                importModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        importModal.classList.add('hidden');
                    }
                });

                // Handle import form submission
                importForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const progressDiv = document.getElementById('importProgress');
                    const progressBar = document.getElementById('progressBar');
                    const resultDiv = document.getElementById('importResult');
                    
                    progressDiv.classList.remove('hidden');
                    progressBar.style.width = '50%';
                    resultDiv.classList.add('hidden');

                    fetch('/management/zones/import', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: formData
                    })
                    .then(response => {
                        progressBar.style.width = '100%';
                        return response.json();
                    })
                    .then(result => {
                        progressBar.style.width = '100%';
                        
                        if (result.errors) {
                            showValidationErrors(result.errors);
                            resultDiv.classList.remove('hidden');
                            resultDiv.innerHTML = `
                                <div class="text-red-600 text-xs">${Object.values(result.errors).flat().join('<br>')}</div>
                            `;
                            return;
                        }

                        if (result.error) {
                            notyf.error(result.error);
                            resultDiv.classList.remove('hidden');
                            resultDiv.innerHTML = `
                                <div class="text-red-600 text-xs">${result.error}</div>
                            `;
                            return;
                        }

                        resultDiv.classList.remove('hidden');
                        
                        let html = `<div class="text-xs">`;
                        if (result.success_count > 0) {
                            html += `<div class="text-green-600">✅ ${result.success_count} zones imported successfully</div>`;
                        }
                        if (result.error_count > 0) {
                            html += `<div class="text-red-600 mt-2">❌ ${result.error_count} errors</div>`;
                            if (result.errors) {
                                html += `<div class="text-gray-500 mt-1 text-xs">`;
                                result.errors.forEach(error => {
                                    html += `<div>• ${error}</div>`;
                                });
                                html += `</div>`;
                            }
                        }
                        html += `</div>`;
                        resultDiv.innerHTML = html;

                        if (result.success_count > 0) {
                            notyf.success(`Imported ${result.success_count} zones successfully!`);
                            // Reload page after successful import
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            notyf.error('Import completed with errors. Check the details below.');
                        }
                    })
                    .catch(error => {
                        progressBar.style.width = '100%';
                        notyf.error('Error importing file: ' + error.message);
                        resultDiv.classList.remove('hidden');
                        resultDiv.innerHTML = `
                            <div class="text-red-600 text-xs">Error: ${error.message}</div>
                        `;
                        console.error(error);
                    });
                });
            });
        </script>
    @endpush

</x-app-layout>