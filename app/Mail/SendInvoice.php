<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInvoice extends Mailable
{
    use Queueable, SerializesModels;

    protected $response;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.invoices.paid', $this->response)
            ->attach($this->response['pdfPath'])
            ->subject('Payment Successfull!');
    }
}
