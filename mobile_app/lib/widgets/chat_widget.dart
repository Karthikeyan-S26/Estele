import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

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

  /// Conversation state mirroring the website (`g.name`, `g.nameSkipped`).
  /// No persistence beyond the widget lifetime — the site keeps it in
  /// sessionStorage (fresh greeting per tab session); the app keeps it
  /// while the shell lives, and End chat forgets it, same as the site.
  String? _name;
  bool _nameSkipped = false;
  bool _typing = false;

  // Website's exact opening line (its <strong> renders bold — an earlier
  // build leaked the raw ** markers as literal text).
  final List<_ChatMessage> _messages = [
    _ChatMessage.greeting(),
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
    setState(() => _messages.add(_ChatMessage.fromUser(text)));
    _controller.clear();
    _scrollToBottom();
    _handle(text);
  }

  void _quickReply(String label) {
    setState(() => _messages.add(_ChatMessage.fromUser(label)));
    _scrollToBottom();
    _handle(label);
  }

  String get _firstName {
    final name = _name;
    if (name == null || name.isEmpty) return '';
    return name.split(' ').first;
  }

  /// Website `G(t)`: append ", {firstName}" when the name is known.
  String _named(String text) {
    final first = _firstName;
    return first.isEmpty ? text : '$text, $first';
  }

  /// The website's main message handler (`J(e)` in theme/app.js), ported
  /// branch-for-branch: intent match first (answers even before the name
  /// is known, and skips the name flow), then greeting/thanks, then name
  /// extraction, then the short/long fallbacks.
  void _handle(String rawText) {
    final text = rawText.trim();
    final intent = _matchIntent(text);
    if (_name == null && !_nameSkipped) {
      if (intent != null) {
        _nameSkipped = true;
        _answer(intent);
        return;
      }
      if (_isGreeting(text) || _isThanks(text)) {
        _answer(const _Reply('Hi there! May I know your name, please?'));
        return;
      }
      final name = _extractName(text);
      if (name != null) {
        _name = name;
        _answer(
          _Reply(
            'Nice to meet you, $name! 😊\nHow can I help you today?',
            chips: true,
          ),
        );
        return;
      }
      if (text.split(RegExp(r'\s+')).length <= 3) {
        _answer(
          const _Reply(
              "Sorry, I didn't catch that. May I know your name, please?"),
        );
        return;
      }
      _nameSkipped = true;
      _answer(_fallbackReply());
      return;
    }
    if (intent != null) {
      _answer(intent);
      return;
    }
    if (_isGreeting(text)) {
      _answer(_Reply(_named('Hello again') + '! How can I help you today?',
          chips: true));
      return;
    }
    if (_isThanks(text) && text.split(RegExp(r'\s+')).length <= 4) {
      _answer(_Reply(
          _named("You're welcome") +
              '! Is there anything else I can help you with?',
          chips: true));
      return;
    }
    _answer(_fallbackReply());
  }

  /// Website greeting pattern (`V`): hi/hey/hello/helo/hlo/namaste/
  /// namaskar/hola/yo/good morning|afternoon|evening, whole message.
  bool _isGreeting(String text) {
    return RegExp(
      r'^(hi+|hey+|hello+|helo|hlo|namaste|namaskar|hola|yo|good\s+(morning|afternoon|evening))[\s!.,]*$',
      caseSensitive: false,
    ).hasMatch(text.trim());
  }

  /// Website thanks pattern (`H`): ok/thanks/thank you/thanku/thx/ty/
  /// great/cool/nice/bye/goodbye at the start.
  bool _isThanks(String text) {
    return RegExp(
      r'^(ok(ay)?|thanks?|thank\s*you|thanku|thx|ty|great|cool|nice|bye|goodbye)\b',
      caseSensitive: false,
    ).hasMatch(text.trim());
  }

  /// Website name extraction (`re(e)`): strip a leading greeting, strip a
  /// name-introduction prefix, strip trailing punctuation; reject empty,
  /// symbol/digit content, >3 words or >40 chars; Title-Case each word.
  String? _extractName(String text) {
    var t = text.trim();
    t = t.replaceAll(
      RegExp(
        r'^(hi+|hey+|hello+|helo|hlo|namaste|namaskar)\b[\s!.,]*',
        caseSensitive: false,
      ),
      '',
    );
    t = t.replaceAll(
      RegExp(
        r"^(my\s+name\s+is|my\s+name's|name\s+is|i\s+am|i'm|im|this\s+is|it's|its|call\s+me)\s+",
        caseSensitive: false,
      ),
      '',
    );
    t = t.replaceAll(RegExp(r'[.!,]+$'), '').trim();
    if (t.isEmpty ||
        RegExp(r'[0-9@#$%^&*()_+=<>?/\\|{}\[\]~`]').hasMatch(t)) {
      return null;
    }
    final words = t.split(RegExp(r'\s+'));
    if (words.length > 3 || t.length > 40) return null;
    return words
        .map((w) =>
            w.isEmpty ? w : w[0].toUpperCase() + w.substring(1).toLowerCase())
        .join(' ');
  }

  /// Website intent rules (`W`): keyword families with exact site replies.
  /// Returns null when nothing matches (falls through to the fallback).
  _Reply? _matchIntent(String text) {
    final t = text;
    if (RegExp(
      r'\b(returns?|exchanges?|refunds?|replace(ment)?|cancel(lation)?)\b',
      caseSensitive: false,
    ).hasMatch(t)) {
      return _Reply(
        'We accept returns and exchanges within 7 days of delivery, as long as the item is unused and in its original packaging. You can raise a request from the order in My Account.',
        links: const [
          _ChatLink(label: 'View my orders', route: '/orders'),
        ],
      );
    }
    if (RegExp(
      r'\b(track|tracking|orders?|deliver(y|ed)?|shipping|shipped|dispatch(ed)?|courier|parcel)\b',
      caseSensitive: false,
    ).hasMatch(t)) {
      return _Reply(
        'You can see the status of every order in My Account → My Orders.',
        links: const [
          _ChatLink(label: 'View my orders', route: '/orders'),
        ],
      );
    }
    if (RegExp(
      r'\b(sizes?|length|adjustable|fit|measure(ment)?s?)\b',
      caseSensitive: false,
    ).hasMatch(t)) {
      return const _Reply(
        'Most of our necklaces are adjustable. Tell us the piece you are looking at and we will share exact measurements.',
      );
    }
    if (RegExp(
      r'\b(talk|call|contact|support|human|agent|person|team|phone|number|email|mail|whatsapp)\b',
      caseSensitive: false,
    ).hasMatch(t)) {
      return _Reply(
        'You can reach our team ($_teamHours):',
        links: const [
          _ChatLink(label: '📞 $_teamPhone', url: _teamPhoneHref),
          _ChatLink(label: '✉️ $_teamEmail', url: 'mailto:$_teamEmail'),
        ],
      );
    }
    return null;
  }

  /// Website generic responder (`q()`): contextual fallback + team links +
  /// the four quick chips.
  _Reply _fallbackReply() {
    return const _Reply(
      'Thanks for your message! I can help with orders, returns and sizing. For anything else, our team is happy to help ($_teamHours):',
      links: [
        _ChatLink(label: '📞 $_teamPhone', url: _teamPhoneHref),
        _ChatLink(label: '✉️ $_teamEmail', url: 'mailto:$_teamEmail'),
      ],
      chips: true,
    );
  }

  /// Renders a bot reply through the site's queue semantics: typing
  /// indicator first, message after 700ms + 6ms/char capped at +900ms.
  void _answer(_Reply reply) {
    setState(() => _typing = true);
    _scrollToBottom();
    final delay = Duration(
      milliseconds: 700 + (reply.text.length * 6).clamp(0, 900).toInt(),
    );
    Future.delayed(delay, () {
      if (!mounted) return;
      setState(() {
        _typing = false;
        _messages.add(
          _ChatMessage.fromBot(reply.text, links: reply.links),
        );
        if (reply.chips) _messages.add(_ChatMessage.chips());
      });
      _scrollToBottom();
    });
  }

  Future<void> _openLink(_ChatLink link) async {
    if (link.route != null) {
      Navigator.of(context).pushNamed(link.route!);
      return;
    }
    final uri = Uri.tryParse(link.url ?? '');
    if (uri == null) return;
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  /// Fresh conversation (website restart): forget name/state, replay greeting.
  void _restart() {
    setState(() {
      _name = null;
      _nameSkipped = false;
      _typing = false;
      _messages
        ..clear()
        ..add(_ChatMessage.greeting());
    });
    _scrollToBottom();
  }

  /// End chat (website end): forget everything and close the panel.
  void _endChat() {
    setState(() {
      _name = null;
      _nameSkipped = false;
      _typing = false;
      _open = false;
    });
  }

  /// The website's exact reply contract (theme/app.js): EXACT match on the
  /// trimmed-lowercased message against three keys, otherwise the generic
  /// fallback. In particular both quick chips ("Suggest something for me",
  /// "Tell me about best seller") intentionally resolve to the fallback on
  /// the site too — no invented per-topic answers.
  /// Website team contact (live `data-*` values on the site widget).
  static const _teamHours = 'Mon-Sat, 10am-7pm IST';
  static const _teamPhone = '+91 00000 00000';
  static const _teamPhoneHref = 'tel:+910000000000';
  static const _teamEmail = 'support@example.com';

  /// The site's four quick-reply chips, verbatim and in order.
  static const _quickChips = [
    'Track my order',
    'Returns & exchange',
    'Size guide',
    'Talk to our team',
  ];

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
                // Website "Chat options" menu: restart / end conversation.
                PopupMenuButton<String>(
                  icon: const Icon(
                    Icons.more_vert_rounded,
                    color: Colors.white,
                    size: 20,
                  ),
                  padding: EdgeInsets.zero,
                  onSelected: (v) {
                    if (v == 'restart') _restart();
                    if (v == 'end') _endChat();
                  },
                  itemBuilder: (_) => const [
                    PopupMenuItem(
                      value: 'restart',
                      child: Text('Start a new chat'),
                    ),
                    PopupMenuItem(
                      value: 'end',
                      child: Text('End chat'),
                    ),
                  ],
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
                  itemCount: _messages.length + (_typing ? 1 : 0),
                  separatorBuilder: (_, _) => const SizedBox(height: 12),
                  itemBuilder: (context, i) {
                    // Website typing row while the reply beat runs.
                    if (_typing && i == _messages.length) {
                      return const Align(
                        alignment: Alignment.centerLeft,
                        child: _TypingBubble(),
                      );
                    }
                    final message = _messages[i];
                    // Chips follow-up marker: re-renders the four quick
                    // chips inline, like the site appending them.
                    if (message.chipsMarker) {
                      return Column(
                        children: [
                          for (var c = 0; c < _quickChips.length; c++) ...[
                            if (c > 0) const SizedBox(height: 10),
                            _quickReplyChip(_quickChips[c]),
                          ],
                        ],
                      );
                    }
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
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            // The live greeting is plain two-line text (no
                            // bold markup on the site).
                            Text(
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
                            // Website answer link buttons (orders / tel: /
                            // mailto:) rendered under the reply text.
                            for (final link in message.links) ...[
                              const SizedBox(height: 8),
                              _LinkButton(
                                label: link.label,
                                onTap: () => _openLink(link),
                              ),
                            ],
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),
            ),
          ),
          // Quick replies — the website's four chips, verbatim and in
          // order. They send through the same handler as typed text.
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
            child: Column(
              children: [
                for (var i = 0; i < _quickChips.length; i++) ...[
                  if (i > 0) const SizedBox(height: 10),
                  _quickReplyChip(_quickChips[i]),
                ],
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

/// A bot reply: text plus optional tappable link buttons (website
/// `links`) and/or a re-render of the four quick chips (website `chips`).
class _Reply {
  const _Reply(this.text, {this.links = const [], this.chips = false});

  final String text;
  final List<_ChatLink> links;
  final bool chips;
}

/// A tappable link inside a bot message: either an in-app route (orders)
/// or an external URL (tel:/mailto:), like the site's anchor buttons.
class _ChatLink {
  const _ChatLink({required this.label, this.route, this.url});

  final String label;
  final String? route;
  final String? url;
}

class _ChatMessage {
  const _ChatMessage(this.text,
      {required this.fromUser,
      this.greeting = false,
      this.chipsMarker = false,
      this.links = const []});

  factory _ChatMessage.fromBot(String text, {List<_ChatLink> links = const []}) =>
      _ChatMessage(text, fromUser: false, links: links);

  factory _ChatMessage.fromUser(String text) =>
      _ChatMessage(text, fromUser: true);

  /// Website opening line (live theme/app.js `Y()`): plain two-line text,
  /// site name interpolated — "Hello! Greetings from Estele 👋\nMay I know
  /// your name please?".
  factory _ChatMessage.greeting() => const _ChatMessage(
        'Hello! Greetings from Estele 👋\nMay I know your name please?',
        fromUser: false,
        greeting: true,
      );

  /// Marker entry that re-renders the four quick chips under a reply
  /// (website `{chips: h}` follow-up).
  factory _ChatMessage.chips() => const _ChatMessage(
        '',
        fromUser: false,
        chipsMarker: true,
      );

  final String text;
  final bool fromUser;
  final bool greeting;
  final bool chipsMarker;
  final List<_ChatLink> links;
}

/// Website typing row (`chatw__row--typing`): three dots while the reply
/// beat runs.
class _TypingBubble extends StatefulWidget {
  const _TypingBubble();

  @override
  State<_TypingBubble> createState() => _TypingBubbleState();
}

class _TypingBubbleState extends State<_TypingBubble>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: AppColors.line),
      ),
      child: AnimatedBuilder(
        animation: _controller,
        builder: (_, __) {
          return Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              for (var i = 0; i < 3; i++)
                Container(
                  margin: EdgeInsets.only(left: i == 0 ? 0 : 4),
                  width: 7,
                  height: 7,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: AppColors.muted.withValues(
                      alpha: 0.35 +
                          0.65 *
                              (((_controller.value * 3 - i) % 3) / 3)
                                  .clamp(0.0, 1.0),
                    ),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

/// Website answer link button (orders route / tel: / mailto: anchors).
class _LinkButton extends StatelessWidget {
  const _LinkButton({required this.label, required this.onTap});

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(
          color: AppColors.paper,
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: AppColors.accent),
        ),
        child: Text(
          label,
          style: AppTypography.bodySmall(
            size: 12,
            color: AppColors.accentDark,
            weight: FontWeight.w600,
          ),
        ),
      ),
    );
  }
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
