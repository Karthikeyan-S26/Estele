import 'package:flutter/material.dart';

import '../../data/repositories/content_repository.dart';
import '../../models/blog_post.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/app_image.dart';
import '../../widgets/load_state.dart';
import 'blog_post_screen.dart';

class BlogScreen extends StatefulWidget {
  const BlogScreen({super.key});

  @override
  State<BlogScreen> createState() => _BlogScreenState();
}

class _BlogScreenState extends State<BlogScreen> {
  List<BlogPost>? _posts;
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
      final posts = await ContentRepository.blog();
      if (mounted) {
        setState(() {
          _posts = posts;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted)
        setState(() {
          _failed = true;
          _loading = false;
        });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('The Journal')),
      body: _loading
          ? const LoadState.loading()
          : _failed && _posts == null
          ? LoadState.error(message: 'Could not load journal.', onRetry: _load)
          : _posts!.isEmpty
          ? LoadState.empty(message: 'No stories published yet.')
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.builder(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                itemCount: _posts!.length,
                itemBuilder: (context, i) {
                  final post = _posts![i];
                  return _PostCard(
                    post: post,
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => BlogPostScreen(slug: post.slug),
                      ),
                    ),
                  );
                },
              ),
            ),
    );
  }
}

class _PostCard extends StatelessWidget {
  const _PostCard({required this.post, required this.onTap});

  final BlogPost post;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: AppColors.paper,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: AppColors.line),
      ),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (post.imageUrl != null && post.imageUrl!.isNotEmpty)
              SizedBox(height: 150, child: AppImage(url: post.imageUrl)),
            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (post.category != null)
                    Text(
                      post.category!.toUpperCase(),
                      style: AppTypography.label(
                        size: 10,
                        color: AppColors.accent,
                        letterSpacing: 1.4,
                      ),
                    ),
                  const SizedBox(height: 6),
                  Text(post.title, style: AppTypography.editorial(size: 18)),
                  if (post.excerpt != null) ...[
                    const SizedBox(height: 6),
                    Text(
                      post.excerpt!,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: AppTypography.body(
                        size: 13,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                  if (post.publishedAt != null) ...[
                    const SizedBox(height: 8),
                    Text(
                      '${post.publishedAt!.day} ${_month(post.publishedAt!.month)} ${post.publishedAt!.year}',
                      style: AppTypography.bodySmall(size: 11.5),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _month(int m) {
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
    return months[(m - 1).clamp(0, 11)];
  }
}
