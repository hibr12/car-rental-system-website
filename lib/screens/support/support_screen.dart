import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
import '../../core/routes/app_routes.dart';
import '../../data/repositories/contact_repository.dart';
import '../../widgets/buttons/app_buttons.dart';

class SupportScreen extends StatefulWidget {
  const SupportScreen({super.key});

  @override
  State<SupportScreen> createState() => _SupportScreenState();
}

class _SupportScreenState extends State<SupportScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _subjectController = TextEditingController();
  final _messageController = TextEditingController();
  bool _isSending = false;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _subjectController.dispose();
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _sendMessage() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _isSending = true);

    final res = await ContactRepository.instance.sendMessage(
      name: _nameController.text.trim(),
      email: _emailController.text.trim(),
      subject: _subjectController.text.trim(),
      message: _messageController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _isSending = false);

    if (res.success) {
      _nameController.clear();
      _emailController.clear();
      _subjectController.clear();
      _messageController.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content:
              Text('Message sent successfully! We\'ll get back to you soon.'),
          backgroundColor: AppColors.success,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content:
              Text(res.error?.friendlyMessage ?? 'Failed to send message.'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Help Center'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.pagePadding),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('How can we help you?',
                style: AppTypography.textTheme.displayMedium),
            const SizedBox(height: AppSpacing.lg),
            _buildSearchBox(),
            const SizedBox(height: AppSpacing.xxxl),
            Text('Contact Us', style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            _buildContactCard(
              context,
              icon: LucideIcons.messageCircle,
              title: 'Live Chat',
              subtitle: 'Typically responds in 5 minutes',
              onTap: () => context.push(AppRoutes.chat),
            ),
            const SizedBox(height: AppSpacing.md),
            _buildContactCard(
              context,
              icon: LucideIcons.phoneCall,
              title: 'Roadside Assistance',
              subtitle: '24/7 emergency support',
              onTap: () => _showRoadsideDialog(context),
            ),
            const SizedBox(height: AppSpacing.xxxl),
            Text('Send us a Message',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            _buildContactForm(),
            const SizedBox(height: AppSpacing.xxxl),
            Text('Frequently Asked Questions',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            _buildFaqList(),
          ],
        ),
      ),
    );
  }

  void _showRoadsideDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Roadside Assistance'),
        content: const Text(
            'For immediate 24/7 emergency support, please call:\n\n1-800-DRIVE-NOW\n(1-800-374-8366)'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Opening dialer...')),
              );
            },
            child: const Text('Call Now',
                style: TextStyle(color: AppColors.primary)),
          ),
        ],
      ),
    );
  }

  Widget _buildSearchBox() {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
        border: Border.all(color: AppColors.border),
      ),
      child: const TextField(
        decoration: InputDecoration(
          hintText: 'Search for help...',
          prefixIcon: Icon(LucideIcons.search, color: AppColors.textTertiary),
          border: InputBorder.none,
          contentPadding: EdgeInsets.symmetric(
              horizontal: AppSpacing.md, vertical: AppSpacing.md),
        ),
      ),
    );
  }

  Widget _buildContactCard(BuildContext context,
      {required IconData icon,
      required String title,
      required String subtitle,
      required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(AppSpacing.radiusSm),
              ),
              child: Icon(icon, color: AppColors.primary),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: AppTypography.textTheme.titleLarge),
                  const SizedBox(height: AppSpacing.xs),
                  Text(subtitle, style: AppTypography.textTheme.bodyMedium),
                ],
              ),
            ),
            const Icon(LucideIcons.chevronRight, color: AppColors.textTertiary),
          ],
        ),
      ),
    );
  }

  Widget _buildContactForm() {
    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Form(
        key: _formKey,
        child: Column(
          children: [
            _buildFormField('Name', _nameController, LucideIcons.user),
            const SizedBox(height: AppSpacing.md),
            _buildFormField('Email', _emailController, LucideIcons.mail,
                keyboardType: TextInputType.emailAddress),
            const SizedBox(height: AppSpacing.md),
            _buildFormField(
                'Subject', _subjectController, LucideIcons.fileText),
            const SizedBox(height: AppSpacing.md),
            TextFormField(
              controller: _messageController,
              maxLines: 4,
              validator: (val) =>
                  val == null || val.trim().isEmpty ? 'Required' : null,
              decoration: InputDecoration(
                labelText: 'Message',
                hintText: 'Describe your issue or question...',
                filled: true,
                fillColor: AppColors.background,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
                  borderSide:
                      const BorderSide(color: AppColors.primary, width: 1.5),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.lg),
            PrimaryButton(
              text: 'Send Message',
              isLoading: _isSending,
              onPressed: _isSending ? null : _sendMessage,
              icon: LucideIcons.send,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFormField(
    String label,
    TextEditingController controller,
    IconData icon, {
    TextInputType keyboardType = TextInputType.text,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      validator: (val) => val == null || val.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: AppColors.primary, size: 20),
        filled: true,
        fillColor: AppColors.background,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(AppSpacing.radiusMd),
          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
        ),
      ),
    );
  }

  Widget _buildFaqList() {
    final faqs = [
      {
        'q': 'How do I cancel my reservation?',
        'a':
            'You can cancel your reservation for free up to 24 hours before your trip starts.'
      },
      {
        'q': 'What happens if I return the car late?',
        'a': 'Late returns are subject to a fee of \$50 per hour.'
      },
      {
        'q': 'How does insurance work?',
        'a':
            'All trips include standard liability coverage. You can upgrade this during checkout.'
      },
    ];

    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: faqs.map((faq) {
          return ExpansionTile(
            title: Text(faq['q']!, style: AppTypography.textTheme.titleMedium),
            children: [
              Padding(
                padding: const EdgeInsets.all(AppSpacing.md).copyWith(top: 0),
                child:
                    Text(faq['a']!, style: AppTypography.textTheme.bodyLarge),
              ),
            ],
          );
        }).toList(),
      ),
    );
  }
}
