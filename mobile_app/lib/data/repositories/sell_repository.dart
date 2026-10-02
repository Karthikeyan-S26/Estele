import '../../models/sell_request.dart';
import '../api_client.dart';

/// Old jewellery sell requests — mirrors the backend
/// `Api/SellRequestController` API.
class SellRepository {
  static const List<String> itemTypes = [
    'ring',
    'chain',
    'necklace',
    'earrings',
    'bracelet',
    'other',
  ];

  /// GET /api/account/sell/requests — the customer's sell requests (newest first).
  static Future<List<SellRequest>> list() async {
    final json = await ApiClient.get('/account/sell/requests', auth: true);
    return (json['data'] as List<dynamic>? ?? [])
        .map((e) => SellRequest.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// GET /api/account/sell/requests/{requestNumber}.
  static Future<SellRequest> show(String requestNumber) async {
    final json = await ApiClient.get(
      '/account/sell/requests/$requestNumber',
      auth: true,
    );
    return SellRequest.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// POST /api/account/sell/requests — create a request. The video is required;
  /// the image is optional. Media arrive as base64 strings with their mime type
  /// ({ 'mime', 'data' }) — image jpeg/png/webp ≤3MB, video mp4/webm ≤20MB.
  static Future<SellRequest> create({
    required String itemType,
    String? description,
    String? city,
    String? contactPhone,
    String? imageMime,
    String? imageBase64,
    required String videoMime,
    required String videoBase64,
  }) async {
    final json = await ApiClient.post(
      '/account/sell/requests',
      body: {
        'item_type': itemType,
        if (description != null && description.trim().isNotEmpty)
          'description': description.trim(),
        if (city != null && city.trim().isNotEmpty) 'city': city.trim(),
        if (contactPhone != null && contactPhone.isNotEmpty)
          'contact_phone': contactPhone.trim(),
        if (imageBase64 != null)
          'image': {'mime': imageMime ?? 'image/jpeg', 'data': imageBase64},
        'video': {'mime': videoMime, 'data': videoBase64},
      },
      auth: true,
    );
    return SellRequest.fromJson(json['data'] as Map<String, dynamic>);
  }

  /// POST /api/account/sell/requests/{requestNumber}/cancel.
  static Future<SellRequest> cancel(String requestNumber, String reason) async {
    final json = await ApiClient.post(
      '/account/sell/requests/$requestNumber/cancel',
      body: {'reason': reason},
      auth: true,
    );
    return SellRequest.fromJson(json['data'] as Map<String, dynamic>);
  }
}
