import 'package:flutter/material.dart';

import '../../data/repositories/content_repository.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/app_image.dart';
import '../../widgets/load_state.dart';

/// Stores tab — flagship + boutique showrooms. Data comes from `/stores`.
class StoresScreen extends StatefulWidget {
  const StoresScreen({super.key});

  @override
  State<StoresScreen> createState() => _StoresScreenState();
}

class _StoresScreenState extends State<StoresScreen> {
  List<Map<String, dynamic>>? _data;
  bool _loading = true;
  bool _failed = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _failed = false;
    });
    try {
      final data = await ContentRepository.stores();
      if (mounted) {
        setState(() {
          _data = data;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() {
        _failed = true;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const LoadState.loading();
    if (_failed && _data == null) {
      return LoadState.error(message: 'Could not load stores.', onRetry: _load);
    }

    final stores = _data ?? const [];

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        children: [
          Text('Our stores', style: AppTypography.scriptAccent(size: 30)),
          Text('Visit us', style: AppTypography.sectionTitle(size: 20)),
          const SizedBox(height: 12),
          if (stores.isEmpty)
            const LoadState.empty(message: 'No stores listed yet.')
          else
            ...stores.map((store) => _StoreCard(store: store)),
          const SizedBox(height: 8),
        ],
      ),
    );
  }
}

class _StoreCard extends StatelessWidget {
  const _StoreCard({required this.store});

  final Map<String, dynamic> store;

  @override
  Widget build(BuildContext context) {
    final image = store['image'] as String?;
    final name = store['name'] as String? ?? '';
    final city = store['city'] as String? ?? '';
    final address = store['address'] as String? ?? '';
    final hours = store['hours'] as String?;
    final phone = store['phone'] as String?;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: AppColors.paper,
        shape: BoxShape.rectangle,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: AppColors.line),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (image != null && image.isNotEmpty)
            SizedBox(height: 140, child: AppImage(url: image)),
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, style: AppTypography.sectionTitle(size: 16)),
                Text(city, style: AppTypography.bodyMedium(weight: FontWeight.w500, color: AppColors.accent)),
                const SizedBox(height: 6),
                if (address.isNotEmpty)
                  Text(address, style: AppTypography.body(size: 13, color: AppColors.muted)),
                const SizedBox(height: 4),
                if (hours != null && hours.isNotEmpty)
                  Text('Hours: $hours', style: AppTypography.bodySmall(size: 12, color: AppColors.muted)),
                if (phone != null && phone.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(phone, style: AppTypography.bodySmall(size: 12, color: AppColors.ink, weight: FontWeight.w600)),
                ],
                const SizedBox(height: 10),
                Row(
                  children: [
                    OutlinedButton.icon(
                      onPressed: () {
                        // Backend may include a maps href.
                        final mapsUrl = store['maps_url'] as String?;
                        if (mapsUrl != null && mapsUrl.isNotEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Opening directions…')),
                          );
                        }
                      },
                      icon: const Icon(Icons.directions_outlined, size: 17),
                      label: const Text('Directions'),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}