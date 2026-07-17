<x-app-layout>
    <div class="p-4">
          <!-- Tabs -->
        <div class="mb-4">
            <nav class="-mb-px flex gap-8" aria-label="Tabs">
                <button 
                    class="tab-btn active inline-flex items-center px-1 py-2 border-b-2 border-primary text-sm font-medium text-primary"
                    data-tab="flags"
                >
                    Overview
                </button>
                <button 
                    class="tab-btn inline-flex items-center px-1 py-2 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:opacity-90 "
                    data-tab="rules"
                >
                    Technical
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="max-h-[70dvh] overflow-y-auto thin-scrollbar pb-6">
            <div id="flagsTab" class="tab-content">
                @include('components.overview', $overviewData)
            </div>
    
            <div id="rulesTab" class="tab-content hidden">
                jdjd
            </div>
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

                // Listen for flag events from components
                document.addEventListener('flagUpdated', function(e) {
                    notyf.success(e.detail.message);
                });

                
            });
        </script>
    @endpush
</x-app-layout>
