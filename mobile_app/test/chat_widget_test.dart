import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:estele/widgets/chat_widget.dart';

void main() {
  Future<void> pumpChat(WidgetTester tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: Scaffold(
          body: Align(alignment: Alignment.bottomRight, child: ChatWidget()),
        ),
      ),
    );
  }

  testWidgets('renders floating chat button with the red badge', (
    tester,
  ) async {
    await pumpChat(tester);

    expect(find.byType(ChatWidget), findsOneWidget);
    expect(find.text('1'), findsOneWidget);
    expect(find.text('Estele Style Expert'), findsNothing);
  });

  testWidgets('opens the panel showing header, quick replies and composer', (
    tester,
  ) async {
    await pumpChat(tester);

    await tester.tap(find.byKey(const ValueKey('chat-toggle-button')));
    await tester.pumpAndSettle();

    expect(find.text('Estele Style Expert'), findsOneWidget);
    expect(find.text('Online'), findsOneWidget);
    expect(find.text('Suggest something for me'), findsOneWidget);
    expect(find.text('Tell me about best seller'), findsOneWidget);
    expect(find.text('You can talk to me in any language'), findsOneWidget);
    // The unread "1" badge clears once opened.
    expect(find.text('1'), findsNothing);
  });

  testWidgets('quick reply appends a user bubble and an assistant reply', (
    tester,
  ) async {
    await pumpChat(tester);

    await tester.tap(find.byKey(const ValueKey('chat-toggle-button')));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Suggest something for me').first);
    await tester.pump();

    expect(
      find.text('Suggest something for me'),
      findsNWidgets(2),
    ); // chip + bubble
    expect(
      find.textContaining(
        "I'd suggest starting with our Necklace Sets",
        findRichText: false,
      ),
      findsOneWidget,
    );
  });

  testWidgets('typing a message and pressing send appends an exchange', (
    tester,
  ) async {
    await pumpChat(tester);

    await tester.tap(find.byKey(const ValueKey('chat-toggle-button')));
    await tester.pumpAndSettle();

    await tester.enterText(find.byType(TextField), 'Do you ship pan-India?');
    await tester.testTextInput.receiveAction(TextInputAction.send);
    await tester.pumpAndSettle();

    expect(find.text('Do you ship pan-India?'), findsOneWidget);
    expect(find.textContaining('We ship across India'), findsOneWidget);
  });
}
