import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  Alert,
  Animated,
  FlatList,
  Image,
  KeyboardAvoidingView,
  Linking,
  Modal,
  PanResponder,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';

import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Haptics from 'expo-haptics';
import type { StackScreenProps } from '@react-navigation/stack';
import {
  useAudioPlayer,
  useAudioPlayerStatus,
  useAudioRecorder,
  useAudioRecorderState,
  RecordingPresets,
  requestRecordingPermissionsAsync,
  setAudioModeAsync,
} from 'expo-audio';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { VideoView, useVideoPlayer } from 'expo-video';
import { Screen } from '../components/Screen';
import { TypingBubble } from '../components/TypingBubble';
import type { Message } from '../types';
import { colors } from '../theme/colors';
import {
  deleteMessageApi,
  getConversationApi,
  sendMessageApi,
  sendFileMessageApi,
  reactToMessageApi,
  removeReactionApi,
  replyToMessageApi,
  markAsReadApi,
  unblockUserApi,
  getBlockStatusApi,
  getUserStatusApi,
  type ApiChatMessage,
} from '../api/chatApi';
import type { AppStackParamList, AuthSession } from '../navigation/types';
import { chatRealtime } from '../api/chatRealtime';

type Props = StackScreenProps<AppStackParamList, 'ChatDetail'> & { session: AuthSession };

const SWIPE_THRESHOLD = 60;
const REACTIONS = ['❤️', '👍', '😂', '😮', '😢', '🙏'];

function formatMessageTime(iso?: string) {
  if (!iso) return 'Now';
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return 'Now';
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
}

function formatDuration(totalSeconds?: number) {
  const s = Math.max(0, Math.floor(totalSeconds ?? 0));
  return `${Math.floor(s / 60)}:${(s % 60).toString().padStart(2, '0')}`;
}

function VoiceMessageContent({ uri, isMe }: { uri: string; isMe: boolean }) {
  const player = useAudioPlayer({ uri }, { updateInterval: 250 });
  const status = useAudioPlayerStatus(player);
  const [speed, setSpeed] = useState<1 | 1.5 | 2>(1);
  const progress = status.duration ? status.currentTime / status.duration : 0;

  const togglePlayback = async () => {
    if (status.playing) { player.pause(); return; }
    const nearEnd = (status.duration || 0) > 0 && status.currentTime >= Math.max((status.duration || 0) - 0.2, 0);
    if (status.didJustFinish || nearEnd) await player.seekTo(0);
    player.play();
  };

  const cycleSpeed = async () => {
    const n = speed === 1 ? 1.5 : speed === 1.5 ? 2 : 1;
    setSpeed(n);
    await player.setPlaybackRate(n);
  };

  return (
    <View style={st.voiceRow}>
      <Pressable style={[st.voicePlay, isMe && st.voicePlayMe]} onPress={() => void togglePlayback()}>
        <Icon name={status.playing ? 'pause' : 'play'} size={16} color={isMe ? '#fff' : colors.primary} />
      </Pressable>
      <View style={st.voiceTrackWrap}>
        <View style={[st.voiceTrack, isMe && { backgroundColor: 'rgba(255,255,255,0.3)' }]}>
          <View style={[st.voiceProgress, isMe && { backgroundColor: '#fff' }, { width: `${Math.round(progress * 100)}%` as any }]} />
        </View>
        <Text style={[st.voiceDuration, isMe && { color: 'rgba(255,255,255,0.7)' }]}>{formatDuration(status.currentTime)} / {formatDuration(status.duration)}</Text>
      </View>
      <Pressable style={[st.speedPill, isMe && { backgroundColor: 'rgba(255,255,255,0.2)' }]} onPress={() => void cycleSpeed()}>
        <Text style={[st.speedText, isMe && { color: '#fff' }]}>{speed}×</Text>
      </Pressable>
    </View>
  );
}

function VideoPlayerModal({ uri, onClose }: { uri: string; onClose: () => void }) {
  const player = useVideoPlayer({ uri }, (p) => { p.play(); });
  return (
    <Modal visible animationType="fade" onRequestClose={onClose} statusBarTranslucent>
      <View style={{ flex: 1, backgroundColor: '#000', justifyContent: 'center' }}>
        <Pressable onPress={onClose} style={{ position: 'absolute', top: 48, right: 18, zIndex: 10 }}>
          <Icon name="close-circle" size={34} color="#fff" />
        </Pressable>
        <VideoView player={player} style={{ width: '100%', height: 340 }} allowsFullscreen allowsPictureInPicture />
      </View>
    </Modal>
  );
}

