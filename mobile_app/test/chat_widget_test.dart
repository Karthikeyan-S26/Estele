import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:estele/widgets/chat_widget.dart';

void main() {
  Future<void> pumpChat(WidgetTester tester) async {
    // A phone-shaped surface (e.g. 412x892 logical). On the default 800x600
    // test canvas the popover's message log is squeezed to ~87px by the
    // header/chips/composer, so appended bubbles would be scrolled out of the
    // lazy list and never built — an artifact of the short canvas, not of the
    // widget, which on a real phone gets its full 280px log.
    tester.view.physicalSize = const Size(1236, 2676);
    tester.view.devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: Align(alignment: Alignment.bottomRight, child: ChatWidget()),
        ),
      ),
    );
  }

  Future<void> openPanel(WidgetTester tester) async {
    await tester.tap(find.byKey(const ValueKey('chat-toggle-button')));
    await tester.pumpAndSettle();
  }

  /// Website reply beat: typing indicator for 700ms + 6ms/char, capped at
  /// +900ms — so the longest reply lands at 1.6s. Pump past that, then settle
  /// so the auto-scroll to the newest message completes (the log is a lazy
  /// list, so anything below the fold is simply not built until it does).
  Future<void> settleReply(WidgetTester tester) async {
    await tester.pump(const Duration(milliseconds: 1800));
    await tester.pumpAndSettle();
  }

  testWidgets('renders floating chat button with the red badge', (
    tester,
  ) async {
    await pumpChat(tester);

    expect(find.byType(ChatWidget), findsOneWidget);
    expect(find.text('1'), findsOneWidget);
    expect(find.text('Estele Style Expert'), findsNothing);
  });

  testWidgets('opens the panel showing header, greeting and composer', (
    tester,
  ) async {
    await pumpChat(tester);
    await openPanel(tester);

    expect(find.text('Estele Style Expert'), findsOneWidget);
    expect(find.text('Online'), findsOneWidget);
    // Website-parity greeting: plain two-line text, brand name included.
    expect(
      find.text('Hello! Greetings from Estele 👋\nMay I know your name please?'),
      findsOneWidget,
    );
    expect(find.text('You can talk to me in any language'), findsOneWidget);
    // The unread "1" badge clears once opened.
    expect(find.text('1'), findsNothing);
  });

  testWidgets('entering a name greets the user and offers the four chips', (
    tester,
  ) async {
    await pumpChat(tester);
    await openPanel(tester);

    await tester.enterText(find.byType(TextField), 'My name is Priya');
    await tester.testTextInput.receiveAction(TextInputAction.send);
    await settleReply(tester);

    expect(
      find.text('Nice to meet you, Priya! 😊\nHow can I help you today?'),
      findsOneWidget,
    );
    // The four website quick chips are offered under the reply (they render
    // both in the panel's always-on chip block and again inline in the log,
    // mirroring the site, so assert presence rather than an exact count).
    expect(find.text('Track my order'), findsWidgets);
    expect(find.text('Returns & exchange'), findsWidgets);
    expect(find.text('Size guide'), findsWidgets);
    expect(find.text('Talk to our team'), findsWidgets);
  });

  testWidgets('exact website intent key resolves to the website answer', (
    tester,
  ) async {
    await pumpChat(tester);
    await openPanel(tester);

    await tester.enterText(find.byType(TextField), 'Track my order');
    await tester.testTextInput.receiveAction(TextInputAction.send);
    await settleReply(tester);

    expect(
      find.textContaining(
        'You can see the status of every order in My Account → My Orders.',
      ),
      findsOneWidget,
    );
    expect(find.text('View my orders'), findsOneWidget);
  });

  testWidgets('unknown messages fall back exactly like the website', (
    tester,
  ) async {
    await pumpChat(tester);
    await openPanel(tester);

    await tester.enterText(find.byType(TextField), 'Do you ship pan-India?');
    await tester.testTextInput.receiveAction(TextInputAction.send);
    await settleReply(tester);

    // Generic responder with the team contact links + the four chips.
    expect(
      find.textContaining('Thanks for your message!'),
      findsOneWidget,
    );
    expect(find.text('Track my order'), findsWidgets);
  });

  testWidgets('tapping a chip appends a user bubble and its reply', (
    tester,
  ) async {
    await pumpChat(tester);
    await openPanel(tester);

    await tester.tap(find.text('Returns & exchange').first);
    // The chip label is echoed as the user's own bubble (asserted before the
    // reply lands, while the log is still scrolled to the top).
    await tester.pump(const Duration(milliseconds: 50));
    expect(find.text('Returns & exchange'), findsNWidgets(2));

    await settleReply(tester);

    expect(
      find.textContaining(
        'We accept returns and exchanges within 7 days of delivery',
      ),
      findsOneWidget,
    );
    expect(find.text('View my orders'), findsOneWidget);
  });
}