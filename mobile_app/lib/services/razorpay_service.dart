import 'dart:async';

import 'package:razorpay_flutter/razorpay_flutter.dart';

/// Result of an in-app Razorpay checkout session.
class RazorpayOutcome {
  const RazorpayOutcome.success({
    required this.paymentId,
    required this.orderId,
    required this.signature,
  })  : success = true,
        message = null;

  const RazorpayOutcome.failure(
    this.message, {
    this.paymentId,
    this.orderId,
    this.signature,
  }) : success = false;

  final bool success;
  final String? paymentId;
  final String? orderId;
  final String? signature;
  final String? message;
}

/// Thin wrapper over the Razorpay Standard Checkout SDK that turns the event
/// callbacks into a single [Future]. One Razorpay instance per checkout.
class RazorpayService {
  /// Launch the in-app Razorpay checkout and await its outcome.
  ///
  /// [amountPaise] must be the order amount in paise (the backend's
  /// `data.razorpay.amount` — already paise).
  static Future<RazorpayOutcome> open({
    required String keyId,
    required int amountPaise,
    required String orderId,
    required String orderNumber,
    required String contact,
    required String email,
  }) async {
    final razorpay = Razorpay();
    final completer = Completer<RazorpayOutcome>();

    void onSuccess(PaymentSuccessResponse response) {
      if (completer.isCompleted) return;
      completer.complete(RazorpayOutcome.success(
        paymentId: response.paymentId,
        orderId: response.orderId,
        signature: response.signature,
      ));
    }

    void onError(PaymentFailureResponse failure) {
      if (completer.isCompleted) return;
      final message = failure.message?.isNotEmpty == true
          ? failure.message!
          : 'Payment could not be completed.';
      completer.complete(RazorpayOutcome.failure(message));
    }

    razorpay.on(Razorpay.EVENT_PAYMENT_SUCCESS, onSuccess);
    razorpay.on(Razorpay.EVENT_PAYMENT_ERROR, onError);

    try {
      razorpay.open({
        'key': keyId,
        'amount': amountPaise,
        'order_id': orderId,
        'name': 'Estele',
        'currency': 'INR',
        'description': 'Order $orderNumber',
        'prefill': {
          'contact': contact,
          'email': email,
        },
        'theme': {
          'color': '#AD3D5F',
        },
      });
    } catch (_) {
      if (!completer.isCompleted) {
        completer.complete(const RazorpayOutcome.failure(
            'Could not start the payment page. Please try again.'));
      }
    }

    return completer.future.whenComplete(razorpay.clear);
  }
}