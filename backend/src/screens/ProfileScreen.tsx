import React, { useEffect, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  Alert,
  Image,
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
import * as ImagePicker from 'expo-image-picker';
import { colors } from '../theme/colors';
import { getUserByIdApi, updateProfileApi } from '../api/chatApi';
import { changePasswordApi } from '../api/authApi';
import type { AuthSession } from '../navigation/types';
import { biometricUtils } from '../utils/biometricUtils';

type Props = {
  session: AuthSession;
  onLogout: () => void;
};

export function ProfileScreen({ session, onLogout }: Props) {
  // ── profile fields ──────────────────────────────────────────
  const [name,       setName]       = useState('');
  const [username,   setUsername]   = useState('');
  const [bio,        setBio]        = useState('');
  const [phone,      setPhone]      = useState('');
  const [occupation, setOccupation] = useState('');
  const [address,    setAddress]    = useState('');
  const [photoUrl,   setPhotoUrl]   = useState<string | null>(null);
  const [localPhoto, setLocalPhoto] = useState<string | null>(null);

  // originals for cancel revert
  const [orig, setOrig] = useState({ name: '', username: '', bio: '', phone: '', occupation: '', address: '' });

  const [loading,   setLoading]   = useState(true);
  const [saving,    setSaving]    = useState(false);
  const [isEditing, setIsEditing] = useState(false);

  // ── change password ─────────────────────────────────────────
  const [showPwd,    setShowPwd]    = useState(false);
  const [oldPwd,     setOldPwd]     = useState('');
  const [newPwd,     setNewPwd]     = useState('');
  const [confirmPwd, setConfirmPwd] = useState('');
  const [pwdSaving,  setPwdSaving]  = useState(false);

  // ── biometrics ───────────────────────────────────────────────
  const [bioAvailable, setBioAvailable] = useState(false);
  const [bioEnabled,   setBioEnabled]   = useState(false);

  // ── stats ───────────────────────────────────────────────────
  const [contactsCount,  setContactsCount]  = useState<number | null>(null);
  const [messagesSent,   setMessagesSent]   = useState<number | null>(null);

  useEffect(() => {
    void load();
    void checkBio();
  }, []);

  const load = async () => {
    setLoading(true);
    try {
      const d = await getUserByIdApi(session.token, session.userId) as any;
      // API returns user directly (no wrapper)
      const u = d?.data ?? d?.user ?? d;
      if (u) {
        const snap = {
          name:       u.name       ?? '',
          username:   u.username   ?? '',
          bio:        u.bio        ?? '',
          phone:      u.phone      ?? '',
          occupation: u.occupation ?? '',
          address:    u.address    ?? '',
        };
        setName(snap.name);
        setUsername(snap.username);
        setBio(snap.bio);
        setPhone(snap.phone);
        setOccupation(snap.occupation);
        setAddress(snap.address);
        setOrig(snap);
        setPhotoUrl(u.photo_url ?? null);
        if (u.contacts_count != null) setContactsCount(u.contacts_count);
        if (u.messages_sent  != null) setMessagesSent(u.messages_sent);
      }
    } catch (e) {
      console.warn('[Profile] load failed:', e);
    } finally {
      setLoading(false);
    }
  };

  const checkBio = async () => {
    const { isAvailable } = await biometricUtils.getAvailability();
    setBioAvailable(isAvailable);
    if (isAvailable) setBioEnabled(await biometricUtils.isAppLockEnabled());
  };

  const pickPhoto = async () => {
    const perm = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!perm.granted) {
      Alert.alert('Permission required', 'Allow photo access to change your picture.');
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      aspect: [1, 1],
      quality: 0.85,
    });
    if (!result.canceled && result.assets[0]) {
      setLocalPhoto(result.assets[0].uri);
      setIsEditing(true);
    }
  };

  const handleSave = async () => {
    if (!name.trim()) { Alert.alert('Required', 'Name cannot be empty.'); return; }
    setSaving(true);
    try {
      const resp = await updateProfileApi(session.token, {
        name:       name.trim(),
        username:   username.trim(),
        bio:        bio.trim(),
        phone:      phone.trim(),
        occupation: occupation.trim(),
        address:    address.trim(),
        photoUri:   localPhoto ?? undefined,
      });

      const u = (resp as any)?.data ?? (resp as any)?.user ?? resp;
      const snap = {
        name:       (u as any)?.name       ?? name.trim(),
        username:   (u as any)?.username   ?? username.trim(),
        bio:        (u as any)?.bio        ?? bio.trim(),
        phone:      (u as any)?.phone      ?? phone.trim(),
        occupation: (u as any)?.occupation ?? occupation.trim(),
        address:    (u as any)?.address    ?? address.trim(),
      };
      setName(snap.name);
      setUsername(snap.username);
      setBio(snap.bio);
      setPhone(snap.phone);
      setOccupation(snap.occupation);
      setAddress(snap.address);
      setOrig(snap);
      if ((u as any)?.photo_url) setPhotoUrl((u as any).photo_url);
      else if (localPhoto) setPhotoUrl(localPhoto);
      setLocalPhoto(null);
      setIsEditing(false);
      Alert.alert('Saved', (resp as any)?.message ?? 'Profile updated.');
    } catch (err: any) {
      Alert.alert('Error', err?.message ?? 'Could not save profile.');
    } finally {
      setSaving(false);
    }
  };

  const handleCancel = () => {
    setName(orig.name);
    setUsername(orig.username);
    setBio(orig.bio);
    setPhone(orig.phone);
    setOccupation(orig.occupation);
    setAddress(orig.address);
    setLocalPhoto(null);
    setIsEditing(false);
  };

  const handleChangePassword = async () => {
    if (!oldPwd.trim() || !newPwd.trim() || !confirmPwd.trim()) {
      Alert.alert('Required', 'Fill in all password fields.');
      return;
    }
    if (newPwd.length < 8) {
      Alert.alert('Too short', 'New password must be at least 8 characters.');
      return;
    }
    if (newPwd !== confirmPwd) {
      Alert.alert('Mismatch', 'New password and confirmation do not match.');
      return;
    }
    setPwdSaving(true);
    try {
      await changePasswordApi(session.token, {
        old_password:     oldPwd,
        password:         newPwd,
        password_confirm: confirmPwd,
      });
      setOldPwd(''); setNewPwd(''); setConfirmPwd(''); setShowPwd(false);
      Alert.alert(
        'Password changed',
        'Your password was changed. You will be logged out now.',
        [{ text: 'OK', onPress: onLogout }],
      );
    } catch (err: any) {
      Alert.alert('Error', err?.message ?? 'Could not change password. Check your current password.');
    } finally {
      setPwdSaving(false);
    }
  };

  const hue = name.split('').reduce((a, c) => a + c.charCodeAt(0), 0) % 360;
  const displayPhoto = localPhoto ?? photoUrl;

  if (loading) {
    return (
      <View style={s.loader}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <StatusBar barStyle="light-content" backgroundColor={colors.primaryDark} />
      <ScrollView style={s.root} contentContainerStyle={s.content} showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">

        {/* ── Hero ─────────────────────────────────────────── */}
        <View style={s.hero}>
          <Pressable style={s.avatarWrap} onPress={pickPhoto}>
            {displayPhoto ? (
              <Image source={{ uri: displayPhoto }} style={s.avatar} />
            ) : (
              <View style={[s.avatar, s.avatarFallback, { backgroundColor: `hsl(${hue},45%,52%)` }]}>
                <Text style={s.avatarInit}>{(name || 'U').charAt(0).toUpperCase()}</Text>
              </View>
            )}
            <View style={s.cameraBtn}>
              <Icon name="camera" size={16} color="#fff" />
            </View>
          </Pressable>
          <Text style={s.heroName}>{name || 'Your Name'}</Text>
          {username ? <Text style={s.heroUsername}>@{username}</Text> : null}
          {bio ? <Text style={s.heroBio} numberOfLines={2}>{bio}</Text> : null}

          {/* Stats row */}
          {(contactsCount != null || messagesSent != null) && (
            <View style={s.statsRow}>
              {contactsCount != null && (
                <View style={s.statItem}>
                  <Text style={s.statNum}>{contactsCount}</Text>
                  <Text style={s.statLabel}>Contacts</Text>
                </View>
              )}
              {contactsCount != null && messagesSent != null && <View style={s.statDivider} />}
              {messagesSent != null && (
                <View style={s.statItem}>
                  <Text style={s.statNum}>{messagesSent}</Text>
                  <Text style={s.statLabel}>Messages</Text>
                </View>
              )}
            </View>
          )}
        </View>

        {/* ── Profile Info ──────────────────────────────────── */}
        <View style={s.card}>
          <View style={s.cardHeader}>
            <Text style={s.cardTitle}>Profile Info</Text>
            {!isEditing && (
              <Pressable style={s.editBtn} onPress={() => setIsEditing(true)}>
                <Icon name="pencil" size={13} color={colors.primary} />
                <Text style={s.editBtnText}>Edit</Text>
              </Pressable>
            )}
          </View>

          <Field icon="person-outline"           label="Full Name"   value={name}       onChange={setName}       editable={isEditing} />
          <Field icon="at-outline"               label="Username"    value={username}   onChange={setUsername}   editable={isEditing} autoCapitalize="none" />
          <Field icon="call-outline"             label="Phone"       value={phone}      onChange={setPhone}      editable={isEditing} keyboardType="phone-pad" />
          <Field icon="information-circle-outline" label="Bio"       value={bio}        onChange={setBio}        editable={isEditing} multiline />
          <Field icon="briefcase-outline"        label="Occupation"  value={occupation} onChange={setOccupation} editable={isEditing} />
          <Field icon="location-outline"         label="Address"     value={address}    onChange={setAddress}    editable={isEditing} last />

          {isEditing && (
            <View style={s.saveBtns}>
              <Pressable style={s.cancelBtn} onPress={handleCancel}>
                <Text style={s.cancelText}>Cancel</Text>
              </Pressable>
              <Pressable style={[s.saveBtn, saving && { opacity: 0.6 }]} onPress={() => void handleSave()} disabled={saving}>
                {saving
                  ? <ActivityIndicator size="small" color="#fff" />
                  : <Text style={s.saveText}>Save changes</Text>}
              </Pressable>
            </View>
          )}
        </View>

        {/* ── Change Password ───────────────────────────────── */}
        <View style={s.card}>
          <Pressable style={s.cardHeader} onPress={() => setShowPwd(v => !v)}>
            <Text style={s.cardTitle}>Change Password</Text>
            <Icon name={showPwd ? 'chevron-up' : 'chevron-down'} size={18} color={colors.muted} />
          </Pressable>

          {showPwd && (
            <>
              <View style={s.pwdNote}>
                <Icon name="information-circle-outline" size={15} color={colors.muted} />
                <Text style={s.pwdNoteText}>You will be logged out after changing your password.</Text>
              </View>
              <Field icon="lock-closed-outline" label="Current Password"     value={oldPwd}     onChange={setOldPwd}     editable secureTextEntry />
              <Field icon="lock-open-outline"   label="New Password"         value={newPwd}     onChange={setNewPwd}     editable secureTextEntry />
              <Field icon="checkmark-circle-outline" label="Confirm Password" value={confirmPwd} onChange={setConfirmPwd} editable secureTextEntry last />
              <Pressable
                style={[s.saveBtn, pwdSaving && { opacity: 0.6 }, { marginTop: 12, marginBottom: 8 }]}
                onPress={() => void handleChangePassword()}
                disabled={pwdSaving}
              >
                {pwdSaving
                  ? <ActivityIndicator size="small" color="#fff" />
                  : <Text style={s.saveText}>Update password</Text>}
              </Pressable>
            </>
          )}
        </View>

        {/* ── Biometrics ────────────────────────────────────── */}
        {bioAvailable && (
          <View style={s.card}>
            <Pressable
              style={[s.cardHeader, { paddingVertical: 16 }]}
              onPress={async () => {
                if (bioEnabled) {
                  await biometricUtils.disableAppLock();
                  setBioEnabled(false);
                } else {
                  // Verify biometrics work before enabling
                  const result = await biometricUtils.authenticate('Confirm to enable app lock');
                  if (result.success) {
                    await biometricUtils.enableAppLock();
                    setBioEnabled(true);
                    Alert.alert('App lock enabled', 'You will be asked to verify your identity each time you open the app.');
                  } else if (result.error && result.error !== 'user_cancel') {
                    Alert.alert('Could not enable', result.error);
                  }
                }
              }}
            >
              <View style={s.bioRow}>
                <View style={s.bioIcon}><Icon name="finger-print-outline" size={20} color={colors.primary} /></View>
                <View>
                  <Text style={s.cardTitle}>Biometric login</Text>
                  <Text style={s.bioSub}>{bioEnabled ? 'Enabled' : 'Disabled'}</Text>
                </View>
              </View>
              <View style={[s.toggle, bioEnabled && s.toggleOn]}>
                <View style={[s.toggleDot, bioEnabled && s.toggleDotOn]} />
              </View>
            </Pressable>
          </View>
        )}

        {/* ── Logout ───────────────────────────────────────── */}
        <Pressable style={s.logoutBtn} onPress={onLogout}>
          <Icon name="log-out-outline" size={20} color={colors.danger} />
          <Text style={s.logoutText}>Log out</Text>
        </Pressable>

        <View style={{ height: 40 }} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

// ── Field component ──────────────────────────────────────────────────────────

function Field({
  icon, label, value, onChange, editable,
  multiline, autoCapitalize, keyboardType, secureTextEntry, last,
}: {
  icon: string; label: string; value: string; onChange: (v: string) => void;
  editable?: boolean; multiline?: boolean;
  autoCapitalize?: 'none' | 'sentences';
  keyboardType?: 'default' | 'phone-pad' | 'email-address';
  secureTextEntry?: boolean; last?: boolean;
}) {
  return (
    <View style={[f.row, !last && f.rowBorder]}>
      <View style={f.iconCol}>
        <Icon name={icon as any} size={18} color={editable ? colors.primary : colors.muted} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={f.label}>{label}</Text>
        <TextInput
          style={[f.input, multiline && f.inputMulti]}
          value={value}
          onChangeText={onChange}
          editable={editable}
          autoCapitalize={autoCapitalize ?? 'sentences'}
          keyboardType={keyboardType ?? 'default'}
          secureTextEntry={secureTextEntry}
          multiline={multiline}
          textAlignVertical={multiline ? 'top' : 'center'}
          placeholderTextColor={colors.muted}
          placeholder={editable ? `Enter ${label.toLowerCase()}` : '—'}
        />
      </View>
    </View>
  );
}

const f = StyleSheet.create({
  row:       { flexDirection: 'row', alignItems: 'flex-start', paddingVertical: 10 },
  rowBorder: { borderBottomWidth: 1, borderBottomColor: '#F5F5F5' },
  iconCol:   { width: 36, paddingTop: 14, alignItems: 'center' },
  label:     { color: colors.muted, fontSize: 11, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 2 },
  input:     { color: colors.text, fontSize: 15, paddingVertical: 4, minHeight: 28 },
  inputMulti:{ minHeight: 64 },
});

// ── Styles ───────────────────────────────────────────────────────────────────

const s = StyleSheet.create({
  loader:  { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#fff' },
  root:    { flex: 1, backgroundColor: '#F7F8FA' },
  content: { paddingBottom: 20 },

  // Hero
  hero: {
    backgroundColor: colors.primaryDark,
    alignItems: 'center',
    paddingTop: 52, paddingBottom: 28, paddingHorizontal: 24,
  },
  avatarWrap:  { position: 'relative', marginBottom: 14 },
  avatar:      { width: 100, height: 100, borderRadius: 50, borderWidth: 3, borderColor: 'rgba(255,255,255,0.4)' },
  avatarFallback: { alignItems: 'center', justifyContent: 'center' },
  avatarInit:  { color: '#fff', fontSize: 42, fontWeight: '800' },
  cameraBtn:   {
    position: 'absolute', right: 0, bottom: 0,
    width: 32, height: 32, borderRadius: 16,
    backgroundColor: colors.secondary,
    alignItems: 'center', justifyContent: 'center',
    borderWidth: 2, borderColor: colors.primaryDark,
  },
  heroName:     { color: '#fff', fontSize: 22, fontWeight: '800', marginBottom: 2 },
  heroUsername: { color: 'rgba(255,255,255,0.7)', fontSize: 14, marginBottom: 4 },
  heroBio:      { color: 'rgba(255,255,255,0.6)', fontSize: 13, textAlign: 'center', lineHeight: 18, marginBottom: 4 },

  statsRow:    { flexDirection: 'row', alignItems: 'center', marginTop: 16, backgroundColor: 'rgba(255,255,255,0.1)', borderRadius: 16, paddingVertical: 10, paddingHorizontal: 24 },
  statItem:    { alignItems: 'center', paddingHorizontal: 20 },
  statNum:     { color: '#fff', fontSize: 20, fontWeight: '800' },
  statLabel:   { color: 'rgba(255,255,255,0.65)', fontSize: 12, marginTop: 2 },
  statDivider: { width: 1, height: 32, backgroundColor: 'rgba(255,255,255,0.25)' },

  // Card
  card: {
    backgroundColor: '#fff', borderRadius: 16,
    marginHorizontal: 16, marginTop: 14,
    paddingHorizontal: 16, paddingBottom: 6,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05, shadowRadius: 4, elevation: 2,
  },
  cardHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingVertical: 14 },
  cardTitle:  { color: colors.text, fontSize: 15, fontWeight: '800' },
  editBtn:    { flexDirection: 'row', alignItems: 'center', gap: 4, paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12, backgroundColor: colors.primary + '15' },
  editBtnText:{ color: colors.primary, fontSize: 13, fontWeight: '700' },

  saveBtns:   { flexDirection: 'row', gap: 10, marginTop: 14, marginBottom: 8 },
  cancelBtn:  { flex: 1, height: 46, borderRadius: 12, borderWidth: 1, borderColor: colors.border, alignItems: 'center', justifyContent: 'center' },
  cancelText: { color: colors.muted, fontSize: 14, fontWeight: '700' },
  saveBtn:    { flex: 2, height: 46, borderRadius: 12, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  saveText:   { color: '#fff', fontSize: 14, fontWeight: '800' },

  pwdNote:     { flexDirection: 'row', alignItems: 'flex-start', gap: 6, backgroundColor: '#FFF8E6', borderRadius: 10, padding: 10, marginBottom: 8 },
  pwdNoteText: { flex: 1, color: colors.muted, fontSize: 12, lineHeight: 17 },

  // Biometrics
  bioRow:  { flexDirection: 'row', alignItems: 'center', gap: 12, flex: 1 },
  bioIcon: { width: 36, alignItems: 'center' },
  bioSub:  { color: colors.muted, fontSize: 12, marginTop: 1 },
  toggle:      { width: 46, height: 26, borderRadius: 13, backgroundColor: '#D5DADF', padding: 3, justifyContent: 'center' },
  toggleOn:    { backgroundColor: colors.primary },
  toggleDot:   { width: 20, height: 20, borderRadius: 10, backgroundColor: '#fff' },
  toggleDotOn: { transform: [{ translateX: 20 }] },

  // Logout
  logoutBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8,
    marginHorizontal: 16, marginTop: 14,
    paddingVertical: 16, borderRadius: 16, backgroundColor: '#FEF2F2',
  },
  logoutText: { color: colors.danger, fontSize: 15, fontWeight: '700' },
});
