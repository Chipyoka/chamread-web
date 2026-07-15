<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    /**
     * Display the devices management page.
     */
    public function index()
    {

        $devices = Device::orderBy('name')->get();
        return view('system.device.index', compact('devices'));
    }

    /**
     * Store a newly created device.
     */
    public function store(Request $request)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:phone,tablet,laptop,desktop,other',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:devices,serial_number',
            'imei' => 'nullable|string|max:255|unique:devices,imei',
            'imei_2' => 'nullable|string|max:255|unique:devices,imei_2',
            'sim_serial_number' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:255',
            'processor' => 'nullable|string|max:255',
            'ram' => 'nullable|string|max:255',
            'storage_capacity' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string|max:255|unique:devices,mac_address',
            'ip_address' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,lost,damaged,returned',
            'assigned_at' => 'nullable|date',
            'returned_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $device = Device::create($request->all());

        return response()->json([
            'message' => 'Device created successfully',
            'device' => $device
        ], 201);
    }

    /**
     * Update the specified device.
     */
    public function update(Request $request, Device $device)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:phone,tablet,laptop,desktop,other',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255|unique:devices,serial_number,' . $device->id,
            'imei' => 'nullable|string|max:255|unique:devices,imei,' . $device->id,
            'imei_2' => 'nullable|string|max:255|unique:devices,imei_2,' . $device->id,
            'sim_serial_number' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'operating_system' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:255',
            'processor' => 'nullable|string|max:255',
            'ram' => 'nullable|string|max:255',
            'storage_capacity' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string|max:255|unique:devices,mac_address,' . $device->id,
            'ip_address' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,lost,damaged,returned',
            'assigned_at' => 'nullable|date',
            'returned_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $device->update($request->all());

        return response()->json([
            'message' => 'Device updated successfully',
            'device' => $device
        ]);
    }

    /**
     * Remove the specified device.
     */
    public function destroy(Device $device)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $device->delete();
        return response()->json(['message' => 'Device deleted successfully']);
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
            'updates.*.id' => 'required|exists:devices,id',
            'updates.*.status' => 'required|in:active,inactive,loss,damaged,returned',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->updates as $update) {
            Device::where('id', $update['id'])->update([
                'status' => $update['status'],
            ]);
        }

        return response()->json(['message' => 'All changes saved successfully']);
    }
}