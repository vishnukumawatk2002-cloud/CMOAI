<?php

namespace App\Http\Controllers\Web\Onboarding;

use App\Application\Services\Brand\BrandService;
use App\Domain\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly BrandService $brands,
    ) {
    }

    public function index(Request $request): View
    {
        $subscription = $this->subscriptions->activeForUser($request->user());

        return view('onboarding.plans', [
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'subscription' => $subscription?->isActive() ? $subscription->load('plan') : null,
        ]);
    }

    public function subscribe(Request $request, string $slug): RedirectResponse|View
    {
        $selectedPlan = $this->subscriptions->findPlanBySlug($slug);

        if (! $selectedPlan) {
            abort(422, 'Invalid plan selected.');
        }

        $billingCycle = $request->input('billing_cycle', 'monthly');
        if (! in_array($billingCycle, ['monthly', 'yearly'], true)) {
            $billingCycle = 'monthly';
        }

        $amount = $billingCycle === 'yearly'
            ? (float) $selectedPlan->price_yearly
            : (float) $selectedPlan->price_monthly;

        if ($amount <= 0) {
            abort(422, 'This plan requires a custom quote.');
        }

        $payu = app(\App\Application\Services\Payment\PayUService::class);

        if (! $payu->isConfigured()) {
            return redirect()
                ->route('onboarding.plan')
                ->with('error', 'Payment gateway is not configured. Please contact support.');
        }

        $user = $request->user();
        $txnid = 'CMO'.now()->format('YmdHis').random_int(1000, 9999);

        $brand = $this->brands->currentBrand($user);
        $brandId = $brand?->id ?? (int) session('current_brand_id');

        if ($brandId && ! $brand) {
            $brand = $user->brands()->where('id', $brandId)->first();
            $brandId = $brand?->id;
        }

        $order = Order::query()->create([
            'user_id' => $user->id,
            'plan_id' => $selectedPlan->id,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_provider' => 'payu',
            'transaction_id' => $txnid,
            'billing_cycle' => $billingCycle,
        ]);

        $fields = $payu->buildPaymentRequest([
            'txnid' => $txnid,
            'amount' => number_format($amount, 2, '.', ''),
            'productinfo' => $selectedPlan->name.' plan ('.$billingCycle.')',
            'firstname' => filled($user->first_name) ? $user->first_name : 'Customer',
            'email' => $user->email,
            'phone' => '9999999999',
            'udf1' => (string) $order->id,
            'udf2' => $selectedPlan->slug,
            'udf3' => $billingCycle,
            'udf4' => $brandId ? (string) $brandId : '',
            'surl' => route('onboarding.payment.payu.success'),
            'furl' => route('onboarding.payment.payu.failure'),
        ]);

        return view('onboarding.payu-redirect', [
            'action' => config('services.payu.base_url'),
            'fields' => $fields,
            'planName' => $selectedPlan->name,
        ]);
    }
}
