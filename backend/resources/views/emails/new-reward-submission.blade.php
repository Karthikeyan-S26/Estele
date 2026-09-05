<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New reward submission</title>
</head>
<body style="font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #222;">
    <p>A new reward submission is waiting for review.</p>

    <p>
        <strong>Customer:</strong> {{ $submission->user->name }}<br>
        <strong>Order:</strong> {{ $submission->order->order_number }}<br>
        <strong>Submitted:</strong> {{ $submission->created_at->format('d M Y, h:i A') }}
    </p>

    <p>Review it in the admin panel under Reward Submissions.</p>
</body>
</html>
