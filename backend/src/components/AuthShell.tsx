import React from 'react';
import { Icon } from './Icon';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  View,
  type ViewStyle,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { colors } from '../theme/colors';

type Props = {
  title: string;
  subtitle?: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
  contentStyle?: ViewStyle;
};

export function AuthShell({ title, subtitle, children, footer, contentStyle }: Props) {
  return (
    <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={s.root}>
        {/* Blue header */}
        <LinearGradient colors={colors.headerGradient} style={s.header}>
          <View style={s.logoWrap}>
            <View style={s.logoCircle}>
              <Icon name="chatbubbles" size={28} color="#fff" />
            </View>
            <View style={s.logoTextWrap}>
              <Text style={s.logoName}>SecureComm</Text>
              <Text style={s.logoTagline}>Private · Secure · Fast</Text>
            </View>
          </View>
          <Text style={s.heroTitle}>{title}</Text>
          {subtitle ? <Text style={s.heroSub}>{subtitle}</Text> : null}
        </LinearGradient>

        {/* White card */}
        <ScrollView
          style={s.scroll}
          contentContainerStyle={[s.body, contentStyle]}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >
          <View style={s.card}>
            {children}
          </View>
          {footer ? <View style={s.footer}>{footer}</View> : null}
          <View style={{ height: 32 }} />
        </ScrollView>
      </View>
    </KeyboardAvoidingView>
  );
}

const s = StyleSheet.create({
  root:   { flex: 1, backgroundColor: colors.background },
  header: {
    paddingTop: 60, paddingBottom: 36,
    paddingHorizontal: 28,
    borderBottomLeftRadius: 32,
    borderBottomRightRadius: 32,
  },
  logoWrap: { flexDirection: 'row', alignItems: 'center', gap: 14, marginBottom: 28 },
  logoCircle: {
    width: 52, height: 52, borderRadius: 26,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems: 'center', justifyContent: 'center',
    borderWidth: 2, borderColor: 'rgba(255,255,255,0.35)',
  },
  logoTextWrap: { gap: 2 },
  logoName:    { color: '#fff', fontSize: 22, fontWeight: '800', letterSpacing: 0.3 },
  logoTagline: { color: 'rgba(255,255,255,0.65)', fontSize: 12, fontWeight: '500' },
  heroTitle:   { color: '#fff', fontSize: 30, fontWeight: '800', letterSpacing: -0.5, marginBottom: 6 },
  heroSub:     { color: 'rgba(255,255,255,0.75)', fontSize: 14, lineHeight: 20 },

  scroll: { flex: 1 },
  body:   { paddingHorizontal: 20, paddingTop: 24 },
  card: {
    backgroundColor: '#fff',
    borderRadius: 24,
    padding: 24,
    shadowColor: '#1A6FE8',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 16,
    elevation: 4,
  },
  footer: { marginTop: 20, alignItems: 'center' },
});
