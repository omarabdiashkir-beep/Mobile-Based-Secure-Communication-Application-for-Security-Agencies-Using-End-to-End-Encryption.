import React from 'react';
import { Icon } from '../components/Icon';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import type { StackScreenProps } from '@react-navigation/stack';
import { AppButton } from '../components/AppButton';
import { AppInput } from '../components/AppInput';
import { AuthShell } from '../components/AuthShell';
import type { AuthStackParamList } from '../navigation/types';
import { colors } from '../theme/colors';

type Props = StackScreenProps<AuthStackParamList, 'ForgotPassword'>;

export function ForgotPasswordScreen({ navigation }: Props) {
  const [email, setEmail] = React.useState('');
  const [error, setError] = React.useState('');
  const [success, setSuccess] = React.useState('');

  const onReset = () => {
    if (!email.includes('@')) {
      setSuccess('');
      setError('Enter a valid email address.');
      return;
    }

    setError('');
    setSuccess(`Recovery instructions were sent to ${email}.`);
  };

  return (
    <AuthShell
      title="Forgot password?"
      subtitle="Enter your email and we'll send you a reset link."
      footer={
        <Pressable onPress={() => navigation.goBack()}>
          <Text style={styles.footerLink}>Back to sign in</Text>
        </Pressable>
      }
    >
      <View style={styles.tipCard}>
        <Icon name="mail-open-outline" size={20} color={colors.primary} />
        <Text style={styles.tipText}>Use your work email so the reset request reaches the right account.</Text>
      </View>

      <AppInput
        label="EMAIL ADDRESS"
        icon="mail-outline"
        placeholder="you@example.com"
        autoCapitalize="none"
        keyboardType="email-address"
        value={email}
        onChangeText={setEmail}
      />

      {!!error ? (
        <View style={styles.alertError}>
          <Icon name="alert-circle" size={16} color={colors.danger} />
          <Text style={styles.alertText}>{error}</Text>
        </View>
      ) : null}

      {!!success ? (
        <View style={styles.alertSuccess}>
          <Icon name="checkmark-circle" size={16} color={colors.success} />
          <Text style={styles.successText}>{success}</Text>
        </View>
      ) : null}

      <AppButton title="SEND RESET LINK" onPress={onReset} />
    </AuthShell>
  );
}

const styles = StyleSheet.create({
  tipCard: {
    marginBottom: 10,
    borderRadius: 18,
    backgroundColor: colors.surface,
    padding: 16,
    flexDirection: 'row',
    gap: 10,
    alignItems: 'center',
  },
  tipText: {
    flex: 1,
    color: colors.primaryDark,
    fontSize: 13,
    lineHeight: 19,
    fontWeight: '700',
  },
  alertError: {
    marginTop: 14,
    borderRadius: 16,
    paddingHorizontal: 14,
    paddingVertical: 12,
    backgroundColor: '#FEF2F2',
    borderWidth: 1,
    borderColor: '#FECACA',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  alertSuccess: {
    marginTop: 14,
    borderRadius: 16,
    paddingHorizontal: 14,
    paddingVertical: 12,
    backgroundColor: '#ECFDF5',
    borderWidth: 1,
    borderColor: '#A7F3D0',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  alertText: {
    color: colors.danger,
    flex: 1,
    fontSize: 13,
    fontWeight: '600',
  },
  successText: {
    color: colors.success,
    flex: 1,
    fontSize: 13,
    fontWeight: '600',
  },
  footerLink: {
    color: colors.primaryDark,
    fontSize: 14,
    fontWeight: '800',
  },
});
