import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../config/app_colors.dart';
import '../../config/app_theme.dart';
import '../../l10n/app_localizations.dart';
import '../../models/withdraw_models.dart';
import '../../services/withdraw_service.dart';
import '../../widgets/common_widgets.dart';

class WithdrawScreen extends StatefulWidget {
  const WithdrawScreen({super.key});

  @override
  State<WithdrawScreen> createState() => _WithdrawScreenState();
}

class _WithdrawScreenState extends State<WithdrawScreen> {
  final _amountCtrl = TextEditingController();

  bool _loading = true;
  bool _submitting = false;

  List<WithdrawMethod> _methods = [];
  List<WithdrawAccount> _accounts = [];

  WithdrawMethod? _selectedMethod;
  WithdrawAccount? _selectedAccount;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() => _loading = true);
    try {
      final methods = await WithdrawService.getMethods();
      final accounts = await WithdrawService.getAccounts();
      if (!mounted) return;

      setState(() {
        _methods = methods;
        _accounts = accounts;
        _selectedMethod = methods.isNotEmpty ? methods.first : null;
        _syncSelectedAccount();
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() => _loading = false);
      _showSnack(e.toString(), isError: true);
    }
  }

  void _syncSelectedAccount() {
    if (_selectedMethod == null) {
      _selectedAccount = null;
      return;
    }

    final methodAccounts = _accounts
        .where((a) => a.withdrawMethodId == _selectedMethod!.id)
        .toList();

    if (methodAccounts.isEmpty) {
      _selectedAccount = null;
      return;
    }

    final current = _selectedAccount;
    final keep = current != null
        ? methodAccounts.where((a) => a.id == current.id).cast<WithdrawAccount?>().firstWhere(
              (a) => a != null,
              orElse: () => null,
            )
        : null;

    _selectedAccount = keep ?? methodAccounts.first;
  }

  Future<void> _openAddAccountSheet() async {
    final method = _selectedMethod;
    if (method == null) return;

    final fields = method.fields;
    if (fields.isEmpty) {
      _showSnack(context.tr('method_has_no_account_fields'), isError: true);
      return;
    }

    final added = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: context.colors.bgCard,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => _AddWithdrawAccountSheet(method: method),
    );

    if (added == true && mounted) {
      await _loadData();
      if (mounted) {
        _showSnack(context.tr('withdraw_account_added'), isError: false);
      }
    }
  }

  Future<void> _openEditAccountSheet() async {
    final method = _selectedMethod;
    final account = _selectedAccount;
    if (method == null || account == null) return;

    final fields = method.fields;
    if (fields.isEmpty) {
      _showSnack(context.tr('method_has_no_account_fields'), isError: true);
      return;
    }

    final updated = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: context.colors.bgCard,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => _AddWithdrawAccountSheet(
        method: method,
        account: account,
      ),
    );

    if (updated == true && mounted) {
      await _loadData();
      if (mounted) {
        _showSnack(context.tr('withdraw_account_updated'), isError: false);
      }
    }
  }

  Future<void> _deleteAccount() async {
    final account = _selectedAccount;
    if (account == null) return;
    final successMessage = context.tr('withdraw_account_deleted');

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(context.tr('delete_account')),
        content: Text(context.tr('delete_account_confirmation')),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(context.tr('cancel')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(context.tr('delete')),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    try {
      await WithdrawService.deleteAccount(account.id);
      if (!mounted) return;
      await _loadData();
      _showSnack(successMessage, isError: false);
    } catch (e) {
      if (!mounted) return;
      _showSnack(e.toString(), isError: true);
    }
  }

  Future<void> _submitWithdraw() async {
    final method = _selectedMethod;
    final account = _selectedAccount;
    final amount = double.tryParse(_amountCtrl.text.trim());

    if (method == null) {
      _showSnack(context.tr('select_withdraw_method_error'), isError: true);
      return;
    }
    if (account == null) {
      _showSnack(context.tr('select_withdraw_account_error'), isError: true);
      return;
    }
    if (amount == null || amount <= 0) {
      _showSnack(context.tr('enter_valid_amount'), isError: true);
      return;
    }

    setState(() => _submitting = true);
    try {
      final res = await WithdrawService.initiate(
        withdrawAccountId: account.id,
        amount: amount,
      );
      if (!mounted) return;
      _amountCtrl.clear();
      _showSnack((res['message'] ?? context.tr('withdraw_request_submitted')).toString(), isError: false);
    } catch (e) {
      if (!mounted) return;
      _showSnack(e.toString(), isError: true);
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  void _showSnack(String message, {required bool isError}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? AppTheme.error : AppTheme.success,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.colors;

    return Scaffold(
      backgroundColor: colors.bgDark,
      appBar: AppBar(title: Text(context.tr('withdraw'))),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: colors.primary))
          : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      context.tr('select_withdraw_method'),
                      style: TextStyle(
                        color: colors.textPrimary,
                        fontWeight: FontWeight.w700,
                        fontSize: 17,
                      ),
                    ),
                    const SizedBox(height: 10),
                    DropdownButtonFormField<int>(
                      value: _selectedMethod?.id,
                      isExpanded: true,
                      items: _methods
                          .map((m) => DropdownMenuItem<int>(
                                value: m.id,
                                child: Text(
                                  '${m.name} (${m.type == 'manual' ? context.tr('manual') : context.tr('automatic')})',
                                ),
                              ))
                          .toList(),
                      onChanged: (id) {
                        setState(() {
                          _selectedMethod = _methods.firstWhere((m) => m.id == id);
                          _syncSelectedAccount();
                        });
                      },
                      decoration: InputDecoration(
                        labelText: context.tr('withdraw'),
                      ),
                    ),
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                              context.tr('account'),
                            style: TextStyle(
                              color: colors.textPrimary,
                              fontWeight: FontWeight.w700,
                              fontSize: 16,
                            ),
                          ),
                        ),
                        TextButton.icon(
                          onPressed: _selectedMethod == null ? null : _openAddAccountSheet,
                          icon: const Icon(Icons.add),
                          label: Text(context.tr('add_account')),
                        ),
                        PopupMenuButton<String>(
                          enabled: _selectedAccount != null,
                          icon: Icon(Icons.more_vert, color: colors.textSecondary),
                          onSelected: (value) async {
                            if (value == 'edit') {
                              await _openEditAccountSheet();
                            } else if (value == 'delete') {
                              await _deleteAccount();
                            }
                          },
                          itemBuilder: (ctx) => [
                            PopupMenuItem(
                              value: 'edit',
                              child: Text(context.tr('edit_account')),
                            ),
                            PopupMenuItem(
                              value: 'delete',
                              child: Text(context.tr('delete_account')),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    if ((_selectedMethod == null) || _methodAccounts.isEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: colors.bgCard,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          context.tr('no_withdraw_account_for_method'),
                          style: TextStyle(color: colors.textSecondary),
                        ),
                      )
                    else
                      DropdownButtonFormField<int>(
                        value: _selectedAccount?.id,
                        isExpanded: true,
                        items: _methodAccounts
                            .map((a) => DropdownMenuItem<int>(
                                  value: a.id,
                                  child: Text(a.methodName),
                                ))
                            .toList(),
                        onChanged: (id) {
                          setState(() {
                            _selectedAccount = _methodAccounts.firstWhere((a) => a.id == id);
                          });
                        },
                        decoration: InputDecoration(
                          labelText: context.tr('select_withdraw_account'),
                        ),
                      ),
                    const SizedBox(height: 16),
                    AppTextField(
                      label: context.tr('enter_amount'),
                      hint: '0.00',
                      controller: _amountCtrl,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      prefixIcon: Icons.attach_money_rounded,
                      onChanged: (_) => setState(() {}),
                    ),
                    const SizedBox(height: 12),
                    _summaryCard(),
                    const SizedBox(height: 18),
                    AppButton(
                      label: context.tr('withdraw'),
                      isLoading: _submitting,
                      onTap: (double.tryParse(_amountCtrl.text.trim()) ?? 0) >= 5
                          ? _submitWithdraw
                          : null,
                      icon: Icons.call_made_rounded,
                    ),
                  ],
                ),
              ),
            ),
    );
  }

  List<WithdrawAccount> get _methodAccounts {
    final method = _selectedMethod;
    if (method == null) return [];
    return _accounts.where((a) => a.withdrawMethodId == method.id).toList();
  }

  Widget _summaryCard() {
    final colors = context.colors;
    final method = _selectedMethod;
    final amount = double.tryParse(_amountCtrl.text.trim()) ?? 0;

    final charge = method == null
        ? 0.0
        : (method.chargeType == 'percentage'
            ? amount * (method.charge / 100)
            : method.charge);
    final finalAmount = amount + charge;

    String fmt(double v) => v.toStringAsFixed(2);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: colors.bgCard,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          _row(
            context.tr('min_max'),
            method == null ? '-' : '${fmt(method.minWithdraw)} / ${fmt(method.maxWithdraw)}',
          ),
          const SizedBox(height: 8),
          _row(context.tr('charge'), fmt(charge)),
          const SizedBox(height: 8),
          _row(context.tr('final_amount'), fmt(finalAmount)),
        ],
      ),
    );
  }

  Widget _row(String label, String value) {
    final colors = context.colors;
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: TextStyle(color: colors.textSecondary)),
        Text(value, style: TextStyle(color: colors.textPrimary, fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _AddWithdrawAccountSheet extends StatefulWidget {
  final WithdrawMethod method;
  final WithdrawAccount? account;

  const _AddWithdrawAccountSheet({required this.method, this.account});

  @override
  State<_AddWithdrawAccountSheet> createState() => _AddWithdrawAccountSheetState();
}

class _AddWithdrawAccountSheetState extends State<_AddWithdrawAccountSheet> {
  final _formKey = GlobalKey<FormState>();
  final Map<String, TextEditingController> _controllers = {};
  final _picker = ImagePicker();
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    for (final f in widget.method.fields) {
      _controllers[f.name] = TextEditingController(
        text: widget.account?.credentials[f.name] ?? '',
      );
    }
  }

  @override
  void dispose() {
    for (final c in _controllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    if (_saving) return;
    if (!_formKey.currentState!.validate()) return;

    final values = {
      for (final f in widget.method.fields)
        f.name: _controllers[f.name]!.text.trim(),
    };

    setState(() => _saving = true);
    try {
      if (widget.account == null) {
        await WithdrawService.createAccount(
          methodId: widget.method.id,
          methodName: widget.method.name,
          fields: widget.method.fields,
          values: values,
        );
      } else {
        await WithdrawService.updateAccount(
          accountId: widget.account!.id,
          methodName: widget.method.name,
          fields: widget.method.fields,
          values: values,
        );
      }
      if (!mounted) return;
      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString()),
          backgroundColor: AppTheme.error,
        ),
      );
      setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = context.colors;
    return Padding(
      padding: EdgeInsets.fromLTRB(
        20,
        14,
        20,
        MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '${widget.account == null ? context.tr('add_account') : context.tr('edit_account')} - ${widget.method.name}',
                style: TextStyle(
                  color: colors.textPrimary,
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                ),
              ),
              const SizedBox(height: 12),
              for (final field in widget.method.fields) ...[
                if (field.type.toLowerCase() == 'file')
                  _fileField(field)
                else
                  AppTextField(
                    label: field.name,
                    controller: _controllers[field.name],
                    maxLines: field.type.toLowerCase() == 'textarea' ? 4 : 1,
                    validator: (v) {
                      if (field.isRequired && (v == null || v.trim().isEmpty)) {
                        return context.tr('field_required');
                      }
                      return null;
                    },
                  ),
                const SizedBox(height: 10),
              ],
              const SizedBox(height: 8),
              AppButton(
                label: context.tr('save_changes'),
                isLoading: _saving,
                onTap: _save,
                icon: Icons.save_rounded,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _fileField(WithdrawField field) {
    final colors = context.colors;
    final currentPath = _controllers[field.name]?.text.trim() ?? '';
    final hasFile = currentPath.isNotEmpty;

    return FormField<String>(
      validator: (_) {
        if (field.isRequired && (_controllers[field.name]?.text.trim().isEmpty ?? true)) {
          return context.tr('file_required');
        }
        return null;
      },
      builder: (state) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              field.name,
              style: TextStyle(
                color: colors.textPrimary,
                fontWeight: FontWeight.w600,
                fontSize: 13,
              ),
            ),
            const SizedBox(height: 8),
            InkWell(
              borderRadius: BorderRadius.circular(12),
              onTap: () async {
                final XFile? picked = await _picker.pickImage(source: ImageSource.gallery);
                if (picked == null) return;
                _controllers[field.name]?.text = picked.path;
                if (mounted) {
                  setState(() {});
                  state.didChange(picked.path);
                }
              },
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                decoration: BoxDecoration(
                  color: colors.bgCard,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: state.hasError ? AppTheme.error : colors.divider,
                  ),
                ),
                child: Row(
                  children: [
                    Icon(Icons.upload_file_rounded, color: colors.primary),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        hasFile ? File(currentPath).uri.pathSegments.last : context.tr('change_file'),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: hasFile ? colors.textPrimary : colors.textSecondary,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            if (state.hasError) ...[
              const SizedBox(height: 6),
              Text(
                state.errorText ?? context.tr('file_required'),
                style: const TextStyle(color: AppTheme.error, fontSize: 12),
              ),
            ],
          ],
        );
      },
    );
  }
}

