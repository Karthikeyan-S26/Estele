<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Wallet credit notice</title>
</head>
<body style="font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #222;">
    <p>Hi {{ $credit->user->name }},</p>

    @if($stage === 'expired')
        <p>Your wallet credit of <strong>₹{{ number_format((float) $credit->amount, 2) }}</strong> from your old-jewellery buy-back has now expired.</p>
    @else
        <p>We noticed your wallet credit of <strong>₹{{ number_format((float) $credit->amount, 2) }}</strong> from your old-jewellery buy-back is about to expire.</p>
        <p>
            @if($stage === '1d')
                It expires <strong>tomorrow, {{ $credit->expires_at?->format('d M Y') }}</strong> — anything left unused will be removed after that.
            @else
                It expires on <strong>{{ $credit->expires_at?->format('d M Y') }}</strong> — anything left unused will be removed after that.
            @endif
        </p>
        <p>Hop over to the shop and treat yourself before it's gone.</p>
    @endif

    <p>Thanks,<br>{{ config('app.name') }}</p>
</body>
</html>