// ── Swipeable message bubble ──────────────────────────────────────────────────
function SwipeableMessage({
  item,
  isMe,
  onSwipe,
  onLongPress,
  children,
}: {
  item: Message;
  isMe: boolean;
  onSwipe: (msg: Message) => void;
  onLongPress: (msg: Message) => void;
  children: React.ReactNode;
}) {
  const translateX = useRef(new Animated.Value(0)).current;
  const replyIconOpacity = useRef(new Animated.Value(0)).current;
  const swiped = useRef(false);

  const panResponder = useRef(
    PanResponder.create({
      onMoveShouldSetPanResponder: (_, g) => Math.abs(g.dx) > 8 && Math.abs(g.dx) > Math.abs(g.dy),
      onPanResponderMove: (_, g) => {
        // Only allow right swipe for both me and them
        const dx = Math.max(0, g.dx);
        translateX.setValue(Math.min(dx, 80));
        replyIconOpacity.setValue(Math.min(dx / SWIPE_THRESHOLD, 1));
        if (dx >= SWIPE_THRESHOLD && !swiped.current) {
          swiped.current = true;
        }
      },
      onPanResponderRelease: (_, g) => {
        if (swiped.current) onSwipe(item);
        swiped.current = false;
        Animated.spring(translateX, { toValue: 0, useNativeDriver: true, tension: 80, friction: 8 }).start();
        Animated.timing(replyIconOpacity, { toValue: 0, duration: 150, useNativeDriver: true }).start();
      },
    })
  ).current;

  return (
    <View style={[st.swipeRow, isMe ? st.swipeRowMe : st.swipeRowThem]}>
      {/* Reply icon revealed on swipe */}
      <Animated.View style={[st.replyIcon, { opacity: replyIconOpacity }]}>
        <Icon name="return-down-forward-outline" size={20} color={colors.primary} />
      </Animated.View>

      <Animated.View
        style={{ transform: [{ translateX }] }}
        {...panResponder.panHandlers}
      >
        <Pressable onLongPress={() => onLongPress(item)} delayLongPress={300}>
          {children}
        </Pressable>
      </Animated.View>
    </View>
  );
}

