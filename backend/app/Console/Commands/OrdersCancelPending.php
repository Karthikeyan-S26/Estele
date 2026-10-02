<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cancels "placed but never paid" orders whose Razorpay payment never landed.
 *
 * A COD/Razorpay order that stays `payment_status=pending` past the window
 * holds stock decremented at checkout and a coupon usage booked, forever —
 * previously nothing ever swept them (the retry endpoint existed, but a user
 * who never comes back left the stock permanently short). Cancelling these is
 * safe: Order::updating() runs the ALLOWED_TRANSITIONS guard, restocks the
 * line items and frees the coupon slot (unpaid cancellations only), and
 * Order::updated() refunds any wallet amount used. Each order's cancellation
 * (restock + wallet credit + coupon release together) happens inside a single
 * database transaction so a failure mid-way cannot half-apply it.
 *
 * Late Razorpay callbacks/webhooks for these orders are a known race and are
 * handled in PaymentManager: a captured payment arriving after cancellation is
 * recorded as an audit note (payment_reference + admin_notes) and never
 * resurrects the order to `paid`.
 */
class OrdersCancelPending extends Command
{
    protected $signature = 'orders:cancel-pending
                            {--hours=4 : Abandonment window in hours before a placed+pending order is cancelled}';

    protected $description = 'Cancel placed orders that were never paid for';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $staleOrders = Order::where('status', 'placed')
            ->where('payment_status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->get();

        $cancelled = 0;
        foreach ($staleOrders as $order) {
            try {
                // One transaction per order — the status save fires the
                // restock/coupon-release (updating) and wallet-refund (updated)
                // side effects, which must all commit or roll back together.
                DB::transaction(fn () => $order->update(['status' => 'cancelled']));
                $cancelled++;
            } catch (\Throwable $e) {
                // A transition guard rejection (or any transient DB error)
                // must not kill the sweep — log and move on to the next order.
                logger()->warning('orders:cancel-pending could not cancel order', [
                    'order' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Cancelled {$cancelled} placed-but-unpaid order(s) older than {$hours}h.");

        return self::SUCCESS;
    }
}
