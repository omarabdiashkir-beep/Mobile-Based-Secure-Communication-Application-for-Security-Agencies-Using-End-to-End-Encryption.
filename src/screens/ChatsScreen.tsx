import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  FlatList,
  Image,
  Modal,
  Pressable,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { type NavigationProp, type ParamListBase, useFocusEffect } from '@react-navigation/native';
import { colors } from '../theme/colors';
import { getInboxApi, getMeApi, type ApiConversation } from '../api/chatApi';
import { API_BASE_URL } from '../api/config';
import type { AuthSession, ConversationItem } from '../navigation/types';
import { chatRealtime } from '../api/chatRealtime';

type Props = {
  session: AuthSession;
  navigation: NavigationProp<ParamListBase>;
  onOpenChat: (conversation: ConversationItem) => void;
};

type ChatFilter = 'all' | 'unread';

function formatTime(iso?: string) {
  if (!iso) return '';
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return '';
  const now = new Date();
  const diffDays = Math.floor((now.getTime() - date.getTime()) / 86400000);
  if (diffDays === 0) return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
  if (diffDays === 1) return 'Yesterday';
  if (diffDays < 7) return date.toLocaleDateString([], { weekday: 'short' });
  return date.toLocaleDateString([], { day: '2-digit', month: '2-digit' });
}

function resolvePhoto(raw?: string | null): string | null {
  if (!raw) return null;
  if (raw.startsWith('http://') || raw.startsWith('https://')) return raw;
  return `${API_BASE_URL}/${raw.replace(/^\//, '')}`;
}

function Avatar({ url, name, size = 52 }: { url?: string | null; name: string; size?: number }) {
  const initials = name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();
  const hue = name.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0) % 360;
  const resolved = resolvePhoto(url);
  if (resolved) return <Image source={{ uri: resolved }} style={{ width: size, height: size, borderRadius: size / 2 }} />;
  return (
    <View style={{ width: size, height: size, borderRadius: size / 2, backgroundColor: `hsl(${hue},50%,55%)`, alignItems: 'center', justifyContent: 'center' }}>
      <Text style={{ color: '#fff', fontSize: size * 0.36, fontWeight: '700' }}>{initials}</Text>
    </View>
  );
}

function toConversationItem(item: ApiConversation): ConversationItem {
  return {
    id: item.contact_id,
    name: item.contact_name,
    avatarUrl: item.contact_photo_url ?? resolvePhoto(item.contact_photo) ?? undefined,
    isGroup: false,
    preview: item.content || 'No messages yet',
    time: formatTime(item.created_at),
    unread: Number(item.unread_count) || 0,
    online: !!item.contact_is_online,
  };
}

