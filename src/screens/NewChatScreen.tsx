import React, { useCallback, useEffect, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import type { StackScreenProps } from '@react-navigation/stack';
import { Screen } from '../components/Screen';
import { colors } from '../theme/colors';
import { addContactApi, listContactsApi, type ApiContact } from '../api/chatApi';
import type { AppStackParamList, AuthSession, ConversationItem } from '../navigation/types';

type Props = StackScreenProps<AppStackParamList, 'NewChat'> & {
  session: AuthSession;
  onOpenChat: (conversation: ConversationItem) => void;
};

export function NewChatScreen({ navigation, session, onOpenChat }: Props) {
  const [contacts, setContacts] = useState<ApiContact[]>([]);
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(true);
  const [addModalVisible, setAddModalVisible] = useState(false);
  const [addPhone, setAddPhone] = useState('');
  const [adding, setAdding] = useState(false);

  const loadContacts = useCallback(async () => {
    setLoading(true);
    try {
      const resp = await listContactsApi(session.token) as any;
      // Handle both { data: { contacts: [] } } and { contacts: [] } and []
      const contacts =
        resp?.data?.contacts ??
        resp?.data ??
        resp?.contacts ??
        (Array.isArray(resp) ? resp : []);
      setContacts(Array.isArray(contacts) ? contacts : []);
    } catch (err) {
      console.error('Failed to load contacts', err);
    } finally {
      setLoading(false);
    }
  }, [session.token]);

  useEffect(() => {
    void loadContacts();
  }, [loadContacts]);

  const filtered = contacts.filter((c) => {
    const q = query.trim().toLowerCase();
    if (!q) return true;
    return (
      c.name?.toLowerCase().includes(q) ||
      c.username?.toLowerCase().includes(q) ||
      c.phone?.includes(q) ||
      c.nickname?.toLowerCase().includes(q)
    );
  });

  const openChat = (contact: ApiContact) => {
    const conversation: ConversationItem = {
      id: contact.id,
      name: contact.nickname || contact.name || contact.username || 'Unknown',
      isGroup: false,
      preview: '',
      time: '',
      unread: 0,
      online: !!contact.is_online,
    };
    onOpenChat(conversation);
  };

  const handleAddContact = async () => {
    if (!addPhone.trim()) {
      Alert.alert('Required', 'Enter a phone number to add.');
      return;
    }
    setAdding(true);
    try {
      const resp = await addContactApi(session.token, addPhone.trim()) as any;
      const ok = resp?.status === 'success' || resp?.status === true || resp?.data?.contact || resp?.data;
      if (ok) {
        setAddModalVisible(false);
        setAddPhone('');
        await loadContacts();
        Alert.alert('Contact added', resp?.message ?? 'Contact added successfully.');
      } else {
        Alert.alert('Failed', resp?.message ?? 'Could not add contact. Check the phone number.');
      }
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : 'Could not add contact.';
      Alert.alert('Error', msg);
    } finally {
      setAdding(false);
    }
  };

  return (
    <Screen style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <Pressable style={styles.backBtn} onPress={() => navigation.goBack()}>
          <Icon name="arrow-back" size={22} color={colors.text} />
        </Pressable>
        <Text style={styles.headerTitle}>New Message</Text>
        <Pressable style={styles.addBtn} onPress={() => setAddModalVisible(true)}>
          <Icon name="person-add-outline" size={22} color={colors.text} />
        </Pressable>
      </View>

      {/* Search */}
      <View style={styles.searchRow}>
        <Icon name="search-outline" size={18} color={colors.muted} />
        <TextInput
          style={styles.searchInput}
          placeholder="Search contacts…"
          placeholderTextColor={colors.muted}
          value={query}
          onChangeText={setQuery}
          autoCapitalize="none"
        />
        {query.length > 0 && (
          <Pressable onPress={() => setQuery('')}>
            <Icon name="close-circle" size={18} color={colors.muted} />
          </Pressable>
        )}
      </View>

      {/* Contact list */}
      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={colors.primary} />
        </View>
      ) : (
        <FlatList
          data={filtered}
          keyExtractor={(item) => item.id}
          contentContainerStyle={filtered.length === 0 ? styles.emptyContainer : undefined}
          renderItem={({ item }) => (
            <Pressable style={styles.contactRow} onPress={() => openChat(item)}>
              <View style={styles.avatar}>
                <Text style={styles.avatarInitial}>
                  {(item.nickname || item.name || item.username || '?').charAt(0).toUpperCase()}
                </Text>
              </View>
              <View style={styles.info}>
                <Text style={styles.contactName} numberOfLines={1}>
                  {item.nickname || item.name || item.username || 'Unknown'}
                </Text>
                {item.phone ? (
                  <Text style={styles.contactPhone} numberOfLines={1}>{item.phone}</Text>
                ) : null}
              </View>
              {!!item.is_online && <View style={styles.onlineDot} />}
              <Icon name="chevron-forward" size={18} color={colors.muted} />
            </Pressable>
          )}
          ListEmptyComponent={
            <View style={styles.emptyState}>
              <Icon name="people-outline" size={56} color={colors.muted} />
              <Text style={styles.emptyTitle}>No contacts yet</Text>
              <Text style={styles.emptySubtitle}>
                Tap the{' '}
                <Text style={styles.emptyLink} onPress={() => setAddModalVisible(true)}>
                  add contact
                </Text>{' '}
                button to get started.
              </Text>
            </View>
          }
        />
      )}

      {/* Add Contact Modal */}
      <Modal
        visible={addModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setAddModalVisible(false)}
      >
        <KeyboardAvoidingView
          style={styles.modalBackdrop}
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        >
          <Pressable style={styles.modalBackdropTap} onPress={() => setAddModalVisible(false)} />
          <View style={styles.sheet}>
            <View style={styles.sheetHandle} />
            <Text style={styles.sheetTitle}>Add Contact</Text>
            <Text style={styles.sheetLabel}>PHONE NUMBER</Text>
            <TextInput
              style={styles.sheetInput}
              placeholder="e.g. +1234567890"
              placeholderTextColor={colors.muted}
              keyboardType="phone-pad"
              value={addPhone}
              onChangeText={setAddPhone}
            />
            <Pressable
              style={[styles.sheetBtn, adding && styles.sheetBtnDisabled]}
              onPress={() => void handleAddContact()}
              disabled={adding}
            >
              {adding ? (
                <ActivityIndicator color="#FFFFFF" />
              ) : (
                <Text style={styles.sheetBtnText}>Add Contact</Text>
              )}
            </Pressable>
            <Pressable style={styles.sheetCancel} onPress={() => setAddModalVisible(false)}>
              <Text style={styles.sheetCancelText}>Cancel</Text>
            </Pressable>
          </View>
        </KeyboardAvoidingView>
      </Modal>
    </Screen>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  header: {
    backgroundColor: '#fff',
    flexDirection: 'row',
    alignItems: 'center',
    paddingTop: 48,
    paddingHorizontal: 14,
    paddingBottom: 14,
    gap: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#E9EDEF',
  },
  backBtn: {
    padding: 4,
  },
  headerTitle: {
    flex: 1,
    color: colors.text,
    fontSize: 20,
    fontWeight: '800',
  },
  addBtn: {
    padding: 4,
  },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    margin: 12,
    paddingHorizontal: 14,
    height: 44,
    backgroundColor: '#FFFFFF',
    borderRadius: 22,
    borderWidth: 1,
    borderColor: colors.border,
  },
  searchInput: {
    flex: 1,
    color: colors.text,
    fontSize: 14,
  },
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  contactRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 13,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#EEF1F3',
    gap: 12,
  },
  avatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: colors.primary + '22',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarInitial: {
    color: colors.primaryDark,
    fontSize: 20,
    fontWeight: '800',
  },
  info: {
    flex: 1,
  },
  contactName: {
    color: colors.text,
    fontSize: 16,
    fontWeight: '700',
  },
  contactPhone: {
    color: colors.muted,
    fontSize: 13,
    marginTop: 2,
  },
  onlineDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: colors.secondary,
  },
  emptyContainer: {
    flex: 1,
  },
  emptyState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
    paddingTop: 60,
    gap: 12,
  },
  emptyTitle: {
    color: colors.text,
    fontSize: 20,
    fontWeight: '800',
  },
  emptySubtitle: {
    color: colors.muted,
    fontSize: 14,
    textAlign: 'center',
    lineHeight: 22,
  },
  emptyLink: {
    color: colors.primary,
    fontWeight: '700',
  },
  // Modal sheet
  modalBackdrop: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  modalBackdropTap: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.35)',
  },
  sheet: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingHorizontal: 20,
    paddingBottom: 36,
    paddingTop: 14,
  },
  sheetHandle: {
    width: 36,
    height: 4,
    borderRadius: 2,
    backgroundColor: colors.border,
    alignSelf: 'center',
    marginBottom: 18,
  },
  sheetTitle: {
    color: colors.text,
    fontSize: 18,
    fontWeight: '800',
    marginBottom: 20,
  },
  sheetLabel: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '700',
    marginBottom: 6,
    marginTop: 14,
    letterSpacing: 0.5,
  },
  sheetInput: {
    height: 48,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
    paddingHorizontal: 14,
    color: colors.text,
    fontSize: 15,
  },
  sheetBtn: {
    marginTop: 24,
    height: 50,
    borderRadius: 25,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetBtnDisabled: {
    opacity: 0.6,
  },
  sheetBtnText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '800',
  },
  sheetCancel: {
    marginTop: 12,
    height: 46,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sheetCancelText: {
    color: colors.muted,
    fontSize: 14,
    fontWeight: '700',
  },
});
