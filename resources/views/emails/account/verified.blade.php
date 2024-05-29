<!DOCTYPE html>
<html>

    <head>
        <title>Welcome to {{ config('app.name') }}!</title>
    </head>

    <body>
        <p>Dear {{ $user['username'] }},</p>
        <p>We are thrilled to welcome you to {{ config('app.name') }}! 🎉</p>

        <p>Your profile has been successfully verified, and we can't wait to have you onboard.</p>

        <h2>user Details:</h2>
        <ul>
            <li><strong>Username/Email:</strong> {{ $user['email'] }}</li>
            <li><strong>Password:</strong> {{ $user['username'] }}</li>
        </ul>

        <h2>Next Steps:</h2>
        <ol>
            <li><strong>Log In:</strong> Head over to our website at <a
                    href="{{ config('globals.website_url') }}">{{ config('globals.website_url') }}/login</a>.</li>
           
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
