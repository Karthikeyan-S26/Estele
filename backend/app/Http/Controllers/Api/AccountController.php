<?php

namespace App\Http\Controllers\Api;

use App\Http\Api\ApiResponses;
use App\Http\Api\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController
{
    use ApiResponses;

    /**
     * GET /api/account — the authenticated user's profile.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'wallet_balance' => (float) $user->wallet_balance,
            'created_at' => $user->created_at?->toIso8601String(),
        ]);
    }

    /**
     * PATCH /api/account/profile — update name/email.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $user->update($validated);

        return $this->ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'wallet_balance' => (float) $user->wallet_balance,
            'created_at' => $user->created_at?->toIso8601String(),
        ]);
    }

    /**
     * PATCH /api/account/password — change password (current password required).
     */
    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return $this->error('Your current password is incorrect.', 422, [
                'current_password' => ['Your current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return $this->message('Password updated.');
    }

    /**
     * GET /api/account/orders — paginated order history.
     */
    public function orders(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()->with('items')->paginate($this->perPage($request, 10));

        return $this->paginated($orders, fn (Order $order) => OrderResource::payload($order));
    }

    /**
     * GET /api/account/wallet/transactions — the wallet ledger (credits +
     * debits with remaining validity of expiring credits). Powers the app's
     * real Wallet screen.
     */
    public function walletTransactions(Request $request): JsonResponse
    {
        $page = $request->user()->walletTransactions()
            ->latest('id')
            ->paginate($this->perPage($request, 20));

        $payload = $this->paginated($page, fn (WalletTransaction $t) => [
            'id' => $t->id,
            'type' => $t->type,
            'amount' => (float) $t->amount,
            'balance_after' => (float) $t->balance_after,
            'reason' => $t->reason,
            'status' => $t->lifetimeStatus(),
            'expires_at' => $t->expires_at?->toIso8601String(),
            'created_at' => $t->created_at?->toIso8601String(),
        ]);

        // The ledger and the display balance are kept in sync here so the app
        // never shows a stale cached wallet figure after an order/settlement.
        $data = json_decode($payload->getContent(), true);
        $data['balance'] = (float) $request->user()->wallet_balance;

        return response()->json($data);
    }

    /**
     * GET /api/account/orders/{order_number} — one order's detail.
     */
    public function orderShow(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $this->authorizeOwnOrder($request, $order);

        return $this->ok(OrderResource::payload($order));
    }

    /**
     * GET /api/account/orders/{order_number}/invoice — PDF stream download.
     */
    public function orderInvoice(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $this->authorizeOwnOrder($request, $order);

        $order->load('items');

        return response()->streamDownload(function () use ($order) {
            echo Pdf::loadView('orders.invoice', ['order' => $order])->output();
        }, "invoice-{$order->order_number}.pdf", ['Content-Type' => 'application/pdf']);
    }

    /**
     * POST /api/account/orders/{order_number}/cancellation-request — flag the
     * order for admin review (does NOT cancel it).
     */
    public function requestCancellation(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $this->authorizeOwnOrder($request, $order);

        abort_unless($order->canRequestCancellation(), 422);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $order->update([
            'cancellation_requested_at' => now(),
            'cancellation_reason' => $validated['reason'],
        ]);

        return $this->message('Your request has been sent — our team will review it shortly.');
    }

    /**
     * GET /api/account/addresses — address book.
     */
    public function addresses(Request $request): JsonResponse
    {
        return $this->ok($request->user()->addresses->map(fn (Address $a) => $this->addressPayload($a))->values());
    }

    /**
     * POST /api/account/addresses — add an address.
     */
    public function addressStore(Request $request): JsonResponse
    {
        $validated = $this->validateAddress($request);

        $address = $request->user()->addresses()->create($validated);

        if ($address->is_default) {
            $this->clearOtherDefaults($request->user(), $address);
        }

        return $this->created($this->addressPayload($address->fresh()));
    }

    /**
     * PATCH /api/account/addresses/{id} — update an address.
     */
    public function addressUpdate(Request $request, int $id): JsonResponse
    {
        $address = Address::findOrFail($id);
        $this->authorizeOwnAddress($request, $address);

        $validated = $this->validateAddress($request);
        $address->update($validated);

        if ($address->is_default) {
            $this->clearOtherDefaults($request->user(), $address);
        }

        return $this->ok($this->addressPayload($address->fresh()));
    }

    /**
     * DELETE /api/account/addresses/{id} — delete an address.
     */
    public function addressDestroy(Request $request, int $id): JsonResponse
    {
        $address = Address::findOrFail($id);
        $this->authorizeOwnAddress($request, $address);

        $wasDefault = (bool) $address->is_default;
        $address->delete();

        // Keep the address book coherent: if the deleted address was the
        // default, promote the next most recent address so checkout always has
        // a default to pre-fill (previously the user could end up with zero
        // defaults and the app silently picked nothing).
        if ($wasDefault) {
            $next = $request->user()->addresses()->orderBy('id')->first();
            if ($next) {
                $next->forceFill(['is_default' => true])->save();
            }
        }

        return $this->message('Address removed.');
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'digits:6'],
            'country' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'digits_between:10,15'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
    }

    private function addressPayload(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
            'phone' => $address->phone,
            'is_default' => (bool) $address->is_default,
        ];
    }

    private function clearOtherDefaults(User $user, Address $keep): void
    {
        $user->addresses()->where('id', '!=', $keep->id)->update(['is_default' => false]);
    }

    private function authorizeOwnOrder(Request $request, Order $order): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
    }

    private function authorizeOwnAddress(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 404);
    }
}