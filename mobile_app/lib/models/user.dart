/// A small user model mirroring the backend `Api/AuthController::userPayload`
/// and `Api/AccountController::index` JSON.
class User {
  User({
    required this.id,
    required this.name,
    this.email,
    this.phone,
    this.walletBalance = 0,
    this.createdAt,
  });

  final int id;
  final String name;
  final String? email;
  final String? phone;
  final double walletBalance;
  final DateTime? createdAt;

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      email: json['email'] as String?,
      phone: json['phone'] as String?,
      walletBalance: (json['wallet_balance'] as num?)?.toDouble() ?? 0,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
    );
  }
}
