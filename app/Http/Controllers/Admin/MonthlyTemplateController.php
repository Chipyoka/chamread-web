<?php

namespace App\Http\Controllers\Admin;

use App\Exports\MeterReadingsExport;
use App\Exports\MonthlyTemplateExport;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessMonthlyTemplateJob;
use App\Models\BillingCycle;
use App\Models\ImportProcess;
use App\Services\MonthlyTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * ERP ⇄ Chamread exchange: monthly template uploads (processed in the
 * background by ProcessMonthlyTemplateJob), import progress and history,
 * and per-cycle template and reading exports.
 *
 * Uploads and exports are limited to the roles in MANAGE_ROLES.
 */
class MonthlyTemplateController extends Controller
{
    /**
     * Roles allowed to upload templates and export files.
     *
     * @var list<string>
     */
    protected const MANAGE_ROLES = ['ADMIN', 'COMMERCIAL', 'IT'];

    /**
     * Imports listed per page on the import history page.
     */
    protected const IMPORTS_PER_PAGE = 15;

    /**
     * Uploaded templates are kept here on the `local` disk.
     */
    protected const UPLOAD_DIRECTORY = 'imports/monthly-templates';

    /**
     * Display the import/export page.
     *
     * Reading counts are loaded with withCount() so the page does not load
     * every reading of every cycle. The latest import is chosen by ID
     * because progress updates keep changing updated_at.
     */
    public function index(): View
    {
        $billingCycles = BillingCycle::query()
            ->withCount('readings')
            ->orderByDesc('start_date')
            ->get();

        $latestImport = ImportProcess::query()
            ->with(['user:id,name', 'billingCycle:id,name', 'summaries'])
            ->latest('id')
            ->first();

        return view('management.erp.index', compact('billingCycles', 'latestImport'));
    }

    /**
     * Display the paginated import history, newest first.
     */
    public function showImports(): View
    {
        $imports = ImportProcess::query()
            ->with(['user:id,name', 'billingCycle:id,name', 'summaries'])
            ->latest('id')
            ->paginate(self::IMPORTS_PER_PAGE);

        return view('management.erp.all-imports', compact('imports'));
    }

    /**
     * Store an uploaded template and queue it for processing.
     *
     * Redirects to the import page with `process_id` flashed so the
     * progress tracker can follow the new import.
     *
     * @param  Request  $request  Carries `billing_cycle_id` and the `template` file.
     */
    public function upload(Request $request): RedirectResponse
    {
        if (! $this->canManage($request)) {
            return $this->denied();
        }

        $validated = $request->validate([
            'billing_cycle_id' => ['required', 'integer', 'exists:billing_cycles,id'],
            'template' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $file = $request->file('template');

        $path = $file->store(self::UPLOAD_DIRECTORY, 'local');

        if ($path === false) {
            return back()->with('error', 'Failed to store the uploaded file. Please try again.');
        }

        $process = ImportProcess::create([
            'user_id' => $request->user()->id,
            'billing_cycle_id' => $validated['billing_cycle_id'],
            'file_name' => $file->getClientOriginalName(),
            'status' => ImportProcess::STATUS_PENDING,
            'progress' => 0,
            'current_step' => 'Waiting...',
        ]);

        ProcessMonthlyTemplateJob::dispatch($process, $path);

        return redirect()
            ->route('management.erp.index')
            ->with('process_id', $process->id)
            ->with('success', 'Import started successfully.');
    }

    /**
     * Return an import's progress for the polling tracker.
     *
     * Polled every few seconds, so it returns stored columns only and
     * loads no relations.
     *
     * @param  ImportProcess  $process  Import being tracked.
     * @return JsonResponse {id, status, progress, step, message, error}; `error` is set only when the import failed.
     */
    public function status(ImportProcess $process): JsonResponse
    {
        $failed = $process->status === ImportProcess::STATUS_FAILED;

        return response()->json([
            'id' => $process->id,
            'status' => $process->status,
            'progress' => (int) $process->progress,
            'step' => $process->current_step,
            'message' => $process->message,
            'error' => $failed ? ($process->message ?: 'Import failed unexpectedly.') : null,
        ]);
    }

    /**
     * Download the monthly ERP template rebuilt from current customer accounts.
     *
     * Failures are logged with full detail and reported to the user with a
     * generic message, so internal errors are not exposed.
     *
     * @param  Request                 $request       Current request, used for authorization.
     * @param  BillingCycle            $billingCycle  Cycle the template is exported for.
     * @param  MonthlyTemplateService  $service       Builds the export rows.
     */
    public function download(
        Request $request,
        BillingCycle $billingCycle,
        MonthlyTemplateService $service
    ): BinaryFileResponse|RedirectResponse {
        if (! $this->canManage($request)) {
            return $this->denied();
        }

        try {
            return Excel::download(
                new MonthlyTemplateExport($service->exportRows($billingCycle)),
                $this->exportFileName('meter_reading_file', $billingCycle)
            );
        } catch (Throwable $e) {
            Log::error('Monthly template download failed.', [
                'billing_cycle_id' => $billingCycle->id,
                'exception' => $e,
            ]);

            return back()->with('error', 'Failed to download the template. Please contact IT if this continues.');
        }
    }

    /**
     * Export the meter readings captured in a closed billing cycle.
     *
     * @param  Request       $request       Current request, used for authorization.
     * @param  BillingCycle  $billingCycle  Cycle to export; must be closed.
     */
    public function exportExcel(Request $request, BillingCycle $billingCycle): BinaryFileResponse|RedirectResponse
    {
        if (! $this->canManage($request)) {
            return $this->denied();
        }

        if ($billingCycle->status !== 'closed') {
            return back()->with('error', 'Readings can only be exported after the cycle is closed.');
        }

        return Excel::download(
            new MeterReadingsExport($billingCycle->id),
            $this->exportFileName('meter_readings', $billingCycle)
        );
    }

    /**
     * Whether the current user may upload templates and export files.
     *
     * @param  Request  $request  Current request.
     */
    protected function canManage(Request $request): bool
    {
        return in_array($request->user()?->role, self::MANAGE_ROLES, true);
    }

    /**
     * Response for users without the required role.
     */
    protected function denied(): RedirectResponse
    {
        return back()->with('error', 'Insufficient permissions.');
    }

    /**
     * Build a filesystem-safe export file name.
     *
     * Cycle names are slugged because they may contain spaces or slashes,
     * which break download file names.
     *
     * @param  string        $prefix        File name prefix.
     * @param  BillingCycle  $billingCycle  Cycle being exported.
     * @return string File name ending in `.xlsx`.
     */
    protected function exportFileName(string $prefix, BillingCycle $billingCycle): string
    {
        $cycle = Str::slug($billingCycle->name ?? '', '_') ?: 'cycle_' . $billingCycle->id;

        return sprintf('%s_%s_%s.xlsx', $prefix, $cycle, now()->format('Y-m-d_His'));
    }
}

// TODO: Replace canManage() with a Gate or policy (for example `manage-erp`) applied as route middleware, and use it to restrict status() to users who may view imports.