import React, { useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { colors } from '../theme/colors';
import { changePasswordApi } from '../api/authApi';
import { useAuth } from '../context/AuthContext';

type Props = {
  isExpired: boolean; // true = password_expired (needs old_password), false = must_change_password
};

export function ForceChangePasswordScreen({ isExpired }: Props) {
  const { session, signOut, clearPasswordFlags } = useAuth();

  const [oldPwd, setOldPwd] = useState('');
  const [newPwd, setNewPwd] = useState('');
  const [confirmPwd, setConfirmPwd] = useState('');
  const [showOld, setShowOld] = useState(false);
  const [showNew, setShowNew] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const rules = [
    { label: 'At least 8 characters', ok: newPwd.length >= 8 },
    { label: 'Passwords match', ok: newPwd.length > 0 && newPwd === confirmPwd },
    isExpired && { label: 'Current password entered', ok: oldPwd.length > 0 },
  ].filter(Boolean) as { label: string; ok: boolean }[];

  const canSubmit = rules.every(r => r.ok) && (!isExpired || oldPwd.trim().length > 0);

  const handleSubmit = async () => {
    if (!session) return;
    setError('');

    if (newPwd !== confirmPwd) { setError('Passwords do not match.'); return; }
    if (newPwd.length < 8) { setError('Password must be at least 8 characters.'); return; }
    if (isExpired && !oldPwd.trim()) { setError('Enter your current password.'); return; }

    setSaving(true);
    const payload: { old_password?: string; password: string; password_confirm: string } = {
      password: newPwd,
      password_confirm: confirmPwd,
    };
    if (isExpired) payload.old_password = oldPwd;

    const result = await changePasswordApi(session.token, payload);
    setSaving(false);

    if (result.error) {
      setError(result.error);
      return;
    }

    // Token is now revoked — sign out so user logs in fresh
    clearPasswordFlags();
    await signOut();
  };

  return (
    <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <StatusBar barStyle="light-content" backgroundColor={colors.primaryDark} />
      <ScrollView style={s.root} contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">

        {/* Header */}
        <View style={s.hero}>
          <View style={s.lockCircle}>
            <Icon name={isExpired ? 'time' : 'key'} size={32} color="#fff" />
          </View>
          <Text style={s.heroTitle}>
            {isExpired ? 'Password Expired' : 'Set New Password'}
          </Text>
          <Text style={s.heroSub}>
            {isExpired
              ? 'Your password has expired. Please change it to continue.'
              : 'This is your first login. You must set a new password before continuing.'}
          </Text>
        </View>

        {/* Form */}
        <View style={s.card}>

          {isExpired && (
            <Field
              label="Current Password"
              icon="lock-closed-outline"
              value={oldPwd}
              onChange={setOldPwd}
              show={showOld}
              onToggleShow={() => setShowOld(v => !v)}
            />
          )}

          <Field
            label="New Password"
            icon="lock-open-outline"
            value={newPwd}
            onChange={setNewPwd}
            show={showNew}
            onToggleShow={() => setShowNew(v => !v)}
            last={false}
          />

          <Field
            label="Confirm New Password"
            icon="checkmark-circle-outline"
            value={confirmPwd}
            onChange={setConfirmPwd}
            show={showConfirm}
            onToggleShow={() => setShowConfirm(v => !v)}
            last
          />
        </View>

        {/* Password rules */}
        <View style={s.rules}>
          {rules.map((r, i) => (
            <View key={i} style={s.ruleRow}>
              <Icon
                name={r.ok ? 'checkmark-circle' : 'ellipse-outline'}
                size={16}
                color={r.ok ? '#16A34A' : '#98A2B3'}
              />
              <Text style={[s.ruleText, r.ok && s.ruleTextOk]}>{r.label}</Text>
            </View>
          ))}
        </View>

        {/* Error */}
        {!!error && (
          <View style={s.errorBox}>
            <Icon name="alert-circle" size={16} color={colors.danger} />
            <Text style={s.errorText}>{error}</Text>
          </View>
        )}

        {/* Submit */}
        <Pressable
          style={[s.submitBtn, (!canSubmit || saving) && s.submitBtnDisabled]}
          onPress={() => void handleSubmit()}
          disabled={!canSubmit || saving}
        >
          {saving
            ? <ActivityIndicator size="small" color="#fff" />
            : <>
                <Icon name="checkmark-done" size={18} color="#fff" />
                <Text style={s.submitText}>Set new password</Text>
              </>}
        </Pressable>

        <Text style={s.footerNote}>
          After changing your password, you will be signed out and need to log in again.
        </Text>

      </ScrollView>
    </KeyboardAvoidingView>
  );
}

function Field({ label, icon, value, onChange, show, onToggleShow, last }: {
  label: string; icon: string; value: string;
  onChange: (v: string) => void;
  show: boolean; onToggleShow: () => void;
  last?: boolean;
}) {
  return (
    <View style={[f.row, !last && f.rowBorder]}>
      <Icon name={icon as any} size={18} color={colors.primary} style={f.icon} />
      <View style={{ flex: 1 }}>
        <Text style={f.label}>{label}</Text>
        <TextInput
          style={f.input}
          value={value}
          onChangeText={onChange}
          secureTextEntry={!show}
          placeholder="••••••••"
          placeholderTextColor={colors.muted}
          autoCapitalize="none"
        />
      </View>
      <Pressable onPress={onToggleShow} style={f.eyeBtn}>
        <Icon name={show ? 'eye-off-outline' : 'eye-outline'} size={18} color={colors.muted} />
      </Pressable>
    </View>
  );
}

const f = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12 },
  rowBorder: { borderBottomWidth: 1, borderBottomColor: '#F5F5F5' },
  icon: { marginRight: 12, marginLeft: 4 },
  label: { color: colors.muted, fontSize: 11, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 2 },
  input: { color: colors.text, fontSize: 15, paddingVertical: 2 },
  eyeBtn: { paddingHorizontal: 8, paddingVertical: 4 },
});

