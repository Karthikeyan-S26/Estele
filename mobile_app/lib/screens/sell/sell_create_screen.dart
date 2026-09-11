import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../../data/repositories/sell_repository.dart';
import '../../providers/auth_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';

class SellCreateScreen extends StatefulWidget {
  const SellCreateScreen({super.key});

  @override
  State<SellCreateScreen> createState() => _SellCreateScreenState();
}

class _SellCreateScreenState extends State<SellCreateScreen> {
  static const String _samplePhotoAsset = 'assets/sample/sample_photo.jpg';
  static const String _sampleVideoAsset = 'assets/sample/sample_video.mp4';

  String? _itemType;
  final _description = TextEditingController();
  final _city = TextEditingController();
  final _phone = TextEditingController();

  Uint8List? _photoBytes;
  String? _photoMime;
  Uint8List? _videoBytes;
  String? _videoMime;

  bool _submitting = false;
  String? _error;

  bool get _hasPhoto => _photoBytes != null;
  bool get _hasVideo => _videoBytes != null;

  @override
  void initState() {
    super.initState();
    final auth = context.read<AuthProvider>();
    _phone.text = auth.user?.phone ?? '';
  }

  @override
  void dispose() {
    _description.dispose();
    _city.dispose();
    _phone.dispose();
    super.dispose();
  }

  Future<void> _loadSamplePhoto() async {
    try {
      final data = await rootBundle.load(_samplePhotoAsset);
      if (!mounted) return;
      setState(() {
        _photoBytes = data.buffer.asUint8List();
        _photoMime = 'image/jpeg';
      });
    } catch (_) {
      if (mounted) setState(() => _error = 'Could not load the sample photo.');
    }
  }

  Future<void> _loadSampleVideo() async {
    try {
      final data = await rootBundle.load(_sampleVideoAsset);
      if (!mounted) return;
      setState(() {
        _videoBytes = data.buffer.asUint8List();
        _videoMime = 'video/mp4';
      });
    } catch (_) {
      if (mounted) setState(() => _error = 'Could not load the sample video.');
    }
  }

  void _clearPhoto() => setState(() {
        _photoBytes = null;
        _photoMime = null;
      });

  void _clearVideo() => setState(() {
        _videoBytes = null;
        _videoMime = null;
      });

  Future<void> _submit() async {
    final itemType = _itemType;
    if (itemType == null) {
      setState(() => _error = 'Please choose the item type.');
      return;
    }
    if (!_hasVideo) {
      setState(() => _error = 'Please attach a short video of the jewellery.');
      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });

    final photoB64 = _photoBytes == null ? null : base64Encode(_photoBytes!);
    final videoB64 = base64Encode(_videoBytes!);

    try {
      await SellRepository.create(
        itemType: itemType,
        description: _description.text.trim(),
        city: _city.text.trim(),
        contactPhone: _phone.text.trim().isEmpty ? null : _phone.text.trim(),
        imageMime: _photoMime,
        imageBase64: photoB64,
        videoMime: _videoMime ?? 'video/mp4',
        videoBase64: videoB64,
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _submitting = false;
        _error = e.toString().replaceFirst('ApiException', '').replaceAll(RegExp(r'\(\d+\):?'), '').trim();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sell your old jewellery')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Tell us about the piece', style: AppTypography.sectionTitle(size: 18)),
              const SizedBox(height: 4),
              Text(
                'Buyers will review your item and place cash offers. The settlement is credited to your Estele wallet on acceptance.',
                style: AppTypography.bodySmall(color: AppColors.muted),
              ),
              const SizedBox(height: 18),

              if (_error != null)
                Container(
                  margin: const EdgeInsets.only(bottom: 14),
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.pinkSoft,
                    borderRadius: BorderRadius.circular(3),
                    border: Border.all(color: AppColors.accent.withValues(alpha: 0.4)),
                  ),
                  child: Text(_error!, style: AppTypography.bodySmall(size: 12.5, color: AppColors.error)),
                ),

