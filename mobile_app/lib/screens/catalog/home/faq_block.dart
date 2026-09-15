import 'package:flutter/material.dart';

import '../../../models/faq_item.dart';
import '../../../theme/app_colors.dart';
import '../../../theme/app_typography.dart';
import '../../../widgets/section_header.dart';

/// "Help Centre → Questions? Answered." — mirrors `home/blocks/faq.blade.php`:
///  - `section border-t border-line bg-paper py-7`, content `px-3`;
///  - left-aligned section-head (eyebrow → title + "All FAQs" CTA);
///  - single-column accordion of `rounded-lg border-line bg-white` cards,
///    13.5px semibold question + 12.5px muted answer.
class FaqBlock extends StatelessWidget {
  const FaqBlock({super.key, required this.faqs});

  final List<FaqItem> faqs;

  @override
  Widget build(BuildContext context) {
    if (faqs.isEmpty) return const SizedBox.shrink();

    return Container(
      // border-t border-line bg-paper py-7
      decoration: const BoxDecoration(
        color: AppColors.paper,
        border: Border(top: BorderSide(color: AppColors.line)),
      ),
      padding: const EdgeInsets.symmetric(vertical: 28),
      width: double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // align=left header, mb-4
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 16),
            child: SectionHeader(
              scriptWord: 'Help Centre',
              title: 'Questions? Answered.',
              onViewAll: () => Navigator.of(context).pushNamed('/faq'),
              viewAllLabel: 'All FAQs',
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: Column(
              children: [
                for (var i = 0; i < faqs.length; i++)
                  _FaqCard(
                    key: ValueKey('faq-$i'),
                    question: faqs[i].question,
                    answer: faqs[i].answer,
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// A single FAQ accordion card — `rounded-lg border-line bg-white px-4 py-3`.
class _FaqCard extends StatefulWidget {
  const _FaqCard({super.key, required this.question, required this.answer});

  final String question;
  final String answer;

  @override
  State<_FaqCard> createState() => _FaqCardState();
}

class _FaqCardState extends State<_FaqCard> {
  bool _open = false;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10), // gap-2.5
      decoration: BoxDecoration(
        color: AppColors.paper, // bg-white
        borderRadius: BorderRadius.circular(8), // rounded-lg
        border: Border.all(
          color: _open ? AppColors.gold : AppColors.line, // open:border-gold
        ),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: () => setState(() => _open = !_open),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      widget.question,
                      style: AppTypography.bodyMedium(
                        size: 13.5, // text-[13.5px]
                        color: AppColors.heading,
                        weight: FontWeight.w500,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Padding(
                    padding: const EdgeInsets.only(top: 0),
                    child: Icon(
                      _open
                          ? Icons.remove_rounded
                          : Icons.add_rounded,
                      size: 18,
                      color: AppColors.heading,
                    ),
                  ),
                ],
              ),
              AnimatedCrossFade(
                duration: const Duration(milliseconds: 200),
                crossFadeState: _open
                    ? CrossFadeState.showSecond
                    : CrossFadeState.showFirst,
                firstChild: const SizedBox(width: double.infinity),
                secondChild: Padding(
                  padding: const EdgeInsets.only(top: 8), // mt-2
                  child: Align(
                    alignment: Alignment.centerLeft,
                    child: Text(
                      widget.answer,
                      style: AppTypography.body(
                        size: 12.5,
                        color: AppColors.muted,
                        height: 1.6,
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}