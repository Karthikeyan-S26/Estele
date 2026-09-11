<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buy-back request {{ $sell->request_number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; background: #faf7f2; color: #2b2118; margin: 0; padding: 24px; }
        .card { background: #fff; border: 1px solid #e8e0d4; border-radius: 10px; max-width: 680px; margin: 0 auto 20px; padding: 24px; }
        h1 { font-size: 20px; margin-top: 0; }
        h2 { font-size: 16px; }
        .meta { color: #6b5d4f; font-size: 13px; }
        .badge { display: inline-block; background: #f3ead9; border-radius: 999px; padding: 3px 10px; font-size: 12px; }
        .actions { display: flex; gap: 10px; margin-top: 16px; flex-wrap: wrap; }
        button, .btn { background: #b3562f; color: #fff; border: 0; border-radius: 8px; padding: 10px 18px; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-ghost { background: #eee4d5; color: #6b3f27; }
        input[type=number] { padding: 10px; border: 1px solid #d8cdbb; border-radius: 8px; font-size: 15px; width: 100%; box-sizing: border-box; }
        .flash { background: #e8f5e9; border: 1px solid #b6dfba; color: #256029; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
        .error { background: #fdecea; border: 1px solid #f5c6c0; color: #a41717; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; }
        video, img { max-width: 100%; border-radius: 8px; }
        .row { margin-bottom: 12px; }
        .label { font-weight: 600; font-size: 13px; }
        .lead { color: #8a5a3b; font-size: 15px; font-weight: 700; }
    </style>
</head>
<body>
    @if(session('status'))
        <div class="card"><div class="flash">{{ session('status') }}</div></div>
    @endif

    @if($errors->any())
        <div class="card"><div class="error">{{ $errors->first() }}</div></div>
    @endif

    <div class="card">
        <h1>Old-Jewellery Buy-Back</h1>
        <div class="meta">Request <strong>{{ $sell->request_number }}</strong> · posted {{ $sell->created_at?->format('d M Y, H:i') }}</div>

        <div class="row">
            <div class="label">Item type</div>
            <strong>{{ ucfirst($sell->item_type) }}</strong>
        </div>
        @if($sell->description)
            <div class="row"><div class="label">Description</div>{{ $sell->description }}</div>
        @endif
        @if($sell->city)
            <div class="row"><div class="label">City</div>{{ $sell->city }}</div>
        @endif

        @if($sell->image_path)
            <div class="row"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($sell->image_path) }}" alt="Item photo"></div>
        @endif
        @if($sell->video_path)
            <div class="row">
                <video controls src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($sell->video_path) }}"></video>
            </div>
        @endif

        <div class="row">
            <div class="label">Status</div>
            <span class="badge">{{ str_replace('_', ' ', $sell->status) }}</span>
            <span class="meta"> · bidding closes {{ $sell->bids_end_at?->format('d M Y, H:i') }}</span>
        </div>

        @if($invitation->isPending())
            <div class="actions">
                <form method="POST" action="{{ route('sell.vendor.accept', $invitation->token) }}">
                    @csrf
                    <button type="submit">Accept invitation</button>
                </form>
                <form method="POST" action="{{ route('sell.vendor.decline', $invitation->token) }}">
                    @csrf
                    <button type="submit" class="btn-ghost">Decline</button>
                </form>
            </div>
        @endif
    </div>

    @if($invitation->isAccepted())
        <div class="card">
            <h2>Place your bid</h2>
            @if($sell->isBiddingOpen())
                <p class="lead">
                    @if($myBid)
                        Current bid: ₹{{ number_format((float) $myBid->amount, 0) }} (you can update it)
                    @else
                        No bid yet — how much would you offer?
                    @endif
                </p>
                <form method="POST" action="{{ route('sell.vendor.bid') }}">
                    @csrf
                    <input type="hidden" name="invitation_token" value="{{ $invitation->token }}">
                    <div class="row">
                        <div class="label">Your offer (₹)</div>
                        <input type="number" name="amount" min="100" step="1" value="{{ $myBid ? (float) $myBid->amount : '' }}" required>
                    </div>
                    <button type="submit">Submit / update bid</button>
                </form>
            @else
                <p>The bidding window for this request is closed.</p>
            @endif
        </div>
    @endif

    @if($otherOpen->isNotEmpty())
        <div class="card">
            <h2>Other open invitations</h2>
            @foreach($otherOpen as $other)
                <p>
                    <a href="{{ route('sell.vendor.invitation', $other->token) }}">{{ $other->sellRequest->request_number }}</a>
                    <span class="meta"> · {{ ucfirst($other->sellRequest->item_type) }} · closes {{ $other->sellRequest->bids_end_at?->format('d M, H:i') }}</span>
                </p>
            @endforeach
        </div>
    @endif
</body>
</html>