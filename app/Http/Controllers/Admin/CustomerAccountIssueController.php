<?php

namespace App\Http\Controllers\Admin;


use App\Exports\CustomerAccountIssuesExport;
use App\Http\Controllers\Controller;
use App\Models\CustomerAccountIssue;
use App\Models\Zone;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CustomerAccountIssueController extends Controller
{
    /**
     * Display a listing of customer account issues.
     */
    public function index(Request $request)
    {
        $query = CustomerAccountIssue::query()
            ->with([
                'zone',
                'reporter',
                'resolver',
            ]);

        // Search
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('meter_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('issue', 'like', "%{$search}%");
            });
        }

        // Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Zone
        if ($request->filled('zone')) {
            $query->where('zone_id', $request->zone);
        }

        // Reporter
        if ($request->filled('reported_by')) {
            $query->where('reported_by', $request->reported_by);
        }

        // Date range
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // Sort newest first
        $query->latest();

        $issues = $query
            ->paginate(20)
            ->withQueryString();

        return view('readings.issue.index', [
            'issues' => $issues,
            'zones' => Zone::orderBy('name')->get(),
            'filters' => $request->only([
                'search',
                'status',
                'zone',
                'reported_by',
                'from',
                'to',
            ]),
        ]);
    }

    /**
     * Export issues to Excel.
     */
    public function export()
    {
        return Excel::download(
            new CustomerAccountIssuesExport(),
            'customer-account-issues.xlsx'
        );
    }

    /**
     * Update issue status.
     */
    public function updateStatus(Request $request, CustomerAccountIssue $customerAccountIssue)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $customerAccountIssue->update([
            'status' => $validated['status'],
            'resolved_by' => $validated['status'] === 'completed'
                ? auth()->id()
                : null,
            'resolved_at' => $validated['status'] === 'completed'
                ? now()
                : null,
        ]);

        return back()->with('success', 'Issue status updated successfully.');
    }
}