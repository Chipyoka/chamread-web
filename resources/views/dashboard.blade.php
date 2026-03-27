<x-app-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-4 overflow-y-auto h-full max-h-[80dvh]">
            <div class="text-gray-600 mb-6">
                <h4 class="text-2xl font-medium">Welcome,</h4>
                <p class="text-sm">Below are summaries of key metrics from the overall Chamread performance.</p>
            </div>
            <div class="grid grid-cols-3 gap-x-4 gap-y-8">

                <!-- card total assigned-->
                <div class="hover-sweep flex items-center justify-between bg-white border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-sm ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-primary">0 000 000</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Total assigned cycles</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-blue-50 rounded-full">
                        <i data-lucide="list" class="w-10 h-10 text-primary"></i>
                    </div>
                </div>

                <!-- card total read -->
                <div class="hover-sweep flex items-center justify-between bg-white border-t-8 border-green-400 rounded-sm px-4 py-4 cursor-default hover:shadow-sm ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-green-400">0 000 000</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Total Read</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-green-50 rounded-full">
                        <i data-lucide="circle-check" class="w-10 h-10 text-green-400"></i>
                    </div>
                </div>

                <!-- card total not read-->
                <div class="hover-sweep flex items-center justify-between bg-white border-t-8 border-red-400 rounded-sm px-4 py-4 cursor-default hover:shadow-sm ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-red-400">0 000 000</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Total not read</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-red-50 rounded-full">
                        <i data-lucide="circle-x" class="w-10 h-10 text-red-400"></i>
                    </div>
                </div>

                <!-- card completetion-->
                <div class="hover-sweep flex items-center justify-between bg-white border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-sm ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-primary">000%</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Total Completion rate (%)</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-blue-50 rounded-full">
                        <i data-lucide="pie-chart" class="w-10 h-10 text-primary"></i>
                    </div>
                </div>

                <!-- card abnormal -->
                <div class="hover-sweep flex items-center justify-between bg-white border-t-8 border-amber-400 rounded-sm px-4 py-4 cursor-default hover:shadow-sm ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-amber-400">0 000 000</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Total abnormal readings</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-amber-50 rounded-full">
                        <i data-lucide="alert-triangle" class="w-10 h-10 text-amber-400"></i>
                    </div>
                </div>

                <!-- card zero consumption-->
                <div class="hover-sweep flex items-center justify-between bg-white border-t-8 border-slate-400 rounded-sm px-4 py-4 cursor-default hover:shadow-sm ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-slate-400">0 000 000</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Total zero consumption</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-slate-50 rounded-full">
                        <i data-lucide="circle-minus" class="w-10 h-10 text-slate-400"></i>
                    </div>
                </div>

                  <!-- card Billing Area Edits-->
                <div class="hover-sweep flex items-center justify-between bg-white border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-sm ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-primary">0 000 000</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Total Billing Area Edits</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-blue-50 rounded-full">
                        <i data-lucide="file-edit" class="w-10 h-10 text-primary"></i>
                    </div>
                </div>

                <!-- card GPS Mismatch Alerts-->
                <div class="hover-sweep flex items-center justify-between bg-white border-t-8 border-orange-400 rounded-sm px-4 py-4 cursor-default hover:shadow-sm ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-orange-400">0 000 000</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Total GPS Mismatch Alerts</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-blue-50 rounded-full">
                        <i data-lucide="map-pin-off" class="w-10 h-10 text-orange-400"></i>
                    </div>
                </div>

                <!-- card Total CSAs-->
                <div class="hover-sweep flex items-center justify-between bg-white border-t-8 border-primary rounded-sm px-4 py-4 cursor-default hover:shadow-sm ">
                    <div class="">
                        <h2 class="text-3xl font-bold text-primary">0 000 000</h2>
                        <p class="text-gray-500 text-xs uppercase mt-2">Total Active CSAs</p>
                    </div>
                    <div class="flex items-center justify-center p-4 bg-blue-50 rounded-full">
                        <i data-lucide="users" class="w-10 h-10 text-primary"></i>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>
