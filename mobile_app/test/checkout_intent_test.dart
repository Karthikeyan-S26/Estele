import 'package:flutter_test/flutter_test.dart';

import 'package:estele/providers/checkout_intent.dart';

void main() {
  test('starts disarmed', () {
    CheckoutIntent.disarm();
    expect(CheckoutIntent.isArmed, isFalse);
  });

  test('arm records a pending Buy Now checkout', () {
    CheckoutIntent.arm();
    expect(CheckoutIntent.isArmed, isTrue);
  });

  test('disarm clears the pending intent', () {
    CheckoutIntent.arm();
    CheckoutIntent.disarm();
    expect(CheckoutIntent.isArmed, isFalse);
  });
}