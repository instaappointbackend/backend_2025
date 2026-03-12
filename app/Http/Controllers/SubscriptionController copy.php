<?php

namespace App\Http\Controllers;

use App\Enums\PlanEnum;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PhonePeService;
use App\Traits\SendSmsTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    // 1 month before
    // 15 days
    // 1 week before
    // 1 day before
    // same day
    // in every month send notification

    use SendSmsTrait;

    private $phonePeService;

    public function __construct(PhonePeService $phonePeService)
    {
        $this->phonePeService = $phonePeService;
    }

    public function index(Request $request)
    {
        if ($request->status === 'error') {
            session()->flash('error', $request->message);
        }

        $selected_plan = $request->get('plan');

        return view('subscriptions.index', compact('selected_plan'));
    }

    public function subscribe(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'plan_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            // 'amount'    => 'required|numeric|min:1',
            // 'duration' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();
            // find out user
            $mobile = $request->get('mobile');
            $name = $request->get('name');
            $email = $request->get('email');
            $plan_name = $request->get('plan_name');

            // $user = User::where('mobile', $mobile)->where('email', $email)->first();
            $plan = PlanEnum::getPlanByTitle($plan_name);
            $amount = $plan['discounted_price'];

            if ($plan['title'] !== $plan_name) {
                return redirect()->back()
                    ->with('error', 'Selected plan not found. Please choose a valid plan from the available options');
            }

            // Try finding user by mobile or email
            $user = User::where('mobile', $mobile)->first();

            // Step 3: If user exists but has a different role
            // if ($user && $user?->role == 'vendor') {
            // if ($user) {
            //     return redirect()->back()
            //         ->with('error', 'User already exists with this mobile/email but has a different role.');
            // }

            if (! $user) {
                // Neither exists, create new
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'mobile' => $mobile,
                    'role' => 'vendor',
                    'password' => bcrypt(123456),
                ]);
            }

            $paymentResponse = $this->phonePeService->initiatePayment(
                $amount,
                $user->id,
                $plan_name
            );

            if ($paymentResponse['success']) {
                $transactionId = $paymentResponse['merchant_transaction_id'];
                // Store transaction info in session
                session([
                    'payment_transaction_id' => $transactionId,
                    'payment_amount' => $amount,
                    'payment_name' => $name,
                    'payment_email' => $email,
                    'payment_phone' => $mobile,
                    'plan_name' => $plan_name,
                ]);

                $subscription = new Subscription;
                $subscription->user_id = $user->id;
                $subscription->plan_name = $plan_name;
                $subscription->amount = $amount;
                $subscription->payment_status = 'pending';
                $subscription->transaction_id = $transactionId;

                // Automatically set start and expiry
                $subscription->setDuration(1, 'year');

                $subscription->save();

                DB::commit();

                // Redirect user to PhonePe payment page
                return redirect()->away($paymentResponse['payment_url']);
            }

            // Failed
            return back()->with('error', 'Failed to initialize payment: '.($paymentResponse['message'] ?? 'Unknown error'));
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info('Subscription  failed'.$th->getMessage());

            return back()->with('error', 'Something went wrong');
            // throw $th;
        }
    }

    public function callback(Request $request)
    {

        Log::info('PhonePe callback received', [
            'data' => $request->all(),
            'headers' => $request->header(),
        ]);

        // Get the transaction ID from the callback
        $transactionId = $request->input('transactionId', session('payment_transaction_id'));

        $result = Subscription::with('user')->where('transaction_id', $transactionId)->first();

        if (! $transactionId) {
            Log::error('Error processing PhonePe callback', [
                'transaction_id' => 'N/A',
                'error' => 'subscription id not found, Invalid transaction ID',
            ]);

            return redirect()->route('subscription.status', ['status' => 'success', 'message' => 'Invalid transaction ID']);
        }

        if (! $result) {
            Log::error('Error processing PhonePe callback', [
                'transaction_id' => 'N/A',
                'error' => 'subscription id not found in db',
            ]);

            return redirect()->route('subscription.status', ['status' => 'success', 'message' => 'Subscription not found']);
        }

        try {
            // Retrieve payment status
            $status = $this->checkPaymentStatus($transactionId);

            // Determine if payment was successful
            $isSuccessful = $status['success'] ?? false;
            $isCompleted = ($status['paymentState'] ?? null) === 'COMPLETED';

            // dd($status, $isSuccessful, $isCompleted);
            if ($isSuccessful && $isCompleted) {
                // send sms
                $mobile = $result->user->mobile;
                $this->sendSms($mobile, 'subscription_success', []);

                // Optional: Save completed payment to the database
                $result->payment_status = 'completed';
                $result->status = 'active';
                $result->response = $status['responseData'];

                $result->save();

                return redirect()->route('subscription.status', ['status' => 'success']);
            }

            Log::error('Error processing PhonePe callback', [
                'transaction_id' => $transactionId ?? 'N/A',
                'error' => 'check payment status failed',
            ]);

            $result->payment_status = 'failed';
            $result->save();

            $message = 'Payment was not successful. Please try again.';

            return redirect()->route('subscription.status', ['status' => 'failed', 'message' => $message]);

            // Handle failed or pending payments
        } catch (\Throwable $e) {
            // Catch any exception and log it
            Log::error('Error processing PhonePe callback', [
                'transaction_id' => $transactionId ?? 'N/A',
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('subscription.status', ['status' => 'success', 'message' => 'An unexpected error occurred while processing your payment. Please contact support']);
        }
    }

    private function checkPaymentStatus($transactionId)
    {
        try {
            $response = $this->phonePeService->checkPaymentStatus($transactionId);

            // Parse the response
            $responseData = $response->json();

            // Log response
            Log::info('PhonePe: Status check response', [
                'transaction_id' => $transactionId,
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            // Check HTTP success
            if (! $response->successful()) {
                return [
                    'success' => false,
                    'transactionId' => $transactionId,
                    'paymentState' => 'HTTP_ERROR',
                    'message' => 'Failed to connect to PhonePe API',
                    'responseCode' => $response->status(),
                ];
            }

            // Determine payment state
            $data = $responseData['data'] ?? [];
            $code = $responseData['code'] ?? null;
            // dd($data, $code);
            $paymentState = $data['paymentState']
                ?? match ($code) {
                    'PAYMENT_SUCCESS' => 'COMPLETED',
                    'PAYMENT_ERROR' => 'FAILED',
                    default => 'PENDING',
                };

            // Build unified response
            return [
                'success' => (bool) ($responseData['success'] ?? false),
                'transactionId' => $transactionId,
                'paymentState' => $paymentState,
                'amount' => isset($data['amount']) ? $data['amount'] / 100 : 0,
                'providerReferenceId' => $data['providerReferenceId'] ?? null,
                'responseCode' => $code,
                'message' => $responseData['message'] ?? 'Payment status retrieved successfully',
                'responseData' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('PhonePe: Exception during status check', [
                'transaction_id' => $transactionId ?? 'N/A',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'transactionId' => $transactionId,
                'paymentState' => 'ERROR',
                'message' => 'Error checking payment status: '.$e->getMessage(),
            ];
        }
    }

    public function checkStatus($merchantTransactionId)
    {
        // Manual status check endpoint
        $statusResponse = $this->phonePeService->checkPaymentStatus($merchantTransactionId);

        Log::info('Manual Status Check:', $statusResponse);

        $subscription = Subscription::where('phonepe_merchant_transaction_id', $merchantTransactionId)->first();

        if (! $subscription) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        if ($statusResponse['success'] && $statusResponse['code'] === 'PAYMENT_SUCCESS') {
            $subscription->update([
                'status' => 'completed',
                'transaction_id' => $statusResponse['data']['transactionId'],
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment successful',
                'subscription' => $subscription,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment not completed',
            'status' => $statusResponse['code'] ?? 'UNKNOWN',
        ]);
    }

    public function subscriptionStatus(Request $request, $status)
    {

        if ($status === 'success') {
            $paymentDetails = [
                'transaction_id' => session('payment_transaction_id'),
                'amount' => session('payment_amount'),
                'plan_name' => session('plan_name'),
                'name' => session('payment_name'),
                'email' => session('payment_email'),
                'phone' => session('payment_phone'),
            ];

            // dd($paymentDetails);
            return view('subscriptions.success', compact('paymentDetails', 'status'));
        }

        if ($status === 'failed') {
            $message = $request->get('message');

            return view('subscriptions.failed', [
                'message' => $message,
            ]);
        }
    }
}
