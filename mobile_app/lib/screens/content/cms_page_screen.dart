import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';

import '../../data/repositories/content_repository.dart';
import '../../models/cms_page.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/load_state.dart';

class CmsPageScreen extends StatefulWidget {
  const CmsPageScreen({super.key, required this.slug});

  final String slug;

  @override
  State<CmsPageScreen> createState() => _CmsPageScreenState();
}

class _CmsPageScreenState extends State<CmsPageScreen> {
  CmsPage? _page;
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
      final page = await ContentRepository.page(widget.slug);
      if (mounted) {
        setState(() {
          _page = page;
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
    if (_loading) return const Scaffold(body: LoadState.loading());
    if (_failed || _page == null) {
      return Scaffold(
        appBar: AppBar(),
        body: LoadState.error(message: 'Could not load this page.', onRetry: _load),
      );
    }

    final page = _page!;
    return Scaffold(
      appBar: AppBar(title: Text(page.title)),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(page.title, style: AppTypography.editorial(size: 24)),
          const SizedBox(height: 16),
          Html(
            data: page.content,
            style: {
              'body': Style(
                fontSize: FontSize(14),
                color: AppColors.ink,
                lineHeight: LineHeight(1.6),
              ),
              'h1': Style(fontSize: FontSize(19), fontFamily: 'Cinzel', color: AppColors.heading),
              'h2': Style(fontSize: FontSize(17), fontFamily: 'Cinzel', color: AppColors.heading),
              'h3': Style(fontSize: FontSize(15), fontFamily: 'Cinzel', color: AppColors.heading),
              'a': Style(color: AppColors.accentDark),
            },
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}