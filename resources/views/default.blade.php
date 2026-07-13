<x-app-layout>
    <div x-data="{ open: false }">

        <div class="py-4">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-4 text-gray-500 ">
                <div class="flex flex-col items-center justify-center gap-1 min-h-60 mt-20">
                    <i data-lucide="construction" class="w-14 h-14 text-gray-300 mb-4 animate-pulse"></i>
                    <p class="text-lg text-gray-400">Check back later...</p>
                    <p class="uppercase text-xs text-gray-400 font-light">this room is Under construction</p>
                </div>
            </div>
        </div>
    
        <button
        type="button"
        x-on:click="$dispatch('open-modal', 'confirm-delete')"
        class="..."
    >
        Delete
    </button>
        <button
        type="button"
        x-on:click="$dispatch('open-modal', 'confirm-happy')"
        class="..."
    >
        Happy
    </button>
    
        <x-modal name="confirm-delete" max-width="md">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">Delete this item?</h2>
                <p class="mt-2 text-sm text-gray-500">
                    This action cannot be undone.
                </p>
        
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        x-on:click="$dispatch('close-modal', 'confirm-delete')"
                        class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
        
                
                </div>
            </div>
         </x-modal>
        <x-modal name="confirm-happy" max-width="md">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900">Happy</h2>
                <p class="mt-2 text-sm text-gray-500">
                    This action cannot be undone.
                </p>
        
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        x-on:click="$dispatch('close-modal', 'confirm-delete')"
                        class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
        
                
                </div>
            </div>
         </x-modal>
    </div>
</x-app-layout>