<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password reset code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Hello {{ $user->name }},</p>

    <p>
        A password reset was requested for the Document Management System account
        <strong>{{ $user->email }}</strong>.
    </p>

    <p style="font-size: 28px; font-weight: bold; letter-spacing: 8px; color: #003366;">
        {{ $code }}
    </p>

    <p>This code expires in 10 minutes. If you did not request a reset, you can ignore this email.</p>

    <p style="margin-top: 24px; font-size: 13px; color: #6b7280;">
        Office of the Vice Mayor — Oas, Albay. This is an automated message.
    </p>
</body>
</html>
