<?php

namespace Database\Seeders;

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StripeController;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use App\Traits\GetPermission;
use Illuminate\Http\Request;

class AccountSeeder extends Seeder
{
    use GetPermission;

    protected $stripeController;

    public function __construct(StripeController $stripeController)
    {
        $this->stripeController = $stripeController;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $pc = new PaymentController($this->stripeController);
        
        // Prepare payment details
        $paymentData = [
            'type' => 'card',
            'card_number' => 4242424242424242,
            'exp_month' => 8,
            'cvc' => 314,
            'exp_year' => 2026,
            'name' => 'Test Card',
            'lead_id' => 1,
            'fullname' => 'Tushar Subhra',
            'contact_no' => '7363807606',
            'email' => 'tushar@appzone.in',
            'address' => 'New Town',
            'zip' => '700135',
            'city' => 'Kolkata',
            'state' => 'WB',
            'country' => 'IN',
            'save_card' => 1,
        ];

        // Call the payment processing method with the prepared data
        $pc->pay(new Request($paymentData));

        // Account::create([
        //     'admin_name' => 'rdx',
        //     'company_name' => 'RDX',
        //     'timezone_id' => 1,
        //     'email' => 'rdx@webvio.com',
        //     'contact_no' => 9609090569,
        //     'state' => 'WB',
        //     'company_status' => 1,
        //     'package_id' => 2,
        //     'payment_approved_by' => 1,
        //     'document_approved_by' => 1,
        // ]);

        // $user = User::create([
        //     'name' => 'rdx',
        //     'email' => 'rdx@webvio.com',
        //     'password' => bcrypt('123456'),
        //     'username' => 'RDX',
        //     'account_id' => 1,
        //     'usertype' => 'Company'
        // ]);

        // UserRole::create([
        //     'user_id' => $user->id,
        //     'role_id' => 2
        // ]);

        // $pids = $this->getDefaultCompaniesPermissions();

        // $formattedData = [];
        // foreach($pids as $pid) {
        //     $formattedData[] = [
        //         'role_id' => 2,
        //         'permission_id' => $pid,
        //         'created_at' => date("Y-m-d H:i:s"),
        //         'updated_at' => date("Y-m-d H:i:s")
        //     ];
        // }

        // RolePermission::insert($formattedData);
    }
}
