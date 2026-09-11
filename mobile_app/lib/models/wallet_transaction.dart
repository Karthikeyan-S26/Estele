/// A row in the wallet ledger, mirroring
/// `Api/AccountController::walletTransactions`.
class WalletTransaction {
  WalletTransaction({
    required this.id,
    required this.type,
    required this.amount,
    required this.balanceAfter,
    required this.reason,
    required this.status,
    this.expiresAt,
    this.createdAt,
  });

  final int id;
  final String type; // credit | debit
  final double amount;
  final double balanceAfter;
  final String reason;
  final String status; // active | expiring | expired
  final DateTime? expiresAt;
  final DateTime? createdAt;

  bool get isCredit => type == 'credit';

  factory WalletTransaction.fromJson(Map<String, dynamic> json) {
    return WalletTransaction(
      id: json['id'] as int,
      type: json['type'] as String? ?? 'credit',
      amount: (json['amount'] as num?)?.toDouble() ?? 0,
      balanceAfter: (json['balance_after'] as num?)?.toDouble() ?? 0,
      reason: json['reason'] as String? ?? '',
      status: json['status'] as String? ?? 'active',
      expiresAt: json['expires_at'] != null ? DateTime.tryParse(json['expires_at'] as String) : null,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'] as String) : null,
    );
  }
}