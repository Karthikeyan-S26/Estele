import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../data/repositories/account_repository.dart';
import '../../data/repositories/checkout_repository.dart';
import '../../models/address.dart';
import '../../providers/auth_provider.dart';
import '../../providers/cart_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../utils/formatters.dart';
import '../../widgets/load_state.dart';
import '../auth/login_screen.dart';
import 'order_confirmation_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _addressFormKey = GlobalKey<FormState>();
  final _label = TextEditingController(text: 'Home');
  final _line1 = TextEditingController();
  final _line2 = TextEditingController();
  final _city = TextEditingController();
  final _state = TextEditingController();
  final _postalCode = TextEditingController();
  final _phone = TextEditingController();
  final _note = TextEditingController();

  bool _guest = true;
  bool _loading = true;
  List<Address> _saved = [];
  Address? _selectedSaved;
  bool _placing = false;
  String? _error;

  String _paymentMethod = 'cod';

  @override
  void initState() {
    super.initState();
    _boot();
  }

  @override
  void dispose() {
    for (final c in [_label, _line1, _line2, _city, _state, _postalCode, _phone, _note]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _boot() async {
    final auth = context.read<AuthProvider>();
    if (!auth.isAuthenticated) {
      setState(() {
        _guest = true;
        _loading = false;
      });
      return;
    }
    setState(() {
      _guest = false;
      _loading = true;
    });
    try {
      final result = await AccountRepository.addresses();
      final defaultAddress = result.items.firstWhere((a) => a.isDefault, orElse: () => result.items.firstOrNull as Address);
      if (mounted) {
        setState(() {
          _saved = result.items;
          _selectedSaved = result.items.isEmpty ? null : defaultAddress;
          _loading = false;
        });
        _fillFromSelected();
      }
    } catch (_) {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _fillFromSelected() {
    final a = _selectedSaved;
    if (a == null) return;
    _label.text = a.label;
    _line1.text = a.line1;
    _line2.text = a.line2 ?? '';
    _city.text = a.city;
    _state.text = a.state;
    _postalCode.text = a.postalCode;
    _phone.text = a.phone ?? '';
  }

  CheckoutDetails _checkoutDetails(AuthProvider auth) {
    final name = (auth.user?.name ?? '').trim();
    final parts = name.split(RegExp(r'\s+'));
    final firstName = parts.isNotEmpty ? parts.first : '';
    final lastName = parts.length > 1 ? parts.sublist(1).join(' ') : '';

    return CheckoutDetails(
      firstName: firstName,
      lastName: lastName,
      email: auth.user?.email ?? '',
      phone: _phone.text.trim(),
      line1: _line1.text.trim(),
      line2: _line2.text.trim().isEmpty ? null : _line2.text.trim(),
      city: _city.text.trim(),
      state: _state.text.trim(),
      postalCode: _postalCode.text.trim(),
      orderNote: _note.text.trim().isEmpty ? null : _note.text.trim(),
    );
  }

  Future<void> _placeOrder() async {
    if (!(_addressFormKey.currentState?.validate() ?? false)) {
      return;
    }
    setState(() {
      _placing = true;
      _error = null;
    });

    final details = _checkoutDetails(context.read<AuthProvider>());

    try {
      if (_paymentMethod == 'cod') {
        final order = await CheckoutRepository.placeOrder(details: details);
        if (!mounted) return;
        final cartProvider = context.read<CartProvider>();
        await cartProvider.clear();
        if (!mounted) return;
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => OrderConfirmationScreen(order: order)),
        );
      } else {
        // Razorpay: hand off to the hosted checkout flow returned by the server.
        final handoff = await CheckoutRepository.createPaymentOrder(details: details);
        if (!mounted) return;
        if (handoff.razorpayOrderId.isEmpty) {
          setState(() {
            _placing = false;
            _error =
                'Online payment is currently unavailable. Please choose Cash on Delivery.';
          });
        } else {
          _showRazorpayInstructions(handoff.razorpayOrderId);
        }
      }
    } on Exception catch (e) {
      if (!mounted) return;
      setState(() {
        _placing = false;
        _error = e.toString();
      });
    }
  }

  void _showRazorpayInstructions(String razorpayOrderId) {
    showDialog<void>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Complete your payment'),
        content: Text(
          'A Razorpay order has been created (ID: $razorpayOrderId). '
          'Open the Razorpay app or gateway and pay against this order ID to finish. '
          'Once paid, your order will be booked.',
          style: AppTypography.body(size: 13.5),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('Done'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_guest) {
      return Scaffold(
        appBar: AppBar(title: const Text('Checkout')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.lock_outline, size: 44, color: AppColors.lineStrong),
                const SizedBox(height: 12),
                Text('Sign in to checkout', style: AppTypography.sectionTitle(size: 17)),
                const SizedBox(height: 6),
                Text(
                  'Your bag is saved — just sign in or create an account to place your order.\nYour guest bag merges automatically.',
                  textAlign: TextAlign.center,
                  style: AppTypography.body(size: 13.5, color: AppColors.muted),
                ),
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => const LoginScreen()),
                  ),
                  child: const Text('Sign in / Create account'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    final cart = context.watch<CartProvider>().cart;

    return Scaffold(
      appBar: AppBar(title: const Text('Checkout')),
      body: _loading
          ? const LoadState.loading()
          : Form(
              key: _addressFormKey,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Delivery
                  Text('1 · Delivery details', style: AppTypography.sectionTitle(size: 17)),
                  const SizedBox(height: 12),

                  if (_saved.isNotEmpty) ...[
                    SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(
                        children: _saved.map((a) {
                          final selected = _selectedSaved?.id == a.id;
                          return Padding(
                            padding: const EdgeInsets.only(right: 8),
                            child: FilterChip(
                              label: Text(a.label.isEmpty ? 'Home' : a.label),
                              selected: selected,
                              onSelected: (_) {
                                setState(() => _selectedSaved = selected ? a : null);
                                if (selected) _fillFromSelected();
                              },
                            ),
                          );
                        }).toList(),
                      ),
                    ),
                    const SizedBox(height: 12),
                  ],

                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _label,
                          decoration: const InputDecoration(labelText: 'Label (Home / Office)', isDense: true),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  TextFormField(
                    controller: _line1,
                    decoration: const InputDecoration(labelText: 'Address *', isDense: true),
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Address is required' : null,
                  ),
                  const SizedBox(height: 10),
                  TextFormField(
                    controller: _line2,
                    decoration: const InputDecoration(labelText: 'Address line 2 (optional)', isDense: true),
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        flex: 2,
                        child: TextFormField(
                          controller: _city,
                          decoration: const InputDecoration(labelText: 'City *', isDense: true),
                          validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        flex: 2,
                        child: TextFormField(
                          controller: _state,
                          decoration: const InputDecoration(labelText: 'State *', isDense: true),
                          validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _postalCode,
                          keyboardType: TextInputType.number,
                          maxLength: 6,
                          decoration: const InputDecoration(
                            labelText: 'PIN code *',
                            isDense: true,
                            counterText: '',
                          ),
                          validator: (v) => (v == null || v.length != 6 || !RegExp(r'^\d{6}$').hasMatch(v))
                              ? 'Valid 6-digit PIN required'
                              : null,
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: TextFormField(
                          controller: _phone,
                          keyboardType: TextInputType.phone,
                          decoration: const InputDecoration(labelText: 'Phone *', isDense: true),
                          validator: (v) => (v == null || v.length < 10) ? 'Valid phone required' : null,
                        ),
                      ),
                    ],
                  ),

                  const Divider(height: 32),
                  // Payment
                  Text('2 · Payment method', style: AppTypography.sectionTitle(size: 17)),
                  const SizedBox(height: 12),
                  RadioListTile<String>(
                    value: 'cod',
                    groupValue: _paymentMethod,
                    onChanged: (v) => setState(() => _paymentMethod = v!),
                    title: const Text('Cash on Delivery'),
                    subtitle: const Text('Pay when your order arrives. Small convenience fee may apply.'),
                    secondary: const Icon(Icons.payments_outlined),
                  ),
                  RadioListTile<String>(
                    value: 'razorpay',
                    groupValue: _paymentMethod,
                    onChanged: (v) => setState(() => _paymentMethod = v!),
                    title: const Text('Pay online (Razorpay)'),
                    subtitle: const Text('UPI, cards, netbanking and wallets.'),
                    secondary: const Icon(Icons.account_balance_wallet_outlined),
                  ),

                  const Divider(height: 32),
                  // Summary
                  Text('3 · Review', style: AppTypography.sectionTitle(size: 17)),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppColors.paper,
                      borderRadius: BorderRadius.circular(4),
                      border: Border.all(color: AppColors.line),
                    ),
                    child: Column(
                      children: [
                        _Row(label: 'Subtotal', value: cart.totals.subtotal),
                        if (cart.totals.discount > 0)
                          _Row(label: 'Coupon discount', value: -cart.totals.discount, sale: true),
                        _Row(label: 'Shipping', value: cart.totals.shipping),
                        const Divider(height: 16),
                        _Row(label: 'Order total', value: cart.totals.total, bold: true),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _note,
                    decoration: const InputDecoration(labelText: 'Order note (optional)', isDense: true),
                  ),

                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(_error!, style: AppTypography.bodySmall(size: 13, color: AppColors.error)),
                  ],

                  const SizedBox(height: 20),
                  FilledButton(
                    onPressed: _placing ? null : _placeOrder,
                    child: _placing
                        ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                        : Text('Place order · ${formatINR(cart.totals.total)}'),
                  ),
                  const SizedBox(height: 20),
                ],
              ),
            ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row({required this.label, required this.value, this.sale = false, this.bold = false});

  final String label;
  final double value;
  final bool sale;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: AppTypography.body(size: 13.5, color: sale ? AppColors.sale : AppColors.muted)),
          Text(
            formatINR(value),
            style: (bold ? AppTypography.price(size: 15) : AppTypography.body(size: 13.5, weight: FontWeight.w600))
                .copyWith(color: sale ? AppColors.sale : AppColors.heading),
          ),
        ],
      ),
    );
  }
}