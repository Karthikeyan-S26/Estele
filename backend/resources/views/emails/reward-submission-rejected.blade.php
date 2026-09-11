<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reward submission update</title>
</head>
<body style="font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #222;">
    <p>Hi {{ $submission->user->name }},</p>

    <p>Your reward submission for order <strong>{{ $submission->order->order_number }}</strong> was not approved.</p>

    <p><strong>Reason:</strong> {{ $submission->rejection_reason }}</p>

    <p>You're welcome to submit proof for a different eligible order at any time.</p>

    <p>Thank you for shopping with {{ config('app.name') }}.</p>
</body>
</html>
