class WithdrawField {
  final String name;
  final String type;
  final String validation;

  const WithdrawField({
    required this.name,
    required this.type,
    required this.validation,
  });

  bool get isRequired => validation.toLowerCase() == 'required';

  factory WithdrawField.fromJson(Map<String, dynamic> json) {
    return WithdrawField(
      name: (json['name'] ?? '').toString(),
      type: (json['type'] ?? 'text').toString(),
      validation: (json['validation'] ?? 'nullable').toString(),
    );
  }
}

class WithdrawMethod {
  final int id;
  final String name;
  final String type;
  final String currency;
  final double charge;
  final String chargeType;
  final double rate;
  final double minWithdraw;
  final double maxWithdraw;
  final String? icon;
  final List<WithdrawField> fields;

  const WithdrawMethod({
    required this.id,
    required this.name,
    required this.type,
    required this.currency,
    required this.charge,
    required this.chargeType,
    required this.rate,
    required this.minWithdraw,
    required this.maxWithdraw,
    this.icon,
    this.fields = const [],
  });

  factory WithdrawMethod.fromJson(Map<String, dynamic> json) {
    final rawFields = json['fields'];
    final fields = <WithdrawField>[];
    if (rawFields is List) {
      for (final item in rawFields) {
        if (item is Map<String, dynamic>) {
          fields.add(WithdrawField.fromJson(item));
        }
      }
    } else if (rawFields is Map<String, dynamic>) {
      // Some APIs return fields as a keyed object: {"1": {...}, "2": {...}}
      final entries = rawFields.entries.toList()
        ..sort((a, b) => a.key.compareTo(b.key));
      for (final entry in entries) {
        final item = entry.value;
        if (item is Map<String, dynamic>) {
          fields.add(WithdrawField.fromJson(item));
        }
      }
    }

    return WithdrawMethod(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] ?? '').toString(),
      type: (json['type'] ?? '').toString(),
      currency: (json['currency'] ?? '').toString(),
      charge: _toDouble(json['charge']),
      chargeType: (json['charge_type'] ?? 'fixed').toString(),
      rate: _toDouble(json['rate']),
      minWithdraw: _toDouble(json['min_withdraw']),
      maxWithdraw: _toDouble(json['max_withdraw']),
      icon: json['icon']?.toString(),
      fields: fields,
    );
  }

  static double _toDouble(dynamic value) {
    if (value == null) return 0;
    if (value is num) return value.toDouble();
    return double.tryParse(value.toString()) ?? 0;
  }
}

class WithdrawAccount {
  final int id;
  final int withdrawMethodId;
  final String methodName;
  final Map<String, String> credentials;

  const WithdrawAccount({
    required this.id,
    required this.withdrawMethodId,
    required this.methodName,
    this.credentials = const {},
  });

  factory WithdrawAccount.fromJson(Map<String, dynamic> json) {
    final creds = <String, String>{};
    final rawCreds = json['credentials'];

    if (rawCreds is List) {
      for (final item in rawCreds) {
        if (item is! Map<String, dynamic>) continue;
        final key = (item['name'] ?? '').toString();
        if (key.isEmpty) continue;
        creds[key] = (item['value'] ?? '').toString();
      }
    } else if (rawCreds is Map<String, dynamic>) {
      rawCreds.forEach((key, value) {
        if (value is Map<String, dynamic>) {
          creds[(value['name'] ?? key).toString()] = (value['value'] ?? '').toString();
        } else {
          creds[key] = (value ?? '').toString();
        }
      });
    }

    return WithdrawAccount(
      id: (json['id'] as num?)?.toInt() ?? 0,
      withdrawMethodId: (json['withdraw_method_id'] as num?)?.toInt() ??
          (json['method'] is Map<String, dynamic>
              ? ((json['method']['id'] as num?)?.toInt() ?? 0)
              : 0),
      methodName: (json['method_name'] ?? '').toString(),
      credentials: creds,
    );
  }
}

