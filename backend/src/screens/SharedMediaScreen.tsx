import React, { useEffect, useRef, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  Dimensions,
  FlatList,
  Image,
  Linking,
  Pressable,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import type { StackScreenProps } from '@react-navigation/stack';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';
import { API_BASE_URL } from '../api/config';
import type { AppStackParamList } from '../navigation/types';

type Props = StackScreenProps<AppStackParamList, 'SharedMedia'>;

type MediaItem = {
  id: number;
  type: 'image' | 'video' | 'voice' | 'document';
  file_url: string;
  file_name: string;
  file_size: number;
  file_mime: string;
  content: string | null;
  created_at: string;
  sender_id: number;
  sender_name: string;
  sender_username: string;
  sender_photo: string | null;
};

type Tab = 'Photos' | 'Videos' | 'Audio' | 'Docs';
const TABS: Tab[] = ['Photos', 'Videos', 'Audio', 'Docs'];
const { width: SCREEN_W } = Dimensions.get('window');
const PHOTO_SIZE = (SCREEN_W - 4) / 3;

async function fetchMedia(token: string, userId: string, kind: string): Promise<MediaItem[]> {
  const endpointMap: Record<string, string> = {
    images: 'images',
    videos: 'videos',
    voices: 'voices',
    documents: 'documents',
  };
  const ep = endpointMap[kind];
  const url = `${API_BASE_URL}/api/messages/${userId}/${ep}?page=1&limit=200`;
  const resp = await fetch(url, { headers: { Authorization: `Bearer ${token}` } });
  if (!resp.ok) return [];
  const json = await resp.json();
  return (json?.data?.items ?? []) as MediaItem[];
}

function formatBytes(bytes: number) {
  if (!bytes) return '';
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function formatDate(dt: string) {
  if (!dt) return '';
  const d = new Date(dt);
  if (Number.isNaN(d.getTime())) return dt;
  return d.toLocaleDateString([], { day: '2-digit', month: 'short', year: 'numeric' });
}

function docIcon(mime: string): string {
  if (mime?.includes('pdf')) return 'document-text';
  if (mime?.includes('word') || mime?.includes('doc')) return 'document';
  if (mime?.includes('excel') || mime?.includes('sheet') || mime?.includes('xls')) return 'grid';
  if (mime?.includes('zip') || mime?.includes('rar') || mime?.includes('7z')) return 'archive';
  if (mime?.includes('audio')) return 'musical-note';
  return 'attach';
}

export function SharedMediaScreen({ route, navigation }: Props) {
  const { userId, name } = route.params;
  const { session } = useAuth();
  const [activeTab, setActiveTab] = useState<Tab>('Photos');
  const [photos, setPhotos] = useState<MediaItem[]>([]);
  const [videos, setVideos] = useState<MediaItem[]>([]);
  const [voices, setVoices] = useState<MediaItem[]>([]);
  const [docs, setDocs] = useState<MediaItem[]>([]);
  const [loading, setLoading] = useState(true);
  const mounted = useRef(true);

  useEffect(() => {
    mounted.current = true;
    void load();
    return () => { mounted.current = false; };
  }, [userId]);

  const load = async () => {
    if (!session) return;
    setLoading(true);
    try {
      const [imgs, vids, aud, documents] = await Promise.all([
        fetchMedia(session.token, userId, 'images'),
        fetchMedia(session.token, userId, 'videos'),
        fetchMedia(session.token, userId, 'voices'),
        fetchMedia(session.token, userId, 'documents'),
      ]);
      if (!mounted.current) return;
      setPhotos(imgs);
      setVideos(vids);
      setVoices(aud);
      setDocs(documents);
    } catch { /* silent */ }
    finally { if (mounted.current) setLoading(false); }
  };

  const tabCount = {
    Photos: photos.length,
    Videos: videos.length,
    Audio: voices.length,
    Docs: docs.length,
  };

  return (
    <View style={styles.root}>
      <StatusBar barStyle="light-content" backgroundColor={colors.primaryDark} />

      {/* Header */}
      <View style={styles.header}>
        <Pressable onPress={() => navigation.goBack()} style={styles.backBtn}>
          <Icon name="arrow-back" size={22} color="#fff" />
        </Pressable>
        <View style={{ flex: 1 }}>
          <Text style={styles.headerTitle}>Media, Links & Docs</Text>
          <Text style={styles.headerSub}>{name}</Text>
        </View>
      </View>

      {/* Tab bar */}
      <View style={styles.tabBar}>
        {TABS.map((t) => (
          <TouchableOpacity
            key={t}
            style={[styles.tab, activeTab === t && styles.tabActive]}
            onPress={() => setActiveTab(t)}
            activeOpacity={0.7}
          >
            <Text style={[styles.tabText, activeTab === t && styles.tabTextActive]}>{t}</Text>
            {tabCount[t] > 0 && (
              <View style={[styles.tabBadge, activeTab === t && styles.tabBadgeActive]}>
                <Text style={[styles.tabBadgeText, activeTab === t && styles.tabBadgeTextActive]}>
                  {tabCount[t]}
                </Text>
              </View>
            )}
          </TouchableOpacity>
        ))}
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <>
          {activeTab === 'Photos' && <PhotoGrid items={photos} />}
          {activeTab === 'Videos' && <VideoGrid items={videos} />}
          {activeTab === 'Audio' && <AudioList items={voices} />}
          {activeTab === 'Docs' && <DocList items={docs} />}
        </>
      )}
    </View>
  );
}

