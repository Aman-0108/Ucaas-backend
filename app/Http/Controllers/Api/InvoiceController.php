<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Package;
use App\Models\Payment;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class InvoiceController extends Controller
{
    public function generateInvoice($transactionId)
    {
        $payment = Payment::where('stripe_session_id', $transactionId)->first();

        if(!$payment) {
            return false;
        }

        $account = Account::find($payment->account_id);

        $package = Package::find($account->package_id);

        $response['payment'] = $payment;
        $response['account'] = $account;
        $response['package'] = $package;

        // Pass the data to the Blade view
        $html = View::make('emails.invoices.invoice-pdf', $response)->render();        

        // Create Dompdf instance
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');

        // Render the HTML as PDF
        $dompdf->render();

        // Get the PDF content
        $pdfContent = $dompdf->output();

        // Store the PDF
        $pdfPath = 'pdfs/' . uniqid() . '.pdf';
        Storage::put($pdfPath, $pdfContent);
        
        // Generate URL for the stored PDF
        $pdfUrl = Storage::url($pdfPath);

        $response['pdfPath'] = $pdfUrl;
        
        return $response;

    } 
}
