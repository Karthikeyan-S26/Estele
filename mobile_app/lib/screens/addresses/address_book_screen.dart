import 'package:flutter/material.dart';

import '../../data/repositories/account_repository.dart';
import '../../models/address.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/load_state.dart';
import 'address_edit_screen.dart';

class AddressBookScreen extends StatefulWidget {
  const AddressBookScreen({super.key});

  @override
  State<AddressBookScreen> createState() => _AddressBookScreenState();
}

class _AddressBookScreenState extends State<AddressBookScreen> {
  List<Address>? _addresses;
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
      final result = await AccountRepository.addresses();
      if (mounted) {
        setState(() {
          _addresses = result.items;
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _failed = true;
          _loading = false;
        });
      }
    }
  }

  Future<void> _openEditor([Address? address]) async {
    final saved = await Navigator.of(context).push<Address>(
      MaterialPageRoute(builder: (_) => AddressEditScreen(address: address)),
    );
    if (saved != null) _load();
  }

  Future<void> _delete(Address address) async {
    try {
      await AccountRepository.deleteAddress(address.id);
      if (!mounted) return;
      if (_addresses != null) {
        setState(
          () => _addresses = _addresses!
              .where((a) => a.id != address.id)
              .toList(),
        );
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Address removed')));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Could not remove the address. Try again.'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Address book')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openEditor(),
        icon: const Icon(Icons.add_rounded),
        label: const Text('New address'),
      ),
      body: _loading && _addresses == null
          ? const LoadState.loading()
          : _failed && _addresses == null
          ? LoadState.error(
              message: 'Could not load your addresses.',
              onRetry: _load,
            )
          : _addresses == null || _addresses!.isEmpty
          ? LoadState.empty(
              message: 'No saved addresses yet. Add one to speed up checkout.',
            )
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.builder(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                itemCount: _addresses!.length,
                itemBuilder: (context, i) {
                  final address = _addresses![i];
                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppColors.paper,
                      borderRadius: BorderRadius.circular(4),
                      border: Border.all(
                        color: address.isDefault
                            ? AppColors.accent
                            : AppColors.line,
                        width: address.isDefault ? 1.2 : 1,
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                address.label.isEmpty
                                    ? 'Address ${i + 1}'
                                    : address.label,
                                style: AppTypography.bodyMedium(
                                  weight: FontWeight.w700,
                                ),
                              ),
                            ),
                            if (address.isDefault) const _DefaultTag(),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Text(
                          address.summary,
                          style: AppTypography.body(
                            size: 13.5,
                            color: AppColors.ink,
                          ),
                        ),
                        if (address.phone != null &&
                            address.phone!.isNotEmpty) ...[
                          const SizedBox(height: 4),
                          Text(
                            address.phone!,
                            style: AppTypography.bodySmall(size: 12.5),
                          ),
                        ],
                        const SizedBox(height: 8),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.end,
                          children: [
                            TextButton.icon(
                              onPressed: () => _openEditor(address),
                              icon: const Icon(Icons.edit_outlined, size: 16),
                              label: const Text('Edit'),
                            ),
                            const SizedBox(width: 8),
                            IconButton(
                              onPressed: () => _delete(address),
                              icon: const Icon(
                                Icons.delete_outline_rounded,
                                size: 20,
                                color: AppColors.muted,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
    );
  }
}

class _DefaultTag extends StatelessWidget {
  const _DefaultTag();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: AppColors.pinkSoft,
        borderRadius: BorderRadius.circular(2),
      ),
      child: Text(
        'DEFAULT',
        style: AppTypography.bodySmall(
          size: 9.5,
          color: AppColors.accentDark,
          weight: FontWeight.w700,
        ),
      ),
    );
  }
}