// ── Photos ──────────────────────────────────────────────────────────────────

function PhotoGrid({ items }: { items: MediaItem[] }) {
  if (!items.length) return <Empty label="No photos yet" icon="image-outline" />;
  return (
    <FlatList
      data={items}
      numColumns={3}
      keyExtractor={(i) => String(i.id)}
      renderItem={({ item }) => (
        <Pressable style={styles.photoCell} android_ripple={{ color: '#0002' }}>
          <Image source={{ uri: item.file_url }} style={styles.photoImg} resizeMode="cover" />
        </Pressable>
      )}
      contentContainerStyle={{ gap: 2 }}
      columnWrapperStyle={{ gap: 2 }}
    />
  );
}

// ── Videos ──────────────────────────────────────────────────────────────────

function VideoGrid({ items }: { items: MediaItem[] }) {
  if (!items.length) return <Empty label="No videos yet" icon="videocam-outline" />;
  return (
    <FlatList
      data={items}
      numColumns={3}
      keyExtractor={(i) => String(i.id)}
      renderItem={({ item }) => (
        <Pressable style={styles.photoCell} android_ripple={{ color: '#0002' }}>
          <Image source={{ uri: item.file_url }} style={styles.photoImg} resizeMode="cover" />
          <View style={styles.videoOverlay}>
            <Icon name="play-circle" size={32} color="#fff" />
          </View>
          {item.content ? (
            <Text style={styles.videoDuration} numberOfLines={1}>{item.content}</Text>
          ) : null}
        </Pressable>
      )}
      contentContainerStyle={{ gap: 2 }}
      columnWrapperStyle={{ gap: 2 }}
    />
  );
}

// ── Audio ──────────────────────────────────────────────────────────────────

function AudioList({ items }: { items: MediaItem[] }) {
  if (!items.length) return <Empty label="No voice messages yet" icon="mic-outline" />;
  return (
    <FlatList
      data={items}
      keyExtractor={(i) => String(i.id)}
      contentContainerStyle={{ paddingVertical: 8, paddingHorizontal: 16, gap: 8 }}
      renderItem={({ item }) => (
        <View style={styles.audioRow}>
          <View style={[styles.audioIcon, { backgroundColor: '#5C6BC018' }]}>
            <Icon name="mic" size={22} color="#5C6BC0" />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.audioName} numberOfLines={1}>
              Voice message
            </Text>
            <Text style={styles.audioMeta}>
              {formatBytes(item.file_size)}  ·  {formatDate(item.created_at)}
            </Text>
          </View>
          <Icon name="play-circle-outline" size={32} color={colors.primary} />
        </View>
      )}
    />
  );
}

// ── Docs ──────────────────────────────────────────────────────────────────

