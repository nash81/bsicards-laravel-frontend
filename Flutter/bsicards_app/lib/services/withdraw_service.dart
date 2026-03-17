import '../config/app_config.dart';
import '../models/withdraw_models.dart';
import 'api_service.dart';
import 'dart:convert';
import 'dart:io';

class WithdrawService {
  static Future<List<WithdrawMethod>> getMethods() async {
    final data = await ApiService.get(AppConfig.withdrawMethodsEndpoint);
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(WithdrawMethod.fromJson)
        .toList();
  }

  static Future<List<WithdrawAccount>> getAccounts() async {
    final data = await ApiService.get(AppConfig.withdrawAccountsEndpoint);
    return (data['data'] as List? ?? [])
        .whereType<Map<String, dynamic>>()
        .map(WithdrawAccount.fromJson)
        .toList();
  }

  static Future<void> createAccount({
    required int methodId,
    required String methodName,
    required List<WithdrawField> fields,
    required Map<String, String> values,
  }) async {
    final payload = _buildCredentialsPayload(fields, values);

    if (payload.files.isNotEmpty) {
      await ApiService.postMultipart(
        AppConfig.withdrawAccountsEndpoint,
        fields: {
          'withdraw_method_id': methodId.toString(),
          'method_name': methodName,
          'credentials': jsonEncode(payload.credentials),
        },
        files: payload.files,
      );
      return;
    }

    await ApiService.post(
      AppConfig.withdrawAccountsEndpoint,
      body: {
        'withdraw_method_id': methodId,
        'method_name': methodName,
        'credentials': payload.credentials,
      },
    );
  }

  static Future<void> updateAccount({
    required int accountId,
    required String methodName,
    required List<WithdrawField> fields,
    required Map<String, String> values,
  }) async {
    final payload = _buildCredentialsPayload(fields, values);

    if (payload.files.isNotEmpty) {
      await ApiService.postMultipart(
        '${AppConfig.withdrawAccountsEndpoint}/$accountId',
        fields: {
          '_method': 'PUT',
          'method_name': methodName,
          'credentials': jsonEncode(payload.credentials),
        },
        files: payload.files,
      );
      return;
    }

    await ApiService.put(
      '${AppConfig.withdrawAccountsEndpoint}/$accountId',
      body: {
        'method_name': methodName,
        'credentials': payload.credentials,
      },
    );
  }

  static Future<void> deleteAccount(int accountId) async {
    await ApiService.delete('${AppConfig.withdrawAccountsEndpoint}/$accountId');
  }

  static _CredentialsPayload _buildCredentialsPayload(
    List<WithdrawField> fields,
    Map<String, String> values,
  ) {
    final credentials = <String, Map<String, String>>{};
    final files = <String, File>{};

    for (final field in fields) {
      final value = (values[field.name] ?? '').trim();
      final type = field.type.toLowerCase();

      if (type == 'file' && value.isNotEmpty && File(value).existsSync()) {
        final token = '__file__${field.name}';
        credentials[field.name] = {
          'type': field.type,
          'validation': field.validation,
          'value': token,
        };
        files['credential_files[${field.name}]'] = File(value);
      } else {
        credentials[field.name] = {
          'type': field.type,
          'validation': field.validation,
          'value': value,
        };
      }
    }

    return _CredentialsPayload(credentials: credentials, files: files);
  }

  static Future<Map<String, dynamic>> initiate({
    required int withdrawAccountId,
    required double amount,
  }) async {
    return ApiService.post(
      AppConfig.withdrawInitiateEndpoint,
      body: {
        'withdraw_account': withdrawAccountId,
        'amount': amount,
      },
    );
  }
}


class _CredentialsPayload {
  final Map<String, Map<String, String>> credentials;
  final Map<String, File> files;

  const _CredentialsPayload({required this.credentials, required this.files});
}


