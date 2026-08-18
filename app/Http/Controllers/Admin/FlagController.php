<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flag;
use App\Models\FlagRule;
use App\Models\CustomerAccount;
use App\Models\Reading;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Services\FlagService;
use Illuminate\Http\RedirectResponse;

class FlagController extends Controller
{
    /**
     * Display the flags management page.
     */
    public function index()
    {
        $flags = Flag::with('rules')->orderBy('name')->get();
        return view('system.flags.index', compact('flags'));
    }

    /**
     * Store a newly created flag.
     */
    public function store(Request $request)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL', 'IT'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'applies_to' => 'required|in:account,reading,meter_reader',
            'is_system' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate code
        $code = Str::upper(
            Str::snake($request->name)
        );

        $flag = Flag::create([
            'name' => $request->name,
            'code' => $code,
            'applies_to' => $request->applies_to,
            'is_system' => $request->is_system ?? false,
            'active' => $request->active ?? true,
        ]);

        return response()->json([
            'message' => 'Flag created successfully',
            'flag' => $flag
        ], 201);
    }

    /**
     * Update the specified flag.
     */
    public function update(Request $request, Flag $flag)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL', 'IT'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

      $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'applies_to' => 'required|in:account,reading,meter_reader',
            'is_system' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $data['code'] = Str::upper(Str::snake($data['name']));

        // Ensure the generated code is unique (excluding the current flag)
        if (
            Flag::where('code', $data['code'])
                ->where('id', '!=', $flag->id)
                ->exists()
        ) {
            return response()->json([
                'errors' => [
                    'name' => ['A flag with this name already exists.']
                ]
            ], 422);
        }

        $flag->update($data);

        return response()->json([
            'message' => 'Flag updated successfully',
            'flag' => $flag
        ]);
    }

    /**
     * Remove the specified flag.
     */
    public function destroy(Flag $flag)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL', 'IT'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        if ($flag->is_system) {
            return response()->json([
                'error' => 'System flags cannot be deleted.'
            ], 403);
        }

        $flag->delete();
        return response()->json(['message' => 'Flag deleted successfully']);
    }

    /**
     * Store a new flag rule.
     */
    public function storeRule(Request $request)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL', 'IT'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'flag_id' => 'required|exists:flags,id',
            'field' => 'required|string|max:255',
            'operator' => 'required|string|in:>,<,=,!=,>=,<=,contains,is_null,is_not_null',
            'value' => 'nullable|string|max:255',
            'group_key' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // validate submitted column 
        $flag = Flag::findOrFail($request->flag_id);

        $table = match ($flag->applies_to) {
            'reading' => 'readings',
            'account' => 'customer_accounts',
            default => null,
        };

        if (!$table || !Schema::hasColumn($table, $request->field)) {
            return response()->json([
                'errors' => [
                    'column' => [
                        "Column not found."
                    ]
                ]
            ], 422);
        }

        $rule = FlagRule::create([
            'flag_id' => $request->flag_id,
            'field' => $request->field,
            'operator' => $request->operator,
            'value' => $request->value,
            'group_key' => $request->group_key,
            'order' => $request->order ?? 0,
            'active' => $request->active ?? true,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Flag rule created successfully',
            'rule' => $rule
        ], 201);
    }

    /**
     * Update the specified flag rule.
     */
    public function updateRule(Request $request, FlagRule $flagRule)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL', 'IT'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }


        $validator = Validator::make($request->all(), [
            'flag_id' => 'required|exists:flags,id',
            'field' => 'required|string|max:255',
            'operator' => 'required|string|in:>,<,=,!=,>=,<=,contains,is_null,is_not_null',
            'value' => 'nullable|string|max:255',
            'group_key' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // validate submitted column 
        $flag = Flag::findOrFail($request->flag_id);

        $table = match ($flag->applies_to) {
            'reading' => 'readings',
            'account' => 'customer_accounts',
            default => null,
        };

     

        if (!$table || !Schema::hasColumn($table, $request->field)) {
            return response()->json([
                'errors' => [
                    'column' => [
                        "Column not found."
                    ]
                ]
            ], 422);
        }

        $flagRule->update($request->all());

        return response()->json([
            'message' => 'Flag rule updated successfully',
            'rule' => $flagRule
        ]);
    }

    /**
     * Remove the specified flag rule.
     */
    public function destroyRule(FlagRule $flagRule)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL', 'IT'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $flagRule->delete();
        return response()->json(['message' => 'Flag rule deleted successfully']);
    }

    /**
     * Bulk update flag statuses.
     */
    public function bulkUpdate(Request $request)
    {
        if (!in_array(Auth::user()->role, ['ADMIN', 'COMMERCIAL', 'IT'])) {
            return response()->json([
                'error' => 'Insufficient permissions.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:flags,id',
            'updates.*.active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->updates as $update) {
            Flag::where('id', $update['id'])->update([
                'active' => $update['active'],
            ]);
        }

        return response()->json(['message' => 'All changes saved successfully']);
    }

    /**
     * Re-evaluate all accounts and readings against active flag rules.
     */
    public function runEvaluation(FlagService $flagService): RedirectResponse
    {
        CustomerAccount::chunk(200, function ($accounts) use ($flagService) {
            foreach ($accounts as $account) {
                $flagService->reevaluate($account);
            }
        });

        Reading::chunk(200, function ($readings) use ($flagService) {
            foreach ($readings as $reading) {
                $flagService->reevaluate($reading);
            }
        });

        return back()->with('success', 'All flags re-evaluated successfully.');
    }
}