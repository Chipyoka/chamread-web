<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccountIssue;
use App\Models\CustomerAccount;
use App\Models\Zone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class CustomerAccountIssueController extends Controller
{
    /**
     * Store a newly created customer account issue report.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Start transaction for data integrity
        DB::beginTransaction();
        
        try {
            // Log the start of the request
            Log::info('Customer Account Issue Report Submission Started', [
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'account_number' => $request->input('account_number'),
            ]);

            // Step 1: Validate the request
            $validator = $this->validateReportRequest($request);
            
            if ($validator->fails()) {
                Log::warning('Customer Account Issue Report Validation Failed', [
                    'errors' => $validator->errors()->toArray(),
                    'user_id' => auth()->id(),
                ]);
                
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Step 2: Get authenticated user
            $user = auth()->user();
            if (!$user) {
                Log::error('Unauthenticated user attempted to submit issue report');
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user'
                ], 401);
            }

            // Step 3: Process and validate the account
            $accountData = $this->processAccountData($request, $user);
            Log::info('Account data processed', array_merge(
                ['user_id' => $user->id],
                $accountData
            ));

            // Step 4: Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo') || $request->input('photo')) {
                $photoPath = $this->handlePhotoUpload($request, $user);
                
                if ($photoPath === false) {
                    Log::error('Photo upload failed', ['user_id' => $user->id]);
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload photo. Please ensure the file is a valid image.'
                    ], 400);
                }
                
                Log::info('Photo uploaded successfully', [
                    'user_id' => $user->id,
                    'photo_path' => $photoPath,
                ]);
            } else {
                Log::warning('No photo provided for issue report', ['user_id' => $user->id]);
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Photo evidence is required'
                ], 422);
            }

            // Step 5: Create the issue report
            $issue = $this->createIssueReport($request, $user, $accountData, $photoPath);
            
            Log::info('Customer Account Issue Report Created', [
                'issue_id' => $issue->id,
                'user_id' => $user->id,
                'account_number' => $issue->account_number,
                'issue_type' => $issue->issue,
                'status' => $issue->status,
            ]);

            // Step 6: Commit the transaction
            DB::commit();

            // Step 7: Return success response
            return response()->json([
                'success' => true,
                'message' => 'Report submitted successfully',
                'data' => [
                    'id' => $issue->id,
                    'status' => $issue->status,
                    'created_at' => $issue->created_at->toIso8601String(),
                ]
            ], 201);

        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            // Log the error with full context
            Log::error('Customer Account Issue Report Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id() ?? 'unauthenticated',
                'request_data' => $request->except(['photo']),
            ]);

            // Return error response
            return response()->json([
                'success' => false,
                'message' => config('app.debug') 
                    ? $e->getMessage() 
                    : 'Failed to submit report. Please try again later.',
            ], 500);
        }
    }

    /**
     * Validate the report request.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validateReportRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'meter_number' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'issue' => [
                'required',
                'string',
                Rule::in([
                    'account_missing',
                    'meter_no_account',
                    'meter_missing',
                    'meter_mismatch',
                    'wrong_name',
                    'wrong_phone',
                    'wrong_account_number',
                    'meter_inaccessible',
                    'vacant_property',
                    'illegal_connection',
                    'duplicate_account',
                    'inactive_meter_active',
                    'other',
                ]),
            ],
            'comment' => 'nullable|string|max:1000',
            'photo' => 'required|file|image|max:5120', // 5MB max
            'gps_latitude' => 'required|numeric|between:-90,90',
            'gps_longitude' => 'required|numeric|between:-180,180',
        ]);

        return $validator;
    }

    /**
     * Process and validate account data.
     *
     * @param Request $request
     * @param User $user
     * @return array
     */
    private function processAccountData(Request $request, User $user): array
    {
        $accountData = [];
        $accountNumber = $request->input('account_number');
        $account = CustomerAccount::where('account_number', $accountNumber)->first();

        // Determine zone_id
        if ($account) {
            $accountData['zone_id'] = $account->zone_id;
            $accountData['exists_in_system'] = true;
            
            // If account exists, ensure data consistency
            if (!$request->filled('customer_name')) {
                $accountData['customer_name'] = $account->customer_name;
            }
            if (!$request->filled('meter_number')) {
                $accountData['meter_number'] = $account->meter_number;
            }
            
            Log::info('Account found in system', [
                'account_number' => $accountNumber,
                'zone_id' => $account->zone_id,
                'user_id' => $user->id,
            ]);
        } else {
            // Account not found - find zone based on reported location or set to null
            $accountData['zone_id'] = null;
            $accountData['exists_in_system'] = false;
            
            // Use provided data from request
            if ($request->filled('customer_name')) {
                $accountData['customer_name'] = $request->input('customer_name');
            }
            if ($request->filled('meter_number')) {
                $accountData['meter_number'] = $request->input('meter_number');
            }
            
            Log::warning('Account not found in system', [
                'account_number' => $accountNumber,
                'user_id' => $user->id,
            ]);
        }

        return $accountData;
    }

    /**
     * Handle photo upload with proper validation and storage.
     *
     * @param Request $request
     * @param User $user
     * @return string|false
     */
    private function handlePhotoUpload(Request $request, User $user)
    {
        try {
            $photo = $request->file('photo');
            
            // Log file information for debugging
            Log::debug('Processing photo upload', [
                'user_id' => $user->id,
                'original_name' => $photo->getClientOriginalName(),
                'size' => $photo->getSize(),
                'mime_type' => $photo->getMimeType(),
            ]);

            // Validate file type as an extra precaution
            if (!$photo->isValid()) {
                Log::error('Invalid photo file', ['user_id' => $user->id]);
                return false;
            }

            // Generate unique filename
            $timestamp = Carbon::now()->format('Ymd_His');
            $extension = $photo->getClientOriginalExtension() ?: 'jpg';
            $filename = "issue_report_{$user->id}_{$timestamp}.{$extension}";

            // Store with specific path structure
            $path = $photo->storeAs(
                "customer-issues/{$user->id}/" . Carbon::now()->format('Y/m/d'),
                $filename,
                'public'
            );

            if (!$path) {
                Log::error('Failed to store photo', ['user_id' => $user->id]);
                return false;
            }

            Log::info('Photo stored successfully', [
                'user_id' => $user->id,
                'path' => $path,
                'filename' => $filename,
            ]);

            return $path;

        } catch (\Exception $e) {
            Log::error('Photo upload exception', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
            return false;
        }
    }

    /**
     * Create the customer account issue report.
     *
     * @param Request $request
     * @param User $user
     * @param array $accountData
     * @param string|null $photoPath
     * @return CustomerAccountIssue
     */
    private function createIssueReport(Request $request, User $user, array $accountData, ?string $photoPath): CustomerAccountIssue
    {
        // Prepare the data for insertion
        $data = [
            'zone_id' => $accountData['zone_id'] ?? null,
            'reported_by' => $user->id,
            'resolved_by' => null,
            'account_number' => $request->input('account_number'),
            'customer_name' => $request->input('customer_name') ?? $accountData['customer_name'] ?? null,
            'meter_number' => $request->input('meter_number') ?? $accountData['meter_number'] ?? null,
            'phone' => $request->input('phone'),
            'issue' => $request->input('issue'),
            'comment' => $request->input('comment'),
            'status' => 'pending',
            'resolved_at' => null,
            'photo' => $photoPath,
            'gps_latitude' => $request->input('gps_latitude'),
            'gps_longitude' => $request->input('gps_longitude'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        // Log the data being saved (without sensitive info)
        Log::debug('Creating issue report with data', [
            'account_number' => $data['account_number'],
            'issue' => $data['issue'],
            'zone_id' => $data['zone_id'],
            'reported_by' => $data['reported_by'],
            'has_photo' => !is_null($photoPath),
            'has_gps' => !is_null($data['gps_latitude']) && !is_null($data['gps_longitude']),
        ]);

        // Create and return the model
        return CustomerAccountIssue::create($data);
    }

    /**
     * Get account details for the check account endpoint.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkAccount(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'accountNumber' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'exists' => false,
                    'message' => 'Account number is required',
                ], 422);
            }

            $accountNumber = $request->input('accountNumber');
            
            Log::info('Account check requested', [
                'user_id' => auth()->id(),
                'account_number' => $accountNumber,
            ]);

            $account = CustomerAccount::with('zone')
                ->where('account_number', $accountNumber)
                ->first();

            if ($account) {
                Log::info('Account found for check', [
                    'user_id' => auth()->id(),
                    'account_number' => $accountNumber,
                    'account_id' => $account->id,
                ]);

                return response()->json([
                    'exists' => true,
                    'account' => [
                        'account_number' => $account->account_number,
                        'customer_name' => $account->customer_name,
                        'meter_number' => $account->meter_number ?? null,
                        'phone' => $account->phone ?? null,
                        'zone_id' => $account->zone_id,
                        'zone_name' => $account->zone?->name ?? null,
                    ],
                ]);
            }

            Log::warning('Account not found for check', [
                'user_id' => auth()->id(),
                'account_number' => $accountNumber,
            ]);

            return response()->json([
                'exists' => false,
                'message' => 'Account not found',
            ]);

        } catch (\Exception $e) {
            Log::error('Account check failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'exists' => false,
                'message' => 'Failed to check account',
            ], 500);
        }
    }
}