/// An order — mirrors `App\Http\Api\OrderResource`.
class Order {
  Order({
    required this.id,
    required this.orderNumber,
    required this.customerName,
    this.customerEmail,
    this.customerPhone,
    this.shippingAddress,
    this.orderNote,
    required this.subtotal,
    this.couponCode,
    required this.discountAmount,
    required this.shippingFee,
    required this.total,
    this.walletAmountUsed = 0,
    required this.paymentMethod,
    required this.paymentStatus,
    required this.status,
    this.trackingNumber,
    this.carrier,
    this.cancellationRequested = false,
    this.cancellationReason,
    this.placedAt,
    this.items = const [],
  });

  final int id;
  final String orderNumber;
  final String customerName;
  final String? customerEmail;
  final String? customerPhone;
  final ShippingAddress? shippingAddress;
  final String? orderNote;
  final double subtotal;
  final String? couponCode;
  final double discountAmount;
  final double shippingFee;
  final double total;
  final double walletAmountUsed;
  final String paymentMethod; // COD | RAZORPAY
  final String paymentStatus;
  final String status; // placed | accepted | packed | shipped | delivered | cancelled | returned
  final String? trackingNumber;
  final String? carrier;
  final bool cancellationRequested;
  final String? cancellationReason;
  final DateTime? placedAt;
  final List<OrderItem> items;

  bool get canRequestCancellation {
    const cancellable = {'placed', 'accepted', 'packed'};
    const returnable = {'delivered'};
    return !cancellationRequested && (cancellable.contains(status) || returnable.contains(status));
  }

  /// Human-readable status for chips/banners.
  String get statusLabel {
    const map = {
      'placed': 'Placed',
      'accepted': 'Accepted',
      'packed': 'Packed',
      'shipped': 'Shipped',
      'delivered': 'Delivered',
      'cancelled': 'Cancelled',
      'returned': 'Returned',
      'processing': 'Processing',
    };
    return map[status] ?? status.replaceFirst(status[0], status[0].toUpperCase());
  }

  factory Order.fromJson(Map<String, dynamic> json) {
    return Order(
      id: json['id'] as int,
      orderNumber: json['order_number'] as String? ?? '',
      customerName: json['customer_name'] as String? ?? '',
      customerEmail: json['customer_email'] as String?,
      customerPhone: json['customer_phone'] as String?,
      shippingAddress: json['shipping_address'] != null
          ? ShippingAddress.fromJson(json['shipping_address'] as Map<String, dynamic>)
          : null,
      orderNote: json['order_note'] as String?,
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0,
      couponCode: json['coupon_code'] as String?,
      discountAmount: (json['discount_amount'] as num?)?.toDouble() ?? 0,
      shippingFee: (json['shipping_fee'] as num?)?.toDouble() ?? 0,
      total: (json['total'] as num?)?.toDouble() ?? 0,
      walletAmountUsed: (json['wallet_amount_used'] as num?)?.toDouble() ?? 0,
      paymentMethod: json['payment_method'] as String? ?? 'COD',
      paymentStatus: json['payment_status'] as String? ?? 'pending',
      status: json['status'] as String? ?? 'placed',
      trackingNumber: json['tracking_number'] as String?,
      carrier: json['carrier'] as String?,
      cancellationRequested: json['cancellation_requested'] as bool? ?? false,
      cancellationReason: json['cancellation_reason'] as String?,
      placedAt: json['placed_at'] != null ? DateTime.tryParse(json['placed_at'] as String) : null,
      items: (json['items'] as List<dynamic>?)
              ?.map((e) => OrderItem.fromJson(e as Map<String, dynamic>))
              .toList() ??
          const [],
    );
  }
}

class ShippingAddress {
  ShippingAddress({
    this.line1,
    this.line2,
    this.city,
    this.state,
    this.postalCode,
    this.country,
  });

  final String? line1;
  final String? line2;
  final String? city;
  final String? state;
  final String? postalCode;
  final String? country;

  String get singleLine => [line1, line2, city, state, postalCode].whereType<String>().join(', ');

  factory ShippingAddress.fromJson(Map<String, dynamic> json) {
    return ShippingAddress(
      line1: json['line1'] as String?,
      line2: json['line2'] as String?,
      city: json['city'] as String?,
      state: json['state'] as String?,
      postalCode: json['postal_code'] as String?,
      country: json['country'] as String?,
    );
  }
}

class OrderItem {
  OrderItem({
    required this.id,
    this.productId,
    required this.productTitle,
    required this.sku,
    required this.price,
    required this.quantity,
    required this.subtotal,
    this.variantAttributes,
  });

  final int id;
  final int? productId;
  final String productTitle;
  final String sku;
  final double price;
  final int quantity;
  final double subtotal;
  final Map<String, dynamic>? variantAttributes;

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    return OrderItem(
      id: json['id'] as int,
      productId: json['product_id'] as int?,
      productTitle: json['product_title'] as String? ?? '',
      sku: json['sku'] as String? ?? '',
      price: (json['price'] as num?)?.toDouble() ?? 0,
      quantity: (json['quantity'] as num?)?.toInt() ?? 1,
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0,
      variantAttributes: json['variant_attributes'] as Map<String, dynamic>?,
    );
  }
}