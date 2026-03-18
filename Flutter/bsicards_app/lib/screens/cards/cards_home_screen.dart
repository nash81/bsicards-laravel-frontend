import 'package:flutter/material.dart';
import '../../config/app_colors.dart';
import '../../l10n/app_localizations.dart';
import '../../models/virtual_card.dart';
import '../../services/card_service.dart';
import '../../widgets/common_widgets.dart';
import 'digital_cards_screen.dart';

class CardsHomeScreen extends StatefulWidget {
  const CardsHomeScreen({super.key});

  @override
  State<CardsHomeScreen> createState() => _CardsHomeScreenState();
}

class _CardsHomeScreenState extends State<CardsHomeScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tab;
  List<VirtualCard> _digital = [];
  List<VirtualCard> _digitalVisa = [];
  List<VirtualCard> _master  = [];
  List<VirtualCard> _visa    = [];
  List<Map<String, dynamic>> _masterPending = [];
  List<Map<String, dynamic>> _visaPending = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _tab = TabController(length: 4, vsync: this);
    _tab.addListener(_handleTabChange);
    _loadCards();
  }

  void _handleTabChange() {
    if (!mounted) return;
    setState(() {});
  }

  @override
  void dispose() {
    _tab.removeListener(_handleTabChange);
    _tab.dispose();
    super.dispose();
  }

  Future<void> _loadCards() async {
    if (!mounted) return;
    setState(() => _loading = true);
    try {
      final results = await Future.wait([
        CardService.getDigitalCards(),
        CardService.getDigitalVisaCards(),
        CardService.getMasterCards(),
        CardService.getVisaCards(),
      ]);
      if (mounted) {
        setState(() {
          _digital = results[0] as List<VirtualCard>;
          _digitalVisa = results[1] as List<VirtualCard>;
          final masterData = results[2] as Map<String, dynamic>;
          _master = masterData['cards'] as List<VirtualCard>;
          _masterPending = (masterData['pending'] as List?)
                  ?.whereType<Map<String, dynamic>>()
                  .toList() ??
              [];
          final visaData = results[3] as Map<String, dynamic>;
          _visa = visaData['cards'] as List<VirtualCard>;
          _visaPending = (visaData['pending'] as List?)
                  ?.whereType<Map<String, dynamic>>()
                  .toList() ??
              [];
          _loading = false;
        });
      }
    } catch (e, st) {
      debugPrint('⚠️ _loadCards error: $e\n$st');
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final tr = context.tr;
    final isInitialLoading = _loading &&
        _digital.isEmpty &&
        _digitalVisa.isEmpty &&
        _master.isEmpty &&
        _visa.isEmpty &&
        _masterPending.isEmpty &&
        _visaPending.isEmpty;

    return Scaffold(
      backgroundColor: context.colors.bgDark,
      appBar: AppBar(
        title: Text(tr('my_cards')),
        bottom: TabBar(
          controller: _tab,
          isScrollable: true,
          indicatorColor: Colors.transparent,
          dividerColor: Colors.transparent,
          labelPadding: const EdgeInsets.symmetric(horizontal: 4, vertical: 8),
          tabs: [
            _buildTabBadge('Digital Master', 0),
            _buildTabBadge('Digital Visa', 1),
            _buildTabBadge('MasterCard', 2),
            _buildTabBadge('VisaCard', 3),
          ],
        ),
      ),
      body: isInitialLoading
          ? ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 100),
              children: [
                for (int i = 0; i < 3; i++) ...[
                  const ShimmerBox(height: 200, radius: 20),
                  if (i < 2) const SizedBox(height: 16),
                ],
              ],
            )
          : TabBarView(
              controller: _tab,
              children: [
                DigitalCardsScreen(cards: _digital, loading: _loading, onRefresh: _loadCards),
                DigitalVisaCardsScreen(
                  cards: _digitalVisa,
                  loading: _loading,
                  onRefresh: _loadCards,
                ),
                MasterCardsScreen(
                  cards: _master,
                  pending: _masterPending,
                  loading: _loading,
                  onRefresh: _loadCards,
                ),
                VisaCardsScreen(
                  cards: _visa,
                  pending: _visaPending,
                  loading: _loading,
                  onRefresh: _loadCards,
                ),
              ],
            ),
    );
  }

  Tab _buildTabBadge(String label, int index) {
    final colors = context.colors;
    final isSelected = _tab.index == index;

    return Tab(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? colors.primary.withValues(alpha: 0.2) : Colors.transparent,
          borderRadius: BorderRadius.circular(999),
          border: Border.all(
            color: isSelected ? colors.primary : colors.divider,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? colors.primary : colors.textSecondary,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
            fontSize: 13,
          ),
        ),
      ),
    );
  }
}
