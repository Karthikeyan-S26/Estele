<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buy-back invitation</title>
</head>
<body style="font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #222;">
    <p>Hi {{ $invitation->vendor->name }},</p>

    <p>You've been invited to bid on an old-jewellery buy-back request.</p>

    <p>
        Request: <strong>{{ $invitation->sellRequest->request_number }}</strong><br>
        Item type: <strong>{{ ucfirst($invitation->sellRequest->item_type) }}</strong><br>
        Customer city: {{ $invitation->sellRequest->city ?? '—' }}<br>
        Description: {{ $invitation->sellRequest->description ?: '—' }}<br>
        Bidding closes: <strong>{{ $invitation->sellRequest->bids_end_at?->format('d M Y, H:i') }}</strong>
    </p>

    <p>
        Accept the invitation to start bidding:
        <a href="{{ route('sell.vendor.invitation', $invitation->token) }}">View request &amp; respond</a>
    </p>

    <p>
        Just let us know if you'd rather not participate — respond straight from the link above. No obligation either way.
    </p>

    <p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>