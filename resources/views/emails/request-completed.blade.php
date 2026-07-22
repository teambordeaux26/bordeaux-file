<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Completed</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Dear {{ $documentRequest->requester_name }},</p>

    <p>
        Your request <strong>{{ $documentRequest->tracking_number }}</strong>
        ({{ $documentRequest->request_type }}) has been completed by the
        Office of the Vice Mayor — Oas, Albay.
    </p>

    <p>
        The requested document is attached to this email. You may also download it
        from the request status page using your tracking number:
        <strong>{{ $documentRequest->tracking_number }}</strong>.
    </p>

    <p style="margin-top: 24px; font-size: 13px; color: #6b7280;">
        This is an automated message. Please do not reply to this email.
    </p>
</body>
</html>
