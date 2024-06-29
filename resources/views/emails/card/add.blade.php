<x-body>

    <p>Dear {{ isset($response['account_name']) ? $response['account_name'] : '' }},</p>

    <p>
        We hope this message finds you well. We want to inform you that a new credit card has been successfully added to your account on our UCaaS (Unified Communications as a Service) application.
    </p>

    <p>Here are the details of the new credit card:</p>

    <p>Card Type: Visa/MasterCard/American Express/etc.</p>
    <p>Last Four Digits: {{ isset($response['card']) ? $response['card'] : '' }}</p>
    <p>Expiration Date: {{ isset($response['expiry']) ? $response['expiry'] : '' }}</p>
    
    <p>
        Please review this information to ensure accuracy. If you did not add this credit card or if you have any concerns
        regarding this update, please contact our support team immediately at {{ config('globals.support_mail') }}. We take the
        security of your account very seriously and will assist you promptly.
    </p>    

    <p>Thank you for using our UCaaS application. We appreciate your continued trust and loyalty.</p>

    <p>Best regards,</p>

    <p>[Your Name]</p>
    <p>[Your Position]</p>
    <p>[Your Company Name]</p>
    <p>[Contact Information]</p>   
    
</x-body>
