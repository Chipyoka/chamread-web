{{--
    ERP ⇄ Chamread page: template import, import progress tracking, latest
    import summary, and per-cycle reading exports.

    Expects:
    - $latestImport   ImportProcess|null  Most recent import, with `user`, `billingCycle` and `summaries` eager loaded.
    - $billingCycles  Collection<BillingCycle>  Cycles available for export, loaded with withCount('readings').

    Progress tracking follows the process flashed as `process_id` after an
    upload, or the latest import while it is still pending or processing, so
    the tracker resumes when the user navigates back mid-import. The status
    endpoint must return JSON with `status`, `progress`, `step`, `message`
    and, on failure, `error`.
--}}
@php
    $runningStatuses = ['pending', 'processing'];

    $trackedProcessId = session('process_id')
        ?? (in_array($latestImport?->status, $runningStatuses, true) ? $latestImport->id : null);

    $statusUrl = $trackedProcessId
        ? url("management/monthly-template/status/{$trackedProcessId}")
        : null;

    $statusStyles = [
        'failed' => 'bg-red-50 text-red-500',
        'pending' => 'bg-amber-50 text-amber-500',
        'processing' => 'bg-blue-50 text-blue-500',
        'completed' => 'bg-green-50 text-green-500',
    ];

    $summaries = collect($latestImport?->summaries)
        ->sortBy(fn ($summary) => $summary->entity === 'zone' ? 0 : 1);

    $outcomes = [
        'created_count' => 'Created',
        'updated_count' => 'Updated',
        'unchanged_count' => 'Unchanged',
        'skipped_count' => 'Skipped',
    ];

    $templateColumns = [
        ['Account', 'Unique customer account number', true],
        ['Name', 'Full name of the customer', true],
        ['Address', 'Customer address or street name', true],
        ['Meter number', 'Unique meter number', true],
        ['Customer Category', 'Customer category (Domestic, Commercial, Government, Institution, Other)', true],
        ['Current Reading Date', 'Reading date field used for meter reading exports', false],
        ['Current Reading', 'Current reading field used for meter reading exports', false],
        ['Meter reading ERP (incl estimates)', 'Previous reading field used for meter reading exports', false],
        ['Meter Status ERP', 'Metering status field used for meter reading exports', false],
        ['Optional Comment', 'Optional comment field used for meter reading exports', false],
        ['MR: This month Code', 'Meter code field used for meter reading exports', false],
        ['MR: Last month Code', 'Previous meter code field used for meter reading exports', false],
        ['Phone number', 'Customer phone number', true],
        ['Previous Date', 'Date and time of the meter reading of the previous month (M-1)', false],
        ['Previous2 Meter Code', 'Meter reading code from two months ago (M-2)', false],
        ['Previous2 Reading', 'Meter reading (from a metered account) from two months ago (M-2)', false],
        ['Previous2 Date', 'Date and time of the meter reading from two months ago (M-2)', false],
        ['Previous3 Meter Code', 'Meter reading code from three months ago (M-3)', false],
        ['Previous3 Reading', 'Meter reading (from a metered account) from three months ago (M-3)', false],
        ['Previous3 Date', 'Date and time of the meter reading from three months ago (M-3)', false],
        ['District', 'District of the account\'s zone', true],
        ['Zone', 'Zone code; the account is assigned to this zone', true],
        ['Consumption', 'Consumption field used for meter reading exports', false],
        ['Province', 'Province of the account\'s zone', true],
    ];
@endphp

