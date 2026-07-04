export const colors = {
  // ── Blue (primary brand) ─────────────────────────────────────
  primary:     '#1A6FE8',   // main blue
  primaryDark: '#0D4FB5',   // deep blue — headers, nav bars
  primaryLight:'#E8F0FD',   // very light blue tint

  // ── Orange (accent / CTA) ────────────────────────────────────
  accent:      '#F97316',   // orange CTA
  accentDark:  '#C85A08',   // darker orange
  accentLight: '#FEF0E6',   // light orange tint

  // ── Chat bubbles ─────────────────────────────────────────────
  bubbleMine:  '#DBEAFE',   // light blue — my messages
  bubbleTheirs:'#FFFFFF',   // white — their messages

  // ── Neutrals ─────────────────────────────────────────────────
  background:  '#F0F4FF',   // page background (light blue-tinted)
  surface:     '#F5F7FF',
  card:        '#FFFFFF',
  text:        '#0F172A',
  muted:       '#64748B',
  border:      '#CBD5E1',
  divider:     '#E2E8F0',

  // ── Status ───────────────────────────────────────────────────
  secondary:   '#22C55E',   // online dot / positive indicator
  danger:      '#EF4444',
  success:     '#22C55E',
  warning:     '#F59E0B',

  // ── Gradients ────────────────────────────────────────────────
  headerGradient:  ['#0D4FB5', '#1A6FE8'] as const,
  buttonGradient:  ['#F97316', '#FB923C'] as const,   // orange button
  darkPanel:       '#0F172A',
};
