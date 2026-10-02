import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:estele/widgets/otp_code_field.dart';

Widget _host(OtpCodeFieldController controller) {
  return MaterialApp(
    home: Scaffold(body: Center(child: OtpCodeField(controller: controller))),
  );
}

void main() {
  testWidgets('auto-advances: each digit shows in its own box', (tester) async {
    final c = OtpCodeFieldController();
    addTearDown(c.dispose);
    await tester.pumpWidget(_host(c));

    await tester.enterText(find.byType(TextField), '12');
    await tester.pump();

    expect(find.text('1'), findsOneWidget);
    expect(find.text('2'), findsOneWidget);
    expect(find.text('3'), findsNothing);
    expect(c.code, '12');
  });

  testWidgets('fires onCompleted exactly once when the 6th digit lands',
      (tester) async {
    final c = OtpCodeFieldController();
    addTearDown(c.dispose);
    final fired = <String>[];
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Center(
            child: OtpCodeField(
              controller: c,
              onCompleted: fired.add,
            ),
          ),
        ),
      ),
    );

    await tester.enterText(find.byType(TextField), '654321');
    await tester.pump();

    expect(fired, ['654321']);

    // Further edits above a 6-digit cap must not re-fire.
    await tester.enterText(find.byType(TextField), '6543219');
    await tester.pump();
    expect(fired, ['654321']);
  });

  testWidgets('does not fire below 6 digits', (tester) async {
    final c = OtpCodeFieldController();
    addTearDown(c.dispose);
    final fired = <String>[];
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Center(
            child: OtpCodeField(
              controller: c,
              onCompleted: fired.add,
              autofocus: false,
            ),
          ),
        ),
      ),
    );

    await tester.enterText(find.byType(TextField), '12345');
    await tester.pump();

    expect(fired, isEmpty);
    expect(c.code, '12345');
  });

  testWidgets('paste of a full code works (junk stripped, capped at 6)',
      (tester) async {
    final c = OtpCodeFieldController();
    addTearDown(c.dispose);
    final fired = <String>[];
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: Center(
            child: OtpCodeField(
              controller: c,
              onCompleted: fired.add,
              autofocus: false,
            ),
          ),
        ),
      ),
    );

    await tester.enterText(find.byType(TextField), 'AB987654XY');
    await tester.pump();

    expect(fired, ['987654']);
    expect(c.code, '987654');
  });

  testWidgets('clear resets the boxes', (tester) async {
    final c = OtpCodeFieldController();
    addTearDown(c.dispose);
    await tester.pumpWidget(_host(c));

    await tester.enterText(find.byType(TextField), '456789');
    await tester.enterText(find.byType(TextField), '');
    await tester.pump();

    expect(find.text('4'), findsNothing);
    expect(c.code, isEmpty);
  });
}