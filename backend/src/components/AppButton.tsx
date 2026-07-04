import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { colors } from '../theme/colors';

type Props = {
  title: string;
  onPress: () => void;
  variant?: 'primary' | 'secondary' | 'orange';
  disabled?: boolean;
  loading?: boolean;
  containerStyle?: any;
};

export function AppButton({ title, onPress, variant = 'primary', disabled = false, loading = false, containerStyle }: Props) {
  const isDisabled = disabled || loading;

  if (variant === 'orange') {
    return (
      <Pressable style={[s.base, isDisabled && s.disabled, containerStyle]} onPress={onPress} disabled={isDisabled}>
        <LinearGradient colors={colors.buttonGradient} style={s.fill} start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }}>
          {loading
            ? <ActivityIndicator color="#fff" />
            : <Text style={[s.text, { color: '#fff' }]}>{title}</Text>}
        </LinearGradient>
      </Pressable>
    );
  }

  if (variant === 'primary') {
    return (
      <Pressable style={[s.base, isDisabled && s.disabled, containerStyle]} onPress={onPress} disabled={isDisabled}>
        <LinearGradient colors={colors.headerGradient} style={s.fill} start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }}>
          {loading
            ? <ActivityIndicator color="#fff" />
            : <Text style={[s.text, { color: '#fff' }]}>{title}</Text>}
        </LinearGradient>
      </Pressable>
    );
  }

  return (
    <Pressable style={[s.base, s.secondary, isDisabled && s.disabled, containerStyle]} onPress={onPress} disabled={isDisabled}>
      {loading
        ? <ActivityIndicator color={colors.primary} />
        : <Text style={[s.text, { color: colors.primary }]}>{title}</Text>}
    </Pressable>
  );
}

const s = StyleSheet.create({
  base: {
    height: 54, borderRadius: 14,
    alignItems: 'center', justifyContent: 'center',
    marginTop: 16, overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.2, shadowRadius: 12, elevation: 5,
  },
  fill: { width: '100%', height: '100%', alignItems: 'center', justifyContent: 'center' },
  secondary: {
    backgroundColor: '#fff',
    borderWidth: 1.5, borderColor: colors.border,
    shadowColor: 'transparent',
  },
  text:     { fontWeight: '800', fontSize: 15, letterSpacing: 0.2 },
  disabled: { opacity: 0.5 },
});
