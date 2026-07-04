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

type Props = StackScreenProps<AuthStackParamList, 'Register'>;

export function RegisterScreen({ navigation }: Props) {
  const { registerAccount } = useAuth();
  const [name,            setName]            = React.useState('');
  const [email,           setEmail]           = React.useState('');
  const [phone,           setPhone]           = React.useState('');
  const [password,        setPassword]        = React.useState('');
  const [confirmPassword, setConfirmPassword] = React.useState('');
  const [showPwd,         setShowPwd]         = React.useState(false);
  const [error,           setError]           = React.useState('');
  const [loading,         setLoading]         = React.useState(false);

  const onRegister = async () => {
    if (!name.trim() || !email.trim() || !password.trim() || !confirmPassword.trim()) {
      setError('Please fill in all required fields.'); return;
    }
    if (password.length < 8) { setError('Password must be at least 8 characters.'); return; }
    if (password !== confirmPassword) { setError('Passwords do not match.'); return; }
    setLoading(true); setError('');
    const result = await registerAccount({ name: name.trim(), email: email.trim(), password, phone: phone.trim() || undefined });
    setLoading(false);
    if (!result.success) setError(result.error || 'Unable to create your account.');
  };

  return (
    <AuthShell
      title="Create account"
      subtitle="Join SecureComm — it's free"
      footer={
        <View style={s.footerRow}>
          <Text style={s.footerText}>Already have an account?</Text>
          <Pressable onPress={() => navigation.goBack()}>
            <Text style={s.footerLink}>Sign in</Text>
          </Pressable>
        </View>
      }
    >
      <AppInput label="FULL NAME"  icon="person-outline"  placeholder="Your full name"    value={name}            onChangeText={setName} />
      <AppInput label="EMAIL"      icon="mail-outline"    placeholder="you@example.com"   value={email}           onChangeText={setEmail}           autoCapitalize="none" keyboardType="email-address" />
      <AppInput label="PHONE (optional)" icon="call-outline" placeholder="+252611111111" value={phone}           onChangeText={setPhone}           keyboardType="phone-pad" />
      <AppInput label="PASSWORD"   icon="lock-closed-outline" placeholder="Create a password" value={password}   onChangeText={setPassword}        secureTextEntry={!showPwd}
        rightLabel={showPwd ? 'HIDE' : 'SHOW'} onRightPress={() => setShowPwd(v => !v)} />
      <AppInput label="CONFIRM PASSWORD" icon="checkmark-circle-outline" placeholder="Repeat password" value={confirmPassword} onChangeText={setConfirmPassword} secureTextEntry={!showPwd} />

      {!!error && (
        <View style={s.errorBox}>
          <Icon name="alert-circle" size={16} color={colors.danger} />
          <Text style={s.errorText}>{error}</Text>
        </View>
      )}

      <AppButton
        title={loading ? 'Creating account…' : 'Create account'}
        variant="orange"
        onPress={() => void onRegister()}
        disabled={loading}
      />
      <AppButton
        title="Already have an account? Sign in"
        variant="secondary"
        onPress={() => navigation.goBack()}
      />
    </AuthShell>
  );
}

const s = StyleSheet.create({
  errorBox: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    marginTop: 14, padding: 12, borderRadius: 12,
    backgroundColor: '#FEF2F2', borderWidth: 1, borderColor: '#FECACA',
  },
  errorText: { flex: 1, color: colors.danger, fontSize: 13, fontWeight: '600' },
  footerRow: { flexDirection: 'row', gap: 6, alignItems: 'center' },
  footerText: { color: colors.muted, fontSize: 14 },
  footerLink: { color: colors.accent, fontSize: 14, fontWeight: '800' },
});
