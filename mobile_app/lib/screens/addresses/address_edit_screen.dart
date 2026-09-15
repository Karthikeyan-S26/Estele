import 'package:flutter/material.dart';

import '../../data/repositories/account_repository.dart';
import '../../models/address.dart';

class AddressEditScreen extends StatefulWidget {
  const AddressEditScreen({super.key, this.address});

  final Address? address;

  @override
  State<AddressEditScreen> createState() => _AddressEditScreenState();
}

class _AddressEditScreenState extends State<AddressEditScreen> {
  final _formKey = GlobalKey<FormState>();
  late final _label = TextEditingController(
    text: widget.address?.label ?? 'Home',
  );
  late final _line1 = TextEditingController(text: widget.address?.line1 ?? '');
  late final _line2 = TextEditingController(text: widget.address?.line2 ?? '');
  late final _city = TextEditingController(text: widget.address?.city ?? '');
  late final _state = TextEditingController(text: widget.address?.state ?? '');
  late final _postalCode = TextEditingController(
    text: widget.address?.postalCode ?? '',
  );
  late final _phone = TextEditingController(text: widget.address?.phone ?? '');
  late bool _isDefault = widget.address?.isDefault ?? false;
  bool _saving = false;

  @override
  void dispose() {
    for (final c in [
      _label,
      _line1,
      _line2,
      _city,
      _state,
      _postalCode,
      _phone,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _saving = true);
    final address = Address(
      id: widget.address?.id ?? 0,
      label: _label.text.trim(),
      line1: _line1.text.trim(),
      line2: _line2.text.trim(),
      city: _city.text.trim(),
      state: _state.text.trim(),
      postalCode: _postalCode.text.trim(),
      country: 'India',
      phone: _phone.text.trim(),
      isDefault: _isDefault,
    );

    try {
      if (widget.address == null) {
        await AccountRepository.storeAddress(address);
      } else {
        await AccountRepository.updateAddress(address);
      }
      if (mounted) Navigator.of(context).pop(address);
    } catch (e) {
      if (mounted) {
        setState(() => _saving = false);
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(e.toString())));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.address == null ? 'New address' : 'Edit address'),
      ),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: const EdgeInsets.all(20),
            children: [
              TextFormField(
                controller: _label,
                decoration: const InputDecoration(
                  labelText: 'Label (Home / Office)',
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _line1,
                decoration: const InputDecoration(
                  labelText: 'Address line 1 *',
                ),
                validator: (v) =>
                    (v == null || v.trim().isEmpty) ? 'Required' : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _line2,
                decoration: const InputDecoration(
                  labelText: 'Address line 2 (optional)',
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _city,
                      decoration: const InputDecoration(labelText: 'City *'),
                      validator: (v) =>
                          (v == null || v.trim().isEmpty) ? 'Required' : null,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextFormField(
                      controller: _state,
                      decoration: const InputDecoration(labelText: 'State *'),
                      validator: (v) =>
                          (v == null || v.trim().isEmpty) ? 'Required' : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _postalCode,
                      keyboardType: TextInputType.number,
                      maxLength: 6,
                      decoration: const InputDecoration(
                        labelText: 'PIN code *',
                        counterText: '',
                      ),
                      validator: (v) =>
                          (v == null ||
                              v.length != 6 ||
                              !RegExp(r'^\d{6}$').hasMatch(v))
                          ? 'Valid 6-digit PIN required'
                          : null,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextFormField(
                      controller: _phone,
                      keyboardType: TextInputType.phone,
                      decoration: const InputDecoration(labelText: 'Phone *'),
                      validator: (v) => (v == null || v.length < 10)
                          ? 'Valid phone required'
                          : null,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Set as default address'),
                value: _isDefault,
                onChanged: (v) => setState(() => _isDefault = v),
              ),
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Save address'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
