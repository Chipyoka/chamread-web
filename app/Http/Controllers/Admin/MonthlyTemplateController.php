<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Jobs\ProcessMonthlyTemplateJob;
use App\Models\BillingCycle;
use App\Models\ImportProcess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Exports\MonthlyTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Services\MonthlyTemplateService;

class MonthlyTemplateController extends Controller
{
    /**
     * Display the monthly import/export page.
     */
    public function index(): View
    {
        return view('management.erp.index', [
            'billingCycles' => BillingCycle::orderByDesc('start_date')->get(),
            'latestImports' => ImportProcess::orderByDesc('updated_at')->limit(5)->get(),
        ]);
    }

    /**
     * Upload a monthly template and start background processing.
     */
    public function upload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'billing_cycle_id' => ['required', 'exists:billing_cycles,id'],
            'template' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240', // 10 MB
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Store uploaded file
        |--------------------------------------------------------------------------
        */

        $file = $request->file('template');

        $path = $file->store('imports/monthly-templates');

        /*
        |--------------------------------------------------------------------------
        | Create import tracking record
        |--------------------------------------------------------------------------
        */

        $process = ImportProcess::create([
            'user_id' => auth()->id(),
            'billing_cycle_id' => $validated['billing_cycle_id'],
            'file_name' => $file->getClientOriginalName(),
            'status' => ImportProcess::STATUS_PENDING,
            'progress' => 0,
            'current_step' => 'Waiting...',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Dispatch import job
        |--------------------------------------------------------------------------
        */

        ProcessMonthlyTemplateJob::dispatch(
            $process,
            $path
        );

        return redirect()
            ->route('management.erp.index')
            ->with('process_id', $process->id)
            ->with('success', 'Import started successfully.');
    }

    /**
     * Return current import progress.
     *
     * Used by the frontend polling every few seconds.
     */
    public function status(ImportProcess $process): JsonResponse
    {
        return response()->json([
            'id' => $process->id,
            'status' => $process->status,
            'progress' => $process->progress,
            'step' => $process->current_step,
            'message' => $process->message,
        ]);
    }

    
    /**
     * Download the reconstructed monthly template.
     */
    public function download(
        BillingCycle $billingCycle,
        MonthlyTemplateService $service
    ) {
        try {
            $rows = $service->exportRows(
                $billingCycle
            );

            return Excel::download(
                new MonthlyTemplateExport($rows),
                sprintf(
                    'meter_reading_file_%s.xlsx',
                    $billingCycle->name
                )
            );

        } catch (\Exception $e) {
            \Log::error('Monthly template download failed: ' . $e->getMessage(), [
                'billing_cycle_id' => $billingCycle->name,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to download template: ' . $e->getMessage());
        }
    }
}