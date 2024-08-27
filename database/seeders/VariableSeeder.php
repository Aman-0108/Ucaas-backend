<?php

namespace Database\Seeders;

use App\Models\Variable;
use Illuminate\Database\Seeder;

class VariableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $jsonData = file_get_contents(base_path('database/data/variables.json'));

        $arrayData = json_decode($jsonData, true);

        $formattedData = [];

        foreach ($arrayData as $row) {
            unset($row['id'], $row['created_by'], $row['deleted_at'], $row['created_at'], $row['updated_at']);
            $row['created_at'] = date("Y-m-d H:i:s");
            $row['updated_at'] = date("Y-m-d H:i:s");
            $formattedData[] = $row;
        }

        Variable::insert($formattedData);
    }
}
