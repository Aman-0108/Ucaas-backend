
<!DOCTYPE html>
<html>

    <head>
        <title>Welcome to {{ config('app.name') }}!</title>
    </head>

    <body>
        <p>Dear {{ $account['company_name'] }},</p>
        <p>We are thrilled to welcome you to {{ config('app.name') }}! 🎉</p>

        <p>Your account has been successfully created, and we can't wait to have you onboard.</p>

        <h2>Account Details:</h2>
        <ul>
            <li><strong>Username/Email:</strong> {{ $account['email'] }}</li>
        </ul>

        <h2>Next Steps:</h2>
        <ol>
            <li><strong>Url to upload company details</strong> {{ $account['dynamicUrl'] }} </li>
            <li><strong>Explore:</strong> Discover all the amazing features and services we offer.</li>
            <li><strong>Customize:</strong> Personalize your account settings to suit your preferences.</li>
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
