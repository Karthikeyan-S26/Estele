import '../../models/address.dart';
import '../../models/order.dart';
import '../../models/user.dart';
import '../../models/wallet_transaction.dart';
import '../api_client.dart';

class AccountRepository {
  /// GET /api/account — the authenticated user's profile.
  static Future<User> profile() async {
    final json = await ApiClient.get('/account', auth: true);
    return User.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// PATCH /api/account/profile — update name/email.
  static Future<User> updateProfile({
    required String name,
    required String email,
  }) async {
    final json = await ApiClient.patch(
      '/account/profile',
      body: {
        'name': name,
        'email': email,
      },
      auth: true,
    );
    return User.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// GET /api/account/orders — paginated order history.
  static Future<({List<Order> items, Map<String, dynamic> meta})> orders({int page = 1, String? status}) async {
    final json = await ApiClient.get(
      '/account/orders?page=$page${status != null ? '&status=$status' : ''}',
      auth: true,
    );

    return (
      items: (json['data'] as List<dynamic>? ?? [])
          .map((e) => Order.fromJson(e as Map<String, dynamic>))
          .toList(),
      meta: (json['meta'] as Map<String, dynamic>?) ?? const {},
    );
  }

  /// GET /api/account/addresses — address book.
  static Future<({List<Address> items, Map<String, dynamic> meta})> addresses() async {
    final json = await ApiClient.get('/account/addresses', auth: true);
    return (
      items: (json['data'] as List<dynamic>? ?? [])
          .map((e) => Address.fromJson(e as Map<String, dynamic>))
          .toList(),
      meta: (json['meta'] as Map<String, dynamic>?) ?? const {},
    );
  }

  /// GET /api/account/wallet/transactions — the wallet ledger (credits, debits,
  /// and validity of expiring credits).
  static Future<({List<WalletTransaction> items, Map<String, dynamic> meta})> walletTransactions({int page = 1}) async {
    final json = await ApiClient.get('/account/wallet/transactions?page=$page', auth: true);
    return (
      items: (json['data'] as List<dynamic>? ?? [])
          .map((e) => WalletTransaction.fromJson(e as Map<String, dynamic>))
          .toList(),
      meta: (json['meta'] as Map<String, dynamic>?) ?? const {},
    );
  }

  /// POST /api/account/addresses.
  static Future<Address> storeAddress(Address address) async {
    final json = await ApiClient.post('/account/addresses', body: address.toJson(), auth: true);
    return Address.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// PATCH /api/account/addresses/{id}.
  static Future<Address> updateAddress(Address address) async {
    final json = await ApiClient.patch('/account/addresses/${address.id}', body: address.toJson(), auth: true);
    return Address.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// DELETE /api/account/addresses/{id}.
  static Future<void> deleteAddress(int id) async {
    await ApiClient.delete('/account/addresses/$id', auth: true);
  }

  /// POST /api/account/orders/{order_number}/cancellation-request — flags the
  /// order for admin review (does not cancel it directly).
  static Future<String?> requestOrderCancellation(String orderNumber, {String? reason}) async {
    final json = await ApiClient.post(
      '/account/orders/$orderNumber/cancellation-request',
      body: {
        if (reason != null && reason.isNotEmpty) 'reason': reason,
      },
      auth: true,
    );
    return json['message'] as String?;
  }
}