<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New buy-back request</title>
</head>
<body style="font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #222;">
    <p>Hi Admin,</p>

    <p>A customer has posted a new old-jewellery buy-back request.</p>

    <p>
        Request: <strong>{{ $sellRequest->request_number }}</strong><br>
        Customer: {{ $sellRequest->user->name }} ({{ $sellRequest->user->phone }})<br>
        Item type: <strong>{{ ucfirst($sellRequest->item_type) }}</strong><br>
        City: {{ $sellRequest->city ?? '—' }}<br>
        Description: {{ $sellRequest->description ?: '—' }}<br>
        Bidding closes: {{ $sellRequest->bids_end_at?->format('d M Y, H:i') }}
    </p>

    @if($sellRequest->image_path)
        <p><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($sellRequest->image_path) }}" alt="Item photo" style="max-width: 320px;"></p>
    @endif

    <p>Review bids in the admin panel once the window closes.</p>
</body>
</html>