import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../widgets/states/empty_state_widget.dart';

class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        title: const Text('Notifications'),
      ),
      body: const EmptyStateWidget(
        icon: LucideIcons.bellOff,
        title: 'Notifications Not Available',
        message: 'Push notifications are not available yet. '
            'Check back later for booking updates and offers.',
      ),
    );
  }
}
