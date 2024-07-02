<x-body>

    <p>Dear {{ isset($response['company_name']) ? $response['company_name'] : '' }},</p>

    <p>
        We are pleased to inform you that your recent recharge was successful. Your account has been credited with
        {{ isset($response['amount']) ? '$' . $response['amount'] : '' }} on
        {{ isset($response['transaction_date']) ? $response['transaction_date'] : '' }}.
    </p>

    <p>
        If you have any questions or require further assistance, please feel free to contact our customer support team
        at 1-760-999-711 or {{ config('globals.support_mail') }}. We are here to help you.
    </p>

    <p>Thank you for choosing our services.</p>

    <p>Best regards,</p>

</x-body>
