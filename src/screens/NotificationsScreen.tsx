import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { colors } from '../theme/colors';
import {
  getNotificationsApi,
  markNotificationReadApi,
  markAllNotificationsReadApi,
  type ApiNotification,
} from '../api/chatApi';
import type { AuthSession } from '../navigation/types';

type Props = {
  session: AuthSession;
  onUnreadCountChange?: (count: number) => void;
};

function typeConfig(type: ApiNotification['type']) {
  switch (type) {
    case 'alert':       return { icon: 'warning',           bg: '#FEF3C7', color: '#D97706' };
    case 'message':     return { icon: 'chatbubble',        bg: '#EFF6FF', color: '#2563EB' };
    case 'announcement':return { icon: 'megaphone',         bg: '#F0FDF4', color: '#16A34A' };
    case 'update':      return { icon: 'refresh-circle',    bg: '#F5F3FF', color: '#7C3AED' };
    default:            return { icon: 'notifications',     bg: '#F1F5F9', color: '#475569' };
  }
}

function formatTime(dt: string) {
  if (!dt) return '';
  const d = new Date(dt);
  if (Number.isNaN(d.getTime())) return dt;
  const now = new Date();
  const diff = (now.getTime() - d.getTime()) / 1000;
  if (diff < 60)   return 'Just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400)return `${Math.floor(diff / 3600)}h ago`;
  if (diff < 604800)return `${Math.floor(diff / 86400)}d ago`;
  return d.toLocaleDateString([], { day: '2-digit', month: 'short' });
}

