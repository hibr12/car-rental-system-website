import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../widgets/states/empty_state_widget.dart';

class BranchListScreen extends StatelessWidget {
  const BranchListScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(title: const Text('Our Locations')),
      body: EmptyStateWidget(
        icon: LucideIcons.map,
        title: 'Locations Coming Soon',
        message:
            'We are expanding! Check back later to see our new physical branches.',
        actionText: 'Go Back',
        onAction: () => context.pop(),
      ),
    );
  }
}
