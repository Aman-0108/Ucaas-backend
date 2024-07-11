<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Path to the directory containing your model files
        $modelsDirectory = app_path('Models');

        // Retrieve all PHP files in the directory
        $files = File::files($modelsDirectory);

        // Extract file names without the extension
        $modelFiles = [];
        foreach ($files as $file) {
            $modelFiles[] = pathinfo($file, PATHINFO_FILENAME);
            // Extract file name without the extension
            $modelName = pathinfo($file, PATHINFO_FILENAME);
            

            // Use preg_replace with a regular expression to add a space before every capital letter except the first one
            $slug = preg_replace('/(?<=\p{Ll})(?=\p{Lu})/', ' ', $modelName);

            // Check if the model exists
            if (class_exists('App\\Models\\' . $modelName)) {
                $actions = ['browse','read','edit','add','delete', 'search'];

                foreach($actions as $action) {
                    Permission::create([
                        'model' => $modelName,
                        'type' => strtolower($modelName),
                        'action' => $action,
                        'slug' => $slug
                    ]);
                }                
               
            }
        }
    }
}
