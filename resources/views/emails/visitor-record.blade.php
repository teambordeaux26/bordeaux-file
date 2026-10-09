<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visitor record</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Dear {{ $visitor->visitor_name }},</p>

    <p>
        This is a copy of your visit record at the Office of the Vice Mayor — Oas, Albay.
    </p>

    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse;">
        <tr>
            <td style="font-weight: bold;">Name</td>
            <td>{{ $visitor->visitor_name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Address</td>
            <td>{{ $visitor->address ?: '—' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Purpose</td>
            <td>{{ $visitor->purpose }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Time in</td>
            <td>{{ $visitor->time_in?->format('F d, Y, \a\t g:i A') }}</td>
        </tr>
        @if($visitor->visitor_phone)
            <tr>
                <td style="font-weight: bold;">Contact number</td>
                <td>{{ $visitor->visitor_phone }}</td>
            </tr>
        @endif
    </table>

    <p style="margin-top: 24px; font-size: 13px; color: #6b7280;">
        This message was sent because an email address was provided at the front desk.
        Walk-in visitors are not required to give an email address.
    </p>
</body>
</html>
