<x-app-layout>
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-500">Add New CSA</h1>
                <p class="text-sm text-gray-500">Create a Customer Service Agent account</p>
            </div>

            <a href="{{ route('readings.csas.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 transition">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>
                Back to List
            </a>
        </div>

    
    </div>

    

</x-app-layout>