<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\SellRequestService;
use App\Services\WhatsApp\LogWhatsAppSender;
use App\Services\WhatsApp\WhatsAppManager;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerifyWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function logSpy(): LogWhatsAppSender
    {
        $spy = new class extends LogWhatsAppSender
        {
            /** @var array<int, array{phone: string, message: string, context: array}> */
            public array $calls = [];

            public function send(string $phone, string $message, array $context = []): void
            {
                $this->calls[] = ['phone' => $phone, 'message' => $message, 'context' => $context];
            }
        };

        $this->app->instance(LogWhatsAppSender::class, $spy);

        return $spy;
    }

    public function test_no_gateway_config_uses_the_log_sender(): void
    {
        config(['services.whatsapp.driver' => '']);
        config(['services.twilio.sid' => null, 'services.twilio.token' => null, 'services.whatsapp.from' => null]);

        $spy = $this->logSpy();

        app(WhatsAppManager::class)->send('9876543210', 'Hello Estele', ['kind' => 'test']);

        $this->assertCount(1, $spy->calls);
        $this->assertSame('9876543210', $spy->calls[0]['phone']);
        $this->assertSame('Hello Estele', $spy->calls[0]['message']);
        $this->assertSame(['kind' => 'test'], $spy->calls[0]['context']);
    }

    public function test_twilio_driver_without_credentials_falls_back_to_log(): void
    {
        config(['services.whatsapp.driver' => 'twilio']);
        config(['services.twilio.sid' => null, 'services.twilio.token' => null, 'services.whatsapp.from' => null]);

        $spy = $this->logSpy();

        app(WhatsAppManager::class)->send('9876543210', 'Fallback check');

        $this->assertCount(1, $spy->calls);
        $this->assertSame('Fallback check', $spy->calls[0]['message']);
    }

    public function test_twilio_driver_with_credentials_posts_whatsapp_prefixed_message(): void
    {
        config([
            'services.whatsapp.driver' => 'twilio',
            'services.whatsapp.from' => '+14155238886',
            'services.twilio.sid' => 'AC_test',
            'services.twilio.token' => 'token',
        ]);

        Http::fake();

        app(WhatsAppManager::class)->send('9876543210', 'Hello Estele');

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'api.twilio.com/2010-04-01/Accounts/AC_test/Messages.json')
                && $request['To'] === 'whatsapp:+919876543210'
                && $request['From'] === 'whatsapp:+14155238886'
                && $request['Body'] === 'Hello Estele';
        });
    }

    public function test_sell_workflow_notifies_vendor_invitation_and_settlement_on_whatsapp(): void
    {
        $this->seed(ShieldSeeder::class);

        $spy = $this->logSpy();
        $service = app(SellRequestService::class);

        $customer = User::factory()->create(['phone' => '9876543210', 'wallet_balance' => 0]);
        $vendor = User::factory()->create(['phone' => '8765432109', 'email' => 'vendor@example.com']);
        $vendor->assignRole('vendor');

        $sell = $service->createForCustomer($customer, [
            'item_type' => 'gold',
            'city' => 'Mumbai',
            'description' => 'Antique gold chain',
        ], null, null, null, null);

        $invitation = $sell->invitations()->where('vendor_id', $vendor->id)->firstOrFail();
        $service->acceptInvitation($invitation);
        $service->submitBid($invitation->fresh(), 10000);
        $service->closeBidding($sell);

        $this->assertTrue($service->settle($sell, 10000));
        $sell->refresh();

        $this->assertSame('completed', $sell->status);
        $this->assertSame('9000.00', $sell->wallet_credit);
        $this->assertSame('9000.00', $customer->fresh()->wallet_balance);

        $toVendor = collect($spy->calls)->first(fn (array $call) => $call['phone'] === '8765432109');
        $this->assertNotNull($toVendor, 'Vendor should be notified on WhatsApp with the invitation.');
        $this->assertStringContainsString($sell->request_number, $toVendor['message']);

        $toCustomer = collect($spy->calls)->first(fn (array $call) => $call['phone'] === '9876543210');
        $this->assertNotNull($toCustomer, 'Customer should be notified on WhatsApp about the settlement.');
        $this->assertStringContainsString('settled', $toCustomer['message']);
        $this->assertStringContainsString('9,000.00', $toCustomer['message']);
    }

    public function test_placing_a_sell_request_with_no_vendors_does_not_break_whatsapp(): void
    {
        $this->seed(ShieldSeeder::class);

        $spy = $this->logSpy();
        $customer = User::factory()->create(['phone' => '9876543210', 'wallet_balance' => 0]);

        $sell = app(SellRequestService::class)->createForCustomer($customer, [
            'item_type' => 'silver',
            'city' => 'Delhi',
        ], null, null, null, null);

        $this->assertNotNull($sell->request_number);
        $this->assertEmpty($spy->calls);
    }

    public function test_order_acceptance_notifies_customer_on_whatsapp(): void
    {
        $spy = $this->logSpy();

        $customer = User::factory()->create();

        $order = Order::create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-WA-'.uniqid(),
            'customer_name' => 'WhatsApp Test',
            'customer_email' => $customer->email,
            'customer_phone' => '9876543210',
            'shipping_address_line1' => 'Test St',
            'shipping_city' => 'Hyderabad',
            'shipping_state' => 'Telangana',
            'shipping_postal_code' => '500001',
            'shipping_country' => 'India',
            'subtotal' => 1200,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'total' => 1200,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'status' => 'placed',
        ]);

        $order->update(['status' => 'accepted']);

        $this->assertSame('accepted', $order->fresh()->status);

        $call = collect($spy->calls)->first(fn (array $call) => $call['phone'] === '9876543210');
        $this->assertNotNull($call, 'Customer should be notified on WhatsApp when the order is accepted.');
        $this->assertStringContainsString($order->order_number, $call['message']);
        $this->assertSame($order->order_number, $call['context']['order_number']);
    }
}