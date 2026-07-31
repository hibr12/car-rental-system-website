import '../../core/config/api_endpoints.dart';
import '../models/api_models.dart';
import '../api/api_client.dart';
import '../../models/transaction_model.dart';

class TransactionRepository {
  static final TransactionRepository instance =
      TransactionRepository._internal();
  TransactionRepository._internal();

  final ApiClient _api = ApiClient.instance;

  Future<ApiResponse<List<Transaction>>> getUserTransactions() async {
    try {
      final json = await _api.get(ApiEndpoints.payments);
      final dataList = json['data'] as List? ?? [];
      final transactions = dataList
          .map((item) => Transaction.fromJson(item as Map<String, dynamic>))
          .toList();
      return ApiResponse.success(transactions);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }

  Future<ApiResponse<Transaction>> getTransactionById(String id) async {
    try {
      final json = await _api.get(ApiEndpoints.payment(int.tryParse(id) ?? 0));
      final transactionData = json['data'] as Map<String, dynamic>;
      final transaction = Transaction.fromJson(transactionData);
      return ApiResponse.success(transaction);
    } on ApiException catch (e) {
      return ApiResponse.error(e.error);
    }
  }
}
