<?php

namespace App\Console\Commands;

use App\Mail\LowBalance;
use App\Models\Account;
use App\Models\AccountBalance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class LowBalanceNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:send-low-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send low balance notification emails to users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $datas = AccountBalance::whereColumn('amount', '<=', 'min_amount')->get();

        foreach ($datas as $data) {
            // notification logic: send email
            $this->sendNotificationEmail($data);

            // Optionally, log the notification
            $this->logNotification($data);
        }

        $this->info('Notifications sent to users with low balances.');
    }

    private function sendNotificationEmail($data)
    {
        $accountId = $data->account_id;

        $account = Account::find($accountId);

        $mailData = [
            'account_name' => $account->company_name,
            'current_balance' => $data->amount,
            'current_date' => date('Y-m-d')
        ];

        // Replace with your notification logic, such as sending an email
        Mail::to($account->email)->send(new LowBalance($mailData));

        // For demonstration, just log the email sending
        $this->info("Notification sent to User ID: {$account->email}");
    }

    private function logNotification($data)
    {
        // Example: Log the notification for auditing or further processing
        // Example: Log::info("User ID {$user->user_id} has reached the minimum amount.");

        // For demonstration, just log the notification
        $this->info("Logged notification for User ID: {$data->account_id}");
    }
}
