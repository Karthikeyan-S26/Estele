/// Carries a Buy Now purchase intent across the guest auth wall.
///
/// The buy-now item is already inside the (guest) server cart before auth
/// starts, and the backend merges that guest cart into the authenticated
/// cart at OTP verification / account creation. So the intent only has to
/// remember *where to land* once sign-in (or registration) completes:
/// straight back into checkout instead of dropping the user at the root.
class CheckoutIntent {
  CheckoutIntent._();

  static bool _armed = false;

  /// Whether a Buy Now checkout is waiting for authentication to finish.
  static bool get isArmed => _armed;

  /// Set right before pushing the login screen for a Buy Now express order.
  static void arm() => _armed = true;

  /// Consumed once the user lands in checkout (or auth is abandoned).
  static void disarm() => _armed = false;
}