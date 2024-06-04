<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PaymentGateway::create([
            'name' => 'Stripe',
            'api_key' => 'pk_test_51PGfwnP2K5GdGJjusUXgzFjSLTx0u5fBOlNy8Q4IcrZUXE6DkCBjmJYt1NcwJXlVMKOWqb0JtkXZhRIQxPTgikbp00zQbHXdg9',
            'api_secret' => 'sk_test_51PGfwnP2K5GdGJjuToL0GuOyy7QmWu151MKu3KYk5jyD8XK3WuUaAlSVd3HWurpYlnV5DKgols59TefvwuscSclc00EEFwblts',
            'status' => 'active',
        ]);
    }
}
