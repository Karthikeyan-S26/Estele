/// A customer "sell your old jewellery" request, mirroring
/// `Api/SellRequestController::payload`.
class SellRequest {
  SellRequest({
    required this.id,
    required this.requestNumber,
    required this.itemType,
    this.description,
    this.city,
    this.contactPhone,
    required this.status,
    this.imageUrl,
    this.videoUrl,
    this.bidsStartAt,
    this.bidsEndAt,
    required this.biddingOpen,
    required this.bidCount,
    this.highestBidAmount,
    this.adminValuation,
    this.deductionAmount,
    this.walletCredit,
    this.settlementAt,
    this.resultSelectedAt,
    this.cancelledBy,
    this.cancelReason,
    this.createdAt,
    this.timeline = const [],
  });

  final int id;
  final String requestNumber;
  final String itemType;
  final String? description;
  final String? city;
  final String? contactPhone;
  final String status;
  final String? imageUrl;
  final String? videoUrl;
  final DateTime? bidsStartAt;
  final DateTime? bidsEndAt;
  final bool biddingOpen;
  final int bidCount;
  final double? highestBidAmount;
  final double? adminValuation;
  final double? deductionAmount;
  final double? walletCredit;
  final DateTime? settlementAt;
  final DateTime? resultSelectedAt;
  final String? cancelledBy;
  final String? cancelReason;
  final DateTime? createdAt;
  final List<SellTimelineEvent> timeline;

  bool get canCancel => status == 'pending_bids' || status == 'bidding';

  String get statusLabel {
    const map = {
      'pending_bids': 'WAITING FOR SELLERS',
      'bidding': 'BIDDING OPEN',
      'valuation_review': 'UNDER REVIEW',
      'completed': 'COMPLETED',
      'expired': 'EXPIRED',
      'cancelled': 'CANCELLED',
    };
    return map[status] ?? status.toUpperCase();
  }

  factory SellRequest.fromJson(Map<String, dynamic> json) {
    return SellRequest(
      id: json['id'] as int,
      requestNumber: json['request_number'] as String,
      itemType: json['item_type'] as String,
      description: json['description'] as String?,
      city: json['city'] as String?,
      contactPhone: json['contact_phone'] as String?,
      status: json['status'] as String,
      imageUrl: json['image_url'] as String?,
      videoUrl: json['video_url'] as String?,
      bidsStartAt: json['bids_start_at'] != null
          ? DateTime.tryParse(json['bids_start_at'] as String)
          : null,
      bidsEndAt: json['bids_end_at'] != null
          ? DateTime.tryParse(json['bids_end_at'] as String)
          : null,
      biddingOpen: json['bidding_open'] as bool? ?? false,
      bidCount: (json['bid_count'] as num?)?.toInt() ?? 0,
      highestBidAmount: (json['highest_bid_amount'] as num?)?.toDouble(),
      adminValuation: (json['admin_valuation'] as num?)?.toDouble(),
      deductionAmount: (json['deduction_amount'] as num?)?.toDouble(),
      walletCredit: (json['wallet_credit'] as num?)?.toDouble(),
      settlementAt: json['settlement_at'] != null
          ? DateTime.tryParse(json['settlement_at'] as String)
          : null,
      resultSelectedAt: json['result_selected_at'] != null
          ? DateTime.tryParse(json['result_selected_at'] as String)
          : null,
      cancelledBy: json['cancelled_by'] as String?,
      cancelReason: json['cancel_reason'] as String?,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
      timeline: ((json['timeline'] as List<dynamic>?) ?? const [])
          .map((e) => SellTimelineEvent.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class SellTimelineEvent {
  SellTimelineEvent({
    required this.event,
    this.fromStatus,
    this.toStatus,
    this.metadata = const {},
    this.at,
  });

  final String event;
  final String? fromStatus;
  final String? toStatus;
  final Map<String, dynamic> metadata;
  final DateTime? at;

  factory SellTimelineEvent.fromJson(Map<String, dynamic> json) {
    return SellTimelineEvent(
      event: json['event'] as String,
      fromStatus: json['from_status'] as String?,
      toStatus: json['to_status'] as String?,
      metadata: (json['metadata'] as Map<String, dynamic>?) ?? const {},
      at: json['at'] != null ? DateTime.tryParse(json['at'] as String) : null,
    );
  }
}
