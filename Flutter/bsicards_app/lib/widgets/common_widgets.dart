import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../config/app_theme.dart';

// ── Primary Button ───────────────────────────────────────────────────────────
class AppButton extends StatelessWidget {
  final String label;
  final VoidCallback? onTap;
  final bool isLoading;
  final bool outlined;
  final IconData? icon;
  final Color? color;
  final double? width;

  const AppButton({
    super.key,
    required this.label,
    this.onTap,
    this.isLoading = false,
    this.outlined = false,
    this.icon,
    this.color,
    this.width,
  });

  @override
  Widget build(BuildContext context) {
    final bg = color ?? AppTheme.primary;
    return SizedBox(
      width: width ?? double.infinity,
      height: 52,
      child: outlined
          ? OutlinedButton.icon(
              onPressed: isLoading ? null : onTap,
              icon: icon != null ? Icon(icon, size: 18) : const SizedBox.shrink(),
              label: isLoading
                  ? const SizedBox(
                      width: 20, height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : Text(label),
              style: OutlinedButton.styleFrom(
                foregroundColor: bg,
                side: BorderSide(color: bg),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            )
          : ElevatedButton.icon(
              onPressed: isLoading ? null : onTap,
              icon: icon != null ? Icon(icon, size: 18) : const SizedBox.shrink(),
              label: isLoading
                  ? const SizedBox(
                      width: 20, height: 20,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white),
                    )
                  : Text(label),
              style: ElevatedButton.styleFrom(
                backgroundColor: bg,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
    ).animate().fadeIn(duration: 300.ms);
  }
}

// ── Text Input ───────────────────────────────────────────────────────────────
class AppTextField extends StatefulWidget {
  final String label;
  final String? hint;
  final TextEditingController? controller;
  final bool obscure;
  final TextInputType keyboardType;
  final IconData? prefixIcon;
  final Widget? suffix;
  final String? Function(String?)? validator;
  final void Function(String)? onChanged;
  final int? maxLines;
  final bool readOnly;

  const AppTextField({
    super.key,
    required this.label,
    this.hint,
    this.controller,
    this.obscure = false,
    this.keyboardType = TextInputType.text,
    this.prefixIcon,
    this.suffix,
    this.validator,
    this.onChanged,
    this.maxLines = 1,
    this.readOnly = false,
  });

  @override
  State<AppTextField> createState() => _AppTextFieldState();
}

class _AppTextFieldState extends State<AppTextField> {
  late bool _obscure;

  @override
  void initState() {
    super.initState();
    _obscure = widget.obscure;
  }

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: widget.controller,
      obscureText: _obscure,
      keyboardType: widget.keyboardType,
      validator: widget.validator,
      onChanged: widget.onChanged,
      onTapOutside: (_) => FocusScope.of(context).unfocus(),
      maxLines: widget.obscure ? 1 : widget.maxLines,
      readOnly: widget.readOnly,
      style: const TextStyle(color: AppTheme.textPrimary, fontSize: 15),
      decoration: InputDecoration(
        labelText: widget.label,
        hintText: widget.hint,
        prefixIcon: widget.prefixIcon != null
            ? Icon(widget.prefixIcon, size: 20)
            : null,
        suffixIcon: widget.obscure
            ? IconButton(
                icon: Icon(
                  _obscure ? Icons.visibility_off : Icons.visibility,
                  color: AppTheme.textSecondary,
                  size: 20,
                ),
                onPressed: () => setState(() => _obscure = !_obscure),
              )
            : widget.suffix,
      ),
    );
  }
}

// ── Section Header ───────────────────────────────────────────────────────────
class SectionHeader extends StatelessWidget {
  final String title;
  final String? action;
  final VoidCallback? onAction;

  const SectionHeader({
    super.key,
    required this.title,
    this.action,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(title,
            style: const TextStyle(
                fontSize: 17,
                fontWeight: FontWeight.w700,
                color: AppTheme.textPrimary)),
        if (action != null)
          TextButton(
            onPressed: onAction,
            child: Text(action!,
                style: const TextStyle(
                    color: AppTheme.primary,
                    fontSize: 13,
                    fontWeight: FontWeight.w600)),
          ),
      ],
    );
  }
}

// ── Shimmer Placeholder ──────────────────────────────────────────────────────
class ShimmerBox extends StatelessWidget {
  final double height;
  final double? width;
  final double radius;

  const ShimmerBox({super.key, required this.height, this.width, this.radius = 8});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: height,
      width: width,
      decoration: BoxDecoration(
        color: AppTheme.surfaceLight,
        borderRadius: BorderRadius.circular(radius),
      ),
    ).animate(onPlay: (c) => c.repeat()).shimmer(
          duration: 1200.ms,
          color: AppTheme.bgCard.withOpacity(0.6),
        );
  }
}

// ── Empty State ──────────────────────────────────────────────────────────────
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;

  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: AppTheme.surfaceLight,
              shape: BoxShape.circle,
            ),
            child: Icon(icon, size: 48, color: AppTheme.textSecondary),
          ),
          const SizedBox(height: 20),
          Text(title,
              style: const TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w600,
                  color: AppTheme.textPrimary)),
          if (subtitle != null) ...[
            const SizedBox(height: 8),
            Text(subtitle!,
                style: const TextStyle(
                    fontSize: 14, color: AppTheme.textSecondary),
                textAlign: TextAlign.center),
          ],
        ],
      ),
    ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.1);
  }
}

// ── Info Tile ────────────────────────────────────────────────────────────────
class InfoTile extends StatelessWidget {
  final String label;
  final String value;
  final IconData? icon;
  final Color? valueColor;

  const InfoTile({
    super.key,
    required this.label,
    required this.value,
    this.icon,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        children: [
          if (icon != null) ...[
            Icon(icon, size: 18, color: AppTheme.primary),
            const SizedBox(width: 10),
          ],
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label,
                    style: const TextStyle(
                        fontSize: 12, color: AppTheme.textSecondary)),
                const SizedBox(height: 2),
                Text(value,
                    style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                        color: valueColor ?? AppTheme.textPrimary)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

