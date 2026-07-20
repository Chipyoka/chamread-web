<x-app-layout>

      <x-slot:breadcrumb>
            <x-breadcrumb :items="[
                [
                    'label'=>'Management'
                ],
                [
                    'label'=>'ERP'
                ]
            ]"/>
        </x-slot:breadcrumb>

    <div 
        class="max-w-7xl mx-auto py-8 px-6 space-y-8"
        x-data="importTracker()"
        x-init="startTracking()"
    >

                <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500 flex items-center gap-4">ERP <span><i data-lucide="arrow-right-left" class="w-5 h-5"></i></span> Chamread</h1>
                <p class="text-sm text-gray-500">Upload and download meter reading files. <button x-on:click="$dispatch('open-modal', 'see-ref')" class="font-medium hover:underline text-primary">See Reference</button></p>
                
                
            </div>

            <div class="flex items-center justify-end gap-4">
                <a title="refresh page" href="{{ route( 'management.erp.index' ) }}"><i data-lucide="refresh-cw" class="w-5 h-5 text-gray-500"></i></a>

                <button
                        x-on:click="$dispatch('open-modal', 'upload-file')"
                        class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition"
                    >
                    <i data-lucide="file-up" class="w-4 h-4 mr-2"></i>
                        Import file
                </button>
            </div>
        </div>

        <div >
                 <!-- {{-- Progress Area --}} -->
                <div
                    class="
                    bg-gray-100/70
                        p-2
                    "
                    
                    x-show="tracking"
                    x-transition
                >
                    <div class="flex justify-between items-center mb-3">

                        <h3 class="font-medium text-gray-500 text-xs">
                            Import Progress
                        </h3>

                        <span
                            class="
                                text-sm
                                text-gray-500
                            "
                            x-text="progress + '%'"
                        ></span>

                    </div>

                    <!-- {{-- Progress Bar --}} -->
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-primary h-2 rounded-full transition-all"
                            :style="`width:${progress}%`"
                        ></div>
                    </div>

                    <!-- {{-- Current Step --}} -->
                    <div class="mt-1">
                        <p
                            class="text-xxs font-medium text-gray-700"
                            x-text="step"
                        ></p>
                    </div>
                </div>
        </div>
    
        <!-- {{-- recent imports Section --}} -->
       <section class="bg-white border rounded-md border-gray-200  px-6 py-4 ">


           <div class="text-gray-500 mb-6">
               <p class="text-gray-400 text-xs uppercase my-2"> Recent file imports</p>
           </div>
           <div class="my-4 border-t pt-2 border-gray-100 text-gray-500  flex items-center">
               <i data-lucide="info" class="mr-2 w-3 h-3"></i>
               <p class="text-xxs ">Contact IT for any failed import before retries.</p>
           </div>

           <div class="max-h-full pb-2">


               <div class="grid grid-cols-1 gap-3">

                   @foreach($latestImports as $import)

                       <div
                           class="
                               border
                               border-gray-100
                               rounded-md
                               p-2
                               flex
                               justify-between
                               items-center
                               hover:bg-gray-50/70 transition-all duration-300 ease-out
                               cursor-default
                           "
                       >

                           <div class="grid grid-cols-4 gap-4">

                               <p class="font-medium text-gray-500 text-xs truncate" title="{{ $import->file_name ?? 'No name' }}">
                                   {{ $import->file_name ?? "No name" }}
                               </p>

                               <p class="font-medium text-gray-500 text-xs">
                                   {{ $import->created_at->format('D d M Y H:m:s') ?? '-' }}
                               </p>
                               <p class="font-medium text-gray-400 text-xs">
                                  By {{ $import->user->name ?? "unknown" }}
                               </p>

                               <p class="font-medium text-gray-500 text-xs text-right">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                   {{ $import->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                   {{ $import->status === 'failed' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                   {{ ucfirst($import->status ?? '-') }}
                               </span>
                               </p>

                           </div>


                          


                       </div>

                   @endforeach


               </div>

       


           </div>


       </section>

        <!-- {{-- Download Section --}} -->
        <section class="bg-white border rounded-md border-gray-200  px-6 py-4 ">


             <div class="text-gray-500 mb-6">
                <p class="text-gray-400 text-xs uppercase my-2"> Available Cycles for export</p>
            </div>
            <div class="my-4 border-t pt-2 border-gray-100 text-gray-500  flex items-center">
                <i data-lucide="info" class="mr-2 w-3 h-3"></i>
                <p class="text-xxs ">Download a reading file of a specific cycle.</p>

            </div>

            <div>


                <div class="grid grid-cols-1 gap-4">

                    @foreach($billingCycles as $cycle)

                        <div
                            class="
                                border
                                border-gray-100
                                rounded-md
                                p-2
                                flex
                                justify-between
                                items-center
                                hover:bg-gray-50/70 transition-all duration-300 ease-out
                            "
                        >

                            <div class="grid grid-cols-4 gap-8 w-[50%]">

                                <p class="font-medium text-gray-500 uppercase text-xs">
                                    {{ $cycle->name ?? 'Cycle '.$cycle->id }}
                                </p>

                                <p class="font-medium text-gray-500 text-xs">
                                    {{ $cycle->start_date->format('D d M Y') ?? '-' }}
                                </p>
                                <p class="font-medium text-gray-400 text-xs text-center">
                                    to
                                </p>

                                <p class="font-medium text-gray-500 text-xs">
                                    {{ $cycle->end_date->format('D d M Y') ?? '-' }}
                                </p>

                            </div>


                            <a
                                href="{{ route('management.monthly-template.download',$cycle) }}"
                                class="
                                    text-xs
                                    bg-blue-50
                                    flex items-center
                                    text-primary
                                    font-medium
                                    
                                    p-2
                                    rounded-md
                                "
                            >
                            <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                Download
                            </a>


                        </div>

                    @endforeach


                </div>

           


            </div>


        </section>


    </div>

    <!-- ================================================
    | MODAL SECTION 
    -->

    <!-- Header reference modal -->
    <x-modal name="see-ref" max-width="3xl" :closable="false">
        <div class="p-4 ">
            <h2 class="text-lg font-semibold text-gray-900">Column Details</h2>
        </div>
        <div class="p-6 max-h-[80dvh] overflow-y-auto thin-scrollbar">
            <div class="bg-gray-100 p-4 rounded-sm max-w-3xl mx-auto">
                <div class=" rounded-sm border border-gray-200 bg-white">
                    <!-- Header Row -->
                    <div class="grid grid-cols-2 gap-4 bg-gray-200 px-4 py-3 text-xxs font-semibold uppercase tracking-wider text-gray-700 border-b border-gray-300">
                    <div>Header</div>
                    <div>Description</div>
                    </div>

                    <!-- Data Rows -->
                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-gray-50">
                    <div class="font-medium">Account</div>
                    <div>Unique customer account number</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-white">
                    <div class="font-medium">Name</div>
                    <div>Full name of the customer</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-gray-50">
                    <div class="font-medium">Address</div>
                    <div>Customer address or street name</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-white">
                    <div class="font-medium">Meter number</div>
                    <div>Unique meter number</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-gray-50">
                    <div class="font-medium">Customer Category</div>
                    <div>Customer Category</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-white">
                    <div class="font-medium">Current Reading Date</div>
                    <div>Reading Date field used for meter reading exports</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-gray-50">
                    <div class="font-medium">Current Reading</div>
                    <div>Current Reading field used for meter reading exports</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-white">
                    <div class="font-medium">Meter reading ERP (incl estimates)</div>
                    <div>Previous Reading field used for meter reading exports</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-gray-50">
                    <div class="font-medium">Consumption</div>
                    <div>Consumption field used for meter reading exports</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-white">
                    <div class="font-medium">Meter Status ERP</div>
                    <div>Metering Status field used for meter reading exports</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-gray-50">
                    <div class="font-medium">Optional Comment</div>
                    <div>Optional Comment field used for meter reading exports</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-white">
                    <div class="font-medium">MR: This month Code</div>
                    <div>Meter Code field used for meter reading exports</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-gray-50">
                    <div class="font-medium">MR: Last month Code</div>
                    <div>Previous Meter Code field used for meter reading exports</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-white">
                    <div class="font-medium">Phone number</div>
                    <div>Customer Phone Number</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-gray-50">
                    <div class="font-medium">Previous Date</div>
                    <div>Date and time of the meter reading of the previous month (M-1)</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-white">
                    <div class="font-medium">Previous2 Meter Code</div>
                    <div>Meter reading code reading two months ago month (M-2)</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-gray-50">
                    <div class="font-medium">Previous2 Reading</div>
                    <div>Meter reading (from a metered account) two months ago month (M-2)</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-white">
                    <div class="font-medium">Previous2 Date</div>
                    <div>Date and time of the meter reading from two months ago month (M-2)</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-gray-50">
                    <div class="font-medium">Previous3 Meter Code</div>
                    <div>Meter reading code reading three months ago month (M-3)</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 border-b border-gray-200 bg-white">
                    <div class="font-medium">Previous3 Reading</div>
                    <div>Meter reading (from a metered account) three months ago month (M-3)</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800 bg-gray-50">
                    <div class="font-medium">Previous3 Date</div>
                    <div>Date and time of the meter reading from three months ago month (M-3)</div>
                    </div>
                </div>
            </div>
            
        </div>
    </x-modal>

    <!-- upload file modal -->
    <x-modal name="upload-file" max-width="md" :closable="false">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900"> Import Meter readings file</h2>

            <div class="">
    
                <form 
                    method="POST"
                    action="{{ route('management.monthly-template.upload') }}"
                    enctype="multipart/form-data"
                    class="space-y-5"
                >
                    @csrf
    
                    <!-- {{-- Billing Cycle --}} -->
                    <div>
                        <label for="billing_cycle_id" class="block text-sm font-medium text-gray-700">Select Cycle</label>
    
                        <select
                            name="billing_cycle_id"
                            class="mt-1 block w-full border py-2 border-gray-300 rounded-sm shadow-sm focus:border-primary focus:ring-primary sm:text-sm @error('billing_cycle_id') border-red-500 @enderror"
                            required
                        >
                            <option value="">
                                Select billing cycle
                            </option>
                            @foreach($billingCycles as $cycle)
                                <option value="{{ $cycle->id }}">
                                    {{ $cycle->name ?? 'Cycle '.$cycle->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
    
                    <!-- {{-- File --}} -->
                    <div>
                        <label for="template" class="block text-sm font-medium text-gray-700">Choose file</label>
                        <input
                            type="file"
                            name="template"
                            accept=".xlsx,.xls"
                            required
                            class="mt-1 block w-full rounded-sm border px-3 py-2 border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm @error('template') border-red-500 @enderror"
    
                        >
                        <p class="mt-2 text-xxs text-gray-500">
                            Supported formats: XLSX, XLS. Maximum size: 10MB.
                        </p>
    
                    </div>
    
                    <!-- {{-- Submit --}} -->
                    <div>
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition"
                        >
                            Start Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </x-modal>


    @push('scripts')
        <script>
            function importTracker() {
                return {
                    tracking: false,
                    processId: null,
                    progress: 0,
                    step: '',
                    message: '',
                    error: null, // Add error state

                    startTracking() {
                        this.processId = "{{ session('process_id') }}";
                        
                        if (this.processId) {
                            this.tracking = true;
                            this.error = null; // Reset error
                            this.pollStatus();
                        } else {
                            this.error = 'No process ID found. Please start an import first.';
                            console.error('ImportTracker: No process ID available');
                        }
                    },

                    pollStatus() {
                        fetch(`/management/monthly-template/status/${this.processId}`)
                            .then(response => {
                                // Check if response is OK
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status} ${response.statusText}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                // Validate data structure
                                if (!data || typeof data !== 'object') {
                                    throw new Error('Invalid response format: expected object');
                                }

                                // Update progress
                                this.progress = data.progress ?? 0;
                                this.step = data.step ?? '';
                                this.message = data.message ?? '';
                                this.error = null; // Clear any previous errors

                                // Check for explicit failure status
                                if (data.status === 'failed') {
                                    this.error = data.error || data.message || 'Import failed unexpectedly.';
                                    console.error('ImportTracker: Import failed:', {
                                        processId: this.processId,
                                        error: this.error,
                                        step: this.step,
                                        progress: this.progress
                                    });
                                    return;
                                }

                                // Continue polling if not completed
                                if (data.status !== 'completed') {
                                    setTimeout(() => {
                                        this.pollStatus();
                                    }, 3000);
                                } else {
                                    // Successfully completed
                                    console.log('ImportTracker: Import completed successfully', {
                                        processId: this.processId,
                                        progress: this.progress,
                                        step: this.step
                                    });
                                }
                            })
                            .catch(error => {
                                // Network or parsing errors
                                this.error = `Connection error: ${error.message}. Please check your network and try again.`;
                                console.error('ImportTracker: Polling error:', {
                                    processId: this.processId,
                                    error: error.message,
                                    stack: error.stack,
                                    timestamp: new Date().toISOString()
                                });
                                
                                // Optional: Retry logic for network errors
                                if (this.tracking) {
                                    setTimeout(() => {
                                        console.log('ImportTracker: Retrying poll...');
                                        this.pollStatus();
                                    }, 5000);
                                }
                            });
                    },

                    // Utility method to manually retry
                    retryPolling() {
                        if (this.processId && this.tracking) {
                            this.error = null;
                            console.log('ImportTracker: Manual retry triggered');
                            this.pollStatus();
                        } else {
                            console.warn('ImportTracker: Cannot retry - no active tracking');
                        }
                    },

                    // Utility method to stop tracking
                    stopTracking() {
                        this.tracking = false;
                        console.log('ImportTracker: Tracking stopped', {
                            processId: this.processId,
                            finalProgress: this.progress,
                            finalStep: this.step
                        });
                    }
                };
            }
        </script>
    @endpush

</x-app-layout>