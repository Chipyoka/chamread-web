<x-app-layout>

    {{-- Named slots must be direct children of the component, not nested in markup --}}
    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['label' => 'Management'],
            ['label' => 'ERP', 'url' => route('management.erp.index')],
            ['label' => 'All Imports'],
        ]" />
    </x-slot:breadcrumb>

    {{-- Single Alpine scope wraps BOTH the table and the modal --}}
    <div class="max-w-7xl mx-auto py-8 px-6 space-y-8" x-data="importView">

        <!-- Table -->
        <div class="bg-white rounded-md p-4 space-y-4 border border-gray-200 overflow-hidden">

            @if ($imports->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="px-6 py-3 whitespace-nowrap">File</th>
                                <th class="px-6 py-3 whitespace-nowrap">Cycle</th>
                                <th class="px-6 py-3 whitespace-nowrap">Status</th>
                                <th class="px-6 py-3 whitespace-nowrap">Date</th>
                                <th class="px-6 py-3 whitespace-nowrap">By</th>
                                <th class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">
                            @foreach ($imports as $import)
                                @php
                                    $statusClasses = match ($import->status) {
                                        'failed'     => 'bg-red-50 text-red-500',
                                        'pending'    => 'bg-amber-50 text-amber-500',
                                        'processing' => 'bg-blue-50 text-blue-500',
                                        'completed'  => 'bg-green-50 text-green-500',
                                        default      => 'bg-gray-50 text-gray-500',
                                    };

                                    $viewPayload = [
                                        'file'         => $import->file_name ?? '-',
                                        'cycle'        => $import->billingCycle?->name ?? '-',
                                        'user'         => $import->user?->name ?? '-',
                                        'status'       => $import->status ?? '-',
                                        'current_step' => $import->current_step ?? '-',
                                        'progress'     => (int) ($import->progress ?? 0),
                                        'import_date'  => $import->updated_at?->format('Y-m-d H:i:s') ?? '-',
                                        'summaries'    => $import->summaries->map(fn ($s) => [
                                            'entity'    => $s->entity,
                                            'created'   => $s->created_count,
                                            'updated'   => $s->updated_count,
                                            'skipped'   => $s->skipped_count,
                                            'unchanged' => $s->unchanged_count,
                                        ])->values(),
                                    ];
                                @endphp

                                <tr class="hover:bg-gray-50 transition">
                                    <!-- File -->
                                    <td title="{{ $import->file_name ?? '-' }}"
                                        class="w-64 max-w-64 px-6 py-3 text-xs text-gray-500 font-medium truncate whitespace-nowrap">
                                        {{ $import->file_name ?? '-' }}
                                    </td>

                                    <!-- Cycle -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-600 rounded-sm text-[10px] font-medium">
                                            {{ $import->billingCycle?->name ?? '-' }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-3 text-xs font-medium whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-sm text-[10px] uppercase {{ $statusClasses }}">
                                            {{ $import->status ?? '-' }}
                                            @if (in_array($import->status, ['pending', 'processing']))
                                                {{ (int) ($import->progress ?? 0) }}%
                                            @endif
                                        </span>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $import->updated_at?->format('Y-m-d H:i:s') ?? '-' }}
                                    </td>

                                    <!-- Imported by -->
                                    <td class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $import->user?->name ?? '-' }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-3 text-right text-xs space-x-2 whitespace-nowrap">
                                        <x-micro-button
                                            type="button"
                                            x-on:click="selectView({{ \Illuminate\Support\Js::from($viewPayload) }}); $dispatch('open-modal', 'view-import')"
                                            color="blue"
                                            icon="eye"
                                            size="sm"
                                        >
                                            View
                                        </x-micro-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pt-4 border-t border-gray-100">
                    {{ $imports->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="p-10 text-center">
                    <div class="flex flex-col items-center space-y-3">
                        <i data-lucide="file-up" class="w-12 h-12 text-gray-300"></i>
                        <p class="text-gray-500 text-sm font-medium">No imports found</p>
                        <p class="text-gray-400 text-xs">Imports are managed by the IT Department.</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- ============================================================
             MODAL — inside the x-data scope so it can see selectedView
        ============================================================= -->
        <x-modal name="view-import" max-width="3xl" :closable="true">
            <template x-if="selectedView">
                <div class="p-6 space-y-6">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">
                        Import — <span x-text="selectedView.file"></span>
                    </h2>

                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                        <div>
                            <dt class="text-gray-400 uppercase">Cycle</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView.cycle"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 uppercase">Status</dt>
                            <dd class="text-gray-700 font-medium uppercase" x-text="selectedView.status"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 uppercase">Progress</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView.progress + '%'"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 uppercase">Current step</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView.current_step"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 uppercase">Imported by</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView.user"></dd>
                        </div>
                        <div>
                            <dt class="text-gray-400 uppercase">Date</dt>
                            <dd class="text-gray-700 font-medium" x-text="selectedView.import_date"></dd>
                        </div>
                    </dl>

                    <!-- Summaries -->
                    <div class="overflow-x-auto border border-gray-100 rounded-md">
                        <table class="min-w-full divide-y divide-gray-100 text-xs">
                            <thead class="bg-gray-50 text-left text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-2">Entity</th>
                                    <th class="px-4 py-2 text-right">Created</th>
                                    <th class="px-4 py-2 text-right">Updated</th>
                                    <th class="px-4 py-2 text-right">Unchanged</th>
                                    <th class="px-4 py-2 text-right">Skipped</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-gray-600">
                                <template x-for="row in selectedView.summaries" :key="row.entity">
                                    <tr>
                                        <td class="px-4 py-2 font-medium" x-text="row.entity"></td>
                                        <td class="px-4 py-2 text-right text-green-600" x-text="row.created"></td>
                                        <td class="px-4 py-2 text-right text-blue-600" x-text="row.updated"></td>
                                        <td class="px-4 py-2 text-right" x-text="row.unchanged"></td>
                                        <td class="px-4 py-2 text-right text-amber-600" x-text="row.skipped"></td>
                                    </tr>
                                </template>
                                <tr x-show="!selectedView.summaries.length">
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-400">No summary available.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </x-modal>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('importView', () => ({
                    selectedView: null,

                    selectView(payload) {
                        this.selectedView = payload;
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>