              Text('Item type', style: AppTypography.label(letterSpacing: 1)),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final type in SellRepository.itemTypes)
                    ChoiceChip(
                      label: Text(_itemTypeLabel(type)),
                      selected: _itemType == type,
                      onSelected: _submitting
                          ? null
                          : (selected) => setState(() => _itemType = selected ? type : null),
                    ),
                ],
              ),
              const SizedBox(height: 18),

              TextField(
                controller: _description,
                enabled: !_submitting,
                maxLines: 4,
                maxLength: 2000,
                decoration: const InputDecoration(
                  labelText: 'Description (optional)',
                  hintText: 'Weight, purity, brand, how long you have owned it…',
                  border: OutlineInputBorder(),
                  counterText: '',
                ),
              ),
              const SizedBox(height: 14),

              TextField(
                controller: _city,
                enabled: !_submitting,
                decoration: const InputDecoration(
                  labelText: 'City',
                  hintText: 'Where would you like the offer?',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 14),

              TextField(
                controller: _phone,
                enabled: !_submitting,
                keyboardType: TextInputType.phone,
                maxLength: 15,
                decoration: const InputDecoration(
                  labelText: 'Contact number',
                  prefixText: '+91 ',
                  border: OutlineInputBorder(),
                  counterText: '',
                ),
              ),
              const SizedBox(height: 22),

              Text('Photos & video', style: AppTypography.label(letterSpacing: 1)),
              const SizedBox(height: 8),
              _MediaCard(
                icon: _hasPhoto ? Icons.check_circle : Icons.image_outlined,
                title: _hasPhoto ? 'Photo attached' : 'Photo (optional)',
                subtitle: _hasPhoto
                    ? '${_photoBytes!.length ~/ 1024} KB · JPEG'
                    : 'JPEG/PNG/WebP, up to 3 MB',
                preview: _hasPhoto ? Image.memory(_photoBytes!, fit: BoxFit.cover) : null,
                trailing: _hasPhoto
                    ? TextButton(onPressed: _submitting ? null : _clearPhoto, child: const Text('Remove'))
                    : FilledButton.tonal(
                        onPressed: _submitting ? null : _loadSamplePhoto,
                        child: const Text('Use sample photo'),
                      ),
              ),
              const SizedBox(height: 10),
              _MediaCard(
                icon: _hasVideo ? Icons.check_circle : Icons.videocam_outlined,
                title: _hasVideo ? 'Video attached' : 'Short video (required)',
                subtitle: _hasVideo
                    ? '${_videoBytes!.length ~/ 1024} KB · MP4'
                    : 'MP4/WebM, up to 20 MB',
                trailing: _hasVideo
                    ? TextButton(onPressed: _submitting ? null : _clearVideo, child: const Text('Remove'))
                    : FilledButton.tonal(
                        onPressed: _submitting ? null : _loadSampleVideo,
                        child: const Text('Use sample clip'),
                      ),
              ),

              const SizedBox(height: 24),
              FilledButton(
                onPressed: _submitting ? null : _submit,
                style: FilledButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  backgroundColor: AppColors.deepWine,
                ),
                child: _submitting
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('Submit for valuation'),
              ),
              const SizedBox(height: 8),
              Text(
                'Key in fewer flows: demo uses a bundled sample clip and photo so the full submission works offline.',
                style: AppTypography.bodySmall(size: 11, color: AppColors.muted),
              ),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  String _itemTypeLabel(String itemType) {
    const map = {
      'ring': 'Ring',
      'chain': 'Chain',
      'necklace': 'Necklace',
      'earrings': 'Earrings',
      'bracelet': 'Bracelet',
      'other': 'Other',
    };
    return map[itemType] ?? itemType;
  }
}

class _MediaCard extends StatelessWidget {
  const _MediaCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.trailing,
    this.preview,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Widget trailing;
  final Widget? preview;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: AppColors.line),
      ),
      child: Row(
        children: [
          if (preview != null)
            ClipRRect(
              borderRadius: BorderRadius.circular(3),
              child: SizedBox(width: 46, height: 46, child: preview),
            )
          else
            Icon(icon, size: 24, color: AppColors.accentDark),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: AppTypography.bodyMedium(size: 13, weight: FontWeight.w600)),
                const SizedBox(height: 2),
                Text(subtitle, style: AppTypography.bodySmall(size: 11.5)),
              ],
            ),
          ),
          trailing,
        ],
      ),
    );
  }
}