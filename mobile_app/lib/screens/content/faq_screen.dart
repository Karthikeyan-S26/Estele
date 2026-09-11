import 'package:flutter/material.dart';

import '../../data/repositories/content_repository.dart';
import '../../models/faq_item.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/load_state.dart';

class FaqScreen extends StatefulWidget {
  const FaqScreen({super.key});

  @override
  State<FaqScreen> createState() => _FaqScreenState();
}

class _FaqScreenState extends State<FaqScreen> {
  List<FaqCategory>? _categories;
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
      final categories = await ContentRepository.faqs();
      if (mounted) {
        setState(() {
          _categories = categories;
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
    return Scaffold(
      appBar: AppBar(title: const Text('Help & FAQ')),
      body: _loading
          ? const LoadState.loading()
          : _failed && _categories == null
              ? LoadState.error(message: 'Could not load FAQ.', onRetry: _load)
              : _categories!.isEmpty
                  ? LoadState.empty(message: 'No FAQs published yet.')
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _categories!.length,
                      itemBuilder: (context, i) {
                        final category = _categories![i];
                        return _CategorySection(category: category);
                      },
                    ),
    );
  }
}

class _CategorySection extends StatelessWidget {
  const _CategorySection({required this.category});

  final FaqCategory category;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(top: 8, bottom: 6),
          child: Text(category.name, style: AppTypography.sectionTitle(size: 16)),
        ),
        for (final faq in category.faqs)
          _FaqTile(faq: faq),
        const SizedBox(height: 12),
      ],
    );
  }
}

class _FaqTile extends StatelessWidget {
  const _FaqTile({required this.faq});

  final FaqItem faq;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: AppColors.line),
      ),
      child: Theme(
        data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          title: Text(faq.question, style: AppTypography.bodyMedium(weight: FontWeight.w600, size: 13.5)),
          childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 14),
          tilePadding: const EdgeInsets.symmetric(horizontal: 16),
          children: [
            Align(
              alignment: Alignment.centerLeft,
              child: Text(faq.answer, style: AppTypography.body(size: 13.5, color: AppColors.ink)),
            ),
          ],
        ),
      ),
    );
  }
}