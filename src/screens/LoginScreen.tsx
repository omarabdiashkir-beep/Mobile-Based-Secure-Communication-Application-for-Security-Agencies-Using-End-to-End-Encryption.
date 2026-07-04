import React from 'react';
import { Icon } from '../components/Icon';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import type { StackScreenProps } from '@react-navigation/stack';
import { AppButton } from '../components/AppButton';
import { AppInput } from '../components/AppInput';
import { AuthShell } from '../components/AuthShell';
import type { AuthStackParamList } from '../navigation/types';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';
import { biometricUtils } from '../utils/biometricUtils';

type Props = StackScreenProps<AuthStackParamList, 'Login'>;

export function LoginScreen({ navigation }: Props) {
  const { signInWithPassword, signInWithBiometrics } = useAuth();
  const [email, setEmail]           = React.useState('');
  const [password, setPassword]     = React.useState('');
  const [showPwd, setShowPwd]       = React.useState(false);
  const [error, setError]           = React.useState('');
  const [loading, setLoading]       = React.useState(false);
  const [biometricLabel, setBiometricLabel] = React.useState<string | null>(null);

  React.useEffect(() => {
    let mounted = true;
    const init = async () => {
      const enabled = await biometricUtils.isEnabled();
      const av = await biometricUtils.getAvailability();
      if (!mounted || !enabled || !av.isAvailable) return;
      setBiometricLabel(av.isFaceIdSupported ? 'Face ID' : av.isFingerprintSupported ? 'Fingerprint' : null);
    };
    void init();
    return () => { mounted = false; };
  }, []);

  const onLogin = async () => {
    if (!email.trim() || !password.trim()) { setError('Enter your email and password.'); return; }
    setLoading(true); setError('');
    const result = await signInWithPassword(email.trim(), password);
    setLoading(false);
    if (!result.success) setError(result.error || 'Unable to sign in.');
  };

  const onBiometric = async () => {
    setLoading(true); setError('');
    const result = await signInWithBiometrics();
    setLoading(false);
    if (!result.success) setError(result.error || 'Biometric sign-in failed.');
  };

  return (
    <AuthShell
      title="Welcome back"
      subtitle="Sign in to your account to continue"
      footer={
        <View style={s.footerRow}>
          <Text style={s.footerText}>Don't have an account?</Text>
          <Pressable onPress={() => navigation.navigate('Register')}>
            <Text style={s.footerLink}>Sign up</Text>
          </Pressable>
        </View>
      }
    >
      <AppInput
        label="EMAIL"
        icon="mail-outline"
        placeholder="you@example.com"
        autoCapitalize="none"
        keyboardType="email-address"
        value={email}
        onChangeText={setEmail}
      />

      <AppInput
        label="PASSWORD"
        icon="lock-closed-outline"
        placeholder="Your password"
        secureTextEntry={!showPwd}
        value={password}
        onChangeText={setPassword}
        rightLabel={showPwd ? 'HIDE' : 'SHOW'}
        onRightPress={() => setShowPwd(v => !v)}
      />

      <Pressable style={s.forgotWrap} onPress={() => navigation.navigate('ForgotPassword')}>
        <Text style={s.forgotText}>Forgot password?</Text>
      </Pressable>

      {!!error && (
        <View style={s.errorBox}>
          <Icon name="alert-circle" size={16} color={colors.danger} />
          <Text style={s.errorText}>{error}</Text>
        </View>
      )}

      <AppButton
        title={loading ? 'Signing in…' : 'Sign in'}
        variant="orange"
        onPress={() => void onLogin()}
        disabled={loading}
      />

      {biometricLabel && (
        <Pressable style={s.bioBtn} onPress={() => void onBiometric()} disabled={loading}>
          <Icon
            name={biometricLabel === 'Face ID' ? 'scan-circle-outline' : 'finger-print-outline'}
            size={22}
            color={colors.primary}
          />
          <Text style={s.bioBtnText}>Use {biometricLabel}</Text>
        </Pressable>
      )}
    </AuthShell>
  );
}

const s = StyleSheet.create({
  forgotWrap: { alignSelf: 'flex-end', marginTop: 10 },
  forgotText: { color: colors.primary, fontSize: 13, fontWeight: '700' },
  errorBox: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    marginTop: 14, padding: 12, borderRadius: 12,
    backgroundColor: '#FEF2F2', borderWidth: 1, borderColor: '#FECACA',
  },
  errorText: { flex: 1, color: colors.danger, fontSize: 13, fontWeight: '600' },
  bioBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10,
    marginTop: 14, height: 52, borderRadius: 14,
    borderWidth: 1.5, borderColor: colors.border,
    backgroundColor: colors.primaryLight,
  },
  bioBtnText: { color: colors.primaryDark, fontSize: 14, fontWeight: '800' },
  footerRow: { flexDirection: 'row', gap: 6, alignItems: 'center' },
  footerText: { color: colors.muted, fontSize: 14 },
  footerLink: { color: colors.accent, fontSize: 14, fontWeight: '800' },
});
