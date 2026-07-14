<x-app-layout>

    <div 
        class="max-w-7xl mx-auto py-8 px-6 space-y-8"
        x-data="importTracker()"
        x-init="startTracking()"
    >

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">
                Monthly Template Management
            </h1>

            <p class="mt-1 text-sm text-gray-600">
                Upload monthly ERP templates and export reconstructed billing files.
            </p>
        </div>


        {{-- Import Section --}}
        <section class="bg-white border border-gray-200 rounded-lg shadow-sm">

            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    Import Monthly Template
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Upload the monthly template file to synchronize customers and zones.
                </p>
            </div>


            <div class="p-6">

                <form 
                    method="POST"
                    action="{{ route('management.monthly-template.upload') }}"
                    enctype="multipart/form-data"
                    class="space-y-5"
                >

                    @csrf


                    {{-- Billing Cycle --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Billing Cycle
                        </label>

                        <select
                            name="billing_cycle_id"
                            class="w-full rounded-md border-gray-300 text-sm focus:border-black focus:ring-black"
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


                    {{-- File --}}
                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Template File
                        </label>


                        <input
                            type="file"
                            name="template"
                            accept=".xlsx,.xls"
                            required
                            class="
                                block w-full text-sm
                                text-gray-700
                                border border-gray-300
                                rounded-md
                                cursor-pointer
                                bg-gray-50
                            "
                        >

                        <p class="mt-2 text-xs text-gray-500">
                            Supported formats: XLSX, XLS. Maximum size: 10MB.
                        </p>

                    </div>


                    {{-- Submit --}}
                    <div>

                        <button
                            type="submit"
                                     class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition"

                        >
                            Start Import
                        </button>

                    </div>

                </form>


                {{-- Progress Area --}}
                <div
                    x-show="tracking"
                    x-transition
                    class="
                        mt-8
                        border
                        border-gray-200
                        rounded-md
                        p-5
                        bg-gray-50
                    "
                >

                    <div class="flex justify-between items-center mb-3">

                        <h3 class="font-medium text-gray-900">
                            Import Progress
                        </h3>

                        <span
                            class="
                                text-sm
                                text-gray-600
                            "
                            x-text="progress + '%'"
                        ></span>

                    </div>


                    {{-- Progress Bar --}}
                    <div class="w-full bg-gray-200 rounded-full h-2">

                        <div
                            class="bg-black h-2 rounded-full transition-all"
                            :style="`width:${progress}%`"
                        ></div>

                    </div>


                    {{-- Current Step --}}
                    <div class="mt-4">

                        <p
                            class="text-sm font-medium text-gray-900"
                            x-text="step"
                        ></p>


                        <p
                            class="text-sm text-gray-600 mt-1"
                            x-text="message"
                        ></p>

                    </div>


                </div>


            </div>

        </section>



        {{-- Download Section --}}
        <section class="bg-white border border-gray-200 rounded-lg shadow-sm">


            <div class="px-6 py-4 border-b border-gray-200">

                <h2 class="text-lg font-semibold text-gray-900">
                    Download Monthly Template
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Generate the ERP-compatible template from system data.
                </p>

            </div>


            <div class="p-6">


                <div class="grid md:grid-cols-3 gap-4">

                    @foreach($billingCycles as $cycle)

                        <div
                            class="
                                border
                                border-gray-200
                                rounded-md
                                p-4
                                flex
                                justify-between
                                items-center
                            "
                        >

                            <div>

                                <p class="font-medium text-gray-900">
                                    {{ $cycle->name ?? 'Cycle '.$cycle->id }}
                                </p>

                            </div>


                            <a
                                href="{{ route('management.monthly-template.download',$cycle) }}"
                                class="
                                    text-sm
                                    bg-gray-900
                                    text-white
                                    px-4
                                    py-2
                                    rounded-md
                                    hover:bg-black
                                "
                            >
                                Download
                            </a>


                        </div>

                    @endforeach


                </div>


            </div>


        </section>


    </div>



<script>

function importTracker(){

    return {

        tracking:false,

        processId:null,

        progress:0,

        step:'',

        message:'',


        startTracking(){

            this.processId = "{{ session('process_id') }}";


            if(this.processId){

                this.tracking = true;

                this.pollStatus();

            }

        },


        pollStatus(){

            fetch(`/monthly-template/status/${this.processId}`)

                .then(response => response.json())

                .then(data => {


                    this.progress = data.progress;

                    this.step = data.step;

                    this.message = data.message;


                    if(
                        data.status !== 'completed' &&
                        data.status !== 'failed'
                    ){

                        setTimeout(() => {

                            this.pollStatus();

                        },3000);

                    }


                });


        }


    }

}

</script>


</x-app-layout>