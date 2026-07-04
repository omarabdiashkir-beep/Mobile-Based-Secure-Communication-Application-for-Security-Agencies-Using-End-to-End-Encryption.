import React from 'react';
import { Icon } from '../components/Icon';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '../components/Screen';
import { AppButton } from '../components/AppButton';
import type { StackScreenProps } from '@react-navigation/stack';
import type { AuthStackParamList } from '../navigation/types';
import { colors } from '../theme/colors';

type Props = StackScreenProps<AuthStackParamList, 'Onboarding'>;

export function OnboardingScreen({ navigation }: Props) {
  return (
    <Screen style={styles.page}>
      <View style={styles.topRow}>
        <Pressable onPress={() => navigation.replace('Login')}>
          <Text style={styles.skip}>Skip</Text>
        </Pressable>
      </View>

      <View style={styles.center}>
        <View style={styles.logoCircle}>
          <Icon name="chatbubbles" size={52} color="#FFFFFF" />
        </View>
        <Text style={styles.title}>Simple private messaging</Text>
        <Text style={styles.subtitle}>Send messages, voice notes, images and video in one clean app.</Text>
      </View>

      <AppButton title="Continue" onPress={() => navigation.replace('Login')} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  page: {
    padding: 24,
    justifyContent: 'space-between',
  },
  topRow: {
    alignItems: 'flex-end',
  },
  skip: {
    color: colors.primary,
    fontSize: 14,
    fontWeight: '700',
  },
  center: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  logoCircle: {
    width: 110,
    height: 110,
    borderRadius: 55,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 28,
  },
  title: {
    color: colors.text,
    fontSize: 30,
    fontWeight: '800',
    textAlign: 'center',
  },
  subtitle: {
    marginTop: 12,
    color: colors.muted,
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
  },
});
