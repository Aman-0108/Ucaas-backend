<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Creates a new subscription for an account based on the provided package information.
     *
     * This function generates subscription data based on the package type (monthly or annually),
     * sets the start date to the current date and calculates the end date accordingly.
     * It then creates a new subscription record associated with the provided account and package.
     *
     * @param $package object The package object containing subscription type and other details.
     * @param $transactionId int|string The ID of the transaction associated with the subscription.
     * @param $accountId int The ID of the account for which the subscription is created.
     * @return void
     */
    public function createSubscription($accountId, $package, $transactionId)
    {
        // Get current date
        $currentDate = Carbon::now();

        // Calculate end date based on package subscription type
        if ($package->subscription_type == 'monthly') {
            $endDate = $currentDate->addMonth()->format('Y-m-d H:i:s');
        } elseif ($package->subscription_type == 'annually') {
            $endDate = $currentDate->addYear()->format('Y-m-d H:i:s');
        }

        // Subscription data to be inserted
        $subscriptionData = [
            'transaction_id' => $transactionId,
            'account_id' => $accountId,
            'package_id' => $package->id,
            'start_date' => date("Y-m-d H:i:s"),
            'end_date' => $endDate,
            'status' => 'active'
        ];

        // Create a new subscription record
        $subscription = Subscription::create($subscriptionData);

        return $subscription;
    }
}
