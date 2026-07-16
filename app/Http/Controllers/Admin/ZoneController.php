<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Zone;

use App\Models\CsaAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ZonesImport;

class ZoneController extends Controller
{
    /**
     * Display the zones management page.
     */
    public function index()
    {
 
        $zones = Zone::withCount('assignments')->orderBy('province')->orderBy('district')->orderBy('code')->get();
        return view('management.zones.index', compact('zones'));
    }

    /**
     * Store a newly created zone.
     */
    public function store(Request $request)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:255|unique:zones,code',
            'district' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Automatically set the name from the code
        $data['name'] = $data['code'];

        $zone = Zone::create($data);



        return response()->json([
            'message' => 'Zone created successfully',
            'zone' => $zone
        ], 201);
    }

    /**
     * Update the specified zone.
     */
    public function update(Request $request, Zone $zone)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:255|unique:zones,code,' . $zone->id,
            'district' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

         if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Automatically set the name from the code
        $data['name'] = $data['code'];

       

        $zone->update($data);

        return response()->json([
            'message' => 'Zone updated successfully',
            'zone' => $zone
        ]);
    }

    /**
     * Remove the specified zone.
     */
    public function destroy(Zone $zone)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        if (CsaAssignment::where('zone_id', $zone->id)->exists()) {
        return response()->json([
            'message' => 'This zone cannot be deleted because it has active assignments.'
        ], 422);
}

        $zone->delete();
        return response()->json(['message' => 'Zone deleted successfully']);
    }

    /**
     * Bulk update statuses.
     */
    public function bulkUpdate(Request $request)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:zones,id',
            'updates.*.status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->updates as $update) {
            Zone::where('id', $update['id'])->update([
                'status' => $update['status'],
            ]);
        }

        return response()->json(['message' => 'All changes saved successfully']);
    }

    /**
     * Import zones from Excel file.
     */
    public function import(Request $request)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $import = new ZonesImport();
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $errorCount = $import->getErrorCount();
            $errors = $import->getErrors();

            if ($errorCount > 0) {
                return response()->json([
                    'message' => "{$successCount} successful and {$errorCount} errors",
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'errors' => $errors
                ], 207); // Multi-status
            }

            return response()->json([
                'message' => "Successfully imported {$successCount} zones",
                'success_count' => $successCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to import file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download sample Excel template.
     */
    public function downloadTemplate()
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL'])) {
            return redirect()->back()
                ->with('error', 'Insufficient permissions.');
        }

        // Create a simple CSV template
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="zones_import_template.csv"',
        ];

        $columns = ['PROVINCE', 'DISTRICT', 'ZONE'];
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Add sample data
            fputcsv($file, ['NORTHERN', 'KASAMA CENTRAL', 'KCT011']);
            fputcsv($file, ['NORTHERN', 'KASAMA CENTRAL', 'KCT012']);
            fputcsv($file, ['MUCHINGA', 'MPIKA', 'MPK03']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}