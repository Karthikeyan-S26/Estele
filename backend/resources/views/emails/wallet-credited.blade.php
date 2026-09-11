<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Wallet Updated</title>
</head>
<body style="font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #222;">
    <p>Hi {{ $transaction->user->name }},</p>

    <p>
        @if($transaction->reason === 'reward_approved')
            Congratulations! ₹{{ number_format((float) $transaction->amount, 2) }} has been credited to your wallet as a reward.
        @elseif($transaction->reason === 'sell_settlement')
            Thank you for selling your old jewellery with us — ₹{{ number_format((float) $transaction->amount, 2) }} has been credited to your wallet (90% of the valuation).
            @if($transaction->expires_at)
                <br>This credit is valid until <strong>{{ $transaction->expires_at->format('d M Y') }}</strong>.
            @endif
        @elseif($transaction->reason === 'order_refund')
            ₹{{ number_format((float) $transaction->amount, 2) }} has been refunded to your wallet.
        @else
            ₹{{ number_format((float) $transaction->amount, 2) }} has been credited to your wallet.
        @endif
    </p>

    <p>Current wallet balance: <strong>₹{{ number_format((float) $transaction->balance_after, 2) }}</strong></p>

    <p>Thank you for shopping with {{ config('app.name') }}.</p>
</body>
</html>
