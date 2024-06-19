<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountDetail;
use Illuminate\Database\Seeder;

class AccountDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $account = Account::find(1);

        if ($account) {
            $account->company_status = 4;
            $account->payment_approved_by = 1;
            $account->document_approved_by = 1;
            $account->save();
        }

        AccountDetail::create([
            'account_id' => 1,
            'registration_path' => 'storage/pdf/rpath.pdf',
            'tin_path' => 'storage/pdf/tin.pdf',
            'moa_path' => 'storage/pdf/moa.pdf'
        ]);
    }
}