<x-app-layout>

    <x-slot:breadcrumb>
        <x-breadcrumb :items="[
            ['label' => 'Management'],
            ['label' => 'ERP'],
        ]"/>
    </x-slot:breadcrumb>

    <div class="max-w-7xl mx-auto py-8 px-6 space-y-8">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-500 flex items-center gap-4">
                    ERP <span><i data-lucide="arrow-right-left" class="w-5 h-5"></i></span> Chamread
                </h1>
                <p class="text-sm text-gray-500">
                    Upload and download meter reading files.
                    <button
                        type="button"
                        x-data
                        x-on:click="$dispatch('open-modal', 'see-ref')"
                        class="font-medium hover:underline text-primary"
                    >See Reference</button>
                </p>
            </div>

            <div class="flex items-center justify-end gap-4">
                <a title="Refresh page" href="{{ route('management.erp.index') }}">
                    <i data-lucide="refresh-cw" class="w-5 h-5 text-gray-500"></i>
                </a>

                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'upload-file')"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition"
                >
                    <i data-lucide="file-up" class="w-4 h-4 mr-2"></i>
                    Import file
                </button>
            </div>
        </div>

        {{-- Import progress --}}
        <div x-data="importTracker(@js($statusUrl))">
            <div
                x-show="tracking"
                x-cloak
                x-transition
                class="rounded-md border p-3"
                :class="panelClass"
            >
                <div class="flex justify-between items-center mb-2">
                    <div class="flex items-center gap-2">
                        <h3 class="font-medium text-gray-500 text-xs">Import Progress</h3>
                        <span
                            class="px-1.5 py-0.5 rounded-sm text-[10px] font-medium uppercase"
                            :class="badgeClass"
                            x-text="statusLabel"
                        ></span>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 tabular-nums" x-text="`${progress}%`"></span>
                        <button
                            type="button"
                            x-show="!isRunning"
                            x-on:click="dismiss()"
                            class="text-gray-400 hover:text-gray-600"
                            title="Dismiss"
                        >
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div
                        class="h-2 rounded-full transition-all duration-500"
                        :class="barClass"
                        :style="`width: ${progress}%`"
                    ></div>
                </div>

                <div class="mt-2 space-y-0.5">
                    <p class="text-xs font-medium text-gray-700" x-text="step || 'Waiting for the import to start...'"></p>
                    <p class="text-xxs text-gray-500" x-show="message && status !== 'failed'" x-text="message"></p>
                    <p class="text-xxs text-red-600" x-show="status === 'failed'" x-text="error"></p>
                </div>

                <div x-show="connectionError" class="mt-2 flex items-center gap-2 text-xxs text-amber-600">
                    <i data-lucide="wifi-off" class="w-3 h-3"></i>
                    <span x-text="connectionError"></span>
                    <button
                        type="button"
                        x-show="pollingStopped"
                        x-on:click="retry()"
                        class="font-medium underline hover:no-underline"
                    >Retry</button>
                </div>

                <div x-show="status === 'completed'" class="mt-3">
                    <button
                        type="button"
                        x-on:click="window.location.reload()"
                        class="inline-flex items-center text-xs font-medium text-primary hover:underline"
                    >
                        <i data-lucide="refresh-cw" class="w-3 h-3 mr-1.5"></i>
                        Refresh to view the import summary
                    </button>
                </div>
            </div>
        </div>

        {{-- Latest import --}}
        <section class="bg-white border rounded-md border-gray-200 px-6 py-4">

            <div class="text-gray-500 mb-6">
                <p class="text-gray-400 text-xs uppercase my-2">Current import</p>
            </div>

            @if ($latestImport)
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="col-span-2 px-2 py-1.5 bg-gray-100/70 rounded-sm">
                        <p class="text-gray-500 font-semibold truncate" title="{{ $latestImport->file_name ?? 'N/A' }}">
                            {{ $latestImport->file_name ?? 'N/A' }}
                        </p>
                        <p class="text-xs my-1 text-gray-500">
                            For the <span class="text-secondary">{{ $latestImport->billingCycle?->name ?? 'N/A' }}</span> cycle on
                            <span class="text-secondary">{{ $latestImport->updated_at?->format('d M Y H:i:s') ?? 'N/A' }}</span>
                        </p>
                    </div>

                    <div class="px-2 py-1.5 rounded-sm {{ $statusStyles[$latestImport->status] ?? 'bg-gray-100/70 text-gray-500' }}">
                        <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Current Step</h2>
                        <p class="font-semibold">{{ $latestImport->current_step ?? 'N/A' }}</p>
                    </div>

                    <div class="px-2 py-1.5 bg-gray-100/70 rounded-sm">
                        <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">Uploaded by</h2>
                        <p class="text-gray-500 font-semibold">{{ $latestImport->user?->name ?? '-' }}</p>
                    </div>
                </div>

                <div class="text-gray-500 p-2 bg-gray-50/30 my-3 border border-gray-100 rounded-sm">
                    @forelse ($summaries as $summary)
                        <p class="text-gray-500 font-semibold text-xs my-2">
                            {{ \Illuminate\Support\Str::plural(\Illuminate\Support\Str::headline($summary->entity)) }}
                        </p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 px-3">
                            @foreach ($outcomes as $column => $label)
                                <div class="bg-gray-50 p-4 hover:bg-gray-100 transition-all duration-300 ease-out cursor-default">
                                    <h2 class="text-gray-500 mb-1 text-xs uppercase font-normal">{{ $label }}</h2>
                                    <p class="text-gray-500 font-semibold tabular-nums">{{ number_format($summary->{$column} ?? 0) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-gray-500 font-semibold text-xs my-2">
                            @if (in_array($latestImport->status, $runningStatuses, true))
                                The summary will be available when the import finishes.
                            @else
                                No summary data available.
                            @endif
                        </p>
                    @endforelse
                </div>
            @else
                <p class="text-gray-500 text-xs py-4">No imports yet. Use <span class="font-medium">Import file</span> to upload a template.</p>
            @endif

            <div class="flex justify-between items-center">
                <div class="mt-4 text-amber-500 flex items-center">
                    <i data-lucide="info" class="mr-2 w-3 h-3"></i>
                    <p class="text-xxs">Contact IT for any failed import before retries.</p>
                </div>
                <a href="{{ route('management.erp.imports') }}" class="mt-4 text-primary hover:opacity-90 text-sm flex items-center">
                    View all imports
                    <i data-lucide="arrow-right" class="ml-1 w-5 h-5"></i>
                </a>
            </div>
        </section>

        {{-- Cycles available for export --}}
        <section class="bg-white border rounded-md border-gray-200 px-6 py-4">

            <div class="text-gray-500 mb-2">
                <p class="text-gray-400 text-xs uppercase my-2">Available cycles for export</p>
            </div>

            <div class="mb-4 border-t pt-2 border-gray-100 text-gray-500 flex items-center">
                <i data-lucide="info" class="mr-2 w-3 h-3"></i>
                <p class="text-xxs">Export a reading file of a specific cycle.</p>
            </div>

            <div class="overflow-x-auto border border-gray-100 rounded-md">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xxs tracking-wider">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 text-left font-medium">Cycle</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-medium">Start date</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-medium">End date</th>
                            <th scope="col" class="px-4 py-2.5 text-right font-medium">Export</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-500">
                        @forelse ($billingCycles as $cycle)
                            <tr class="hover:bg-gray-50/70 transition-colors duration-300 ease-out">
                                <td class="px-4 py-2.5 font-medium uppercase whitespace-nowrap">
                                    {{ $cycle->name ?? 'Cycle ' . $cycle->id }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    {{ $cycle->start_date?->format('D d M Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    {{ $cycle->end_date?->format('D d M Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <a
                                        href="{{ route('management.monthly-template.export', $cycle) }}"
                                        class="inline-flex items-center text-xs bg-primary text-blue-50 font-medium p-2 rounded-md hover:bg-primary/90 transition whitespace-nowrap"
                                    >
                                        <i data-lucide="download" class="w-4 h-4 mr-2"></i>
                                        Export Readings
                                        <span class="ml-1.5 px-1.5 py-0.5 bg-white/20 rounded-sm text-[10px] tabular-nums">
                                            {{ number_format($cycle->readings_count ?? $cycle->readings()->count()) }}
                                        </span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                                    No billing cycles available for export.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- Template column reference --}}
    <x-modal name="see-ref" max-width="3xl" :closable="false">
        <div class="p-4">
            <h2 class="text-lg font-semibold text-gray-900">Column Details</h2>
            <p class="text-xxs text-gray-500 mt-1">
                Columns marked <span class="px-1 py-0.5 bg-primary/10 text-primary rounded-sm">Import</span> are required to upload a template.
            </p>
        </div>
        <div class="p-6 max-h-[80dvh] overflow-y-auto thin-scrollbar">
            <div class="bg-gray-100 p-4 rounded-sm max-w-3xl mx-auto">
                <div class="rounded-sm border border-gray-200 bg-white">
                    <div class="grid grid-cols-2 gap-4 bg-gray-200 px-4 py-3 text-xxs font-semibold uppercase tracking-wider text-gray-700 border-b border-gray-300">
                        <div>Header</div>
                        <div>Description</div>
                    </div>

                    @foreach ($templateColumns as [$header, $description, $requiredForImport])
                        <div @class([
                            'grid grid-cols-2 gap-4 px-4 py-3 text-xs text-gray-800',
                            'border-b border-gray-200' => ! $loop->last,
                            'bg-gray-50' => $loop->odd,
                            'bg-white' => $loop->even,
                        ])>
                            <div class="font-medium flex items-center gap-2">
                                {{ $header }}
                                @if ($requiredForImport)
                                    <span class="px-1 py-0.5 bg-primary/10 text-primary rounded-sm text-[10px] font-normal">Import</span>
                                @endif
                            </div>
                            <div>{{ $description }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-modal>

    {{-- Upload template --}}
    <x-modal
        name="upload-file"
        max-width="md"
        :closable="false"
        :show="$errors->hasAny(['billing_cycle_id', 'template'])"
    >
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900">Import Meter readings file</h2>

            <form
                method="POST"
                action="{{ route('management.monthly-template.upload') }}"
                enctype="multipart/form-data"
                class="space-y-5 mt-4"
                x-data="{ submitting: false }"
                x-on:submit="submitting = true"
            >
                @csrf

                <div>
                    <label for="billing_cycle_id" class="block text-sm font-medium text-gray-700">Select Cycle</label>

                    <select
                        id="billing_cycle_id"
                        name="billing_cycle_id"
                        required
                        class="mt-1 block w-full border py-2 border-gray-300 rounded-sm shadow-sm focus:border-primary focus:ring-primary sm:text-sm @error('billing_cycle_id') border-red-500 @enderror"
                    >
                        <option value="">Select billing cycle</option>
                        @foreach ($billingCycles as $cycle)
                            <option value="{{ $cycle->id }}" @selected(old('billing_cycle_id') == $cycle->id)>
                                {{ $cycle->name ?? 'Cycle ' . $cycle->id }}
                            </option>
                        @endforeach
                    </select>
                    @error('billing_cycle_id')
                        <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="template" class="block text-sm font-medium text-gray-700">Choose file</label>
                    <input
                        id="template"
                        type="file"
                        name="template"
                        accept=".xlsx,.xls"
                        required
                        class="mt-1 block w-full rounded-sm border px-3 py-2 border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm @error('template') border-red-500 @enderror"
                    >
                    @error('template')
                        <p class="mt-1 text-xxs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xxs text-gray-500">Supported formats: XLSX, XLS. Maximum size: 10MB.</p>
                </div>

                <div class="flex items-start gap-2 text-xxs text-amber-600 bg-amber-50 rounded-sm p-2">
                    <i data-lucide="info" class="w-3 h-3 mt-0.5 shrink-0"></i>
                    <p>
                        The file is treated as the source of truth: existing accounts and zones are updated with its values,
                        and blank cells clear the stored value. Accounts not in the file are left unchanged.
                    </p>
                </div>

                <div>
                    <button
                        type="submit"
                        x-bind:disabled="submitting"
                        class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary/90 transition disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <span x-text="submitting ? 'Uploading...' : 'Start Import'">Start Import</span>
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    @push('scripts')
        <script>
            /**
             * Alpine component that polls an import's status endpoint and
             * exposes its progress, step, message and outcome.
             *
             * Polling stops when the import completes or fails. Network
             * errors retry with exponential backoff and stop after
             * MAX_CONSECUTIVE_FAILURES, leaving a manual retry.
             *
             * @param {string|null} statusUrl Status endpoint; null disables tracking.
             */
            function importTracker(statusUrl) {
                const POLL_INTERVAL_MS = 3000;
                const MAX_RETRY_DELAY_MS = 30000;
                const MAX_CONSECUTIVE_FAILURES = 5;

                const STATUS_LABELS = {
                    pending: 'Queued',
                    processing: 'Processing',
                    completed: 'Completed',
                    failed: 'Failed',
                };

                return {
                    statusUrl,
                    tracking: statusUrl !== null,
                    status: 'pending',
                    progress: 0,
                    step: '',
                    message: '',
                    error: null,
                    connectionError: null,
                    pollingStopped: false,
                    failures: 0,
                    timer: null,

                    get isRunning() {
                        return this.status === 'pending' || this.status === 'processing';
                    },

                    get statusLabel() {
                        return STATUS_LABELS[this.status] ?? this.status;
                    },

                    get panelClass() {
                        return {
                            completed: 'border-green-100 bg-green-50/40',
                            failed: 'border-red-100 bg-red-50/40',
                        }[this.status] ?? 'border-gray-100 bg-gray-100/70';
                    },

                    get badgeClass() {
                        return {
                            pending: 'bg-amber-50 text-amber-600',
                            processing: 'bg-blue-50 text-blue-600',
                            completed: 'bg-green-100 text-green-700',
                            failed: 'bg-red-100 text-red-700',
                        }[this.status] ?? 'bg-gray-100 text-gray-600';
                    },

                    get barClass() {
                        return {
                            completed: 'bg-green-500',
                            failed: 'bg-red-500',
                        }[this.status] ?? 'bg-primary';
                    },

                    init() {
                        if (this.tracking) {
                            this.poll();
                        }
                    },

                    destroy() {
                        clearTimeout(this.timer);
                    },

                    async poll() {
                        clearTimeout(this.timer);

                        try {
                            const response = await fetch(this.statusUrl, {
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status} ${response.statusText}`);
                            }

                            const data = await response.json();

                            if (!data || typeof data !== 'object') {
                                throw new Error('Invalid response format: expected an object.');
                            }

                            this.apply(data);
                            this.failures = 0;
                            this.connectionError = null;
                        } catch (error) {
                            this.handleConnectionError(error);
                            return;
                        }

                        if (this.isRunning) {
                            this.schedule(POLL_INTERVAL_MS);
                        }
                    },

                    /**
                     * @param {Object} data Status payload with status, progress, step, message and error.
                     */
                    apply(data) {
                        this.status = data.status ?? this.status;
                        this.step = data.step ?? '';
                        this.message = data.message ?? '';
                        this.progress = this.status === 'completed'
                            ? 100
                            : Math.min(Math.max(Math.round(Number(data.progress) || 0), 0), 100);
                        this.error = this.status === 'failed'
                            ? (data.error || data.message || 'Import failed unexpectedly.')
                            : null;
                    },

                    handleConnectionError(error) {
                        this.failures++;

                        console.error('ImportTracker: polling error', {
                            statusUrl: this.statusUrl,
                            attempt: this.failures,
                            error: error.message,
                        });

                        if (this.failures >= MAX_CONSECUTIVE_FAILURES) {
                            this.pollingStopped = true;
                            this.connectionError = 'Lost connection to the import status. The import keeps running on the server.';
                            return;
                        }

                        this.connectionError = 'Connection issue, retrying...';
                        this.schedule(Math.min(POLL_INTERVAL_MS * 2 ** this.failures, MAX_RETRY_DELAY_MS));
                    },

                    schedule(delay) {
                        this.timer = setTimeout(() => this.poll(), delay);
                    },

                    retry() {
                        this.failures = 0;
                        this.pollingStopped = false;
                        this.connectionError = null;
                        this.poll();
                    },

                    dismiss() {
                        clearTimeout(this.timer);
                        this.tracking = false;
                    },
                };
            }
        </script>
    @endpush

</x-app-layout>

{{-- TODO: Link each summary outcome to a filtered list of import_changes once an import detail page exists. --}}