function DocList({ items }: { items: MediaItem[] }) {
  if (!items.length) return <Empty label="No documents yet" icon="document-outline" />;
  return (
    <FlatList
      data={items}
      keyExtractor={(i) => String(i.id)}
      contentContainerStyle={{ paddingVertical: 8, paddingHorizontal: 16, gap: 8 }}
      renderItem={({ item }) => (
        <TouchableOpacity
          style={styles.docRow}
          activeOpacity={0.75}
          onPress={() => { void Linking.openURL(item.file_url); }}
        >
          <View style={[styles.docIcon, { backgroundColor: colors.primary + '18' }]}>
            <Icon name={docIcon(item.file_mime) as any} size={24} color={colors.primary} />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.docName} numberOfLines={2}>{item.file_name || 'Document'}</Text>
            <Text style={styles.docMeta}>
              {formatBytes(item.file_size)}  ·  {formatDate(item.created_at)}
            </Text>
            {item.content ? (
              <Text style={styles.docCaption} numberOfLines={1}>{item.content}</Text>
            ) : null}
          </View>
          <Icon name="open-outline" size={18} color={colors.muted} style={{ marginLeft: 8 }} />
        </TouchableOpacity>
      )}
    />
  );
}

// ── Empty state ──────────────────────────────────────────────────────────────

function Empty({ label, icon }: { label: string; icon: string }) {
  return (
    <View style={styles.center}>
      <Icon name={icon as any} size={56} color="#D0D5DD" />
      <Text style={styles.emptyText}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#F7F8FA' },
  header: {
    backgroundColor: colors.primaryDark,
    flexDirection: 'row', alignItems: 'center',
    paddingTop: 48, paddingBottom: 12, paddingHorizontal: 8,
    gap: 4,
  },
  backBtn: { width: 40, height: 40, alignItems: 'center', justifyContent: 'center' },
  headerTitle: { color: '#fff', fontSize: 17, fontWeight: '800' },
  headerSub: { color: 'rgba(255,255,255,0.65)', fontSize: 12 },

  tabBar: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderBottomWidth: 1, borderBottomColor: '#EAECF0',
    paddingHorizontal: 4,
  },
  tab: {
    flex: 1, alignItems: 'center', justifyContent: 'center',
    flexDirection: 'row', gap: 4,
    paddingVertical: 12,
    borderBottomWidth: 2, borderBottomColor: 'transparent',
  },
  tabActive: { borderBottomColor: colors.primary },
  tabText: { color: '#98A2B3', fontSize: 13, fontWeight: '600' },
  tabTextActive: { color: colors.primary },
  tabBadge: {
    backgroundColor: '#E9ECEF', borderRadius: 10,
    paddingHorizontal: 6, paddingVertical: 1,
  },
  tabBadgeActive: { backgroundColor: colors.primary + '22' },
  tabBadgeText: { color: '#98A2B3', fontSize: 10, fontWeight: '700' },
  tabBadgeTextActive: { color: colors.primary },

  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 12 },
  emptyText: { color: '#98A2B3', fontSize: 15, fontWeight: '600' },

  // Photos / Videos grid
  photoCell: { width: PHOTO_SIZE, height: PHOTO_SIZE, backgroundColor: '#E5E7EB' },
  photoImg: { width: '100%', height: '100%' },
  videoOverlay: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center', justifyContent: 'center',
    backgroundColor: 'rgba(0,0,0,0.25)',
  },
  videoDuration: {
    position: 'absolute', bottom: 4, right: 6,
    color: '#fff', fontSize: 10, fontWeight: '700',
    textShadowColor: '#0006', textShadowOffset: { width: 0, height: 1 }, textShadowRadius: 2,
  },

  // Audio
  audioRow: {
    flexDirection: 'row', alignItems: 'center', gap: 12,
    backgroundColor: '#fff', borderRadius: 14, padding: 14,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.05, shadowRadius: 3, elevation: 1,
  },
  audioIcon: { width: 44, height: 44, borderRadius: 22, alignItems: 'center', justifyContent: 'center' },
  audioName: { color: '#1C2437', fontSize: 14, fontWeight: '700' },
  audioMeta: { color: '#98A2B3', fontSize: 12, marginTop: 2 },

  // Docs
  docRow: {
    flexDirection: 'row', alignItems: 'center', gap: 12,
    backgroundColor: '#fff', borderRadius: 14, padding: 14,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.05, shadowRadius: 3, elevation: 1,
  },
  docIcon: { width: 48, height: 48, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  docName: { color: '#1C2437', fontSize: 14, fontWeight: '700' },
  docMeta: { color: '#98A2B3', fontSize: 12, marginTop: 2 },
  docCaption: { color: '#667085', fontSize: 12, marginTop: 3, fontStyle: 'italic' },
});