// ── Main screen ───────────────────────────────────────────────────────────────
export function ChatDetailScreen({ route, navigation, session }: Props) {
  const insets = useSafeAreaInsets();
  const conversation = route.params.conversation;
  const [chatInput, setChatInput] = useState('');
  const [messages, setMessages] = useState<Message[]>([]);
  const [loading, setLoading] = useState(true);
  const [viewerImage, setViewerImage] = useState<string | null>(null);
  const [viewerVideo, setViewerVideo] = useState<string | null>(null);
  const [showAttachMenu, setShowAttachMenu] = useState(false);
  const [replyTarget, setReplyTarget] = useState<Message | null>(null);
  const [selectedMessage, setSelectedMessage] = useState<Message | null>(null);
  const [typingText, setTypingText] = useState('');
  const [typingActivity, setTypingActivity] = useState<'typing' | 'recording'>('typing');
  const [isOnline, setIsOnline] = useState(!!conversation.online);
  const [blockedStatus, setBlockedStatus] = useState<'none' | 'you_blocked' | 'you_are_blocked' | 'both_blocked'>('none');
  const flatListRef = useRef<FlatList>(null);
  const typingTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const recorder = useAudioRecorder(RecordingPresets.HIGH_QUALITY);
  const recorderState = useAudioRecorderState(recorder, 200);
  const [isRecording, setIsRecording] = useState(false);
  const recorderReadyRef = useRef(false);

  const mapApiMessage = async (item: ApiChatMessage): Promise<Message> => {
    const kind: Message['kind'] = item.type === 'voice' ? 'voice' : item.type === 'image' ? 'image' : item.type === 'video' ? 'video' : item.type === 'document' ? 'file' : 'text';
    const isMe = item.sender_id === session.userId;
    const text = item.file_url ?? item.content ?? '';
    const isRead = !!(item.read_at || item.delivery_status === 'read' || (item as any).is_read === true || (item as any).is_read === 1);
    const isDelivered = isRead || item.delivery_status === 'delivered' || (item as any).is_delivered === true;
    const ds: 'sent' | 'delivered' | 'read' = isRead ? 'read' : isDelivered ? 'delivered' : 'sent';

    const replyToMessage = item.reply_to_id ? {
      id: String(item.reply_to_id),
      senderId: String(item.reply_to?.sender_id ?? ''),
      senderName: item.reply_to?.sender_name ?? item.reply_sender_name ?? 'Unknown',
      message: item.reply_to?.content ?? item.reply_content ?? '',
      fileType: item.reply_to?.type,
    } : undefined;

    const reactions = (item.reactions ?? []).flatMap((r) => {
      const emoji = r.reaction;
      const users: any[] = Array.isArray(r.users) ? r.users : [];
      if (users.length > 0) return users.map((u: any) => ({ id: String(u.id ?? u), userId: String(u.id ?? u), userName: typeof u === 'object' ? (u.name ?? '') : String(u), emoji, createdAt: '' }));
      return Array.from({ length: r.count ?? 1 }, (_, i) => ({ id: `${emoji}-${i}`, userId: '', userName: '', emoji, createdAt: '' }));
    });

    return { id: item.id, from: isMe ? 'me' : 'them', kind, value: text, fileUrl: item.file_url, fileName: item.file_name, replyToMessageId: item.reply_to_id, replyToMessage, reactions, isDeletedForEveryone: Number(item.is_deleted) === 1, time: formatMessageTime(item.created_at), deliveryStatus: isMe ? ds : undefined };
  };

  const loadHistory = useCallback(async () => {
    if (!session.token || !session.userId) return;
    try {
      const resp = await getConversationApi(session.token, conversation.id);
      const msgs = resp?.data?.messages ?? [];
      setMessages(await Promise.all(msgs.map(mapApiMessage)));
      if (msgs.length > 0) {
        const senderId = parseInt(conversation.id, 10);
        if (!Number.isNaN(senderId)) void markAsReadApi(session.token, { sender_id: senderId });
      }
    } catch { /* silent */ }
    finally { setLoading(false); }
  }, [conversation.id, session.token, session.userId]);

  const silentRefresh = useCallback(async () => {
    if (!session.token || !session.userId) return;
    try {
      const resp = await getConversationApi(session.token, conversation.id);
      const msgs = resp?.data?.messages ?? [];
      if (msgs.length > 0) {
        setMessages(await Promise.all(msgs.map(mapApiMessage)));
        const senderId = parseInt(conversation.id, 10);
        if (!Number.isNaN(senderId)) void markAsReadApi(session.token, { sender_id: senderId });
      }
    } catch { /* silent */ }
  }, [conversation.id, session.token, session.userId]);

  const loadBlockStatus = useCallback(async () => {
    try {
      const resp = await getBlockStatusApi(session.token, conversation.id);
      setBlockedStatus(resp?.data?.blocked_status ?? 'none');
    } catch { /* silent */ }
  }, [conversation.id, session.token]);

  useEffect(() => {
    void loadHistory();
    void loadBlockStatus();
    void getUserStatusApi(session.token, conversation.id).then((r) => {
      const d = (r as any)?.data;
      if (d?.is_online === true || d?.is_online === 1) setIsOnline(true);
    }).catch(() => {});

    const pollInterval = setInterval(() => { void silentRefresh(); }, 1_000);
    const unSubMessage = chatRealtime.onMessage(() => loadHistory());
    const unSubMessageUpdated = chatRealtime.onMessageUpdated(() => loadHistory());
    const unSubDelivery = chatRealtime.onMessageDeliveryUpdated(() => loadHistory());
    const unSubRead = chatRealtime.onMessagesRead(() => loadHistory());
    const unSubTyping = chatRealtime.onTyping((event) => {
      const matches = conversation.isGroup ? event.groupId === conversation.id : event.senderId === conversation.id;
      if (!matches || event.senderId === session.userId) return;
      setTypingActivity(event.activity === 'recording' ? 'recording' : 'typing');
      setTypingText(event.isTyping ? (event.activity === 'recording' ? 'recording…' : 'typing…') : '');
    });
    const unSubPresence = chatRealtime.onPresence((event) => {
      if (event.userId !== conversation.id) return;
      setIsOnline(event.isOnline);
    });

    return () => {
      clearInterval(pollInterval);
      unSubMessage(); unSubMessageUpdated(); unSubDelivery(); unSubRead(); unSubTyping(); unSubPresence();
    };
  }, [conversation.id, conversation.isGroup, loadHistory, silentRefresh, session.token, session.userId]);

  useEffect(() => {
    return () => { if (typingTimeoutRef.current) clearTimeout(typingTimeoutRef.current); };
  }, []);

  const checkBlocked = (err: unknown): boolean => {
    const obj = err as any;
    const bs = obj?.blocked_status ?? obj?.data?.blocked_status;
    if (bs === 'you_blocked' || bs === 'you_are_blocked') { setBlockedStatus(bs); return true; }
    if (obj instanceof Error) {
      try { const p = JSON.parse(obj.message); if (p?.blocked_status) { setBlockedStatus(p.blocked_status); return true; } } catch { /* ok */ }
    }
    return false;
  };

  const sendPayload = useCallback(async (payload: { content: string; replyToMessageId?: string }) => {
    if (!session.token) return;
    if (payload.replyToMessageId) {
      await replyToMessageApi(session.token, payload.replyToMessageId, { content: payload.content });
    } else {
      await sendMessageApi(session.token, { receiver_id: conversation.id, content: payload.content });
    }
    await loadHistory();
  }, [conversation.id, loadHistory, session.token]);

  const handleSend = async () => {
    if (!chatInput.trim()) return;
    void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    const text = chatInput.trim();
    setChatInput('');
    const optimisticId = `opt-${Date.now()}`;
    setMessages((prev) => [...prev, { id: optimisticId, from: 'me', kind: 'text', value: text, time: formatMessageTime(new Date().toISOString()), deliveryStatus: 'sent' }]);
    try {
      await sendPayload({ content: text, replyToMessageId: replyTarget?.id });
      setReplyTarget(null);
      void chatRealtime.sendTyping(conversation.id, false);
    } catch (err) {
      setMessages((prev) => prev.filter((m) => m.id !== optimisticId));
      if (!checkBlocked(err)) { Alert.alert('Error', 'Failed to send.'); setChatInput(text); }
    }
  };

  const handleTypingChange = async (value: string) => {
    setChatInput(value);
    await chatRealtime.sendTyping(conversation.id, value.trim().length > 0, undefined, 'typing');
    if (typingTimeoutRef.current) clearTimeout(typingTimeoutRef.current);
    typingTimeoutRef.current = setTimeout(() => void chatRealtime.sendTyping(conversation.id, false), 1200);
  };

  const FILE_LIMITS: Record<string, number> = { image: 10 * 1024 * 1024, video: 100 * 1024 * 1024, voice: 20 * 1024 * 1024, document: 50 * 1024 * 1024 };

  const sendFile = async (type: 'image' | 'video' | 'voice' | 'document', fileUri: string, fileName: string, mimeType: string) => {
    try {
      const info = await FileSystem.getInfoAsync(fileUri);
      const limit = FILE_LIMITS[type] ?? 50 * 1024 * 1024;
      if (info.exists && (info as any).size > limit) { Alert.alert('File too large', `Max ${Math.round(limit / 1024 / 1024)} MB`); return; }
    } catch { /* ok */ }
    const optId = `opt-${Date.now()}`;
    const msgKind = type === 'voice' ? 'voice' : type === 'image' ? 'image' : type === 'video' ? 'video' : 'file';
    setMessages((prev) => [...prev, { id: optId, from: 'me', kind: msgKind, value: fileName, fileUrl: fileUri, time: formatMessageTime(new Date().toISOString()) }]);
    try {
      const resp = await sendFileMessageApi(session.token, { receiver_id: conversation.id, type, fileUri, fileName, mimeType, caption: fileName });
      const realMsg = (resp as any)?.data;
      if (realMsg) {
        const mapped = await mapApiMessage(realMsg);
        setMessages((prev) => prev.map((m) => m.id === optId ? mapped : m));
      } else {
        await loadHistory();
        setMessages((prev) => prev.filter((m) => m.id !== optId));
      }
    } catch (err) {
      setMessages((prev) => prev.filter((m) => m.id !== optId));
      if (!checkBlocked(err)) Alert.alert('Error', 'Failed to send file.');
    }
  };

  const pickImage = async () => { setShowAttachMenu(false); const r = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.85 }); if (!r.canceled && r.assets[0]) { const a = r.assets[0]; await sendFile('image', a.uri, a.fileName ?? `photo_${Date.now()}.jpg`, a.mimeType ?? 'image/jpeg'); } };
  const pickVideo = async () => { setShowAttachMenu(false); const r = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['videos'], quality: 0.8 }); if (!r.canceled && r.assets[0]) { const a = r.assets[0]; await sendFile('video', a.uri, a.fileName ?? `video_${Date.now()}.mp4`, a.mimeType ?? 'video/mp4'); } };
  const pickDocument = async () => { setShowAttachMenu(false); const r = await DocumentPicker.getDocumentAsync({ copyToCacheDirectory: true }); if (!r.canceled && r.assets[0]) { const a = r.assets[0]; await sendFile('document', a.uri, a.name, a.mimeType ?? 'application/octet-stream'); } };
  const pickCamera = async () => { setShowAttachMenu(false); const p = await ImagePicker.requestCameraPermissionsAsync(); if (!p.granted) return; const r = await ImagePicker.launchCameraAsync({ mediaTypes: ['images'], quality: 0.85 }); if (!r.canceled && r.assets[0]) { const a = r.assets[0]; await sendFile('image', a.uri, a.fileName ?? `cam_${Date.now()}.jpg`, a.mimeType ?? 'image/jpeg'); } };

  const startVoiceRecording = async () => {
    try {
      const { granted } = await requestRecordingPermissionsAsync();
      if (!granted) { Alert.alert('Permission required', 'Microphone access needed.'); return; }
      await setAudioModeAsync({ allowsRecording: true, playsInSilentMode: true });
      await recorder.prepareToRecordAsync();
      recorder.record();
      setIsRecording(true);
      void chatRealtime.sendTyping(conversation.id, true, undefined, 'recording');
    } catch {
      Alert.alert('Error', 'Could not start recording.');
    }
  };

  const stopVoiceRecording = async () => {
    if (!isRecording) return;
    setIsRecording(false);
    void chatRealtime.sendTyping(conversation.id, false);
    try {
      const result = await recorder.stop();
      const uri = (result as any)?.uri ?? recorder.uri;
      if (uri) {
        const fn = `voice_${Date.now()}.mp4`;
        const dest = `${FileSystem.cacheDirectory}${fn}`;
        await FileSystem.copyAsync({ from: uri, to: dest });
        await sendFile('voice', dest, fn, 'audio/mp4');
      }
    } catch {
      Alert.alert('Error', 'Could not send voice message.');
    }
  };

  const handleReaction = async (emoji: string) => {
    if (!selectedMessage) return;
    await reactToMessageApi(session.token, selectedMessage.id, emoji);
    setSelectedMessage(null);
    await loadHistory();
  };

  const handleDelete = () => {
    if (!selectedMessage) return;
    const msg = selectedMessage;
    setSelectedMessage(null);
    Alert.alert('Delete message', 'Delete for everyone?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Delete', style: 'destructive', onPress: async () => { try { await deleteMessageApi(session.token, msg.id); await loadHistory(); } catch { Alert.alert('Error', 'Could not delete.'); } } },
    ]);
  };

  const downloadAndOpen = async (url: string, fileName: string) => {
    try {
      const dest = `${FileSystem.cacheDirectory}${fileName}`;
      const e = await FileSystem.getInfoAsync(dest);
      if (!e.exists) await FileSystem.downloadAsync(url, dest);
      await Sharing.shareAsync(dest);
    } catch { Alert.alert('Error', 'Could not open file.'); }
  };

  const renderContent = (item: Message, isMe: boolean) => {
    if (item.isDeletedForEveryone) return <Text style={st.deletedText}>🚫 Message deleted</Text>;

    if (item.kind === 'image' && item.fileUrl) return (
      <Pressable onPress={() => setViewerImage(item.fileUrl!)} style={st.mediaWrap}>
        <Image source={{ uri: item.fileUrl }} style={st.mediaImg} resizeMode="cover" />
        <View style={st.mediaExpandBtn}>
          <Icon name="expand-outline" size={14} color="#fff" />
        </View>
      </Pressable>
    );

    if (item.kind === 'video' && item.fileUrl) return (
      <Pressable onPress={() => setViewerVideo(item.fileUrl!)} style={st.mediaWrap}>
        <View style={st.videoPlaceholder}>
          <Icon name="videocam" size={32} color="rgba(255,255,255,0.4)" style={{ marginBottom: 8 }} />
          <View style={st.videoPlayCircle}>
            <Icon name="play" size={26} color="#fff" />
          </View>
          <Text style={st.videoLabel}>Tap to play</Text>
        </View>
      </Pressable>
    );

    if (item.kind === 'voice' && item.fileUrl) return <VoiceMessageContent uri={item.fileUrl} isMe={isMe} />;

    if (item.kind === 'file' && item.fileUrl) {
      const name = item.fileName || item.value || 'document';
      return (
        <Pressable style={st.fileRow} onPress={() => void downloadAndOpen(item.fileUrl!, name)}>
          <View style={st.fileIconBox}><Icon name="document-text-outline" size={22} color={isMe ? '#fff' : colors.primary} /></View>
          <View style={{ flex: 1 }}>
            <Text style={[st.fileName, isMe && { color: '#fff' }]} numberOfLines={2}>{name}</Text>
            <Text style={[st.fileSub, isMe && { color: 'rgba(255,255,255,0.7)' }]}>Tap to open</Text>
          </View>
          <Icon name="download-outline" size={18} color={isMe ? '#fff' : colors.muted} />
        </Pressable>
      );
    }

    return <Text style={[st.msgText, isMe && st.msgTextMe]}>{item.value}</Text>;
  };

  const avatarHue = conversation.name.split('').reduce((a, c) => a + c.charCodeAt(0), 0) % 360;

  return (
    <Screen style={st.root}>
      {/* ── Header ── */}
      <View style={st.header}>
        <Pressable onPress={() => navigation.goBack()} style={st.headerBtn}>
          <Icon name="arrow-back" size={22} color={colors.text} />
        </Pressable>
        <Pressable
          style={st.headerProfile}
          onPress={() => navigation.navigate('UserDetail', { userId: conversation.id, name: conversation.name, avatarUrl: conversation.avatarUrl })}
        >
          {conversation.avatarUrl ? (
            <Image source={{ uri: conversation.avatarUrl }} style={st.headerAvatar} />
          ) : (
            <View style={[st.headerAvatar, { backgroundColor: `hsl(${avatarHue},50%,55%)`, alignItems: 'center', justifyContent: 'center' }]}>
              <Text style={{ color: '#fff', fontSize: 16, fontWeight: '700' }}>{conversation.name.charAt(0).toUpperCase()}</Text>
            </View>
          )}
          {isOnline && <View style={st.headerOnlineDot} />}
        </Pressable>
        <Pressable
          style={{ flex: 1 }}
          onPress={() => navigation.navigate('UserDetail', { userId: conversation.id, name: conversation.name, avatarUrl: conversation.avatarUrl })}
        >
          <Text style={st.headerName} numberOfLines={1}>{conversation.name}</Text>
          <Text style={[st.headerStatus, isOnline && !typingText && st.headerStatusOnline]}>
            {typingText || (isOnline ? 'online' : 'offline')}
          </Text>
        </Pressable>
      </View>

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={Platform.OS === 'ios' ? 0 : 0}
        style={{ flex: 1 }}
      >
        {loading ? (
          <View style={st.loader}><ActivityIndicator size="large" color={colors.primary} /></View>
        ) : (
          <FlatList
            ref={flatListRef}
            data={messages}
            keyExtractor={(item) => item.id}
            contentContainerStyle={st.list}
            onContentSizeChange={() => flatListRef.current?.scrollToEnd({ animated: false })}
            renderItem={({ item }) => {
              const isMe = item.from === 'me';
              const isMedia = (item.kind === 'image' || item.kind === 'video') && item.fileUrl && !item.isDeletedForEveryone;

              return (
                <SwipeableMessage item={item} isMe={isMe} onSwipe={setReplyTarget} onLongPress={setSelectedMessage}>
                  <View style={[
                    st.bubbleWrap,
                    isMe ? st.bubbleWrapMe : st.bubbleWrapThem,
                    isMedia && st.bubbleWrapMedia,
                  ]}>
                    {/* Reply preview */}
                    {item.replyToMessage && !isMedia ? (
                      <View style={[st.replySnippet, isMe ? st.replySnippetMe : st.replySnippetThem]}>
                        <Text style={st.replySnippetName}>{item.replyToMessage.senderName}</Text>
                        <Text style={st.replySnippetText} numberOfLines={1}>{item.replyToMessage.message || item.replyToMessage.fileType || '…'}</Text>
                      </View>
                    ) : null}

                    {renderContent(item, isMe)}

                    {/* Reactions */}
                    {(item.reactions?.length ?? 0) > 0 && (
                      <View style={[st.reactions, isMedia && st.reactionsMedia]}>
                        {(Array.from(new Set(item.reactions!.map((r) => r.emoji))) as string[]).map((e) => (
                          <View key={e} style={st.reactionChip}>
                            <Text style={st.reactionEmoji}>{e}</Text>
                            <Text style={st.reactionCount}>{item.reactions!.filter((r) => r.emoji === e).length}</Text>
                          </View>
                        ))}
                      </View>
                    )}

                    {/* Time + ticks — overlaid on media, normal for text */}
                    <View style={[st.metaRow, isMedia && st.metaRowMedia]}>
                      <Text style={[st.timeText, isMe && st.timeTextMe, isMedia && st.timeTextMedia]}>{item.time}</Text>
                      {isMe && !item.isDeletedForEveryone ? (
                        <Icon
                          name={item.deliveryStatus === 'sent' ? 'checkmark' : 'checkmark-done'}
                          size={13}
                          color={item.deliveryStatus === 'read' ? '#53BDEB' : (isMedia ? '#fff' : 'rgba(255,255,255,0.7)')}
                        />
                      ) : null}
                    </View>
                  </View>
                </SwipeableMessage>
              );
            }}
          />
        )}

        {typingText ? <TypingBubble activity={typingActivity} /> : null}

        {/* Reply composer bar */}
        {replyTarget ? (
          <View style={st.replyBar}>
            <View style={st.replyBarAccent} />
            <View style={{ flex: 1 }}>
              <Text style={st.replyBarName}>{replyTarget.from === 'me' ? 'You' : conversation.name}</Text>
              <Text style={st.replyBarText} numberOfLines={1}>{replyTarget.value || replyTarget.fileName || replyTarget.kind}</Text>
            </View>
            <Pressable onPress={() => setReplyTarget(null)} style={st.replyBarClose}>
              <Icon name="close" size={18} color={colors.muted} />
            </Pressable>
          </View>
        ) : null}

        {/* Attach menu */}
        {showAttachMenu ? (
          <View style={st.attachMenu}>
            {[
              { icon: 'camera-outline', label: 'Camera', color: '#F97316', action: pickCamera },
              { icon: 'image-outline', label: 'Image', color: '#22C55E', action: pickImage },
              { icon: 'videocam-outline', label: 'Video', color: '#3B82F6', action: pickVideo },
              { icon: 'document-outline', label: 'File', color: '#8B5CF6', action: pickDocument },
            ].map(({ icon, label, color, action }) => (
              <Pressable key={label} style={st.attachItem} onPress={() => void action()}>
                <View style={[st.attachIcon, { backgroundColor: color }]}>
                  <Icon name={icon as any} size={24} color="#fff" />
                </View>
                <Text style={st.attachLabel}>{label}</Text>
              </Pressable>
            ))}
          </View>
        ) : null}

        {/* Recording bar */}
        {isRecording ? (
          <View style={st.recordingBar}>
            <View style={st.recordingDot} />
            <Text style={st.recordingText}>Recording… {Math.floor((recorderState.durationMillis ?? 0) / 1000)}s</Text>
            <Text style={st.recordingHint}>Release to send</Text>
          </View>
        ) : null}

        {/* Blocked bar */}
        {blockedStatus !== 'none' ? (
          <View style={st.blockedBar}>
            <Icon name="ban-outline" size={18} color={colors.danger} />
            <Text style={st.blockedText}>
              {blockedStatus === 'you_blocked' && 'You blocked this contact.'}
              {blockedStatus === 'you_are_blocked' && 'You have been blocked.'}
              {blockedStatus === 'both_blocked' && 'You have blocked each other.'}
            </Text>
            {(blockedStatus === 'you_blocked' || blockedStatus === 'both_blocked') && (
              <Pressable style={st.unblockBtn} onPress={async () => { try { await unblockUserApi(session.token, conversation.id); await loadBlockStatus(); } catch { Alert.alert('Error', 'Could not unblock.'); } }}>
                <Text style={st.unblockText}>Unblock</Text>
              </Pressable>
            )}
          </View>
        ) : (
          /* Input row */
          <View style={[st.inputRow, { paddingBottom: insets.bottom + 16 }]}>
            <Pressable onPress={() => setShowAttachMenu((v) => !v)} style={st.attachToggle}>
              <Icon name={showAttachMenu ? 'close' : 'attach'} size={22} color={colors.primary} />
            </Pressable>
            <View style={st.inputWrap}>
              <TextInput
                style={st.input}
                placeholder="Message…"
                placeholderTextColor={colors.muted}
                value={chatInput}
                onChangeText={(v) => void handleTypingChange(v)}
                multiline
              />
            </View>
            {chatInput.trim() ? (
              <Pressable onPress={() => void handleSend()} style={st.sendBtn}>
                <Icon name="send" size={18} color="#fff" />
              </Pressable>
            ) : (
              <Pressable
                onPressIn={() => void startVoiceRecording()}
                onPressOut={() => void stopVoiceRecording()}
                style={[st.sendBtn, isRecording && st.sendBtnRec]}
              >
                <Icon name={isRecording ? 'stop' : 'mic'} size={20} color="#fff" />
              </Pressable>
            )}
          </View>
        )}
      </KeyboardAvoidingView>

      {/* Long-press context menu */}
      <Modal visible={!!selectedMessage} transparent animationType="fade" onRequestClose={() => setSelectedMessage(null)}>
        <Pressable style={st.menuOverlay} onPress={() => setSelectedMessage(null)}>
          <View style={st.menu}>
            {/* Reaction picker */}
            <View style={st.emojiRow}>
              {REACTIONS.map((e) => (
                <Pressable key={e} style={st.emojiBtn} onPress={() => void handleReaction(e)}>
                  <Text style={st.emojiText}>{e}</Text>
                </Pressable>
              ))}
            </View>
            <View style={st.menuDivider} />
            <Pressable style={st.menuItem} onPress={() => { setReplyTarget(selectedMessage); setSelectedMessage(null); }}>
              <Icon name="return-down-back-outline" size={18} color={colors.text} />
              <Text style={st.menuItemText}>Reply</Text>
            </Pressable>
            <Pressable style={st.menuItem} onPress={() => { Alert.alert('Copy', selectedMessage?.value ?? ''); setSelectedMessage(null); }}>
              <Icon name="copy-outline" size={18} color={colors.text} />
              <Text style={st.menuItemText}>Copy</Text>
            </Pressable>
            <Pressable style={[st.menuItem, { borderBottomWidth: 0 }]} onPress={handleDelete}>
              <Icon name="trash-outline" size={18} color={colors.danger} />
              <Text style={[st.menuItemText, { color: colors.danger }]}>Delete</Text>
            </Pressable>
          </View>
        </Pressable>
      </Modal>

      {/* Image viewer */}
      <Modal visible={!!viewerImage} transparent animationType="fade" onRequestClose={() => setViewerImage(null)} statusBarTranslucent>
        <View style={st.viewer}>
          <Pressable style={st.viewerClose} onPress={() => setViewerImage(null)}>
            <Icon name="close-circle" size={34} color="#fff" />
          </Pressable>
          {viewerImage ? <Image source={{ uri: viewerImage }} style={st.viewerImg} resizeMode="contain" /> : null}
        </View>
      </Modal>

      {viewerVideo ? <VideoPlayerModal uri={viewerVideo} onClose={() => setViewerVideo(null)} /> : null}
    </Screen>
  );
}

