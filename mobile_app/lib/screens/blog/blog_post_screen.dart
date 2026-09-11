import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';

import '../../data/repositories/content_repository.dart';
import '../../models/blog_post.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/app_image.dart';
import '../../widgets/load_state.dart';

class BlogPostScreen extends StatefulWidget {
  const BlogPostScreen({super.key, required this.slug});

  final String slug;

  @override
  State<BlogPostScreen> createState() => _BlogPostScreenState();
}

class _BlogPostScreenState extends State<BlogPostScreen> {
  BlogPost? _post;
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
      final post = await ContentRepository.blogPost(widget.slug);
      if (mounted) {
        setState(() {
          _post = post;
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
    if (_failed || _post == null) {
      return Scaffold(
        appBar: AppBar(),
        body: LoadState.error(message: 'Could not load this story.', onRetry: _load),
      );
    }

    final post = _post!;
    final detailImage = post.detailImageUrl ?? post.imageUrl;

    return Scaffold(
      appBar: AppBar(title: const Text('Journal')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (post.category != null)
            Text(post.category!.toUpperCase(), style: AppTypography.label(size: 10, color: AppColors.accent, letterSpacing: 1.4)),
          const SizedBox(height: 8),
          Text(post.title, style: AppTypography.editorial(size: 24)),
          const SizedBox(height: 8),
          if (post.author != null)
            Text('By ${post.author}', style: AppTypography.bodyMedium(weight: FontWeight.w600)),
          if (post.publishedAt != null)
            Text(
              '${post.publishedAt!.day}/${post.publishedAt!.month}/${post.publishedAt!.year}',
              style: AppTypography.bodySmall(size: 12),
            ),
          const SizedBox(height: 16),
          if (detailImage != null && detailImage.isNotEmpty)
            ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: SizedBox(height: 190, child: AppImage(url: detailImage)),
            ),
          const SizedBox(height: 16),
          if (post.excerpt != null)
            Text(post.excerpt!, style: AppTypography.editorial(size: 16, color: AppColors.muted)),
          const SizedBox(height: 12),
          if (post.content != null && post.content!.isNotEmpty)
            Html(
              data: post.content!,
              style: {
                'body': Style(
                  fontSize: FontSize(14),
                  color: AppColors.ink,
                  lineHeight: LineHeight(1.6),
                ),
                'h1': Style(
                  fontSize: FontSize(20),
                  fontFamily: 'Cinzel',
                  color: AppColors.heading,
                ),
                'h2': Style(fontSize: FontSize(18), fontFamily: 'Cinzel', color: AppColors.heading),
                'h3': Style(fontSize: FontSize(16), fontFamily: 'Cinzel', color: AppColors.heading),
                'a': Style(color: AppColors.accentDark),
              },
            ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}