export function ChatsScreen({ session, onOpenChat, navigation }: Props) {
  const [search, setSearch] = useState('');
  const [filter, setFilter] = useState<ChatFilter>('all');
  const [apiChats, setApiChats] = useState<ApiConversation[]>([]);
  const [menuVisible, setMenuVisible] = useState(false);
  const [myPhotoUrl, setMyPhotoUrl] = useState<string | null>(null);
  const [myName, setMyName] = useState(session.userName || '');

  const loadChats = useCallback(async () => {
    if (!session.token) return;
    try {
      const resp = await getInboxApi(session.token);
      const list = (resp as any)?.data ?? (resp as any)?.conversations ?? resp;
      const arr = Array.isArray(list) ? list : [];
      setApiChats(arr);
    } catch { /* silent */ }
  }, [session.token]);

  const loadMe = useCallback(async () => {
    try {
      const resp = await getMeApi(session.token);
      const d = (resp as any)?.data ?? (resp as any)?.user ?? resp;
      if (d && (d.name || d.photo_url)) {
        setMyPhotoUrl(d.photo_url ?? null);
        setMyName(d.name || session.userName);
      }
    } catch { /* silent */ }
  }, [session.token, session.userName]);

  useFocusEffect(useCallback(() => {
    void loadChats();
    void loadMe();
  }, [loadChats, loadMe]));

  // Silent 1-second background poll — no spinner, invisible to user
  const silentRefresh = useCallback(async () => {
    if (!session.token) return;
    try {
      const resp = await getInboxApi(session.token);
      const list = (resp as any)?.data ?? (resp as any)?.conversations ?? resp;
      if (Array.isArray(list)) setApiChats(list);
    } catch { /* silent */ }
  }, [session.token]);

  useEffect(() => {
    const pollInterval = setInterval(() => { void silentRefresh(); }, 1_000);

    // Incoming message → update preview + unread instantly, bring to top
    const unSubMessage = chatRealtime.onMessage((msg) => {
      setApiChats((prev) => {
        const isIncoming = msg.receiverId !== msg.senderId;
        const matchId = msg.groupId ?? msg.senderId;
        const idx = prev.findIndex((c) => c.contact_id === matchId);

        let next: ApiConversation[];
        if (idx >= 0) {
          const updated = {
            ...prev[idx],
            content: msg.message || prev[idx].content,
            created_at: msg.timestamp,
            unread_count: isIncoming ? (prev[idx].unread_count || 0) + 1 : prev[idx].unread_count,
          };
          next = [updated, ...prev.filter((_, i) => i !== idx)];
        } else {
          // Unknown conversation — fetch to populate it
          void loadChats();
          return prev;
        }
        return next;
      });
    });

    // Messages marked read → clear unread badge instantly
    const unSubRead = chatRealtime.onMessagesRead((ev) => {
      setApiChats((prev) =>
        prev.map((c) => (c.contact_id === ev.chatId ? { ...c, unread_count: 0 } : c))
      );
    });

    // Presence change → refresh list (cheap, infrequent)
    const unSubPresence = chatRealtime.onPresence(() => void loadChats());

    return () => { clearInterval(pollInterval); unSubMessage(); unSubRead(); unSubPresence(); };
  }, [loadChats, silentRefresh]);

  const filteredChats = useMemo(() => {
    const query = search.trim().toLowerCase();
    return apiChats.filter((chat) => {
      const matchSearch = !query || chat.contact_name.toLowerCase().includes(query);
      const matchFilter = filter === 'all' ? true : chat.unread_count > 0;
      return matchSearch && matchFilter;
    });
  }, [apiChats, filter, search]);

  return (
    <View style={s.root}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />

      {/* Header */}
      <View style={s.header}>
        <Pressable onPress={() => navigation.navigate('Profile')} style={s.myAvatarBtn}>
          <Avatar url={myPhotoUrl} name={myName} size={36} />
        </Pressable>
        <Text style={s.headerTitle}>Chats</Text>
        <View style={s.headerActions}>
          <Pressable style={s.headerBtn} onPress={() => setMenuVisible(true)}>
            <Icon name="ellipsis-vertical" size={22} color={colors.text} />
          </Pressable>
        </View>
      </View>

      {/* Search bar */}
      <View style={s.searchWrap}>
        <Icon name="search" size={17} color={colors.muted} />
        <TextInput
          style={s.searchInput}
          placeholder="Search…"
          placeholderTextColor={colors.muted}
          value={search}
          onChangeText={setSearch}
        />
        {search.length > 0 && (
          <Pressable onPress={() => setSearch('')}>
            <Icon name="close-circle" size={17} color={colors.muted} />
          </Pressable>
        )}
      </View>

      {/* Filter chips */}
      <View style={s.filterRow}>
        {(['all', 'unread'] as ChatFilter[]).map((f) => (
          <Pressable
            key={f}
            style={[s.chip, filter === f && s.chipActive]}
            onPress={() => setFilter(f)}
          >
            <Text style={[s.chipText, filter === f && s.chipTextActive]}>
              {f === 'all' ? 'All' : 'Unread'}
            </Text>
          </Pressable>
        ))}
      </View>

      {/* Chat list */}
      <FlatList
        data={filteredChats}
        keyExtractor={(item) => item.contact_id}
        contentContainerStyle={filteredChats.length === 0 ? s.emptyContainer : undefined}
        renderItem={({ item }) => {
          const isOnline = !!item.contact_is_online;
          const lastSeenText = item.contact_last_seen_text ?? '';
          const isMine = item.sender_id !== undefined && String(item.sender_id) === session.userId;
          const status = item.last_message_status ?? null;

          return (
            <Pressable style={s.row} onPress={() => onOpenChat(toConversationItem(item))}>
              {/* Avatar + online dot */}
              <View style={s.avatarWrap}>
                <Avatar url={item.contact_photo_url ?? resolvePhoto(item.contact_photo)} name={item.contact_name} size={50} />
                {isOnline && <View style={s.onlineDot} />}
              </View>

              <View style={s.rowBody}>
                <View style={s.rowTop}>
                  <Text style={s.rowName} numberOfLines={1}>{item.contact_name}</Text>
                  <Text style={[s.rowTime, Number(item.unread_count) > 0 && s.rowTimeUnread]}>
                    {formatTime(item.created_at)}
                  </Text>
                </View>

                <View style={s.rowBottom}>
                  {/* Delivery ticks for my own last message */}
                  {isMine && status ? (
                    <Icon
                      name={status === 'sent' ? 'checkmark' : 'checkmark-done'}
                      size={14}
                      color={status === 'read' ? '#1A6FE8' : '#94A3B8'}
                      style={{ marginRight: 4 }}
                    />
                  ) : null}

                  <Text style={[s.rowPreview, Number(item.unread_count) > 0 && s.rowPreviewUnread]} numberOfLines={1}>
                    {item.content || 'No messages yet'}
                  </Text>

                  {Number(item.unread_count) > 0 ? (
                    <View style={s.badge}>
                      <Text style={s.badgeText}>{Number(item.unread_count) > 99 ? '99+' : item.unread_count}</Text>
                    </View>
                  ) : null}
                </View>

                {/* Last seen text under preview */}
                {!isOnline && lastSeenText ? (
                  <Text style={s.lastSeen} numberOfLines={1}>{lastSeenText}</Text>
                ) : null}
              </View>
            </Pressable>
          );
        }}
        ListEmptyComponent={
          <View style={s.empty}>
            <View style={s.emptyIcon}>
              <Icon name="chatbubbles-outline" size={52} color={colors.muted} />
            </View>
            <Text style={s.emptyTitle}>No chats yet</Text>
            <Text style={s.emptyText}>Tap + to start a conversation</Text>
          </View>
        }
        ItemSeparatorComponent={() => <View style={s.separator} />}
      />

      {/* Orange FAB */}
      <Pressable style={s.fab} onPress={() => navigation.navigate('NewChat')}>
        <Icon name="add" size={28} color="#fff" />
      </Pressable>

      {/* Dropdown menu */}
      <Modal visible={menuVisible} transparent animationType="fade" onRequestClose={() => setMenuVisible(false)}>
        <Pressable style={s.overlay} onPress={() => setMenuVisible(false)}>
          <View style={s.dropdown}>
            {[
              { label: 'New group', icon: 'people-outline', route: 'CreateGroup' },
              { label: 'Profile', icon: 'person-outline', route: 'Profile' },
            ].map(({ label, icon, route }) => (
              <Pressable key={route} style={s.dropItem} onPress={() => { setMenuVisible(false); navigation.navigate(route); }}>
                <Icon name={icon as any} size={18} color={colors.text} style={{ marginRight: 12 }} />
                <Text style={s.dropLabel}>{label}</Text>
              </Pressable>
            ))}
          </View>
        </Pressable>
      </Modal>
    </View>
  );
}

