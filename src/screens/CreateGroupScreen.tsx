import React, { useState } from 'react';
import { Icon } from '../components/Icon';
import { Image, Pressable, ScrollView, StyleSheet, Text, View, Alert, TextInput, FlatList, ActivityIndicator } from 'react-native';
import { Screen } from '../components/Screen';
import { colors } from '../theme/colors';
import type { StackScreenProps } from '@react-navigation/stack';
import type { AppStackParamList, AuthSession } from '../navigation/types';
import { listContactsApi, createGroupApi, type ApiContact } from '../api/chatApi';

type Props = StackScreenProps<AppStackParamList, 'CreateGroup'> & {
    session: AuthSession;
};

export function CreateGroupScreen({ navigation, session }: Props) {
    const [groupName, setGroupName] = useState('');
    const [selectedMembers, setSelectedMembers] = useState<ApiContact[]>([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<ApiContact[]>([]);
    const [loading, setLoading] = useState(false);
    const [creating, setCreating] = useState(false);

    React.useEffect(() => {
        void loadContacts();
    }, []);

    const loadContacts = async () => {
        setLoading(true);
        try {
            const resp = await listContactsApi(session.token);
            const r = resp as any;
            const list = r?.data?.contacts ?? r?.data ?? r?.contacts ?? (Array.isArray(r) ? r : []);
            setSearchResults(Array.isArray(list) ? list : []);
        } catch (error) {
            console.error('Failed to load contacts:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = (q: string) => {
        setSearchQuery(q);
    };

    const toggleMember = (user: ApiContact) => {
        if (selectedMembers.find(m => m.id === user.id)) {
            setSelectedMembers(selectedMembers.filter(m => m.id !== user.id));
        } else {
            setSelectedMembers([...selectedMembers, user]);
        }
    };

    const handleCreate = async () => {
        if (!groupName.trim()) {
            Alert.alert('Error', 'Please enter a group name');
            return;
        }
        if (selectedMembers.length === 0) {
            Alert.alert('Error', 'Please select at least one member');
            return;
        }

        setCreating(true);
        try {
            const resp = await createGroupApi(session.token, {
                name: groupName.trim(),
                member_ids: selectedMembers.map((m) => m.id),
            });
            const groupId = (resp?.data as any)?.id;
            if (groupId) {
                navigation.replace('GroupChat', {
                    groupId,
                    name: groupName.trim(),
                    membersCount: selectedMembers.length + 1,
                });
            } else {
                navigation.navigate('HomeTabs');
            }
        } catch (error) {
            console.error('Failed to create group:', error);
            Alert.alert('Error', 'Failed to create group');
        } finally {
            setCreating(false);
        }
    };

    return (
        <Screen>
            <View style={styles.header}>
                <Pressable onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="close" size={28} color={colors.text} />
                </Pressable>
                <Text style={styles.headerTitle}>New Group</Text>
                <Pressable
                    onPress={handleCreate}
                    disabled={creating || !groupName.trim() || selectedMembers.length === 0}
                    style={({ pressed }) => [
                        styles.createBtn,
                        (creating || !groupName.trim() || selectedMembers.length === 0) && { opacity: 0.5 },
                        pressed && { opacity: 0.7 }
                    ]}
                >
                    {creating ? (
                        <ActivityIndicator size="small" color={colors.primary} />
                    ) : (
                        <Text style={styles.createText}>Create</Text>
                    )}
                </Pressable>
            </View>

            <View style={styles.inputSection}>
                <View style={styles.avatarPlaceholder}>
                    <Icon name="camera" size={32} color={colors.muted} />
                </View>
                <TextInput
                    style={styles.nameInput}
                    placeholder="Group Name"
                    value={groupName}
                    onChangeText={setGroupName}
                    autoFocus
                />
            </View>

            <View style={styles.selectedSection}>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.selectedList}>
                    {selectedMembers.map((member, index) => (
                        <Pressable key={member.id || `sel-${index}`} style={styles.selectedItem} onPress={() => toggleMember(member)}>
                            <View style={styles.selectedAvatar}>
                                <Text style={styles.memberInitial}>{(member.name || member.username || '?').charAt(0)}</Text>
                                <View style={styles.removeIcon}>
                                    <Icon name="close-circle" size={16} color={colors.danger} />
                                </View>
                            </View>
                            <Text style={styles.selectedName} numberOfLines={1}>{(member.name || member.username || 'User').split(' ')[0]}</Text>
                        </Pressable>
                    ))}
                    {selectedMembers.length === 0 && (
                        <Text style={styles.emptySelected}>No members selected</Text>
                    )}
                </ScrollView>
            </View>

            <View style={styles.searchBar}>
                <Icon name="search" size={20} color={colors.muted} />
                <TextInput
                    style={styles.searchInput}
                    placeholder="Add members..."
                    value={searchQuery}
                    onChangeText={handleSearch}
                />
                {loading && <ActivityIndicator size="small" color={colors.primary} style={{ marginLeft: 8 }} />}
            </View>

            <FlatList
                data={searchResults.filter(c => !searchQuery.trim() || c.name?.toLowerCase().includes(searchQuery.toLowerCase()) || c.username?.toLowerCase().includes(searchQuery.toLowerCase()))}
                keyExtractor={(item) => item.id}
                contentContainerStyle={styles.resultsList}
                renderItem={({ item }) => {
                    const isSelected = !!selectedMembers.find(m => m.id === item.id);
                    return (
                        <Pressable
                            style={styles.resultItem}
                            onPress={() => toggleMember(item)}
                        >
                            <View style={styles.memberAvatar}>
                                {false ? (
                                    <Image source={{ uri: '' }} style={styles.avatarImg} />
                                ) : (
                                    <Text style={styles.memberInitial}>{(item.name || item.username || '?').charAt(0)}</Text>
                                )}
                            </View>
                            <View style={styles.memberInfo}>
                                <Text style={styles.memberName}>{item.name || item.username || 'Unknown'}</Text>
                                <Text style={styles.memberUserName}>@{item.username}</Text>
                            </View>
                            <Icon
                                name={isSelected ? "checkmark-circle" : "ellipse-outline"}
                                size={24}
                                color={isSelected ? colors.primary : colors.muted}
                            />
                        </Pressable>
                    );
                }}
                ListEmptyComponent={() => (
                    <View style={styles.emptyResults}>
                        <Text style={styles.emptyText}>No contacts found</Text>
                    </View>
                )}
            />
        </Screen>
    );
}

const styles = StyleSheet.create({
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 16,
        paddingVertical: 12,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: colors.border,
    },
    backBtn: {
        width: 40,
        height: 40,
        alignItems: 'center',
        justifyContent: 'center',
    },
    headerTitle: {
        fontSize: 18,
        fontWeight: '700',
        color: colors.text,
    },
    createBtn: {
        paddingHorizontal: 12,
        paddingVertical: 6,
    },
    createText: {
        fontSize: 16,
        fontWeight: '700',
        color: colors.primary,
    },
    inputSection: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 20,
        backgroundColor: '#fff',
    },
    avatarPlaceholder: {
        width: 60,
        height: 60,
        borderRadius: 30,
        backgroundColor: colors.surface,
        alignItems: 'center',
        justifyContent: 'center',
        borderWidth: 1,
        borderColor: colors.border,
        borderStyle: 'dashed',
    },
    nameInput: {
        flex: 1,
        marginLeft: 16,
        fontSize: 18,
        fontWeight: '600',
        color: colors.text,
        borderBottomWidth: 1,
        borderBottomColor: colors.primary,
        paddingVertical: 8,
    },
    selectedSection: {
        height: 100,
        backgroundColor: '#F8FAFC',
        borderBottomWidth: 1,
        borderBottomColor: colors.border,
    },
    selectedList: {
        paddingHorizontal: 16,
        alignItems: 'center',
    },
    selectedItem: {
        alignItems: 'center',
        marginRight: 16,
        width: 60,
    },
    selectedAvatar: {
        width: 50,
        height: 50,
        borderRadius: 25,
        backgroundColor: colors.surface,
        alignItems: 'center',
        justifyContent: 'center',
        overflow: 'hidden',
    },
    avatarImg: {
        width: '100%',
        height: '100%',
    },
    memberInitial: {
        fontSize: 20,
        fontWeight: '700',
        color: colors.muted,
    },
    removeIcon: {
        position: 'absolute',
        top: -2,
        right: -2,
        backgroundColor: '#fff',
        borderRadius: 10,
    },
    selectedName: {
        fontSize: 11,
        fontWeight: '600',
        color: colors.text,
        marginTop: 4,
    },
    emptySelected: {
        color: colors.muted,
        fontSize: 14,
        fontStyle: 'italic',
    },
    searchBar: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#fff',
        paddingHorizontal: 16,
        height: 56,
        borderBottomWidth: 1,
        borderBottomColor: colors.border,
    },
    searchInput: {
        flex: 1,
        marginLeft: 12,
        fontSize: 16,
        color: colors.text,
    },
    resultsList: {
        paddingBottom: 20,
    },
    resultItem: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 16,
        borderBottomWidth: 1,
        borderBottomColor: '#F8FAFC',
        backgroundColor: '#fff',
    },
    memberAvatar: {
        width: 48,
        height: 48,
        borderRadius: 24,
        backgroundColor: colors.surface,
        alignItems: 'center',
        justifyContent: 'center',
        overflow: 'hidden',
    },
    memberInfo: {
        flex: 1,
        marginLeft: 16,
    },
    memberName: {
        fontSize: 16,
        fontWeight: '700',
        color: colors.text,
    },
    memberUserName: {
        fontSize: 13,
        color: colors.muted,
    },
    emptyResults: {
        alignItems: 'center',
        paddingTop: 40,
    },
    emptyText: {
        color: colors.muted,
        fontSize: 15,
    },
});
