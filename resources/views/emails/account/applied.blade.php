<!DOCTYPE html>
<html>

    <head>
        <title>Welcome to {{ config('app.name') }}!</title>
    </head>

    <body>
        <p>Dear {{ $account['company_name'] }},</p>
        <p>We are thrilled to welcome you to {{ config('app.name') }}! 🎉</p>

        <p>Your account has been successfully verified, and we can't wait to have you onboard.</p>

        <h2>Account Details:</h2>
        <ul>
            <li><strong>Username/Email:</strong> {{ $account['email'] }}</li>
            <li><strong>Password:</strong> {{ $account['temp_password'] }}</li>
        </ul>

        <h2>Next Steps:</h2>
        <ol>
            <li><strong>Log In:</strong> Head over to our website at <a
                    href="http://192.168.1.88:3001/login">http://192.168.1.88:3001/login</a>.</li>
            <li><strong>Payment Link:</strong>
                <a href="{{ $account['payment_url'] }}">Click here to pay</a>
            </li>
            <li><strong>Get Started:</strong> Dive right in and start making the most of {{ config('app.name') }}.</li>
        </ol>

        <p>If you have any questions or need assistance, don't hesitate to reach out to our support team at
            {{ config('globals.support_mail') }}. We're here to help!</p>

        <p>Once again, welcome aboard! We're excited to have you as part of our community.</p>

        <p>Best regards,<br>
            [Your Name]<br>
            [Your Job Title]<br>
            {{ config('app.name') }}</p>
    </body>

</html>
