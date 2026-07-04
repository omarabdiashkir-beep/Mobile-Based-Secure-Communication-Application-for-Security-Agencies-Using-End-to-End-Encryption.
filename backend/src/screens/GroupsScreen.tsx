import React, { useCallback, useEffect, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  FlatList,
  Image,
  Pressable,
  StatusBar,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { type NavigationProp, type ParamListBase, useFocusEffect } from '@react-navigation/native';
import { colors } from '../theme/colors';
import { listGroupsApi, type ApiGroup } from '../api/chatApi';
import type { AuthSession } from '../navigation/types';
import { chatRealtime } from '../api/chatRealtime';

type Props = {
  session: AuthSession;
  navigation: NavigationProp<ParamListBase>;
};

function GroupAvatar({ url, name, size = 50 }: { url?: string | null; name: string; size?: number }) {
  const hue = name.split('').reduce((a, c) => a + c.charCodeAt(0), 0) % 360;
  const initials = name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();
  if (url) return <Image source={{ uri: url }} style={{ width: size, height: size, borderRadius: size / 2 }} />;
  return (
    <View style={{ width: size, height: size, borderRadius: size / 2, backgroundColor: `hsl(${hue},45%,52%)`, alignItems: 'center', justifyContent: 'center' }}>
      <Text style={{ color: '#fff', fontSize: size * 0.34, fontWeight: '700' }}>{initials}</Text>
    </View>
  );
}

export function GroupsScreen({ session, navigation }: Props) {
  const [groups, setGroups] = useState<ApiGroup[]>([]);
  const [loading, setLoading] = useState(true);

  const loadGroups = useCallback(async () => {
    setLoading(true);
    try {
      const resp = await listGroupsApi(session.token);
      setGroups(Array.isArray(resp?.data) ? resp.data : []);
    } catch { /* silent */ }
    finally { setLoading(false); }
  }, [session.token]);

  useFocusEffect(useCallback(() => { void loadGroups(); }, [loadGroups]));

  useEffect(() => {
    // Group message arrives → update last message preview & bubble it to top
    const unSub = chatRealtime.onMessage((msg) => {
      if (!msg.groupId) return;
      setGroups((prev) => {
        const idx = prev.findIndex((g) => g.id === msg.groupId);
        if (idx < 0) { void loadGroups(); return prev; }
        const updated = { ...prev[idx], last_message: msg.message, last_message_at: msg.timestamp };
        return [updated, ...prev.filter((_, i) => i !== idx)];
      });
    });
    return () => unSub();
  }, [loadGroups]);

  return (
    <View style={s.root}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />

      <View style={s.header}>
        <Text style={s.headerTitle}>Groups</Text>
      </View>

      {loading ? (
        <View style={s.loader}><ActivityIndicator size="large" color={colors.primary} /></View>
      ) : (
        <FlatList
          data={groups}
          keyExtractor={(item) => item.id}
          contentContainerStyle={groups.length === 0 ? s.emptyContainer : undefined}
          renderItem={({ item }) => (
            <Pressable
              style={s.row}
              onPress={() => navigation.navigate('GroupChat', {
                groupId: item.id,
                name: item.name,
                avatarUrl: item.photo_url,
                membersCount: item.members_count,
              })}
            >
              <View style={s.avatarWrap}>
                <GroupAvatar url={item.photo_url} name={item.name} size={50} />
              </View>
              <View style={s.rowBody}>
                <Text style={s.rowName} numberOfLines={1}>{item.name}</Text>
                <Text style={s.rowSub} numberOfLines={1}>
                  {(item as any).last_message || (item.members_count ? `${item.members_count} members` : item.description || 'Group')}
                </Text>
              </View>
            </Pressable>
          )}
          ItemSeparatorComponent={() => <View style={s.separator} />}
          ListEmptyComponent={
            <View style={s.empty}>
              <View style={s.emptyIcon}>
                <Icon name="people-outline" size={52} color={colors.muted} />
              </View>
              <Text style={s.emptyTitle}>No groups yet</Text>
              <Text style={s.emptyText}>Tap + to create your first group</Text>
            </View>
          }
        />
      )}

      {/* Orange FAB */}
      <Pressable style={s.fab} onPress={() => navigation.navigate('CreateGroup')}>
        <Icon name="add" size={28} color="#fff" />
      </Pressable>
    </View>
  );
}

const s = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#fff' },
  header: {
    backgroundColor: '#fff',
    paddingTop: 52, paddingBottom: 8, paddingHorizontal: 16,
  },
  headerTitle: { color: colors.text, fontSize: 24, fontWeight: '800' },
  loader: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  row: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 11 },
  avatarWrap: { marginRight: 14 },
  rowBody: { flex: 1 },
  rowName: { color: colors.text, fontSize: 16, fontWeight: '700', marginBottom: 3 },
  rowSub: { color: colors.muted, fontSize: 13 },
  separator: { height: 1, backgroundColor: '#F5F5F5', marginLeft: 80 },
  emptyContainer: { flex: 1 },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingTop: 80, paddingHorizontal: 32, gap: 10 },
  emptyIcon: { width: 90, height: 90, borderRadius: 45, backgroundColor: '#F0F2F5', alignItems: 'center', justifyContent: 'center', marginBottom: 8 },
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
});
