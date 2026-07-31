import 'package:shared_preferences/shared_preferences.dart';

class LocalStorageService {
  static const String _favoritesKey = 'favorite_vehicle_ids';
  static const String _addressesKey = 'saved_addresses';

  static final LocalStorageService instance = LocalStorageService._internal();

  LocalStorageService._internal();

  /// Favorites

  Future<List<String>> getFavoriteVehicleIds() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getStringList(_favoritesKey) ?? [];
  }

  Future<void> addFavorite(String vehicleId) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = prefs.getStringList(_favoritesKey) ?? [];
    if (!favorites.contains(vehicleId)) {
      favorites.add(vehicleId);
      await prefs.setStringList(_favoritesKey, favorites);
    }
  }

  Future<void> removeFavorite(String vehicleId) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = prefs.getStringList(_favoritesKey) ?? [];
    favorites.remove(vehicleId);
    await prefs.setStringList(_favoritesKey, favorites);
  }

  Future<bool> isFavorite(String vehicleId) async {
    final prefs = await SharedPreferences.getInstance();
    final favorites = prefs.getStringList(_favoritesKey) ?? [];
    return favorites.contains(vehicleId);
  }

  /// Saved Addresses

  Future<List<String>> getSavedAddresses() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getStringList(_addressesKey) ?? [];
  }

  Future<void> addAddress(String address) async {
    final prefs = await SharedPreferences.getInstance();
    final addresses = prefs.getStringList(_addressesKey) ?? [];
    addresses.add(address);
    await prefs.setStringList(_addressesKey, addresses);
  }

  Future<void> removeAddress(String address) async {
    final prefs = await SharedPreferences.getInstance();
    final addresses = prefs.getStringList(_addressesKey) ?? [];
    addresses.remove(address);
    await prefs.setStringList(_addressesKey, addresses);
  }
}
