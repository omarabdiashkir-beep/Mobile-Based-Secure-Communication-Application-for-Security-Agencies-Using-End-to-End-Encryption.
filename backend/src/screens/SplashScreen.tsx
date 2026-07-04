import React, { useEffect } from 'react';
import { Icon } from '../components/Icon';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { Screen } from '../components/Screen';
import { colors } from '../theme/colors';
import type { StackScreenProps } from '@react-navigation/stack';
import type { AuthStackParamList } from '../navigation/types';

type Props = Partial<StackScreenProps<AuthStackParamList, 'Splash'>>;

export function SplashScreen({ navigation }: Props) {
  useEffect(() => {
    if (!navigation) {
      return;
    }

    const timer = setTimeout(() => navigation.replace('Login'), 1500);
    return () => clearTimeout(timer);
  }, [navigation]);

  return (
    <Screen style={styles.page}>
      <View style={styles.logo}>
        <Icon name="chatbubbles" size={44} color="#FFFFFF" />
      </View>
      <Text style={styles.title}>ChatApp</Text>
      <ActivityIndicator size="small" color={colors.primary} style={styles.loader} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  page: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.background,
  },
  logo: {
    width: 92,
    height: 92,
    borderRadius: 46,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  title: {
    marginTop: 18,
    color: colors.text,
    fontSize: 30,
    fontWeight: '800',
  },
  loader: {
    marginTop: 20,
  },
});
