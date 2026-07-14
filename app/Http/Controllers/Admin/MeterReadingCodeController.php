<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeterReadingCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Auth;

class MeterReadingCodeController extends Controller
{
    /**
     * Display the meter reading codes management page.
     */
    public function index()
    {
        $codes = MeterReadingCode::orderBy('code')->get();
        return view('system.mrc.index', compact('codes'));
    }

    /**
     * Store a newly created code.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:255|unique:meter_reading_codes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:reading,explanation,billing,other',
            'status' => 'required|in:active,inactive',
        ]);

        if (!in_array(Auth::user()->role, ['ADMIN','COMMERCIAL'])) {
                return response()->json([
                    'error' => 'Insufficient permissions.'
                ], 403);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $code = MeterReadingCode::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Code created successfully',
            'code' => $code
        ], 201);
    }

    /**
     * Update the specified code.
     */
    public function update(Request $request, MeterReadingCode $meterReadingCode)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:255|unique:meter_reading_codes,code,' . $meterReadingCode->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:reading,explanation,billing,other',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        };

        if (!in_array(Auth::user()->role, ['ADMIN','COMMERCIAL'])) {
                return response()->json([
                    'error' => 'Insufficient permissions.'
                ], 403);
        }
        

        $meterReadingCode->update([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Code updated successfully',
            'code' => $meterReadingCode
        ]);
    }

    /**
     * Remove the specified code.
     */
    public function destroy(MeterReadingCode $meterReadingCode)
    {
        if (!in_array(Auth::user()->role, ['ADMIN','IT'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $meterReadingCode->delete();

        return response()->json(['message' => 'Code deleted successfully']);
    }

    /**
     * Bulk update types and statuses.
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:meter_reading_codes,id',
            'updates.*.type' => 'required|in:reading,explanation,billing,other',
            'updates.*.status' => 'required|in:active,inactive',
        ]);

        if (!in_array(Auth::user()->role, ['ADMIN','COMMERCIAL'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->updates as $update) {
            MeterReadingCode::where('id', $update['id'])->update([
                'type' => $update['type'],
                'status' => $update['status'],
            ]);
        }

        return response()->json(['message' => 'All changes saved successfully']);
    }
}