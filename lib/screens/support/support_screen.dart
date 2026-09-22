import 'package:flutter/material.dart';
import 'package:lucide_icons/lucide_icons.dart';
import '../../core/colors/app_colors.dart';
import '../../core/spacing/app_spacing.dart';
import '../../core/typography/app_typography.dart';
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
  String _searchQuery = '';

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
            const SizedBox(height: AppSpacing.xxl),
            Text('Send us a Message',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            _buildContactForm(),
            const SizedBox(height: AppSpacing.xxxl),
            Text('Frequently Asked Questions',
                style: AppTypography.textTheme.headlineMedium),
            const SizedBox(height: AppSpacing.md),
            _buildFaqList(_searchQuery),
          ],
        ),
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
      child: TextField(
        onChanged: (value) => setState(() => _searchQuery = value),
        decoration: const InputDecoration(
          hintText: 'Search for help...',
          prefixIcon: Icon(LucideIcons.search, color: AppColors.textTertiary),
          border: InputBorder.none,
          contentPadding: EdgeInsets.symmetric(
              horizontal: AppSpacing.md, vertical: AppSpacing.md),
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

  Widget _buildFaqList(String query) {
    final faqs = [
      {
        'q': 'How do I cancel my reservation?',
        'a':
            'Open your booking from the Bookings tab and tap "Cancel Reservation" while it is still awaiting payment or approval.'
      },
      {
        'q': 'How do I pay for my booking?',
        'a':
            'Pay securely online with Chapa (Telebirr, cards, and bank transfer) or choose "Pay with Cash at Branch" and settle at the counter — staff will confirm your payment.'
      },
      {
        'q': 'Do I need a driver license to book?',
        'a':
            'Yes. Upload your driver license (front and back) from Profile → Driver\'s License. Your booking requires a verified license, and some vehicles require specific license categories.'
      },
      {
        'q': 'How do refunds work?',
        'a':
            'If you cancel a booking that was already paid, our team reviews and processes your refund. Contact support with your booking reference for assistance.'
      },
    ];

    final filtered = query.trim().isEmpty
        ? faqs
        : faqs
            .where((faq) =>
                faq['q']!.toLowerCase().contains(query.toLowerCase()) ||
                faq['a']!.toLowerCase().contains(query.toLowerCase()))
            .toList();

    if (filtered.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(AppSpacing.lg),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
          border: Border.all(color: AppColors.border),
        ),
        child: Text(
          'No answers match "$query". Send us a message below and we will help.',
          style: AppTypography.textTheme.bodyMedium
              ?.copyWith(color: AppColors.textSecondary),
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppSpacing.radiusLg),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: filtered.map((faq) {
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
