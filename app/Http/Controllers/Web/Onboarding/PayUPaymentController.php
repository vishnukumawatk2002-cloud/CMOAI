<?php

namespace App\Http\Controllers\Web\Onboarding;

use App\Application\Services\Brand\BrandService;
use App\Application\Services\Payment\PayUService;
use App\Domain\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayUPaymentController extends Controller
{
    public function __construct(
        private readonly PayUService $payu,
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly BrandService $brands,
    ) {
    }

    public function success(Request $request): RedirectResponse
    {
        $payload = $request->all();

        // PayU sometimes posts nested/odd keys — keep scalar callback fields only.
        if ($request->isMethod('get') && $payload === [] && $request->query()) {
            $payload = $request->query();
        }

        if (! $this->payu->verifyResponse($payload)) {
            \Illuminate\Support\Facades\Log::warning('PayU success callback rejected', [
                'method' => $request->method(),
                'has_hash' => $request->filled('hash'),
                'status' => $request->input('status'),
                'txnid' => $request->input('txnid'),
            ]);

            // Still try to recover session from order if txnid exists (user landed without hash).
            $order = Order::query()
                ->where('transaction_id', $request->input('txnid'))
                ->where('payment_provider', 'payu')
                ->first();

            if ($order?->user_id) {
                $user = User::query()->find($order->user_id);

                if ($user) {
                    Auth::login($user, true);
                    $request->session()->regenerate();

                    return redirect()
                        ->route('onboarding.plan')
                        ->with('error', 'Payment verification failed. PayU dashboard me Key + Salt (V1/V2) check karo, ya naya payment try karo.');
                }
            }

            return redirect()
                ->route('login')
                ->with('error', 'Payment verification failed. Please login and try again.');
        }

        if (strtolower((string) ($payload['status'] ?? $request->input('status'))) !== 'success') {
            return $this->markFailedAndRedirect($request);
        }

        $order = Order::query()
            ->where('transaction_id', $payload['txnid'] ?? $request->input('txnid'))
            ->where('payment_provider', 'payu')
            ->first();

        if (! $order) {
            return redirect()
                ->route('login')
                ->with('error', 'Payment order not found. Please login and contact support if amount was deducted.');
        }

        $user = User::query()->find($order->user_id);

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'Payment received but user account was not found.');
        }

        // PayU cross-site POST often drops the session cookie — re-login from verified order.
        Auth::login($user, true);
        $request->session()->regenerate();

        if ($order->isPaid()) {
            return $this->redirectAfterPaymentSuccess(
                $user,
                $payload,
                'Your plan is already active.',
            );
        }

        DB::transaction(function () use ($order, $user, $payload) {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $plan = $order->plan()->firstOrFail();
            $existing = $this->subscriptions->activeForUser($user);

            if ($existing) {
                $existing->update([
                    'plan_id' => $plan->id,
                    'billing_cycle' => $order->billing_cycle,
                    'status' => 'active',
                    'payment_provider' => 'payu',
                    'provider_subscription_id' => $order->transaction_id,
                    'starts_at' => now(),
                    'trial_ends_at' => null,
                ]);

                $order->update(['subscription_id' => $existing->id]);
            } else {
                $subscription = $this->subscriptions->createSubscription($user, $plan, [
                    'billing_cycle' => $order->billing_cycle,
                    'status' => 'active',
                    'payment_provider' => 'payu',
                    'provider_subscription_id' => $order->transaction_id,
                    'starts_at' => now(),
                ]);

                $order->update(['subscription_id' => $subscription->id]);
            }

            $brandId = (int) ($payload['udf4'] ?? request()->input('udf4') ?? 0);
            if ($brandId > 0) {
                $user->brands()->where('id', $brandId)->update(['plan_id' => $plan->id]);
            }
        });

        return $this->redirectAfterPaymentSuccess(
            $user,
            $payload,
            'Payment successful! Your plan is now active.',
        );
    }

    /**
     * Restore the brand that started payment (survives PayU session loss via udf4)
     * and send the user into that brand's setup/workspace.
     *
     * @param  array<string, mixed>  $payload
     */
    private function redirectAfterPaymentSuccess(User $user, array $payload, string $message): RedirectResponse
    {
        $brandId = (int) ($payload['udf4'] ?? request()->input('udf4') ?? 0);

        if ($brandId > 0 && $user->brands()->where('id', $brandId)->exists()) {
            $this->brands->switchBrand($user, $brandId);
        } else {
            $brand = $this->brands->currentBrand($user);
            $brandId = $brand?->id ?? 0;
        }

        if ($brandId > 0) {
            $brand = $user->brands()->where('id', $brandId)->first();

            if ($brand && ! $brand->isSetupComplete()) {
                return redirect()
                    ->route('onboarding.wizard')
                    ->with('success', $message);
            }

            return redirect()
                ->route('app.brand.dashboard')
                ->with('success', $message);
        }

        return redirect()
            ->route('app.dashboard')
            ->with('success', $message);
    }

    public function failure(Request $request): RedirectResponse
    {
        return $this->markFailedAndRedirect($request);
    }

    private function markFailedAndRedirect(Request $request): RedirectResponse
    {
        $order = Order::query()
            ->where('transaction_id', $request->input('txnid'))
            ->where('payment_provider', 'payu')
            ->first();

        if ($order && $order->status === 'pending') {
            $order->update(['status' => 'failed']);
        }

        if ($order?->user_id) {
            $user = User::query()->find($order->user_id);

            if ($user) {
                Auth::login($user, true);
                $request->session()->regenerate();

                return redirect()
                    ->route('onboarding.plan')
                    ->with('error', 'Payment failed or was cancelled. Please try again.');
            }
        }

        return redirect()
            ->route('login')
            ->with('error', 'Payment failed or was cancelled. Please login and try again.');
    }
}
