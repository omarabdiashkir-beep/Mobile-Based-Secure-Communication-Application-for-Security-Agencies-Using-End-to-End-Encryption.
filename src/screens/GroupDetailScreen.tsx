import React, { useCallback, useEffect, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Image,
  Modal,
  Pressable,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import type { StackScreenProps } from '@react-navigation/stack';
import { colors } from '../theme/colors';
import {
  getGroupApi,
  addGroupMemberApi,
  removeGroupMemberApi,
  leaveGroupApi,
  listContactsApi,
  getGroupMessagesApi,
  getGroupMessageSeenByApi,
  type ApiGroupMember,
  type ApiContact,
} from '../api/chatApi';
import type { AppStackParamList, AuthSession } from '../navigation/types';

type Props = StackScreenProps<AppStackParamList, 'GroupDetail'> & { session: AuthSession };

export function GroupDetailScreen({ route, navigation, session }: Props) {
  const { groupId, name: initialName, avatarUrl } = route.params;
  const [groupName, setGroupName] = useState(initialName);
  const [description, setDescription] = useState('');
  const [members, setMembers] = useState<ApiGroupMember[]>([]);
  const [loading, setLoading] = useState(true);
  const [addModalVisible, setAddModalVisible] = useState(false);
  const [contacts, setContacts] = useState<ApiContact[]>([]);
  const [contactsLoading, setContactsLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [lastMsgSeenBy, setLastMsgSeenBy] = useState<{ id: string; name: string; photo_url?: string }[]>([]);
  const [totalMembers, setTotalMembers] = useState(0);

  const hue = initialName.split('').reduce((a, c) => a + c.charCodeAt(0), 0) % 360;
  const initial = initialName.charAt(0).toUpperCase();

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const resp = await getGroupApi(session.token, groupId);
      if (resp?.data) {
        const d = resp.data;
        setGroupName((d as any).name ?? initialName);
        setDescription((d as any).description ?? '');
        const mbs: ApiGroupMember[] = (d as any).members ?? [];
        setMembers(mbs);
        setTotalMembers(mbs.length);
      }
    } catch { /* silent */ }
    finally { setLoading(false); }

    // Load last message's seen-by list
    try {
      const msgsResp = await getGroupMessagesApi(session.token, groupId);
      const msgs = msgsResp?.data?.messages ?? [];
      if (msgs.length > 0) {
        const lastId = msgs[msgs.length - 1].id;
        const seenResp = await getGroupMessageSeenByApi(session.token, groupId, lastId);
        const seenList = (seenResp as any)?.data?.seen_by ?? (seenResp as any)?.data ?? [];
        setLastMsgSeenBy(Array.isArray(seenList) ? seenList : []);
      }
    } catch { /* silent */ }
  }, [groupId, session.token, initialName]);

  useEffect(() => { void load(); }, [load]);

  const openAddModal = async () => {
    setAddModalVisible(true);
    setContactsLoading(true);
    try {
      const resp = await listContactsApi(session.token);
      const r = resp as any;
      const all: ApiContact[] = r?.data?.contacts ?? r?.data ?? r?.contacts ?? (Array.isArray(r) ? r : []);
      const memberIds = new Set(members.map((m) => m.id));
      setContacts(all.filter((c) => !memberIds.has(c.id)));
    } catch { /* silent */ }
    finally { setContactsLoading(false); }
  };

  const handleAdd = async (userId: string) => {
    try {
      await addGroupMemberApi(session.token, groupId, userId);
      setAddModalVisible(false);
      await load();
    } catch {
      Alert.alert('Error', 'Could not add member.');
    }
  };

  const handleRemove = (member: ApiGroupMember) => {
    Alert.alert('Remove member', `Remove ${member.name ?? 'this member'}?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Remove', style: 'destructive', onPress: async () => {
          try {
            await removeGroupMemberApi(session.token, groupId, member.id);
            await load();
          } catch { Alert.alert('Error', 'Could not remove member.'); }
        },
      },
    ]);
  };

  const handleLeave = () => {
    Alert.alert('Leave group', 'Are you sure you want to leave this group?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Leave', style: 'destructive', onPress: async () => {
          try {
            await leaveGroupApi(session.token, groupId);
            navigation.navigate('HomeTabs');
          } catch { Alert.alert('Error', 'Could not leave group.'); }
        },
      },
    ]);
  };

  const filtered = contacts.filter((c) => {
    const q = searchQuery.toLowerCase();
    return !q || c.name?.toLowerCase().includes(q) || c.username?.toLowerCase().includes(q);
  });

  return (
    <View style={s.root}>
      <StatusBar barStyle="light-content" backgroundColor={colors.primaryDark} />

      <View style={s.header}>
        <Pressable onPress={() => navigation.goBack()} style={s.backBtn}>
          <Icon name="arrow-back" size={22} color="#fff" />
        </Pressable>
        <Text style={s.headerTitle}>Group Info</Text>
        <View style={{ width: 40 }} />
      </View>

      {loading ? (
        <View style={s.loader}><ActivityIndicator size="large" color={colors.primary} /></View>
      ) : (
        <ScrollView showsVerticalScrollIndicator={false}>
          {/* Hero */}
          <View style={s.hero}>
            {avatarUrl ? (
              <Image source={{ uri: avatarUrl }} style={s.avatar} />
            ) : (
              <View style={[s.avatar, s.avatarFallback, { backgroundColor: `hsl(${hue},45%,52%)` }]}>
                <Text style={s.avatarInit}>{initial}</Text>
              </View>
            )}
            <Text style={s.heroName}>{groupName}</Text>
            {description ? <Text style={s.heroDesc}>{description}</Text> : null}
            <Text style={s.heroSub}>{members.length} members</Text>
          </View>

          {/* Quick actions */}
          <View style={s.actionRow}>
            <ActionBtn icon="chatbubble" label="Message" color={colors.primary} onPress={() => navigation.goBack()} />
            <ActionBtn icon="person-add" label="Add" color="#FF9800" onPress={() => void openAddModal()} />
            <ActionBtn icon="exit-outline" label="Leave" color={colors.danger} onPress={handleLeave} />
          </View>

          {/* Members list */}
          <View style={s.section}>
            <Text style={s.sectionTitle}>Members</Text>
            <View style={s.membersList}>
              {members.map((m, i) => {
                const mhue = (m.name ?? '').split('').reduce((a, c) => a + c.charCodeAt(0), 0) % 360;
                const isMe = m.id === session.userId;
                return (
                  <Pressable
                    key={m.id ?? `m-${i}`}
                    style={[s.memberRow, i < members.length - 1 && s.memberRowBorder]}
                    onLongPress={() => !isMe && handleRemove(m)}
                  >
                    {m.photo_url ? (
                      <Image source={{ uri: m.photo_url }} style={s.memberAvatar} />
                    ) : (
                      <View style={[s.memberAvatar, s.memberAvatarFallback, { backgroundColor: `hsl(${mhue},45%,52%)` }]}>
                        <Text style={s.memberAvatarInit}>{(m.name ?? '?').charAt(0).toUpperCase()}</Text>
                      </View>
                    )}
                    <View style={s.memberInfo}>
                      <Text style={s.memberName}>{m.name ?? m.username ?? 'Unknown'}{isMe ? ' (You)' : ''}</Text>
                      {m.username ? <Text style={s.memberUsername}>@{m.username}</Text> : null}
                    </View>
                    {m.role === 'admin' && (
                      <View style={s.adminChip}><Text style={s.adminText}>Admin</Text></View>
                    )}
                    {(m as any).is_online && <View style={s.onlineDot} />}
                  </Pressable>
                );
              })}
            </View>
          </View>

          {/* Read receipts for last message */}
          {lastMsgSeenBy.length > 0 && (
            <View style={s.section}>
              <Text style={s.sectionTitle}>
                Read by ({lastMsgSeenBy.length}/{totalMembers})
              </Text>
              <View style={s.membersList}>
                {lastMsgSeenBy.map((u, i) => {
                  const uh = (u.name ?? '').split('').reduce((a, c) => a + c.charCodeAt(0), 0) % 360;
                  return (
                    <View key={u.id ?? `seen-${i}`} style={[s.memberRow, i < lastMsgSeenBy.length - 1 && s.memberRowBorder]}>
                      {u.photo_url ? (
                        <Image source={{ uri: u.photo_url }} style={s.memberAvatar} />
                      ) : (
                        <View style={[s.memberAvatar, s.memberAvatarFallback, { backgroundColor: `hsl(${uh},45%,52%)` }]}>
                          <Text style={s.memberAvatarInit}>{(u.name ?? '?').charAt(0).toUpperCase()}</Text>
                        </View>
                      )}
                      <Text style={[s.memberName, { marginLeft: 12, flex: 1 }]}>{u.name ?? 'Unknown'}</Text>
                      <Icon name="checkmark-done" size={16} color="#1A6FE8" />
                    </View>
                  );
                })}
              </View>
            </View>
          )}

          <Pressable style={s.leaveBtn} onPress={handleLeave}>
            <Icon name="exit-outline" size={20} color={colors.danger} />
            <Text style={s.leaveBtnText}>Leave Group</Text>
          </Pressable>

          <View style={{ height: 40 }} />
        </ScrollView>
      )}

      {/* Add member modal */}
      <Modal visible={addModalVisible} animationType="slide" transparent onRequestClose={() => setAddModalVisible(false)}>
        <View style={s.modalOverlay}>
          <View style={s.modal}>
            <View style={s.modalHeader}>
              <Text style={s.modalTitle}>Add Member</Text>
              <Pressable onPress={() => setAddModalVisible(false)}>
                <Icon name="close" size={24} color={colors.text} />
              </Pressable>
            </View>
            <View style={s.modalSearch}>
              <Icon name="search" size={18} color={colors.muted} />
              <TextInput
                style={s.modalSearchInput}
                placeholder="Search contacts…"
                placeholderTextColor={colors.muted}
                value={searchQuery}
                onChangeText={setSearchQuery}
                autoFocus
              />
            </View>
            {contactsLoading ? (
              <ActivityIndicator style={{ marginTop: 24 }} color={colors.primary} />
            ) : (
              <FlatList
                data={filtered}
                keyExtractor={(item) => item.id}
                renderItem={({ item }) => {
                  const ch = (item.name ?? '').split('').reduce((a, c) => a + c.charCodeAt(0), 0) % 360;
                  return (
                    <Pressable style={s.contactRow} onPress={() => void handleAdd(item.id)}>
                      {item.photo_url ? (
                        <Image source={{ uri: item.photo_url }} style={s.contactAvatar} />
                      ) : (
                        <View style={[s.contactAvatar, s.memberAvatarFallback, { backgroundColor: `hsl(${ch},45%,52%)` }]}>
                          <Text style={s.memberAvatarInit}>{(item.name ?? '?').charAt(0).toUpperCase()}</Text>
                        </View>
                      )}
                      <View style={{ flex: 1, marginLeft: 12 }}>
                        <Text style={s.memberName}>{item.name ?? item.username ?? 'Unknown'}</Text>
                        {item.username ? <Text style={s.memberUsername}>@{item.username}</Text> : null}
                      </View>
                      <Icon name="add-circle-outline" size={24} color={colors.primary} />
                    </Pressable>
                  );
                }}
                ListEmptyComponent={<View style={{ padding: 24 }}><Text style={{ color: colors.muted, textAlign: 'center' }}>No contacts available to add</Text></View>}
              />
            )}
          </View>
        </View>
      </Modal>
    </View>
  );
}

function ActionBtn({ icon, label, color, onPress }: { icon: string; label: string; color: string; onPress: () => void }) {
  return (
    <Pressable style={s.actionBtn} onPress={onPress}>
      <View style={[s.actionIcon, { backgroundColor: color + '18' }]}>
        <Icon name={icon as any} size={24} color={color} />
      </View>
      <Text style={s.actionLabel}>{label}</Text>
    </Pressable>
  );
}

const s = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#F7F8FA' },
  header: {
    backgroundColor: colors.primary,
    flexDirection: 'row', alignItems: 'center',
    paddingTop: 48, paddingBottom: 12, paddingHorizontal: 8,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  headerTitle: { flex: 1, color: '#fff', fontSize: 18, fontWeight: '800', textAlign: 'center' },
  loader: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  hero: { backgroundColor: colors.primary, alignItems: 'center', paddingBottom: 28, paddingHorizontal: 24 },
  avatar: { width: 100, height: 100, borderRadius: 50, borderWidth: 3, borderColor: 'rgba(255,255,255,0.4)', marginBottom: 12 },
  avatarFallback: { alignItems: 'center', justifyContent: 'center' },
  avatarInit: { color: '#fff', fontSize: 38, fontWeight: '800' },
  heroName: { color: '#fff', fontSize: 22, fontWeight: '800', marginBottom: 4 },
  heroDesc: { color: 'rgba(255,255,255,0.7)', fontSize: 14, textAlign: 'center', marginBottom: 4 },
  heroSub: { color: '#9DCFCA', fontSize: 13, fontWeight: '600' },
  actionRow: {
    flexDirection: 'row', justifyContent: 'space-evenly',
    backgroundColor: '#fff',
    paddingVertical: 18, marginTop: 8, marginHorizontal: 16, borderRadius: 16,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.05, shadowRadius: 4, elevation: 2,
  },
  actionBtn: { alignItems: 'center', gap: 6 },
  actionIcon: { width: 52, height: 52, borderRadius: 26, alignItems: 'center', justifyContent: 'center' },
  actionLabel: { color: colors.muted, fontSize: 12, fontWeight: '700' },
  section: { paddingHorizontal: 16, marginTop: 20 },
  sectionTitle: { color: colors.muted, fontSize: 12, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 10 },
  membersList: { backgroundColor: '#fff', borderRadius: 16, overflow: 'hidden', shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.04, shadowRadius: 4, elevation: 1 },
  memberRow: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 12 },
  memberRowBorder: { borderBottomWidth: 1, borderBottomColor: '#F5F5F5' },
  memberAvatar: { width: 44, height: 44, borderRadius: 22 },
  memberAvatarFallback: { alignItems: 'center', justifyContent: 'center' },
  memberAvatarInit: { color: '#fff', fontSize: 18, fontWeight: '700' },
  memberInfo: { flex: 1, marginLeft: 12 },
  memberName: { color: colors.text, fontSize: 15, fontWeight: '700' },
  memberUsername: { color: colors.muted, fontSize: 12, marginTop: 1 },
  adminChip: { backgroundColor: '#EEF4FF', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 10, marginRight: 8 },
  adminText: { color: '#2196F3', fontSize: 11, fontWeight: '700' },
  onlineDot: { width: 10, height: 10, borderRadius: 5, backgroundColor: colors.secondary, borderWidth: 2, borderColor: '#fff' },
  leaveBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8,
    marginHorizontal: 16, marginTop: 20, paddingVertical: 16, borderRadius: 16, backgroundColor: '#FEF2F2',
  },
  leaveBtnText: { color: colors.danger, fontSize: 15, fontWeight: '700' },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modal: { backgroundColor: '#fff', borderTopLeftRadius: 24, borderTopRightRadius: 24, height: '75%', paddingTop: 20 },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 20, marginBottom: 16 },
  modalTitle: { color: colors.text, fontSize: 20, fontWeight: '800' },
  modalSearch: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: '#F5F5F5', borderRadius: 12,
    paddingHorizontal: 12, height: 44,
    marginHorizontal: 16, marginBottom: 12,
  },
  modalSearchInput: { flex: 1, marginLeft: 8, fontSize: 15, color: colors.text },
  contactRow: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#F5F5F5' },
  contactAvatar: { width: 44, height: 44, borderRadius: 22 },
});