const s = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#fff' },

  header: {
    backgroundColor: '#fff',
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 52,
    paddingBottom: 8,
    paddingHorizontal: 16,
    gap: 12,
  },
  myAvatarBtn: { borderRadius: 20, overflow: 'hidden' },
  headerTitle: { flex: 1, color: colors.text, fontSize: 24, fontWeight: '800' },
  headerActions: { flexDirection: 'row', gap: 4 },
  headerBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },

  searchWrap: {
    flexDirection: 'row', alignItems: 'center', gap: 10,
    backgroundColor: '#F0F2F5',
    marginHorizontal: 16, marginBottom: 10,
    borderRadius: 24, paddingHorizontal: 14, height: 42,
  },
  searchInput: { flex: 1, color: colors.text, fontSize: 14 },

  filterRow: {
    flexDirection: 'row', gap: 8,
    paddingHorizontal: 16, paddingBottom: 10,
  },
  chip: {
    paddingHorizontal: 18, paddingVertical: 6,
    borderRadius: 20, backgroundColor: '#F0F2F5',
  },
  chipActive: { backgroundColor: colors.primaryLight, borderWidth: 1, borderColor: colors.primary },
  chipText: { color: colors.muted, fontSize: 13, fontWeight: '600' },
  chipTextActive: { color: colors.primary, fontWeight: '700' },

  row: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 11 },
  avatarWrap: { marginRight: 14, position: 'relative' },
  onlineDot: {
    position: 'absolute', bottom: 1, right: 1,
    width: 12, height: 12, borderRadius: 6,
    backgroundColor: '#22C55E',
    borderWidth: 2, borderColor: '#fff',
  },
  rowBody: { flex: 1 },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 3 },
  rowName: { flex: 1, color: colors.text, fontSize: 16, fontWeight: '700', marginRight: 8 },
  rowTime: { color: colors.muted, fontSize: 12 },
  rowTimeUnread: { color: colors.accent, fontWeight: '700' },
  rowBottom: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  rowPreview: { flex: 1, color: colors.muted, fontSize: 13, marginRight: 8 },
  rowPreviewUnread: { color: colors.text, fontWeight: '600' },
  lastSeen: { color: colors.muted, fontSize: 11, marginTop: 2 },
  badge: {
    minWidth: 20, height: 20, borderRadius: 10,
    backgroundColor: colors.accent,
    alignItems: 'center', justifyContent: 'center',
    paddingHorizontal: 5,
  },
  badgeText: { color: '#fff', fontSize: 11, fontWeight: '800' },
  separator: { height: 1, backgroundColor: '#F5F5F5', marginLeft: 80 },

  emptyContainer: { flex: 1 },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingTop: 80, paddingHorizontal: 32, gap: 10 },
  emptyIcon: {
    width: 90, height: 90, borderRadius: 45,
    backgroundColor: '#F0F2F5',
    alignItems: 'center', justifyContent: 'center', marginBottom: 8,
  },
  emptyTitle: { color: colors.text, fontSize: 20, fontWeight: '800' },
  emptyText: { color: colors.muted, fontSize: 14, textAlign: 'center' },

  fab: {
    position: 'absolute', bottom: 24, right: 20,
    width: 58, height: 58, borderRadius: 29,
    backgroundColor: colors.accent,
    alignItems: 'center', justifyContent: 'center',
    shadowColor: colors.accent,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4, shadowRadius: 8, elevation: 8,
  },

  overlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.15)', alignItems: 'flex-end', paddingTop: 60, paddingRight: 8 },
  dropdown: {
    backgroundColor: '#fff', borderRadius: 12, overflow: 'hidden', minWidth: 180,
    shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.12, shadowRadius: 12, elevation: 8,
  },
  dropItem: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 14 },
  dropLabel: { color: colors.text, fontSize: 15, fontWeight: '600' },
});
