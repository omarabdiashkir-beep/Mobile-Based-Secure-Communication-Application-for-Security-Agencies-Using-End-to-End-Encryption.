import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Image,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
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
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Haptics from 'expo-haptics';
import type { StackScreenProps } from '@react-navigation/stack';
import { colors } from '../theme/colors';
import { TypingBubble } from '../components/TypingBubble';
import {
  getGroupMessagesApi,
  sendGroupMessageApi,
  sendGroupFileMessageApi,
  deleteGroupMessageApi,
  replyToGroupMessageApi,
  reactToGroupMessageApi,
  removeGroupReactionApi,
  markGroupMessagesReadApi,
  type ApiGroupMessage,
} from '../api/chatApi';
import type { AppStackParamList, AuthSession } from '../navigation/types';
import { chatRealtime } from '../api/chatRealtime';

type Props = StackScreenProps<AppStackParamList, 'GroupChat'> & { session: AuthSession };

type Msg = {
  id: string;
  senderId: string;
  senderName: string;
  kind: 'text' | 'image' | 'video' | 'voice' | 'file';
  value: string;
  fileUrl?: string;
  fileName?: string;
  replyToId?: string;
  replyPreview?: string;
  replySenderName?: string;
  isDeleted?: boolean;
  time: string;
  reactions?: { emoji: string }[];
  seenCount?: number;    // how many group members have read this message
  seenBy?: string[];     // names of members who read it
};

function fmtTime(iso?: string) {
  if (!iso) return '';
  const d = new Date(iso);
  return isNaN(d.getTime()) ? '' : d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
}

function fmtDur(s?: number) {
  const t = Math.max(0, Math.floor(s ?? 0));
  return `${Math.floor(t / 60)}:${(t % 60).toString().padStart(2, '0')}`;
}

function VoiceMsg({ uri, isMe }: { uri: string; isMe: boolean }) {
  const player = useAudioPlayer({ uri }, { updateInterval: 250 });
  const st = useAudioPlayerStatus(player);
  const [speed, setSpeed] = useState<1 | 1.5 | 2>(1);
  const toggle = async () => {
    if (st.playing) { player.pause(); return; }
    if (st.didJustFinish) await player.seekTo(0);
    player.play();
  };
  const cycle = async () => {
    const n = speed === 1 ? 1.5 : speed === 1.5 ? 2 : 1;
    setSpeed(n); await player.setPlaybackRate(n);
  };
  return (
    <View style={s.voiceRow}>
      <Pressable style={[s.voiceBtn, isMe && s.voiceBtnMe]} onPress={() => void toggle()}>
        <Icon name={st.playing ? 'pause' : 'play'} size={15} color={isMe ? '#fff' : colors.primary} />
      </Pressable>
      <Text style={s.voiceTime}>{fmtDur(st.currentTime)} / {fmtDur(st.duration)}</Text>
      <Pressable style={s.speedChip} onPress={() => void cycle()}>
        <Text style={s.speedText}>{speed}x</Text>
      </Pressable>
    </View>
  );
}

function VideoModal({ uri, onClose }: { uri: string; onClose: () => void }) {
  const player = useVideoPlayer({ uri }, (p) => { p.play(); });
  return (
    <Modal visible animationType="fade" onRequestClose={onClose} statusBarTranslucent>
      <View style={{ flex: 1, backgroundColor: '#000', justifyContent: 'center' }}>
        <Pressable onPress={onClose} style={{ position: 'absolute', top: 48, right: 18, zIndex: 10 }}>
          <Icon name="close-circle" size={34} color="#fff" />
        </Pressable>
        <VideoView player={player} style={{ width: '100%', height: 300 }} allowsFullscreen allowsPictureInPicture />
      </View>
    </Modal>
  );
}

