<?php

namespace Database\Seeders;

use App\Models\DefaultPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(TimezoneSeeder::class);
        $this->call(PackageSeeder::class);
        $this->call(FeatureSeeder::class);
        $this->call(PaymentGatewaySeeder::class);
        $this->call(UserSeeder::class);

        // $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(DefaultPermissionSeeder::class);
        // $this->call(RolePermissionSeeder::class);

        $this->call(DidVendorSeeder::class);
        $this->call(DidRateChartSeeder::class);

        $this->call(SofiaGlobalsettingsSeeder::class);
        $this->call(SipProfileSeeder::class);
        $this->call(SipProfileSettingsSeeder::class);
        $this->call(SipProfileDomainSeeder::class);
        $this->call(DialplanSeeder::class);

        $this->call(LeadSeeder::class);
        $this->call(AccountSeeder::class);
        // $this->call(DomainSeeder::class);

        // $this->call(ExtensionSeeder::class);
        $this->call(DocumentSeeder::class);
        // $this->call(AccountDetailsSeeder::class);

        // Clear storage/app/public directory
        Storage::disk('public')->deleteDirectory('pdfs');
        Storage::disk('public')->deleteDirectory('company');
    
        $htaccessContent = 'Header set Access-Control-Allow-Origin "*"';

        // Ensure the directory exists
        $companyDirectory = 'company';
        $pdfDirectory = 'pdfs';

        if (!Storage::exists($companyDirectory)) {
            Storage::makeDirectory($companyDirectory);
            $htaccessPath = storage_path('app/public/'.$companyDirectory.'/.htaccess');
            file_put_contents($htaccessPath, $htaccessContent, FILE_APPEND);
        }

        if (!Storage::exists($pdfDirectory)) {
            Storage::makeDirectory($pdfDirectory);
            $htaccessPath = storage_path('app/public/'.$pdfDirectory.'/.htaccess');
            file_put_contents($htaccessPath, $htaccessContent, FILE_APPEND);
        }
    }
}
