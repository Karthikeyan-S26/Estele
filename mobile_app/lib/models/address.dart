/// A saved shipping address — mirrors the API `Address` payload.
class Address {
  Address({
    required this.id,
    required this.label,
    required this.line1,
    this.line2,
    required this.city,
    required this.state,
    required this.postalCode,
    required this.country,
    this.phone,
    this.isDefault = false,
  });

  final int id;
  final String label;
  final String line1;
  final String? line2;
  final String city;
  final String state;
  final String postalCode;
  final String country;
  final String? phone;
  final bool isDefault;

  String get summary => '$line1, ${line2 != null && line2!.isNotEmpty ? '$line2, ' : ''}$city, $state $postalCode';

  Address copyWith({
    String? label,
    String? line1,
    String? line2,
    String? city,
    String? state,
    String? postalCode,
    String? country,
    String? phone,
    bool? isDefault,
  }) {
    return Address(
      id: id,
      label: label ?? this.label,
      line1: line1 ?? this.line1,
      line2: line2 ?? this.line2,
      city: city ?? this.city,
      state: state ?? this.state,
      postalCode: postalCode ?? this.postalCode,
      country: country ?? this.country,
      phone: phone ?? this.phone,
      isDefault: isDefault ?? this.isDefault,
    );
  }

  factory Address.fromJson(Map<String, dynamic> json) {
    return Address(
      id: json['id'] as int,
      label: json['label'] as String? ?? '',
      line1: json['line1'] as String? ?? '',
      line2: json['line2'] as String?,
      city: json['city'] as String? ?? '',
      state: json['state'] as String? ?? '',
      postalCode: json['postal_code'] as String? ?? '',
      country: json['country'] as String? ?? 'India',
      phone: json['phone'] as String?,
      isDefault: json['is_default'] as bool? ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'label': label,
      'line1': line1,
      'line2': line2,
      'city': city,
      'state': state,
      'postal_code': postalCode,
      'country': country,
      'phone': phone,
      'is_default': isDefault,
    };
  }
}