export function NotificationsScreen({ session, onUnreadCountChange }: Props) {
  const [items, setItems] = useState<ApiNotification[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [unreadCount, setUnreadCount] = useState(0);
  const mounted = useRef(true);

  useEffect(() => {
    mounted.current = true;
    void load(1, true);
    return () => { mounted.current = false; };
  }, []);

  const load = async (p: number, reset = false) => {
    if (reset) setLoading(true);
    try {
      const resp = await getNotificationsApi(session.token, p, 20);
      const d = (resp as any)?.data;
      if (!mounted.current) return;
      const newItems: ApiNotification[] = d?.notifications ?? [];
      setItems(prev => reset ? newItems : [...prev, ...newItems]);
      setPage(d?.pagination?.page ?? p);
      setTotalPages(d?.pagination?.total_pages ?? 1);
      const uc = d?.unread_count ?? 0;
      setUnreadCount(uc);
      onUnreadCountChange?.(uc);
    } catch { /* silent */ }
    finally { if (mounted.current) { setLoading(false); setRefreshing(false); setLoadingMore(false); } }
  };

  const onRefresh = () => {
    setRefreshing(true);
    void load(1, true);
  };

  const onEndReached = () => {
    if (loadingMore || page >= totalPages) return;
    setLoadingMore(true);
    void load(page + 1);
  };

  const markRead = async (item: ApiNotification) => {
    if (item.is_read) return;
    setItems(prev => prev.map(n => n.id === item.id ? { ...n, is_read: true, read_at: new Date().toISOString() } : n));
    try {
      const resp = await markNotificationReadApi(session.token, item.id);
      const uc = (resp as any)?.data?.unread_count ?? Math.max(0, unreadCount - 1);
      setUnreadCount(uc);
      onUnreadCountChange?.(uc);
    } catch { /* silent */ }
  };

  const markAllRead = async () => {
    setItems(prev => prev.map(n => ({ ...n, is_read: true, read_at: n.read_at ?? new Date().toISOString() })));
    try {
      await markAllNotificationsReadApi(session.token);
      setUnreadCount(0);
      onUnreadCountChange?.(0);
    } catch { /* silent */ }
  };

  const renderItem = useCallback(({ item }: { item: ApiNotification }) => {
    const cfg = typeConfig(item.type);
    return (
      <Pressable
        style={[styles.item, !item.is_read && styles.itemUnread]}
        onPress={() => void markRead(item)}
        android_ripple={{ color: '#0001' }}
      >
        <View style={[styles.iconCircle, { backgroundColor: cfg.bg }]}>
          <Icon name={cfg.icon as any} size={22} color={cfg.color} />
        </View>
        <View style={styles.itemBody}>
          <View style={styles.itemTop}>
            <Text style={styles.itemTitle} numberOfLines={1}>{item.title}</Text>
            <Text style={styles.itemTime}>{formatTime(item.created_at)}</Text>
          </View>
          <Text style={styles.itemText} numberOfLines={2}>{item.body}</Text>
          {item.type !== 'general' && (
            <View style={[styles.typePill, { backgroundColor: cfg.bg }]}>
              <Text style={[styles.typePillText, { color: cfg.color }]}>{item.type}</Text>
            </View>
          )}
        </View>
        {!item.is_read && <View style={styles.unreadDot} />}
      </Pressable>
    );
  }, [unreadCount]);

  return (
    <View style={styles.root}>
      <StatusBar barStyle="light-content" backgroundColor={colors.primaryDark} />

      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Notifications</Text>
        {unreadCount > 0 && (
          <TouchableOpacity style={styles.markAllBtn} onPress={() => void markAllRead()} activeOpacity={0.7}>
            <Icon name="checkmark-done" size={16} color={colors.primary} />
            <Text style={styles.markAllText}>Mark all read</Text>
          </TouchableOpacity>
        )}
      </View>

      {/* Unread badge summary */}
      {unreadCount > 0 && (
        <View style={styles.unreadBanner}>
          <Icon name="ellipse" size={8} color={colors.primary} />
          <Text style={styles.unreadBannerText}>{unreadCount} unread notification{unreadCount !== 1 ? 's' : ''}</Text>
        </View>
      )}

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : items.length === 0 ? (
        <View style={styles.center}>
          <Icon name="notifications-off-outline" size={56} color="#D0D5DD" />
          <Text style={styles.emptyText}>No notifications yet</Text>
        </View>
      ) : (
        <FlatList
          data={items}
          keyExtractor={i => String(i.id)}
          renderItem={renderItem}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />}
          onEndReached={onEndReached}
          onEndReachedThreshold={0.3}
          contentContainerStyle={{ paddingBottom: 24 }}
          ListFooterComponent={loadingMore ? <ActivityIndicator style={{ padding: 16 }} color={colors.primary} /> : null}
          ItemSeparatorComponent={() => <View style={styles.sep} />}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#F7F8FA' },

  header: {
    backgroundColor: colors.primaryDark,
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingTop: 52, paddingBottom: 14, paddingHorizontal: 16,
  },
  headerTitle: { color: '#fff', fontSize: 20, fontWeight: '800' },
  markAllBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 5,
    backgroundColor: 'rgba(255,255,255,0.15)',
    paddingHorizontal: 12, paddingVertical: 6, borderRadius: 20,
  },
  markAllText: { color: '#fff', fontSize: 12, fontWeight: '700' },

  unreadBanner: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    backgroundColor: colors.primary + '12',
    paddingHorizontal: 16, paddingVertical: 8,
    borderBottomWidth: 1, borderBottomColor: colors.primary + '20',
  },
  unreadBannerText: { color: colors.primary, fontSize: 13, fontWeight: '600' },

  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 12 },
  emptyText: { color: '#98A2B3', fontSize: 15, fontWeight: '600' },

  item: {
    flexDirection: 'row', alignItems: 'flex-start',
    paddingHorizontal: 16, paddingVertical: 14,
    backgroundColor: '#fff', gap: 12,
  },
  itemUnread: { backgroundColor: colors.primary + '08' },

  iconCircle: {
    width: 46, height: 46, borderRadius: 23,
    alignItems: 'center', justifyContent: 'center',
    flexShrink: 0,
  },

  itemBody: { flex: 1, gap: 3 },
  itemTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 8 },
  itemTitle: { flex: 1, color: '#1C2437', fontSize: 14, fontWeight: '700' },
  itemTime: { color: '#98A2B3', fontSize: 11, flexShrink: 0 },
  itemText: { color: '#667085', fontSize: 13, lineHeight: 18 },

  typePill: {
    alignSelf: 'flex-start',
    paddingHorizontal: 8, paddingVertical: 2,
    borderRadius: 10, marginTop: 4,
  },
  typePillText: { fontSize: 10, fontWeight: '700', textTransform: 'capitalize' },

  unreadDot: {
    width: 8, height: 8, borderRadius: 4,
    backgroundColor: colors.primary,
    marginTop: 4, flexShrink: 0,
  },

  sep: { height: 1, backgroundColor: '#F2F4F7', marginLeft: 74 },
});
