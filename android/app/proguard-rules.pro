# Flutter & Plugin ProGuard / R8 Rules for Release Builds

# Flutter Engine
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.** { *; }
-keep class io.flutter.util.** { *; }
-keep class io.flutter.view.** { *; }
-keep class io.flutter.** { *; }
-keep class io.flutter.plugins.** { *; }

# Flutter Secure Storage & Jetpack Security
-keep class androidx.security.crypto.** { *; }
-dontwarn androidx.security.crypto.**

# Google Fonts & GMS
-keep class com.google.android.gms.** { *; }
-dontwarn com.google.android.gms.**

# OkHttp & HTTP client
-dontwarn okhttp3.**
-dontwarn okio.**
-keep class okhttp3.** { *; }
-keep interface okhttp3.** { *; }
