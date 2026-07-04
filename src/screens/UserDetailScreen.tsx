import React, { useEffect, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  Alert,
  Image,
  Pressable,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { StackScreenProps } from '@react-navigation/stack';
import { colors } from '../theme/colors';
import { getContactProfileApi, blockUserApi, unblockUserApi, getUserStatusApi } from '../api/chatApi';
import type { AppStackParamList } from '../navigation/types';
import { useAuth } from '../context/AuthContext';

type Props = StackScreenProps<AppStackParamList, 'UserDetail'>;

export function UserDetailScreen({ route, navigation }: Props) {
  const { userId, name: initialName, avatarUrl: initialAvatar } = route.params;
  const { session } = useAuth();

  const [name, setName] = useState(initialName);
  const [photo, setPhoto] = useState<string | null>(initialAvatar ?? null);
  const [username, setUsername] = useState('');
  const [bio, setBio] = useState('');
  const [phone, setPhone] = useState('');
  const [isOnline, setIsOnline] = useState(false);
  const [lastSeen, setLastSeen] = useState('');
  const [isBlocked, setIsBlocked] = useState(false);
  const [blockLoading, setBlockLoading] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    void loadProfile();
  }, [userId]);

  const loadProfile = async () => {
    if (!session) return;
    setLoading(true);
    try {
      const [profileResp, statusResp] = await Promise.all([
        getContactProfileApi(session.token, userId),
        getUserStatusApi(session.token, userId),
      ]);
      const pd = (profileResp as any)?.data ?? (profileResp as any)?.user ?? profileResp;
      if (pd && (pd.name || pd.username)) {
        setName(pd.name ?? initialName);
        setPhoto(pd.photo_url ?? initialAvatar ?? null);
        setUsername(pd.username ?? '');
        setBio(pd.bio ?? '');
        setPhone(pd.phone ?? '');
        setIsBlocked(!!(pd.is_blocked));
      }
      const sd = (statusResp as any)?.data ?? statusResp;
      if (sd) {
        setIsOnline(sd.is_online ?? false);
        setLastSeen(sd.last_seen ?? '');
      }
    } catch { /* silent */ }
    finally { setLoading(false); }
  };

  const handleBlock = () => {
    Alert.alert('Block user', `Are you sure you want to block ${name}?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Block', style: 'destructive', onPress: async () => {
          if (!session) return;
          setBlockLoading(true);
          try {
            await blockUserApi(session.token, userId);
            setIsBlocked(true);
          } catch {
            Alert.alert('Error', 'Could not block user.');
          } finally { setBlockLoading(false); }
        },
      },
    ]);
  };

  const handleUnblock = () => {
    Alert.alert('Unblock user', `Unblock ${name}? They will be able to message you again.`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Unblock', onPress: async () => {
          if (!session) return;
          setBlockLoading(true);
          try {
            await unblockUserApi(session.token, userId);
            setIsBlocked(false);
          } catch {
            Alert.alert('Error', 'Could not unblock user.');
          } finally { setBlockLoading(false); }
        },
      },
    ]);
  };

  const formatLastSeen = (iso: string) => {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    return `Last seen ${d.toLocaleDateString([], { day: '2-digit', month: 'short' })} at ${d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
  };

  return (
    <View style={styles.root}>
      <StatusBar barStyle="light-content" backgroundColor={colors.primaryDark} />

      {/* Header */}
      <View style={styles.header}>
        <Pressable onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Icon name="arrow-back" size={22} color="#fff" />
        </Pressable>
        <Text style={styles.headerTitle}>Contact Info</Text>
        <View style={{ width: 40 }} />
      </View>

      {loading ? (
        <View style={styles.loader}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <ScrollView showsVerticalScrollIndicator={false}>

          {/* Avatar hero */}
          <View style={styles.hero}>
            {photo ? (
              <Image source={{ uri: photo }} style={styles.avatar} />
            ) : (
              <View style={[styles.avatar, styles.avatarFallback]}>
                <Text style={styles.avatarInitial}>{(name || '?').charAt(0).toUpperCase()}</Text>
              </View>
            )}
            <Text style={styles.heroName}>{name}</Text>
            <View style={styles.statusRow}>
              <View style={[styles.statusDot, isOnline && styles.statusDotOnline]} />
              <Text style={[styles.statusText, isOnline && styles.statusTextOnline]}>
                {isOnline ? 'Online' : formatLastSeen(lastSeen) || 'Offline'}
              </Text>
            </View>
          </View>

          {/* Quick actions */}
          <View style={styles.actionRow}>
            <ActionBtn icon="chatbubble" label="Message" color="#128C7E" onPress={() => navigation.goBack()} />
            <ActionBtn icon="call" label="Voice" color="#25D366" onPress={() => Alert.alert('Call', 'Voice call coming soon.')} />
            <ActionBtn icon="videocam" label="Video" color="#2196F3" onPress={() => Alert.alert('Call', 'Video call coming soon.')} />
          </View>

          {/* Info card */}
          <View style={styles.card}>
            {bio ? (
              <InfoRow icon="information-circle-outline" label="Bio" value={bio} />
            ) : null}
            {username ? (
              <InfoRow icon="at-outline" label="Username" value={`@${username}`} />
            ) : null}
            {phone ? (
              <InfoRow icon="call-outline" label="Phone" value={phone} last />
            ) : null}
            {!bio && !username && !phone && (
              <View style={styles.noInfo}>
                <Text style={styles.noInfoText}>No additional info available</Text>
              </View>
            )}
          </View>

          {/* Media, links and docs */}
          <Pressable
            style={styles.mediaRow}
            onPress={() => navigation.navigate('SharedMedia', { userId, name })}
            android_ripple={{ color: '#0001' }}
          >
            <View style={styles.mediaIconWrap}>
              <Icon name="image-outline" size={22} color={colors.primary} />
            </View>
            <Text style={styles.mediaLabel}>Media, links and docs</Text>
            <Icon name="chevron-forward" size={18} color={colors.muted} />
          </Pressable>

          {/* Block / Unblock */}
          {isBlocked ? (
            <Pressable style={styles.unblockBtn} onPress={handleUnblock} disabled={blockLoading}>
              {blockLoading
                ? <ActivityIndicator size="small" color={colors.primary} />
                : <>
                    <Icon name="checkmark-circle-outline" size={20} color={colors.primary} />
                    <Text style={styles.unblockText}>Unblock {name}</Text>
                  </>}
            </Pressable>
          ) : (
            <Pressable style={styles.blockBtn} onPress={handleBlock} disabled={blockLoading}>
              {blockLoading
                ? <ActivityIndicator size="small" color={colors.danger} />
                : <>
                    <Icon name="ban-outline" size={20} color={colors.danger} />
                    <Text style={styles.blockText}>Block {name}</Text>
                  </>}
            </Pressable>
          )}

          <View style={{ height: 40 }} />
        </ScrollView>
      )}
    </View>
  );
}

function ActionBtn({ icon, label, color, onPress }: { icon: string; label: string; color: string; onPress: () => void }) {
  return (
    <Pressable style={styles.actionBtn} onPress={onPress}>
      <View style={[styles.actionIcon, { backgroundColor: color + '18' }]}>
        <Icon name={icon as any} size={24} color={color} />
      </View>
      <Text style={styles.actionLabel}>{label}</Text>
    </Pressable>
  );
}

function InfoRow({ icon, label, value, last }: { icon: string; label: string; value: string; last?: boolean }) {
  return (
    <View style={[styles.infoRow, !last && styles.infoRowBorder]}>
      <Icon name={icon as any} size={20} color={colors.muted} style={{ marginRight: 14 }} />
      <View style={{ flex: 1 }}>
        <Text style={styles.infoLabel}>{label}</Text>
        <Text style={styles.infoValue}>{value}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#F7F8FA' },
  header: {
    backgroundColor: colors.primaryDark,
    flexDirection: 'row', alignItems: 'center',
    paddingTop: 48, paddingBottom: 12, paddingHorizontal: 8,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  headerTitle: { flex: 1, color: '#fff', fontSize: 18, fontWeight: '800', textAlign: 'center' },
  loader: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  hero: {
    backgroundColor: colors.primaryDark,
    alignItems: 'center',
    paddingBottom: 28, paddingHorizontal: 24,
  },
  avatar: { width: 100, height: 100, borderRadius: 50, borderWidth: 3, borderColor: 'rgba(255,255,255,0.4)', marginBottom: 12 },
  avatarFallback: { backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  avatarInitial: { color: '#fff', fontSize: 40, fontWeight: '800' },
  heroName: { color: '#fff', fontSize: 22, fontWeight: '800', marginBottom: 6 },
  statusRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  statusDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: '#aaa' },
  statusDotOnline: { backgroundColor: colors.secondary },
  statusText: { color: 'rgba(255,255,255,0.6)', fontSize: 13 },
  statusTextOnline: { color: colors.secondary },
  actionRow: {
    flexDirection: 'row', justifyContent: 'space-evenly',
    backgroundColor: '#fff',
    paddingVertical: 18,
    marginTop: 8, marginHorizontal: 16, borderRadius: 16,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.05, shadowRadius: 4, elevation: 2,
  },
  actionBtn: { alignItems: 'center', gap: 6 },
  actionIcon: { width: 52, height: 52, borderRadius: 26, alignItems: 'center', justifyContent: 'center' },
  actionLabel: { color: colors.muted, fontSize: 12, fontWeight: '700' },
  card: {
    backgroundColor: '#fff', borderRadius: 16,
    marginHorizontal: 16, marginTop: 12,
    paddingHorizontal: 16,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.05, shadowRadius: 4, elevation: 2,
  },
  infoRow: { flexDirection: 'row', alignItems: 'center', paddingVertical: 14 },
  infoRowBorder: { borderBottomWidth: 1, borderBottomColor: '#F5F5F5' },
  infoLabel: { color: colors.muted, fontSize: 11, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 2 },
  infoValue: { color: colors.text, fontSize: 15 },
  noInfo: { paddingVertical: 20, alignItems: 'center' },
  noInfoText: { color: colors.muted, fontSize: 13 },
  blockBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8,
    marginHorizontal: 16, marginTop: 12,
    paddingVertical: 16, borderRadius: 16,
    backgroundColor: '#FEF2F2',
  },
  blockText: { color: colors.danger, fontSize: 15, fontWeight: '700' },
  unblockBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8,
    marginHorizontal: 16, marginTop: 12,
    paddingVertical: 16, borderRadius: 16,
    backgroundColor: '#EEF8F4',
  },
  unblockText: { color: colors.primary, fontSize: 15, fontWeight: '700' },
  mediaRow: {
    flexDirection: 'row', alignItems: 'center', gap: 12,
    backgroundColor: '#fff', borderRadius: 16,
    marginHorizontal: 16, marginTop: 12,
    paddingHorizontal: 16, paddingVertical: 16,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.05, shadowRadius: 4, elevation: 2,
  },
  mediaIconWrap: {
    width: 40, height: 40, borderRadius: 20,
    backgroundColor: colors.primary + '15',
    alignItems: 'center', justifyContent: 'center',
  },
  mediaLabel: { flex: 1, color: colors.text, fontSize: 15, fontWeight: '600' },
});
