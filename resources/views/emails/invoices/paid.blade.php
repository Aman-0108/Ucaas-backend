<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invoice</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.1/css/all.min.css"
            integrity="sha256-2XFplPlrFClt0bIdPgpz8H7ojnk10H69xRqd9+uTShA=" crossorigin="anonymous" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <style>
            body {
                margin-top: 20px;
                background-color: #eee;
            }

            .card {
                box-shadow: 0 20px 27px 0 rgb(0 0 0 / 5%);
            }

            .card {
                position: relative;
                display: flex;
                flex-direction: column;
                min-width: 0;
                word-wrap: break-word;
                background-color: #fff;
                background-clip: border-box;
                border: 0 solid rgba(0, 0, 0, .125);
                border-radius: 1rem;
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="invoice-title">
                                <h4 class="float-end font-size-15">Invoice #{{ $payment->id }} <span
                                        class="badge bg-success font-size-12 ms-2">{{ $payment->payment_status }}</span></h4>
                                <div class="mb-4">
                                    <h2 class="mb-1 text-muted">Ucaas</h2>
                                </div>
                                <div class="text-muted">
                                    <p class="mb-1">3184 Spruce Drive Pittsburgh, PA 15201</p>
                                    <p class="mb-1"><i class="uil uil-envelope-alt me-1"></i> info@ucaas.com</p>
                                    <p><i class="uil uil-phone me-1"></i> 012-345-6789</p>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="text-muted">
                                        <h5 class="font-size-16 mb-3">Billed To:</h5>
                                        <h5 class="font-size-15 mb-2">{{ $account->company_name }}</h5>
                                        <p class="mb-1">{{ $account->unit }} , {{ $account->street }} , {{ $account->city }} , {{ $account->zip }}</p>
                                        <p class="mb-1">{{ $account->email }}</p>
                                        <p>{{ $account->contact_no }}</p>
                                    </div>
                                </div>
                                <!-- end col -->
                                <div class="col-sm-6">
                                    <div class="text-muted text-sm-end">
                                        <div>
                                            <h5 class="font-size-15 mb-1">Invoice No:</h5>
                                            <p>#DZ0112</p>
                                        </div>
                                        <div class="mt-4">
                                            <h5 class="font-size-15 mb-1">Invoice Date:</h5>
                                            <p>{{ date('d-M-y', strtotime($payment->transaction_date)) }}</p>
                                        </div>
                                        {{-- <div class="mt-4">
                                            <h5 class="font-size-15 mb-1">Order No:</h5>
                                            <p>#</p>
                                        </div> --}}
                                    </div>
                                </div>
                                <!-- end col -->
                            </div>
                            <!-- end row -->

                            <div class="py-2">
                                <h5 class="font-size-15">Order Summary</h5>

                                <div class="table-responsive">
                                    <table class="table align-middle table-nowrap table-centered mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 70px;">No.</th>
                                                <th>Item</th>
                                                <th>Price</th>
                                                <th class="text-end" style="width: 120px;">Total</th>
                                            </tr>
                                        </thead><!-- end thead -->
                                        <tbody>
                                            <tr>
                                                <th scope="row">01</th>
                                                <td>
                                                    <div>
                                                        <h5 class="text-truncate font-size-14 mb-1">
                                                            {{ $package->name }}
                                                        </h5>
                                                        <p class="text-muted mb-0">{{ $package->description }}</p>
                                                    </div>
                                                </td>
                                                <td>$ {{ $package->regular_price }}
                                                    <small>${{ $package->offer_price }}</small>
                                                </td>
                                                <td class="text-end">$ {{ $package->offer_price }}</td>
                                            </tr>
                                            <!-- end tr -->
                                           
                                            <tr>
                                                <th scope="row" colspan="4" class="text-end">Sub Total</th>
                                                <td class="text-end">${{ $payment->amount_total }}</td>
                                            </tr>
                                            <!-- end tr -->
                                                                                 
                                            <tr>
                                                <th scope="row" colspan="4" class="border-0 text-end">
                                                    Tax</th>
                                                <td class="border-0 text-end">$12.00</td>
                                            </tr>
                                            <!-- end tr -->
                                            <tr>
                                                <th scope="row" colspan="4" class="border-0 text-end">Total</th>
                                                <td class="border-0 text-end">
                                                    <h4 class="m-0 fw-semibold">$739.00</h4>
                                                </td>
                                            </tr>
                                            <!-- end tr -->
                                        </tbody><!-- end tbody -->
                                    </table><!-- end table -->
                                </div><!-- end table responsive -->
                              
                            </div>
                        </div>
                    </div>
                </div><!-- end col -->
            </div>
        </div>
    </body>

</html>