function mapMsg(item: ApiGroupMessage, _myId: string): Msg {
  const kind: Msg['kind'] = item.type === 'voice' ? 'voice' : item.type === 'image' ? 'image' : item.type === 'video' ? 'video' : item.type === 'document' ? 'file' : 'text';
  const reactions = (item.reactions ?? []).flatMap((r: any) => {
    const emoji = String(r.reaction ?? r.emoji ?? r);
    const users: any[] = Array.isArray(r.users) ? r.users : [];
    if (users.length > 0) return users.map((u: any) => ({ emoji }));
    return Array.from({ length: r.count ?? 1 }, () => ({ emoji }));
  });
  return {
    id: item.id,
    senderId: item.sender_id,
    senderName: item.sender_name ?? 'Unknown',
    kind,
    value: item.file_url ?? item.content ?? '',
    fileUrl: item.file_url,
    fileName: item.file_name,
    replyToId: item.reply_to_id,
    replyPreview: item.reply_to?.content ?? item.reply_content,
    replySenderName: item.reply_to?.sender_name ?? item.reply_sender_name,
    isDeleted: Number(item.is_deleted) === 1,
    time: fmtTime(item.created_at),
    reactions,
    seenCount: Array.isArray((item as any).seen_by) ? (item as any).seen_by.length : ((item as any).seen_count ?? 0),
    seenBy: Array.isArray((item as any).seen_by) ? (item as any).seen_by.map((u: any) => u?.name ?? u?.username ?? String(u)) : [],
  };
}

const FILE_LIMITS: Record<string, number> = {
  image: 10 * 1024 * 1024,
  video: 100 * 1024 * 1024,
  voice: 20 * 1024 * 1024,
  document: 50 * 1024 * 1024,
};

