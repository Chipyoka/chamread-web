<x-app-layout>

    <div class="p-6 space-y-6">

    <!-- header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500">Flags</h1>
                <p class="text-sm text-gray-500">Define flags on which system alerts and charts will be based.</p>
            </div>

             <button 
                id="addNewFlag"
                class="inline-flex items-center px-3 py-2.5 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition"
            >
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                New Flag
            </button>
        </div>

        <!-- Tabs -->
        <div>
            <nav class="-mb-px flex gap-8" aria-label="Tabs">
                <button 
                    class="tab-btn active inline-flex items-center px-1 py-2 border-b-2 border-primary text-sm font-medium text-primary"
                    data-tab="flags"
                >
                    All Flags
                </button>
                <button 
                    class="tab-btn inline-flex items-center px-1 py-2 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:opacity-90 "
                    data-tab="rules"
                >
                    Flag Rules
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div id="flagsTab" class="tab-content">
            @include('components.flag-list', ['flags' => $flags])
        </div>

        <div id="rulesTab" class="tab-content hidden">
            @include('components.rule-list', ['flags' => $flags])
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
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

                // Tab switching
                document.querySelectorAll('.tab-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        // Update button states
                        document.querySelectorAll('.tab-btn').forEach(btn => {
                            btn.classList.remove('active', 'border-primary', 'text-primary');
                            btn.classList.add('border-transparent', 'text-gray-500');
                        });
                        this.classList.remove('border-transparent', 'text-gray-500');
                        this.classList.add('active', 'border-primary', 'text-primary');

                        // Show/hide content
                        const tab = this.dataset.tab;
                        document.querySelectorAll('.tab-content').forEach(content => {
                            content.classList.add('hidden');
                        });
                        document.getElementById(tab + 'Tab').classList.remove('hidden');
                    });
                });

                // Flag management functions
                function showValidationErrors(errors) {
                    const errorMessages = Object.values(errors).flat().join('\n');
                    notyf.error(errorMessages);
                }

                // Add new flag button
                document.getElementById('addNewFlag').addEventListener('click', function() {
                    // This will be handled by the flags-list component
                    window.dispatchEvent(new CustomEvent('addNewFlag'));
                });

                // Listen for flag events from components
                document.addEventListener('flagUpdated', function(e) {
                    notyf.success(e.detail.message);
                });

                document.addEventListener('flagDeleted', function(e) {
                    notyf.success(e.detail.message);
                });

                document.addEventListener('flagCreated', function(e) {
                    notyf.success(e.detail.message);
                });

                document.addEventListener('ruleUpdated', function(e) {
                    notyf.success(e.detail.message);
                });

                document.addEventListener('ruleDeleted', function(e) {
                    notyf.success(e.detail.message);
                });

                document.addEventListener('ruleCreated', function(e) {
                    notyf.success(e.detail.message);
                });

                document.addEventListener('error', function(e) {
                    notyf.error(e.detail.message);
                });
            });
        </script>
    @endpush
</x-app-layout>