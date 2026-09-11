import '../../models/order.dart';
import '../api_client.dart';

/// The Razorpay hand-off returned by the store endpoint when an order is
/// placed with `payment_method=razorpay` and online payment is enabled.
class RazorpayHandoff {
  RazorpayHandoff({required this.razorpayOrderId, this.orderNumber});

  final String razorpayOrderId;
  final String? orderNumber;

  factory RazorpayHandoff.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;
    final orderJson = data['order'] as Map<String, dynamic>? ?? const {};
    final razorpay = data['razorpay'] as Map<String, dynamic>?;
    return RazorpayHandoff(
      razorpayOrderId: razorpay?['order_id'] as String? ?? '',
      orderNumber: orderJson['order_number'] as String?,
    );
  }
}

/// Flat checkout payload — mirrors the validated fields of
/// `CheckoutController::store` (`POST /api/checkout`).
class CheckoutDetails {
  const CheckoutDetails({
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.phone,
    required this.line1,
    this.line2,
    required this.city,
    required this.state,
    required this.postalCode,
    this.orderNote,
  });

  final String firstName;
  final String lastName;
  final String email;
  final String phone;
  final String line1;
  final String? line2;
  final String city;
  final String state;
  final String postalCode;
  final String? orderNote;

  Map<String, dynamic> toBody({required String paymentMethod}) {
    final note = orderNote;
    return {
      'customer_first_name': firstName,
      'customer_last_name': lastName,
      'customer_email': email,
      'customer_phone': phone,
      'shipping_address_line1': line1,
      'shipping_address_line2': line2,
      'shipping_city': city,
      'shipping_state': state,
      'shipping_postal_code': postalCode,
      if (note != null && note.isNotEmpty) 'order_note': note,
      'payment_method': paymentMethod,
    };
  }
}

class CheckoutRepository {
  /// Place a Cash on Delivery order. Expects an authenticated user (guests are
  /// redirected to login by the checkout screen). The backend clears the cart.
  static Future<Order> placeOrder({required CheckoutDetails details}) async {
    final json = await ApiClient.post(
      '/checkout',
      body: details.toBody(paymentMethod: 'cod'),
      auth: true,
    );
    final data = json['data'] as Map<String, dynamic>? ?? json;
    return Order.fromJson(data['order'] as Map<String, dynamic>);
  }

  /// Place a Razorpay order. Returns the razorpay order id for the hosted
  /// checkout handoff (`data.razorpay.order_id`) — or an empty string when
  /// online payment is disabled server-side.
  static Future<RazorpayHandoff> createPaymentOrder({required CheckoutDetails details}) async {
    final json = await ApiClient.post(
      '/checkout',
      body: details.toBody(paymentMethod: 'razorpay'),
      auth: true,
    );
    return RazorpayHandoff.fromJson(json);
  }

  /// Verify a completed Razorpay payment after the hosted checkout completes.
  static Future<Order> verifyPayment({
    required String orderNumber,
    required String razorpayOrderId,
    required String razorpayPaymentId,
    required String razorpaySignature,
  }) async {
    final json = await ApiClient.post(
      '/payment/callback/$orderNumber',
      body: {
        'razorpay_order_id': razorpayOrderId,
        'razorpay_payment_id': razorpayPaymentId,
        'razorpay_signature': razorpaySignature,
      },
      auth: true,
    );
    final data = json['data'] as Map<String, dynamic>? ?? json;
    return Order.fromJson(data['order'] as Map<String, dynamic>);
  }

  /// Look up city/state for a 6-digit PIN from the postal API (cached server-side).
  static Future<Map<String, dynamic>> pincodeLookup(String postalCode) async {
    final json = await ApiClient.get('/checkout/pincode/$postalCode');
    return (json['data'] as Map<String, dynamic>?) ?? const {};
  }

  /// Fetch a single order by its human order number.
  static Future<Order> orderByNumber(String orderNumber) async {
    final json = await ApiClient.get('/account/orders/$orderNumber', auth: true);
    return Order.fromJson(json['data'] as Map<String, dynamic>);
  }
}