import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// The website's "Estele Style Expert" support chat, ported to a floating
/// widget. Mirrors `layouts/app.blade.php` exactly:
///  - round white 50px button with the chat-avatar, a red "1" badge and a
///    green online dot;
///  - popover panel: `#232323` header with "Estele Style Expert"/Online,
///    `#F7F5F6` message log, two quick-reply chips, and the
///    "You can talk to me in any language" composer with a black send button.
///
/// The chat avatar SVG (`backend/public/assets/images/chat-avatar.svg`) is
/// reproduced here as [_ChatAvatarPainter] — no SVG runtime needed.
class ChatWidget extends StatefulWidget {
  const ChatWidget({super.key});

  @override
  State<ChatWidget> createState() => _ChatWidgetState();
}

class _ChatWidgetState extends State<ChatWidget> {
  bool _open = false;
  final _controller = TextEditingController();
  final _scroll = ScrollController();
  final List<_ChatMessage> _messages = [
    _ChatMessage.fromBot('Hey! **How can I help you?**'),
  ];

  @override
  void dispose() {
    _controller.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _toggle() => setState(() => _open = !_open);

  void _send(String? raw) {
    final text = raw?.trim() ?? '';
    if (text.isEmpty) return;
    setState(() {
      _messages.add(_ChatMessage.fromUser(text));
      _messages.add(_ChatMessage.fromBot(_replyFor(text)));
    });
    _controller.clear();
    _scrollToBottom();
  }

  void _quickReply(String label) {
    setState(() {
      _messages.add(_ChatMessage.fromUser(label));
      _messages.add(_ChatMessage.fromBot(_replyFor(label)));
    });
    _scrollToBottom();
  }

  String _replyFor(String text) {
    final t = text.toLowerCase();
    if (t.contains('best seller') || t.contains('best')) {
      return 'Our Bestsellers are updated every week — tap Categories on the home screen to explore them. Anything specific you\'re looking for?';
    }
    if (t.contains('suggest') || t.contains('recommend')) {
      return 'I\'d suggest starting with our Necklace Sets or new Bridal drops. Use the search bar to find something for a special occasion!';
    }
    if (t.contains('return') || t.contains('exchange')) {
      return 'We offer 7-day easy returns with hassle-free exchange. Visit the FAQ screen for the full policy.';
    }
    if (t.contains('ship') || t.contains('deliver') || t.contains('order')) {
      return 'We ship across India with free express shipping on orders above ₹1,499.';
    }
    if (t.contains('track') || t.contains('where')) {
      return 'You can track your order anytime from the My Orders section of your account.';
    }
    if (t.contains('anti') || t.contains('tarnish')) {
      return 'All Estele jewellery is 100% anti-tarnish with plating that stays bright.';
    }
    return 'Thanks for your message! One of our style experts will assist shortly. For anything urgent, write to care@estele.in.';
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.animateTo(
          _scroll.position.maxScrollExtent,
          duration: const Duration(milliseconds: 220),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.sizeOf(context).width;
    final screenHeight = MediaQuery.sizeOf(context).height;

    return Align(
      alignment: Alignment.bottomRight,
      child: ConstrainedBox(
        constraints: BoxConstraints(
          maxWidth: (screenWidth - 24).clamp(0, double.infinity),
          maxHeight: (screenHeight - 24).clamp(0, double.infinity),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            if (_open) ...[
              Flexible(
                child: ConstrainedBox(
                  constraints: BoxConstraints(
                    maxWidth: (screenWidth - 36).clamp(0, 340),
                  ),
                  child: _buildPanel(context),
                ),
              ),
              const SizedBox(height: 12),
            ],
            _buildButton(),
          ],
        ),
      ),
    );
  }

  Widget _buildButton() {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        Material(
          color: Colors.white,
          shape: const CircleBorder(),
          elevation: 6,
          shadowColor: Colors.black.withValues(alpha: 0.15),
          child: InkWell(
            key: const ValueKey('chat-toggle-button'),
            customBorder: const CircleBorder(),
            onTap: _toggle,
            child: SizedBox(
              width: 50,
              height: 50,
              child: Padding(
                padding: const EdgeInsets.all(6),
                child: CustomPaint(painter: _ChatAvatarPainter()),
              ),
            ),
          ),
        ),
        // Red "1" badge (top-right corner of the button).
        if (!_open)
          Positioned(
            right: -2,
            top: -2,
            child: Container(
              height: 20,
              constraints: const BoxConstraints(minWidth: 20),
              padding: const EdgeInsets.symmetric(horizontal: 4),
              decoration: const BoxDecoration(
                color: Color(0xFFEB001B),
                borderRadius: BorderRadius.all(Radius.circular(10)),
              ),
              alignment: Alignment.center,
              child: const Text(
                '1',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 11,
                  fontWeight: FontWeight.w500,
                  height: 1,
                ),
              ),
            ),
          ),
        // Green online dot (bottom-right).
        const Positioned(
          right: 2,
          bottom: 2,
          child: DecoratedBox(
            decoration: BoxDecoration(
              color: Color(0xFF22C55E),
              shape: BoxShape.circle,
              border: Border.fromBorderSide(
                BorderSide(color: Colors.white, width: 2),
              ),
            ),
            child: SizedBox(width: 12, height: 12),
          ),
        ),
      ],
    );
  }

  Widget _buildPanel(BuildContext context) {
    return Material(
      color: Colors.white,
      elevation: 24,
      shadowColor: Colors.black.withValues(alpha: 0.28),
      borderRadius: BorderRadius.circular(18),
      clipBehavior: Clip.antiAlias,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Header — #232323.
          Container(
            color: const Color(0xFF232323),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Estele Style Expert',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Container(
                            width: 10,
                            height: 10,
                            decoration: const BoxDecoration(
                              color: Color(0xFF22C55E),
                              shape: BoxShape.circle,
                            ),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            'Online',
                            style: AppTypography.bodySmall(
                              size: 12,
                              color: const Color(0xFFCBD5E1),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                InkWell(
                  onTap: _toggle,
                  child: const Padding(
                    padding: EdgeInsets.all(4),
                    child: Text(
                      '\u00d7',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 24,
                        height: 1,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          // Message log — #F7F5F6. Flexible so a short screen truncates the
          // log instead of overflowing the panel.
          Flexible(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 280),
              child: Container(
                width: double.infinity,
                color: const Color(0xFFF7F5F6),
                padding: const EdgeInsets.all(16),
                child: ListView.separated(
                  controller: _scroll,
                  shrinkWrap: true,
                  itemCount: _messages.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 12),
                  itemBuilder: (context, i) {
                    final message = _messages[i];
                    return Align(
                      alignment: message.fromUser
                          ? Alignment.centerRight
                          : Alignment.centerLeft,
                      child: Container(
                        constraints: const BoxConstraints(maxWidth: 300),
                        padding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                        decoration: BoxDecoration(
                          color: message.fromUser
                              ? AppColors.accent
                              : Colors.white,
                          borderRadius: BorderRadius.circular(28),
                          border: message.fromUser
                              ? null
                              : Border.all(color: AppColors.line),
                          boxShadow: message.fromUser
                              ? null
                              : [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.04),
                                    blurRadius: 4,
                                  ),
                                ],
                        ),
                        child: Text(
                          message.text,
                          style: TextStyle(
                            color: message.fromUser
                                ? Colors.white
                                : AppColors.heading,
                            fontSize: 13,
                            height: 1.45,
                            fontWeight: message.fromUser
                                ? FontWeight.w500
                                : FontWeight.w400,
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ),
            ),
          ),
          // Quick replies.
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
            child: Column(
              children: [
                _quickReplyChip('Suggest something for me'),
                const SizedBox(height: 10),
                _quickReplyChip('Tell me about best seller'),
              ],
            ),
          ),
          // Composer.
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _controller,
                    textInputAction: TextInputAction.send,
                    onSubmitted: _send,
                    decoration: InputDecoration(
                      hintText: 'You can talk to me in any language',
                      hintStyle: TextStyle(
                        color: AppColors.muted,
                        fontSize: 13,
                      ),
                      isDense: true,
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 12,
                      ),
                      filled: true,
                      fillColor: const Color(0xFFF4F2F3),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(999),
                        borderSide: const BorderSide(
                          color: AppColors.lineStrong,
                        ),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(999),
                        borderSide: const BorderSide(
                          color: AppColors.lineStrong,
                        ),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(999),
                        borderSide: const BorderSide(color: AppColors.accent),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                InkWell(
                  key: const ValueKey('chat-send-button'),
                  onTap: () => _send(_controller.text),
                  customBorder: const CircleBorder(),
                  child: Container(
                    width: 38,
                    height: 38,
                    decoration: const BoxDecoration(
                      color: Color(0xFF1F1F1F),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.send_rounded,
                      color: Colors.white,
                      size: 18,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _quickReplyChip(String label) {
    return SizedBox(
      width: double.infinity,
      child: InkWell(
        onTap: () => _quickReply(label),
        borderRadius: BorderRadius.circular(999),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(999),
            border: Border.all(color: const Color(0xFFDADADA)),
          ),
          child: Text(
            label,
            style: AppTypography.body(size: 13, color: AppColors.heading),
          ),
        ),
      ),
    );
  }
}

class _ChatMessage {
  const _ChatMessage(this.text, {required this.fromUser});

  factory _ChatMessage.fromBot(String text) =>
      _ChatMessage(text, fromUser: false);
  factory _ChatMessage.fromUser(String text) =>
      _ChatMessage(text, fromUser: true);

  final String text;
  final bool fromUser;
}

/// Reproduces `backend/public/assets/images/chat-avatar.svg` (viewBox 120×120)
/// using only Flutter painting primitives.
class _ChatAvatarPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final s = size.shortestSide.clamp(1, 10000);
    final k = s / 120;

    // Background circle — #FBE4EA.
    canvas.drawCircle(
      Offset(60 * k, 60 * k),
      60 * k,
      Paint()
        ..color = const Color(0xFFFBE4EA)
        ..style = PaintingStyle.fill,
    );

    // Face — #F2B993.
    canvas.drawCircle(
      Offset(60 * k, 68 * k),
      29 * k,
      Paint()..color = const Color(0xFFF2B993),
    );

    // Hair — #4A2F2A.
    final hair = Path()
      ..moveTo(60 * k, 22 * k)
      ..cubicTo(40 * k, 22 * k, 26 * k, 37 * k, 26 * k, 56 * k)
      ..cubicTo(27 * k, 51 * k, 35.5 * k, 49 * k, 45.5 * k, 54 * k)
      ..lineTo(74.5 * k, 54 * k)
      ..cubicTo(81.5 * k, 54 * k, 88.5 * k, 62 * k, 89.5 * k, 74 * k)
      ..cubicTo(92.5 * k, 69 * k, 94 * k, 63 * k, 94 * k, 56 * k)
      ..cubicTo(94 * k, 37 * k, 80 * k, 22 * k, 60 * k, 22 * k)
      ..close();
    canvas.drawPath(hair, Paint()..color = const Color(0xFF4A2F2A));

    // Eyes — #3A2620.
    canvas.drawCircle(
      Offset(49 * k, 69 * k),
      3.6 * k,
      Paint()..color = const Color(0xFF3A2620),
    );
    canvas.drawCircle(
      Offset(71 * k, 69 * k),
      3.6 * k,
      Paint()..color = const Color(0xFF3A2620),
    );

    // Smile — #8A4A3A, 3.2px round cap stroke.
    final smile = Path()
      ..moveTo(49 * k, 81 * k)
      ..cubicTo(53.5 * k, 85.5 * k, 71 * k, 85.5 * k, 71 * k, 81 * k);
    final smilePaint = Paint()
      ..color = const Color(0xFF8A4A3A)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3.2 * k
      ..strokeCap = StrokeCap.round;
    canvas.drawPath(smile, smilePaint);
  }

  @override
  bool shouldRepaint(covariant _ChatAvatarPainter oldDelegate) => false;
}