export function GroupChatScreen({ route, navigation, session }: Props) {
  const { groupId, name, avatarUrl, membersCount } = route.params;
  const insets = useSafeAreaInsets();
  const [messages, setMessages] = useState<Msg[]>([]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(true);
  const [replyTarget, setReplyTarget] = useState<Msg | null>(null);
  const [selectedMsg, setSelectedMsg] = useState<Msg | null>(null);
  const [showAttach, setShowAttach] = useState(false);
  const [viewerImage, setViewerImage] = useState<string | null>(null);
  const [viewerVideo, setViewerVideo] = useState<string | null>(null);
  const [typingUser, setTypingUser] = useState('');
  const [typingActivity, setTypingActivity] = useState<'typing' | 'recording'>('typing');
  const flatRef = useRef<FlatList>(null);
  const typingTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const typingClearRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const recorder = useAudioRecorder(RecordingPresets.HIGH_QUALITY);
  const recorderState = useAudioRecorderState(recorder, 200);
  const [isRecording, setIsRecording] = useState(false);
  const recorderReady = useRef(false);

  const hue = name.split('').reduce((a, c) => a + c.charCodeAt(0), 0) % 360;
  const initial = name.charAt(0).toUpperCase();

  const loadHistory = useCallback(async () => {
    try {
      const resp = await getGroupMessagesApi(session.token, groupId);
      const msgs = resp?.data?.messages ?? [];
      setMessages(msgs.map((m) => mapMsg(m, session.userId)));
      if (msgs.length > 0) {
        const ids = msgs.map((m) => Number(m.id)).filter(Boolean);
        void markGroupMessagesReadApi(session.token, groupId, ids);
      }
    } catch { /* silent */ }
    finally { setLoading(false); }
  }, [groupId, session.token, session.userId]);

  // Silent background refresh — no spinner, invisible to user
  const silentRefresh = useCallback(async () => {
    try {
      const resp = await getGroupMessagesApi(session.token, groupId);
      const msgs = resp?.data?.messages ?? [];
      if (msgs.length > 0) {
        setMessages(msgs.map((m) => mapMsg(m, session.userId)));
        // Mark all as read silently every poll
        const ids = msgs.map((m) => Number(m.id)).filter(Boolean);
        void markGroupMessagesReadApi(session.token, groupId, ids);
      }
    } catch { /* silent */ }
  }, [groupId, session.token, session.userId]);

  useEffect(() => {
    void loadHistory();
    // 1-second silent background poll
    const pollInterval = setInterval(() => { void silentRefresh(); }, 1_000);
    const unMsg = chatRealtime.onMessage(() => loadHistory());
    const unTyping = chatRealtime.onTyping((event) => {
      if (event.groupId !== groupId || event.senderId === session.userId) return;
      setTypingActivity(event.activity === 'recording' ? 'recording' : 'typing');
      if (event.isTyping) {
        setTypingUser(event.senderName ?? 'Someone');
        if (typingClearRef.current) clearTimeout(typingClearRef.current);
        typingClearRef.current = setTimeout(() => setTypingUser(''), 3000);
      } else {
        setTypingUser('');
      }
    });
    return () => { clearInterval(pollInterval); unMsg(); unTyping(); };
  }, [loadHistory, silentRefresh, groupId, session.userId]);

  useEffect(() => {
    return () => {
      if (typingTimer.current) clearTimeout(typingTimer.current);
      if (typingClearRef.current) clearTimeout(typingClearRef.current);
    };
  }, []);

  // Pre-prepare recorder
  useEffect(() => {
    const prepare = async () => {
      const { granted } = await requestRecordingPermissionsAsync();
      if (!granted) return;
      await setAudioModeAsync({ allowsRecording: true, playsInSilentMode: true });
      await recorder.prepareToRecordAsync();
      recorderReady.current = true;
    };
    void prepare();
  }, [recorder]);

  const sendText = async () => {
    const text = input.trim();
    if (!text) return;
    void Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    setInput('');
    try {
      await sendGroupMessageApi(session.token, groupId, {
        content: text,
        reply_to_id: replyTarget?.id ?? null,
      });
      setReplyTarget(null);
      await loadHistory();
    } catch {
      Alert.alert('Error', 'Failed to send message.');
      setInput(text);
    }
  };

  const sendFile = async (
    type: 'image' | 'video' | 'voice' | 'document',
    fileUri: string,
    fileName: string,
    mimeType: string,
  ) => {
    try {
      const info = await FileSystem.getInfoAsync(fileUri);
      const limit = FILE_LIMITS[type] ?? 50 * 1024 * 1024;
      if (info.exists && (info as any).size > limit) {
        Alert.alert('File too large', `${type} files must be under ${Math.round(limit / 1024 / 1024)} MB.`);
        return;
      }
    } catch { /* skip size check */ }
    try {
      await sendGroupFileMessageApi(session.token, groupId, {
        type, fileUri, fileName, mimeType,
        caption: fileName,
        reply_to_id: replyTarget?.id ?? null,
      });
      setReplyTarget(null);
      await loadHistory();
    } catch {
      Alert.alert('Error', 'Failed to send file.');
    }
  };

  const pickImage = async () => {
    setShowAttach(false);
    const r = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.85 });
    if (!r.canceled && r.assets[0]) {
      const a = r.assets[0];
      await sendFile('image', a.uri, a.fileName ?? `photo_${Date.now()}.jpg`, a.mimeType ?? 'image/jpeg');
    }
  };

  const pickVideo = async () => {
    setShowAttach(false);
    const r = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['videos'], quality: 0.8 });
    if (!r.canceled && r.assets[0]) {
      const a = r.assets[0];
      await sendFile('video', a.uri, a.fileName ?? `video_${Date.now()}.mp4`, a.mimeType ?? 'video/mp4');
    }
  };

  const recordVideo = async () => {
    setShowAttach(false);
    const perm = await ImagePicker.requestCameraPermissionsAsync();
    if (!perm.granted) { Alert.alert('Permission required', 'Camera access needed.'); return; }
    const r = await ImagePicker.launchCameraAsync({ mediaTypes: ['videos'], videoMaxDuration: 120, quality: 0.8 });
    if (!r.canceled && r.assets[0]) {
      const a = r.assets[0];
      await sendFile('video', a.uri, a.fileName ?? `video_${Date.now()}.mp4`, a.mimeType ?? 'video/mp4');
    }
  };

  const pickAudio = async () => {
    setShowAttach(false);
    const r = await DocumentPicker.getDocumentAsync({ type: 'audio/*', copyToCacheDirectory: true });
    if (!r.canceled && r.assets[0]) {
      const a = r.assets[0];
      await sendFile('voice', a.uri, a.name, a.mimeType ?? 'audio/mpeg');
    }
  };

  const pickDocument = async () => {
    setShowAttach(false);
    const r = await DocumentPicker.getDocumentAsync({ copyToCacheDirectory: true });
    if (!r.canceled && r.assets[0]) {
      const a = r.assets[0];
      await sendFile('document', a.uri, a.name, a.mimeType ?? 'application/octet-stream');
    }
  };

  const startRecording = async () => {
    try {
      const { granted } = await requestRecordingPermissionsAsync();
      if (!granted) { Alert.alert('Permission required', 'Microphone access needed.'); return; }
      await setAudioModeAsync({ allowsRecording: true, playsInSilentMode: true });
      await recorder.prepareToRecordAsync();
      recorder.record();
      setIsRecording(true);
    } catch {
      Alert.alert('Error', 'Could not start recording.');
    }
  };

  const stopRecording = async () => {
    if (!isRecording) return;
    setIsRecording(false);
    try {
      const result = await recorder.stop();
      const uri = (result as any)?.uri ?? recorder.uri;
      if (uri) {
        const fileName = `voice_${Date.now()}.mp4`;
        const dest = `${FileSystem.cacheDirectory}${fileName}`;
        await FileSystem.copyAsync({ from: uri, to: dest });
        await sendFile('voice', dest, fileName, 'audio/mp4');
      }
    } catch {
      Alert.alert('Error', 'Could not send voice message.');
    }
  };

  const handleReaction = async (emoji: string) => {
    if (!selectedMsg) return;
    await reactToGroupMessageApi(session.token, groupId, selectedMsg.id, emoji);
    setSelectedMsg(null);
    await loadHistory();
  };

  const handleDelete = async () => {
    if (!selectedMsg) return;
    await deleteGroupMessageApi(session.token, groupId, selectedMsg.id);
    setSelectedMsg(null);
    await loadHistory();
  };

  const downloadAndOpen = async (url: string, fileName: string) => {
    try {
      const dest = `${FileSystem.cacheDirectory}${fileName}`;
      const existing = await FileSystem.getInfoAsync(dest);
      if (!existing.exists) await FileSystem.downloadAsync(url, dest);
      await Sharing.shareAsync(dest);
    } catch { Alert.alert('Error', 'Could not open file.'); }
  };

  const renderContent = (item: Msg, isMe: boolean) => {
    if (item.kind === 'image' && item.fileUrl) {
      return (
        <Pressable onPress={() => setViewerImage(item.fileUrl!)}>
          <Image source={{ uri: item.fileUrl }} style={s.bubbleImg} resizeMode="cover" />
          <View style={s.imgOverlay}><Icon name="expand-outline" size={14} color="#fff" /></View>
        </Pressable>
      );
    }
    if (item.kind === 'video' && item.fileUrl) {
      return (
        <Pressable onPress={() => setViewerVideo(item.fileUrl!)} style={s.videoThumb}>
          <View style={s.videoThumbInner}><Icon name="play-circle" size={48} color="#fff" /></View>
        </Pressable>
      );
    }
    if (item.kind === 'voice' && item.fileUrl) {
      return <VoiceMsg uri={item.fileUrl} isMe={isMe} />;
    }
    if (item.kind === 'file' && item.fileUrl) {
      const fn = item.fileName || item.value || 'document';
      return (
        <Pressable style={s.fileRow} onPress={() => void downloadAndOpen(item.fileUrl!, fn)}>
          <View style={s.fileIcon}><Icon name="document-text-outline" size={20} color={colors.primary} /></View>
          <View style={{ flex: 1 }}>
            <Text style={s.fileName} numberOfLines={2}>{fn}</Text>
            <Text style={s.fileSub}>Tap to download</Text>
          </View>
          <Icon name="download-outline" size={16} color={colors.muted} />
        </Pressable>
      );
    }
    return <Text style={s.msgText}>{item.value}</Text>;
  };

  return (
    <View style={s.root}>
      <StatusBar barStyle="dark-content" backgroundColor="#fff" />

      {/* Header */}
      <View style={s.header}>
        <Pressable onPress={() => navigation.goBack()} style={s.headerBtn}>
          <Icon name="arrow-back" size={22} color={colors.text} />
        </Pressable>
        <Pressable
          style={s.headerAvatarBtn}
          onPress={() => navigation.navigate('GroupDetail', { groupId, name, avatarUrl })}
        >
          {avatarUrl ? (
            <Image source={{ uri: avatarUrl }} style={s.headerAvatar} />
          ) : (
            <View style={[s.headerAvatar, s.headerAvatarFallback, { backgroundColor: `hsl(${hue},45%,52%)` }]}>
              <Text style={s.headerAvatarInit}>{initial}</Text>
            </View>
          )}
        </Pressable>
        <Pressable
          style={s.headerInfo}
          onPress={() => navigation.navigate('GroupDetail', { groupId, name, avatarUrl })}
        >
          <Text style={s.headerName}>{name}</Text>
          <Text style={s.headerSub}>{membersCount ? `${membersCount} members` : 'Group'}</Text>
        </Pressable>
        <Pressable style={s.headerBtn} onPress={() => navigation.navigate('GroupDetail', { groupId, name, avatarUrl })}>
          <Icon name="information-circle-outline" size={22} color={colors.text} />
        </Pressable>
      </View>

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={Platform.OS === 'ios' ? 0 : 0}
        style={{ flex: 1 }}
      >
        {loading ? (
          <View style={s.loader}><ActivityIndicator size="large" color={colors.primary} /></View>
        ) : (
          <FlatList
            ref={flatRef}
            data={messages}
            keyExtractor={(item) => item.id}
            contentContainerStyle={s.list}
            onContentSizeChange={() => flatRef.current?.scrollToEnd({ animated: true })}
            renderItem={({ item }) => {
              const isMe = item.senderId === session.userId;
              return (
                <Pressable
                  style={[s.bubbleWrap, isMe ? s.bubbleWrapMe : s.bubbleWrapThem]}
                  onLongPress={() => setSelectedMsg(item)}
                >
                  {!isMe && <Text style={s.senderName}>{item.senderName}</Text>}
                  <View style={[s.bubble, isMe ? s.bubbleMe : s.bubbleThem]}>
                    {item.isDeleted ? (
                      <Text style={s.deletedText}>This message was deleted</Text>
                    ) : (
                      <>
                        {item.replyToId ? (
                          <View style={s.replyBlock}>
                            <Text style={s.replyBlockName}>{item.replySenderName ?? 'Reply'}</Text>
                            <Text style={s.replyBlockText} numberOfLines={1}>{item.replyPreview ?? '…'}</Text>
                          </View>
                        ) : null}
                        {renderContent(item, isMe)}
                      </>
                    )}
                    {(item.reactions?.length ?? 0) > 0 && (
                      <View style={s.reactions}>
                        {(Array.from(new Set(item.reactions!.map((r: any) => r.emoji))) as string[]).map((e) => (
                          <View key={e} style={s.reactionChip}>
                            <Text style={s.reactionEmoji}>{e}</Text>
                            <Text style={s.reactionCount}>{item.reactions!.filter((r: any) => r.emoji === e).length}</Text>
                          </View>
                        ))}
                      </View>
                    )}
                    <View style={s.metaRow}>
                      <Text style={[s.timeText, isMe && s.timeTextMe]}>{item.time}</Text>
                      {isMe && !item.isDeleted ? (
                        <Icon
                          name={(item.seenCount ?? 0) > 0 ? 'checkmark-done' : 'checkmark-done'}
                          size={13}
                          color={(item.seenCount ?? 0) > 0 ? '#1A6FE8' : '#94A3B8'}
                        />
                      ) : null}
                    </View>
                  </View>
                </Pressable>
              );
            }}
          />
        )}

        {typingUser ? (
          <TypingBubble senderName={typingUser} activity={typingActivity} />
        ) : null}

        {/* Reply banner */}
        {replyTarget && (
          <View style={s.replyBanner}>
            <View style={s.replyBannerBar} />
            <View style={{ flex: 1 }}>
              <Text style={s.replyBannerName}>{replyTarget.senderName}</Text>
              <Text style={s.replyBannerText} numberOfLines={1}>{replyTarget.value || replyTarget.fileName || replyTarget.kind}</Text>
            </View>
            <Pressable onPress={() => setReplyTarget(null)}>
              <Icon name="close" size={18} color={colors.muted} />
            </Pressable>
          </View>
        )}

        {/* Attach menu */}
        {showAttach && (
          <View style={s.attachMenu}>
            {[
              { icon: 'image-outline', label: 'Image', color: '#4CAF50', action: pickImage },
              { icon: 'videocam-outline', label: 'Video', color: '#2196F3', action: pickVideo },
              { icon: 'radio-button-on-outline', label: 'Camera', color: '#F44336', action: recordVideo },
              { icon: 'musical-notes-outline', label: 'Audio', color: '#FF9800', action: pickAudio },
              { icon: 'document-outline', label: 'Doc', color: '#9C27B0', action: pickDocument },
            ].map(({ icon, label, color, action }) => (
              <Pressable key={label} style={s.attachItem} onPress={() => void action()}>
                <View style={[s.attachIcon, { backgroundColor: color }]}>
                  <Icon name={icon as any} size={22} color="#fff" />
                </View>
                <Text style={s.attachLabel}>{label}</Text>
              </Pressable>
            ))}
          </View>
        )}

        {/* Recording indicator */}
        {isRecording && (
          <View style={s.recordingBar}>
            <View style={s.recordingDot} />
            <Text style={s.recordingText}>
              Recording… {Math.floor((recorderState.durationMillis ?? 0) / 1000)}s — release to send
            </Text>
          </View>
        )}

        {/* Input row */}
        <View style={[s.inputRow, { paddingBottom: insets.bottom + 16 }]}>
          <Pressable style={s.attachToggle} onPress={() => setShowAttach((v) => !v)}>
            <Icon name={showAttach ? 'close' : 'attach'} size={22} color={colors.primary} />
          </Pressable>
          <View style={s.inputWrap}>
            <TextInput
              style={s.input}
              placeholder="Message"
              placeholderTextColor={colors.muted}
              value={input}
              onChangeText={(v) => {
                setInput(v);
                void chatRealtime.sendTyping(groupId, v.trim().length > 0, groupId, 'typing');
                if (typingTimer.current) clearTimeout(typingTimer.current);
                typingTimer.current = setTimeout(() => void chatRealtime.sendTyping(groupId, false, groupId), 1200);
              }}
              multiline
            />
          </View>
          {input.trim() ? (
            <Pressable style={s.sendBtn} onPress={() => void sendText()}>
              <Icon name="send" size={18} color="#fff" />
            </Pressable>
          ) : (
            <Pressable
              style={[s.sendBtn, isRecording && s.sendBtnRec]}
              onPressIn={() => void startRecording()}
              onPressOut={() => void stopRecording()}
            >
              <Icon name={isRecording ? 'stop' : 'mic'} size={18} color="#fff" />
            </Pressable>
          )}
        </View>
      </KeyboardAvoidingView>

      {/* Long-press menu */}
      <Modal visible={!!selectedMsg} transparent animationType="fade" onRequestClose={() => setSelectedMsg(null)}>
        <Pressable style={s.menuOverlay} onPress={() => setSelectedMsg(null)}>
          <View style={s.menu}>
            <Pressable style={s.menuItem} onPress={() => { setReplyTarget(selectedMsg); setSelectedMsg(null); }}>
              <Icon name="return-down-back-outline" size={18} color={colors.text} style={{ marginRight: 10 }} />
              <Text style={s.menuText}>Reply</Text>
            </Pressable>
            <View style={s.reactionPicker}>
              {['❤️', '👍', '😂', '🔥', '🙏'].map((e) => (
                <Pressable key={e} style={s.reactionPickerItem} onPress={() => void handleReaction(e)}>
                  <Text style={{ fontSize: 22 }}>{e}</Text>
                </Pressable>
              ))}
            </View>
            {selectedMsg?.senderId === session.userId && (
              <Pressable style={s.menuItem} onPress={() => void handleDelete()}>
                <Icon name="trash-outline" size={18} color={colors.danger} style={{ marginRight: 10 }} />
                <Text style={[s.menuText, { color: colors.danger }]}>Delete</Text>
              </Pressable>
            )}
          </View>
        </Pressable>
      </Modal>

      {/* Image viewer */}
      <Modal visible={!!viewerImage} transparent animationType="fade" onRequestClose={() => setViewerImage(null)} statusBarTranslucent>
        <View style={s.viewer}>
          <Pressable style={s.viewerClose} onPress={() => setViewerImage(null)}>
            <Icon name="close-circle" size={34} color="#fff" />
          </Pressable>
          {viewerImage && <Image source={{ uri: viewerImage }} style={s.viewerImg} resizeMode="contain" />}
        </View>
      </Modal>

      {viewerVideo ? <VideoModal uri={viewerVideo} onClose={() => setViewerVideo(null)} /> : null}
    </View>
  );
}

