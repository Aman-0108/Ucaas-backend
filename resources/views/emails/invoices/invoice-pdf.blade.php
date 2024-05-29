<!doctype html>
<html lang="en">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport"
            content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Invoice</title>
        <style>
            .m-60 {
                margin: 60px !important;
            }

            h4 {
                margin: 0;
            }

            .w-full {
                width: 100%;
            }

            .w-half {
                width: 50%;
            }

            .margin-top {
                margin-top: 1.25rem;
            }

            .footer {
                font-size: 0.875rem;
                padding: 1rem;
                background-color: rgb(241 245 249);
            }

            table {
                width: 100%;
                border-spacing: 0;
            }

            table.products {
                font-size: 0.875rem;
            }

            table.products tr {
                background-color: rgb(96 165 250);
            }

            table.products th {
                color: #ffffff;
                padding: 0.5rem;
            }

            table tr.items {
                background-color: rgb(241 245 249);
            }

            table tr.items td {
                padding: 0.5rem;
            }

            .text-end {
                text-align: right
            }

            .total {
                text-align: right;
                margin-top: 1rem;
                font-size: 0.875rem;
            }
        </style>
    </head>

    <body class="m-60">
        <table class="w-full">
            <tr>
                <td class="w-half">
                    <img src="{{ asset('laraveldaily.png') }}" alt="laravel daily" width="200" />
                </td>
                <td class="w-half" style="text-align: end">
                    <h2>Invoice ID: #{{ $payment->id }} </h2>
                </td>
            </tr>
        </table>

        <div class="margin-top">
            <table class="w-full">
                <tr>
                    <td class="w-half">
                        <div>
                            <h4>To:</h4>
                        </div>
                        <div>{{ $account->company_name }}</div>
                        <div>{{ $account->unit }} , {{ $account->street }} , {{ $account->city }} , {{ $account->zip }}
                        </div>
                        <div>{{ $account->email }}</div>
                        <div>{{ $account->country }}</div>
                    </td>
                    <td class="w-half">
                        <div>
                            <h4>From:</h4>
                        </div>
                        <div>Ucaas</div>
                        <div>info@ucaas.com</div>
                        <div>{{ $account->country }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="margin-top">
            <table class="products">
                <tr>
                    <th>Qty</th>
                    <th>Description</th>
                    <th>Price</th>
                </tr>
                <tr class="items">
                    <td style="text-align: start">
                        1.
                    </td>
                    <td style="text-align: center">
                        {{ $package->name }}
                        <p class="text-muted mb-0">{{ $package->description }}</p>
                    </td>
                    <td class="text-end">
                        <div style="text-decoration: line-through">${{ $package->regular_price }}</div>
                        <div>${{ $package->offer_price }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="total">
            Total: ${{ $payment->amount_total }} {{ config('globals.active_currency') }}
        </div>

        <div class="footer margin-top">
            <div style="text-align: center">&copy; {{ config('globals.app_name') }}</div>
        </div>
    </body>

</html>
