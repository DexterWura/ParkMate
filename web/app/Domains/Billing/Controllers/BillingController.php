<?php

namespace App\Domains\Billing\Controllers;

use App\Domains\Billing\Requests\CheckoutRequest;
use App\Domains\Billing\Services\BillingService;
use App\Domains\Subscription\Enums\SubscriptionPlan;
use App\Domains\Subscription\Resources\SubscriptionResource;
use App\Shared\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends BaseController
{
    public function __construct(
        protected BillingService $billing
    ) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::from($request->validated('plan'));
        $url = $this->billing->checkout($request->user(), $plan);

        return $this->success(['checkout_url' => $url]);
    }

    public function portal(Request $request): JsonResponse
    {
        return $this->success([
            'portal_url' => $this->billing->portalUrl($request->user()),
        ]);
    }

    public function subscription(Request $request): JsonResponse
    {
        $subscription = $request->user()->subscription('default');

        return $this->success([
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $this->billing->cancel($request->user());

        return $this->success(message: 'Subscription cancelled.');
    }

    public function resume(Request $request): JsonResponse
    {
        $this->billing->resume($request->user());

        return $this->success(message: 'Subscription resumed.');
    }
}
