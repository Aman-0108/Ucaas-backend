<x-body>

    <p>Dear {{ isset($response['account_name']) ? $response['account_name'] : '' }},</p>

    <p>
        We hope this message finds you well. We wanted to bring to your attention that your account currently has a low
        balance. As of {{ isset($response['current_date']) ? $response['current_date'] : '' }}, your account balance is
        ${{ isset($response['current_balance']) ? $response['current_balance'] : '' }}.
    </p>

    <p>
        It's important to maintain a sufficient balance to ensure uninterrupted service and transactions. We kindly ask
        you to consider adding funds to your account at your earliest convenience.
    </p>

    <p>
        If you have any questions or need assistance with managing your account balance, please don't hesitate to reach
        out to our support team at {{ config('globals.support_mail') }}.
    </p>

    <p>Thank you for your prompt attention to this matter.</p>
    <p>Best regards,</p>

</x-body>