const s = StyleSheet.create({
  root: { flex: 1, backgroundColor: '#EEF2FF' },
  header: {
    backgroundColor: '#fff',
    flexDirection: 'row', alignItems: 'center',
    paddingTop: 44, paddingBottom: 10, paddingHorizontal: 6, gap: 2,
    borderBottomWidth: 1, borderBottomColor: '#E9EDEF',
  },
  headerBtn: { width: 38, height: 38, alignItems: 'center', justifyContent: 'center' },
  headerAvatarBtn: { marginLeft: 2, marginRight: 4 },
  headerAvatar: { width: 40, height: 40, borderRadius: 20 },
  headerAvatarFallback: { alignItems: 'center', justifyContent: 'center' },
  headerAvatarInit: { color: '#fff', fontSize: 16, fontWeight: '800' },
  headerInfo: { flex: 1, marginLeft: 2 },
  headerName: { color: colors.text, fontSize: 16, fontWeight: '700' },
  headerSub: { color: colors.muted, fontSize: 12 },
  loader: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  list: { paddingHorizontal: 8, paddingTop: 10, paddingBottom: 220 },
  bubbleWrap: { marginBottom: 4, maxWidth: '82%' },
  bubbleWrapMe: { alignSelf: 'flex-end' },
  bubbleWrapThem: { alignSelf: 'flex-start' },
  senderName: { color: colors.primary, fontSize: 12, fontWeight: '700', marginBottom: 2, marginLeft: 12 },
  bubble: {
    borderRadius: 16, paddingHorizontal: 12, paddingVertical: 8,
    shadowColor: '#000', shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.06, shadowRadius: 2, elevation: 1,
  },
  bubbleMe: { backgroundColor: '#DBEAFE', borderTopRightRadius: 4 },
  bubbleThem: { backgroundColor: '#fff', borderTopLeftRadius: 4 },
  msgText: { color: colors.text, fontSize: 15 },
  metaRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'flex-end', gap: 4, marginTop: 4 },
  timeText: { color: colors.muted, fontSize: 10 },
  timeTextMe: { color: '#5E8062' },
  bubbleImg: { width: 220, height: 175, borderRadius: 10 },
  imgOverlay: { position: 'absolute', bottom: 6, right: 6, backgroundColor: 'rgba(0,0,0,0.4)', borderRadius: 4, padding: 3 },
  videoThumb: { width: 220, height: 140, borderRadius: 10, overflow: 'hidden', backgroundColor: '#111' },
  videoThumbInner: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(0,0,0,0.5)' },
  voiceRow: { flexDirection: 'row', alignItems: 'center', gap: 10, minWidth: 180 },
  voiceBtn: { width: 30, height: 30, borderRadius: 15, backgroundColor: '#E7F7EF', alignItems: 'center', justifyContent: 'center' },
  voiceBtnMe: { backgroundColor: colors.primary },
  voiceTime: { color: colors.text, fontSize: 12, flex: 1 },
  speedChip: { backgroundColor: '#fff', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 10 },
  speedText: { color: colors.primaryDark, fontSize: 11, fontWeight: '700' },
  fileRow: { flexDirection: 'row', alignItems: 'center', gap: 8, minWidth: 160 },
  fileIcon: { width: 38, height: 38, borderRadius: 8, backgroundColor: '#EEF4FF', alignItems: 'center', justifyContent: 'center' },
  fileName: { color: colors.text, fontSize: 13 },
  fileSub: { color: colors.muted, fontSize: 11, marginTop: 1 },
  reactions: { flexDirection: 'row', flexWrap: 'wrap', gap: 4, marginTop: 4 },
  reactionChip: { flexDirection: 'row', alignItems: 'center', gap: 3, backgroundColor: 'rgba(255,255,255,0.7)', borderRadius: 10, paddingHorizontal: 6, paddingVertical: 2 },
  deletedText: { color: colors.muted, fontSize: 13, fontStyle: 'italic' },
  reactionEmoji: { fontSize: 12 },
  reactionCount: { color: colors.muted, fontSize: 11, fontWeight: '700' },
  replyBlock: {
    marginBottom: 6, paddingLeft: 8,
    borderLeftWidth: 3, borderLeftColor: colors.primary,
    borderRadius: 2,
  },
  replyBlockName: { color: colors.primaryDark, fontSize: 11, fontWeight: '700' },
  replyBlockText: { color: colors.muted, fontSize: 11, marginTop: 1 },
  replyBanner: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    backgroundColor: '#fff', borderTopWidth: 1, borderTopColor: '#E9EDEF',
    paddingHorizontal: 14, paddingVertical: 10,
  },
  replyBannerBar: { width: 3, height: '100%', borderRadius: 2, backgroundColor: colors.primary, alignSelf: 'stretch' },
  replyBannerName: { color: colors.primary, fontSize: 12, fontWeight: '700' },
  replyBannerText: { color: colors.muted, fontSize: 12, marginTop: 1 },
  attachMenu: {
    flexDirection: 'row', justifyContent: 'space-around',
    paddingVertical: 16, paddingHorizontal: 8,
    backgroundColor: '#E8EEFF', borderTopWidth: 1, borderTopColor: '#C7D2F8',
  },
  attachItem: { alignItems: 'center', gap: 6 },
  attachIcon: { width: 52, height: 52, borderRadius: 26, alignItems: 'center', justifyContent: 'center', elevation: 3 },
  attachLabel: { fontSize: 11, color: colors.text, fontWeight: '600' },
  attachToggle: { width: 44, height: 44, borderRadius: 22, backgroundColor: '#fff', alignItems: 'center', justifyContent: 'center' },
  recordingBar: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 6, backgroundColor: '#FFF3F3', borderTopWidth: 1, borderTopColor: '#FFCDD2' },
  recordingDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: '#F44336', marginRight: 8 },
  recordingText: { fontSize: 13, color: '#F44336' },
  inputRow: { flexDirection: 'row', alignItems: 'flex-end', gap: 6, paddingHorizontal: 8, paddingVertical: 8, backgroundColor: '#EEF2FF' },
  inputWrap: { flex: 1, backgroundColor: '#fff', borderRadius: 24, paddingHorizontal: 14, paddingVertical: Platform.OS === 'ios' ? 10 : 6, minHeight: 44, justifyContent: 'center' },
  input: { maxHeight: 110, fontSize: 15, color: colors.text },
  sendBtn: { width: 44, height: 44, borderRadius: 22, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  sendBtnRec: { backgroundColor: '#F44336' },
  menuOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.3)', justifyContent: 'flex-end', padding: 16 },
  menu: { backgroundColor: '#fff', borderRadius: 20, overflow: 'hidden' },
  menuItem: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, paddingVertical: 16, borderBottomWidth: 1, borderBottomColor: '#F5F5F5' },
  menuText: { color: colors.text, fontSize: 16, fontWeight: '500' },
  reactionPicker: { flexDirection: 'row', justifyContent: 'space-around', paddingHorizontal: 12, paddingVertical: 12 },
  reactionPickerItem: { padding: 6 },
  viewer: { flex: 1, backgroundColor: 'rgba(0,0,0,0.95)', alignItems: 'center', justifyContent: 'center' },
  viewerClose: { position: 'absolute', top: 48, right: 18, zIndex: 10 },
  viewerImg: { width: '100%', height: '85%' },
});
