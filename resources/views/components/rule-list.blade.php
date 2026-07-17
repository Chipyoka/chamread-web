<div class="space-y-4">



    <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

        <div class="overflow-x-auto rounded-sm">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Flag</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Column</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Operator</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Value</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="rulesTableBody" class="bg-white divide-y divide-gray-100">
                    @foreach($flags as $flag)
                        @foreach($flag->rules as $rule)
                        <tr class="hover:bg-gray-50 transition-colors" data-rule-id="{{ $rule->id }}" data-flag-id="{{ $flag->id }}">
                            <td class="px-4 py-3">
                                <span class="text-xs font-medium text-gray-700">{{ $flag->name }} ({{ $flag->applies_to }})</span>
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="text" 
                                    class="rule-field-input w-36 px-2 py-1 border border-gray-200 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs"
                                    value="{{ $rule->field }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3">
                                <select class="rule-operator-select px-2 py-1 border border-gray-200 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white" disabled>
                                    <option value=">" {{ $rule->operator === '>' ? 'selected' : '' }}>Greater Than</option>
                                    <option value="<" {{ $rule->operator === '<' ? 'selected' : '' }}>Less Than</option>
                                    <option value="=" {{ $rule->operator === '=' ? 'selected' : '' }}>Equal to</option>
                                    <option value="!=" {{ $rule->operator === '!=' ? 'selected' : '' }}>Not Equal to</option>
                                    <option value=">=" {{ $rule->operator === '>=' ? 'selected' : '' }}>Greater or Equal to</option>
                                    <option value="<=" {{ $rule->operator === '<=' ? 'selected' : '' }}>Less or Equal to</option>
                                    <option value="contains" {{ $rule->operator === 'contains' ? 'selected' : '' }}>Contains</option>
                                    <option value="is_null" {{ $rule->operator === 'is_null' ? 'selected' : '' }}>Is Empty</option>
                                    <option value="is_not_null" {{ $rule->operator === 'is_not_null' ? 'selected' : '' }}>Is Not Empty</option>
                                </select>
                            </td>
                            <td class="px-4 py-3">
                                <input 
                                    type="text" 
                                    class="rule-value-input w-24 px-2 py-1 border border-gray-100 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs"
                                    value="{{ $rule->value }}"
                                    readonly
                                >
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="rule-status-toggle sr-only peer" {{ $rule->active ? 'checked' : '' }}>
                                        <div class="w-9 h-5 bg-gray-300 rounded-full peer peer-checked:bg-green-600 transition-colors"></div>
                                        <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-4"></div>
                                    </label>
                                    <span class="rule-status-label inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $rule->active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $rule->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-6">
                                    <button class="edit-rule inline-flex items-center gap-1 text-gray-600 hover:text-gray-900 transition-colors text-xs font-medium">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        Edit
                                    </button>
                                    <button class="save-rule hidden inline-flex items-center gap-1 text-green-600 hover:text-green-800 transition-colors text-xs font-medium">
                                        <i data-lucide="circle-check" class="w-3.5 h-3.5"></i>
                                        Save
                                    </button>
                                    <button class="cancel-rule hidden inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 transition-colors text-xs font-medium">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                        Cancel
                                    </button>
                                    <button class="delete-rule inline-flex items-center gap-1 text-red-600 hover:text-red-800 transition-colors text-xs font-medium">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 gap-4 flex justify-end">
            <button
                id="addNewRule"
                class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-xs font-medium rounded-md hover:bg-primary/90 transition"
            >
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                New Rule
            </button>
        </div>
    </div>
    <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hiddenw-sm">
        <p class="text-gray-400 text-xs uppercase my-2">How to fill in each column</p>
        
        <div class="space-y-3">
            <!-- Flag -->
            <div class="bg-white rounded-md p-3 border border-gray-200">
                <div class="flex items-start gap-6">
                    <span class="font-mono text-sm font-bold text-gray-600 bg-gray-50 px-2 py-0.5 rounded min-w-[70px]">Flag</span>
                    <div>
                        <p class="text-sm text-gray-700">Choose which <strong>flag</strong> this rule belongs to.</p>
                        <p class="text-xs text-gray-400 mt-0.5">Pick from the list of available flags</p>
                    </div>
                </div>
            </div>
    
            <!-- Column -->
            <div class="bg-white rounded-md p-3 border border-gray-200">
                <div class="flex items-start gap-6">
                    <span class="font-mono text-sm font-bold text-gray-600 bg-gray-50 px-2 py-0.5 rounded min-w-[70px]">Column</span>
                    <div>
                        <p class="text-sm text-gray-700">Which <strong>field</strong> are you checking? (e.g. <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-xs">consumption</span>, <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-xs">status</span>, <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-xs">priority</span>)</p>
                        <p class="text-xs text-gray-400 mt-0.5">This is the data you want to evaluate</p>
                    </div>
                </div>
            </div>
    
            <!-- Operator -->
            <div class="bg-white rounded-md p-3 border border-gray-200">
                <div class="flex items-start gap-6">
                    <span class="font-mono text-sm font-bold text-gray-600 bg-gray-50 px-2 py-0.5 rounded min-w-[70px]">Operator</span>
                    <div>
                        <p class="text-sm text-gray-700">How do you want to <strong>compare</strong> the value?</p>
                        <p class="text-xs text-gray-500 mt-0.5">Options: 
                            <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">Greater Than</span> 
                            <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">Less Than</span> 
                            <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">Equal To</span> 
                            <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">Not Equal To</span> 
                            <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">Greater or Equal To</span> 
                            <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">Less or Equal To</span> 
                            <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">contains</span> 
                            <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">Is Empty</span> 
                            <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">Is Not Empty</span>
                        </p>
                    </div>
                </div>
            </div>
    
            <!-- Value -->
            <div class="bg-white rounded-md p-3 border border-gray-200">
                <div class="flex items-start gap-6">
                    <span class="font-mono text-sm font-bold text-gray-600 bg-gray-50 px-2 py-0.5 rounded min-w-[70px]">Value</span>
                    <div>
                        <p class="text-sm text-gray-700">What <strong>number or text</strong> are you comparing against?</p>
                        <p class="text-xs text-gray-400 mt-0.5">Example: <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">100</span>, <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">"active"</span>, or leave blank for <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">is_null</span> / <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded">is_not_null</span></p>
                    </div>
                </div>
            </div>
        </div>
    
        <!-- Example -->
        <div class="mt-4 bg-gray-50 border border-gray-200 rounded-md p-3">
            <p class="text-sm text-gray-700"><span class="font-semibold">Example:</span> If you want to flag records where <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-blue-200">consumption is greater than 100</span></p>
            <div class="flex flex-wrap gap-6 mt-1.5 text-xs">
                <span class="bg-white px-2 py-1 rounded border border-gray-200"><span class="font-semibold">Column:</span> consumption</span>
                <span class="bg-white px-2 py-1 rounded border border-gray-200"><span class="font-semibold">Operator:</span> Greater Than</span>
                <span class="bg-white px-2 py-1 rounded border border-gray-200"><span class="font-semibold">Value:</span> 100</span>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rulesTableBody = document.getElementById('rulesTableBody');

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

        function getRuleRowData(row) {
            return {
                id: row.dataset.ruleId,
                flag_id: row.dataset.flagId,
                field: row.querySelector('.rule-field-input').value,
                operator: row.querySelector('.rule-operator-select').value,
                value: row.querySelector('.rule-value-input').value,
                active: row.querySelector('.rule-status-toggle').checked ? 1 : 0,
            };
        }

        function setRuleStatusLabel(row, isActive) {
            const label = row.querySelector('.rule-status-label');
            label.textContent = isActive ? 'Active' : 'Inactive';
            label.className = `rule-status-label inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`;
        }

        // Show validation errors
        function showValidationErrors(errors) {
            const errorMessages = Object.values(errors).flat().join('\n');
            notyf.error(errorMessages);
        }

        // Toggle rule status
        rulesTableBody.addEventListener('change', function(e) {
            if (e.target.classList.contains('rule-status-toggle')) {
                const row = e.target.closest('tr');
                setRuleStatusLabel(row, e.target.checked);
            }
        });

        // Edit rule
        rulesTableBody.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.edit-rule');
            if (editBtn) {
                const row = e.target.closest('tr');
                const inputs = row.querySelectorAll('.rule-field-input, .rule-value-input');
                const selects = row.querySelectorAll('.rule-operator-select');

                inputs.forEach(input => input.removeAttribute('readonly'));
                selects.forEach(select => select.disabled = false);

                row.querySelector('.edit-rule').classList.add('hidden');
                row.querySelector('.save-rule').classList.remove('hidden');
                row.querySelector('.cancel-rule').classList.remove('hidden');

                // Store original values
                row.dataset.originalField = row.querySelector('.rule-field-input').value;
                row.dataset.originalOperator = row.querySelector('.rule-operator-select').value;
                row.dataset.originalValue = row.querySelector('.rule-value-input').value;
                row.dataset.originalStatus = row.querySelector('.rule-status-toggle').checked ? '1' : '0';

                refreshIcons();
            }
        });

        // Cancel rule edit
        rulesTableBody.addEventListener('click', function(e) {
            if (e.target.closest('.cancel-rule')) {
                const row = e.target.closest('tr');
                const inputs = row.querySelectorAll('.rule-field-input, .rule-value-input');
                const selects = row.querySelectorAll('.rule-operator-select');

                row.querySelector('.rule-field-input').value = row.dataset.originalField;
                row.querySelector('.rule-operator-select').value = row.dataset.originalOperator;
                row.querySelector('.rule-value-input').value = row.dataset.originalValue;
                const isActive = row.dataset.originalStatus === '1';
                row.querySelector('.rule-status-toggle').checked = isActive;
                setRuleStatusLabel(row, isActive);

                inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                selects.forEach(select => select.disabled = true);

                row.querySelector('.edit-rule').classList.remove('hidden');
                row.querySelector('.save-rule').classList.add('hidden');
                row.querySelector('.cancel-rule').classList.add('hidden');

                refreshIcons();
            }
        });

        // Save single rule
        rulesTableBody.addEventListener('click', async function(e) {
            if (e.target.closest('.save-rule')) {
                const row = e.target.closest('tr');
                const data = getRuleRowData(row);

                if (!data.id) return;

                try {
                    const response = await fetch(`/systems/flags/rules/${data.id}`, {
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

                    const inputs = row.querySelectorAll('.rule-field-input, .rule-value-input');
                    const selects = row.querySelectorAll('.rule-operator-select');

                    inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                    selects.forEach(select => select.disabled = true);

                    row.querySelector('.edit-rule').classList.remove('hidden');
                    row.querySelector('.save-rule').classList.add('hidden');
                    row.querySelector('.cancel-rule').classList.add('hidden');

                    row.dataset.originalField = data.field;
                    row.dataset.originalOperator = data.operator;
                    row.dataset.originalValue = data.value;
                    row.dataset.originalEvaluation = data.evaluation_type;
                    row.dataset.originalDescription = data.description;
                    row.dataset.originalStatus = data.active;

                    notyf.success(result.message || 'Rule updated successfully!');
                    refreshIcons();

                } catch (error) {
                    console.error(error);
                    notyf.error(error.message || 'Error updating rule');
                }
            }
        });

        // Delete rule
        rulesTableBody.addEventListener('click', function(e) {
            if (e.target.closest('.delete-rule')) {
                const row = e.target.closest('tr');
                const id = row.dataset.ruleId;

                if (!id) {
                    row.remove();
                    notyf.success('Row removed');
                    return;
                }

                if (confirm('Are you sure you want to delete this rule?')) {
                    fetch(`/systems/flags/rules/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(result => {
                        row.remove();
                        notyf.success('Rule deleted successfully!');
                    })
                    .catch(error => {
                        notyf.error('Error deleting rule');
                        console.error(error);
                    });
                }
            }
        });

        // Add new rule
        document.getElementById('addNewRule').addEventListener('click', function() {
            // Create a select for flags
            const flagOptions = @json($flags->map(fn($f) => ['id' => $f->id, 'name' => $f->name, 'applies_to' => $f->applies_to]));
            
            const tbody = document.getElementById('rulesTableBody');
            const newRow = document.createElement('tr');
            newRow.className = 'hover:bg-gray-50 transition-colors bg-gray-50';
            newRow.innerHTML = `
                <td class="px-4 py-3">
                    <select class="rule-flag-select px-2 py-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white">
                        ${flagOptions.map(f => `<option value="${f.id}">${f.name} (${f.applies_to})</option>`).join('')}
                    </select>
                </td>
                <td class="px-4 py-3">
                    <input type="text" class="rule-field-input w-32 px-2 py-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Field">
                </td>
                <td class="px-4 py-3">
                    <select class="rule-operator-select px-2 py-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs bg-white">
                        <option value=">">Greater Than</option>
                        <option value="<">Less Than</option>
                        <option value="=">Equal To</option>
                        <option value="!=">Not Equal to</option>
                        <option value=">=">Greater or Equal to</option>
                        <option value="<=">Less or Equal to</option>
                        <option value="contains">Contains</option>
                        <option value="is_null">Is Empty</option>
                        <option value="is_not_null">Is Not Empty</option>
                    </select>
                </td>
                <td class="px-4 py-3">
                    <input type="text" class="rule-value-input w-24 px-2 py-1 border border-gray-300 rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-500 text-xs" placeholder="Value">
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="rule-status-toggle sr-only peer" checked>
                            <div class="w-9 h-5 bg-gray-300 rounded-full peer peer-checked:bg-green-600 transition-colors"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform peer-checked:translate-x-4"></div>
                        </label>
                        <span class="rule-status-label inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-6">
                        <button class="save-new-rule inline-flex items-center gap-1 text-green-600 hover:text-green-800 transition-colors text-xs font-medium">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            Save
                        </button>
                        <button class="cancel-new-rule inline-flex items-center gap-1 text-gray-500 hover:text-gray-700 transition-colors text-xs font-medium">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                            Cancel
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(newRow);
            refreshIcons();
        });

        // Save new rule
        rulesTableBody.addEventListener('click', function(e) {
            if (e.target.closest('.save-new-rule')) {
                const row = e.target.closest('tr');
                const data = {
                    flag_id: row.querySelector('.rule-flag-select').value,
                    field: row.querySelector('.rule-field-input').value,
                    operator: row.querySelector('.rule-operator-select').value,
                    value: row.querySelector('.rule-value-input').value,
                    active: row.querySelector('.rule-status-toggle').checked ? 1 : 0,
                };

                fetch('/systems/flags/rules', {
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

                    row.dataset.ruleId = result.rule.id;
                    row.dataset.flagId = result.rule.flag_id;
                    row.className = 'hover:bg-gray-50 transition-colors';

                    const inputs = row.querySelectorAll('.rule-field-input, .rule-value-input');
                    const selects = row.querySelectorAll('.rule-operator-select');
                    const flagSelect = row.querySelector('.rule-flag-select');

                    inputs.forEach(input => input.setAttribute('readonly', 'readonly'));
                    selects.forEach(select => select.disabled = true);
                    flagSelect.disabled = true;

                    row.querySelector('.save-new-rule').outerHTML = `
                        <button class="edit-rule inline-flex items-center gap-1 text-gray-600 hover:text-gray-900 transition-colors text-xs font-medium">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            Edit
                        </button>`;
                    row.querySelector('.cancel-new-rule').outerHTML = `
                        <button class="delete-rule inline-flex items-center gap-1 text-red-600 hover:text-red-800 transition-colors text-xs font-medium">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            Delete
                        </button>`;

                    refreshIcons();
                    notyf.success('Rule created successfully!');
                })
                .catch(error => {
                    notyf.error('Error creating rule');
                    console.error(error);
                });
            }
        });

        // Cancel new rule
        rulesTableBody.addEventListener('click', function(e) {
            if (e.target.closest('.cancel-new-rule')) {
                const row = e.target.closest('tr');
                row.remove();
            }
        });


        // Initial icon render
        refreshIcons();
    });
</script>
@endpush