const st = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#E9EDF5' },

  // Header
  header: {
    backgroundColor: '#fff',
    flexDirection: 'row', alignItems: 'center',
    paddingTop: 48, paddingBottom: 12, paddingHorizontal: 8,
    gap: 8,
    borderBottomWidth: 1, borderBottomColor: '#E9EDEF',
    elevation: 3, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.08, shadowRadius: 4,
  },
  headerBtn: { width: 38, height: 38, alignItems: 'center', justifyContent: 'center' },
  headerProfile: { position: 'relative' },
  headerAvatar: { width: 44, height: 44, borderRadius: 22 },
  headerOnlineDot: { position: 'absolute', bottom: 1, right: 1, width: 12, height: 12, borderRadius: 6, backgroundColor: '#22C55E', borderWidth: 2, borderColor: '#fff' },
  headerName: { color: colors.text, fontSize: 17, fontWeight: '700' },
  headerStatus: { color: colors.muted, fontSize: 12, marginTop: 1 },
  headerStatusOnline: { color: '#22C55E', fontWeight: '600' },

  loader: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  list: { paddingHorizontal: 12, paddingTop: 16, paddingBottom: 24, gap: 0 },

  // Swipe row
  swipeRow: { flexDirection: 'row', alignItems: 'center', marginBottom: 6 },
  swipeRowMe: { justifyContent: 'flex-end' },
  swipeRowThem: { justifyContent: 'flex-start' },
  replyIcon: { position: 'absolute', left: 4, padding: 6, zIndex: 0 },

  // Bubble — shared base
  bubbleWrap: {
    maxWidth: '78%',
    minWidth: 80,
    borderRadius: 18,
    paddingHorizontal: 13, paddingTop: 9, paddingBottom: 7,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.08, shadowRadius: 3, elevation: 2,
  },
  // MY message: solid blue, white text, tail top-right
  bubbleWrapMe: {
    backgroundColor: colors.primary,
    borderTopRightRadius: 4,
    alignSelf: 'flex-end',
  },
  // THEIR message: white, dark text, tail top-left
  bubbleWrapThem: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 4,
    alignSelf: 'flex-start',
    borderWidth: 1,
    borderColor: '#EAEEF2',
  },

  // Reply snippet inside bubble
  replySnippet: { borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6, marginBottom: 7, borderLeftWidth: 3 },
  replySnippetMe: { backgroundColor: 'rgba(0,0,0,0.15)', borderLeftColor: 'rgba(255,255,255,0.9)' },
  replySnippetThem: { backgroundColor: '#F0F4FF', borderLeftColor: colors.primary },
  replySnippetName: { fontSize: 11, fontWeight: '700', color: '#F97316', marginBottom: 2 },
  replySnippetText: { fontSize: 12, color: colors.muted },

  msgText: { color: '#1A1A2E', fontSize: 15, lineHeight: 22 },
  msgTextMe: { color: '#FFFFFF' },
  deletedText: { color: 'rgba(255,255,255,0.55)', fontSize: 14, fontStyle: 'italic' },

  metaRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'flex-end', gap: 4, marginTop: 4 },
  timeText: { color: '#8696A0', fontSize: 11 },
  timeTextMe: { color: 'rgba(255,255,255,0.65)' },

  reactions: { flexDirection: 'row', flexWrap: 'wrap', gap: 4, marginTop: 6 },
  reactionChip: { flexDirection: 'row', alignItems: 'center', gap: 3, backgroundColor: 'rgba(255,255,255,0.22)', borderRadius: 12, paddingHorizontal: 7, paddingVertical: 3, borderWidth: 1, borderColor: 'rgba(255,255,255,0.2)' },
  reactionEmoji: { fontSize: 13 },
  reactionCount: { color: '#fff', fontSize: 11, fontWeight: '700' },

  // Bubble variants
  bubbleWrapMedia: {
    paddingHorizontal: 0, paddingTop: 0, paddingBottom: 0,
    overflow: 'hidden',
  },

  // Media fills bubble edge-to-edge
  mediaWrap: { width: 230, height: 190, position: 'relative' },
  mediaImg: { width: '100%', height: '100%' },
  mediaExpandBtn: { position: 'absolute', top: 8, right: 8, backgroundColor: 'rgba(0,0,0,0.45)', borderRadius: 6, padding: 4 },
  videoPlaceholder: { width: '100%', height: '100%', backgroundColor: 'rgba(0,0,0,0.35)', alignItems: 'center', justifyContent: 'center', gap: 8 },
  videoPlayCircle: { width: 58, height: 58, borderRadius: 29, backgroundColor: 'rgba(255,255,255,0.3)', alignItems: 'center', justifyContent: 'center', borderWidth: 2, borderColor: 'rgba(255,255,255,0.6)' },
  videoLabel: { color: 'rgba(255,255,255,0.8)', fontSize: 12, fontWeight: '600' },

  // Meta overlaid on media
  metaRowMedia: {
    position: 'absolute', bottom: 6, right: 8,
    backgroundColor: 'rgba(0,0,0,0.45)',
    borderRadius: 10, paddingHorizontal: 7, paddingVertical: 3,
  },
  timeTextMedia: { color: '#fff' },
  reactionsMedia: { position: 'absolute', bottom: 30, left: 8 },

  // Voice
  voiceRow: { flexDirection: 'row', alignItems: 'center', gap: 10, minWidth: 190 },
  voicePlay: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: 'rgba(0,0,0,0.08)',
    alignItems: 'center', justifyContent: 'center',
  },
  voicePlayMe: { backgroundColor: 'rgba(255,255,255,0.25)' },
  voiceTrackWrap: { flex: 1 },
  voiceTrack: { height: 3, backgroundColor: 'rgba(0,0,0,0.12)', borderRadius: 2, overflow: 'hidden' },
  voiceProgress: { height: '100%', backgroundColor: colors.primary, borderRadius: 2 },
  voiceDuration: { color: '#8696A0', fontSize: 10, marginTop: 3 },
  speedPill: { backgroundColor: 'rgba(0,0,0,0.08)', borderRadius: 10, paddingHorizontal: 8, paddingVertical: 3 },
  speedText: { color: colors.primary, fontSize: 11, fontWeight: '700' },

  // File
  fileRow: { flexDirection: 'row', alignItems: 'center', gap: 10, minWidth: 170 },
  fileIconBox: { width: 40, height: 40, borderRadius: 10, backgroundColor: 'rgba(0,0,0,0.08)', alignItems: 'center', justifyContent: 'center' },
  fileName: { color: '#1A1A2E', fontSize: 13, fontWeight: '600' },
  fileSub: { color: colors.muted, fontSize: 11, marginTop: 1 },

  // Reply bar (composer)
  replyBar: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: '#fff',
    borderTopWidth: 1, borderTopColor: '#E9EDEF',
    paddingHorizontal: 14, paddingVertical: 10, gap: 10,
  },
  replyBarAccent: { width: 3, alignSelf: 'stretch', borderRadius: 2, backgroundColor: colors.primary },
  replyBarName: { color: colors.primary, fontSize: 12, fontWeight: '700' },
  replyBarText: { color: colors.muted, fontSize: 12, marginTop: 1 },
  replyBarClose: { padding: 4 },

  // Attach
  attachMenu: {
    flexDirection: 'row', justifyContent: 'space-around',
    paddingVertical: 18, paddingHorizontal: 12,
    backgroundColor: '#fff',
    borderTopWidth: 1, borderTopColor: '#E9EDEF',
  },
  attachItem: { alignItems: 'center', gap: 7 },
  attachIcon: { width: 56, height: 56, borderRadius: 28, alignItems: 'center', justifyContent: 'center', elevation: 3, shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.12, shadowRadius: 5 },
  attachLabel: { fontSize: 11, color: colors.text, fontWeight: '600' },
  attachToggle: { width: 44, height: 44, borderRadius: 22, backgroundColor: '#F0F2F5', alignItems: 'center', justifyContent: 'center' },

  // Input
  inputRow: {
    flexDirection: 'row', alignItems: 'flex-end', gap: 8,
    paddingHorizontal: 10, paddingVertical: 10,
    backgroundColor: '#E9EDF5',
  },
  inputWrap: {
    flex: 1, backgroundColor: '#fff',
    borderRadius: 26, paddingHorizontal: 16,
    paddingVertical: Platform.OS === 'ios' ? 10 : 7,
    minHeight: 46, justifyContent: 'center',
    elevation: 1, shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.05, shadowRadius: 3,
  },
  input: { maxHeight: 110, fontSize: 15, color: colors.text, lineHeight: 20 },
  sendBtn: {
    width: 46, height: 46, borderRadius: 23,
    backgroundColor: colors.primary,
    alignItems: 'center', justifyContent: 'center',
    elevation: 4, shadowColor: colors.primary, shadowOffset: { width: 0, height: 3 }, shadowOpacity: 0.4, shadowRadius: 6,
  },
  sendBtnRec: { backgroundColor: '#EF4444' },

  // Recording
  recordingBar: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 9, backgroundColor: '#FFF1F1', borderTopWidth: 1, borderTopColor: '#FECACA', gap: 8 },
  recordingDot: { width: 9, height: 9, borderRadius: 5, backgroundColor: '#EF4444' },
  recordingText: { color: '#EF4444', fontSize: 13, fontWeight: '600', flex: 1 },
  recordingHint: { color: '#EF4444', fontSize: 11 },

  // Blocked
  blockedBar: { flexDirection: 'row', alignItems: 'center', gap: 8, paddingHorizontal: 16, paddingVertical: 14, backgroundColor: '#FFF8F8', borderTopWidth: 1, borderTopColor: '#FECACA' },
  blockedText: { flex: 1, color: colors.muted, fontSize: 13 },
  unblockBtn: { paddingHorizontal: 14, paddingVertical: 6, borderRadius: 16, backgroundColor: colors.primary },
  unblockText: { color: '#fff', fontSize: 13, fontWeight: '700' },

  // Context menu
  menuOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.45)', justifyContent: 'flex-end', paddingBottom: 32, paddingHorizontal: 16 },
  menu: { backgroundColor: '#fff', borderRadius: 22, overflow: 'hidden' },
  emojiRow: { flexDirection: 'row', justifyContent: 'space-around', paddingVertical: 16, paddingHorizontal: 8 },
  emojiBtn: { padding: 6 },
  emojiText: { fontSize: 28 },
  menuDivider: { height: 1, backgroundColor: '#F0F2F5', marginHorizontal: 16 },
  menuItem: { flexDirection: 'row', alignItems: 'center', gap: 14, paddingHorizontal: 22, paddingVertical: 15, borderBottomWidth: 1, borderBottomColor: '#F5F5F5' },
  menuItemText: { color: colors.text, fontSize: 15, fontWeight: '500' },

  // Viewer
  viewer: { flex: 1, backgroundColor: '#000', alignItems: 'center', justifyContent: 'center' },
  viewerClose: { position: 'absolute', top: 48, right: 18, zIndex: 10 },
  viewerImg: { width: '100%', height: '85%' },
});