const s = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#F7F8FA' },
  content: { paddingBottom: 40 },

  hero: {
    backgroundColor: colors.primaryDark,
    alignItems: 'center',
    paddingTop: 60, paddingBottom: 32, paddingHorizontal: 24,
    gap: 12,
  },
  lockCircle: {
    width: 68, height: 68, borderRadius: 34,
    backgroundColor: 'rgba(255,255,255,0.15)',
    alignItems: 'center', justifyContent: 'center',
    borderWidth: 2, borderColor: 'rgba(255,255,255,0.3)',
  },
  heroTitle: { color: '#fff', fontSize: 22, fontWeight: '800', textAlign: 'center' },
  heroSub: { color: 'rgba(255,255,255,0.7)', fontSize: 13, textAlign: 'center', lineHeight: 19 },

  card: {
    backgroundColor: '#fff', borderRadius: 16,
    marginHorizontal: 16, marginTop: 20,
    paddingHorizontal: 16, paddingVertical: 4,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06, shadowRadius: 4, elevation: 2,
  },

  rules: { marginHorizontal: 20, marginTop: 16, gap: 8 },
  ruleRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  ruleText: { color: '#98A2B3', fontSize: 13 },
  ruleTextOk: { color: '#16A34A' },

  errorBox: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    marginHorizontal: 16, marginTop: 14,
    padding: 12, borderRadius: 12,
    backgroundColor: '#FEF2F2', borderWidth: 1, borderColor: '#FECACA',
  },
  errorText: { flex: 1, color: colors.danger, fontSize: 13, fontWeight: '600' },

  submitBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8,
    marginHorizontal: 16, marginTop: 20,
    height: 52, borderRadius: 16, backgroundColor: colors.primary,
  },
  submitBtnDisabled: { opacity: 0.45 },
  submitText: { color: '#fff', fontSize: 15, fontWeight: '800' },

  footerNote: {
    color: colors.muted, fontSize: 12, textAlign: 'center',
    marginHorizontal: 24, marginTop: 16, lineHeight: 18,
  },
});
