import { apiRequest } from './http';

// ─── Shared wrapper ───────────────────────────────────────────────────────────

type ApiResponse<T> = {
  status?: boolean | string;
  message?: string;
  data: T;
};

// ─── Types ────────────────────────────────────────────────────────────────────

export type ApiUser = {
  id: string;
  name: string;
  username?: string;
  email?: string;
  phone?: string;
  bio?: string;
  address?: string;
  occupation?: string;
  photo_url?: string;
  is_online?: boolean;
  last_seen?: string;
  contacts_count?: number;
  messages_sent?: number;
  is_blocked?: boolean;
};

export type ApiContact = {
  id: string;
  name: string;
  username?: string;
  phone?: string;
  bio?: string;
  photo_url?: string;
  is_online?: number | boolean;
  nickname?: string;
};

export type ApiConversation = {
  id: string;
  contact_id: string;
  contact_name: string;
  contact_username?: string;
  contact_photo?: string | null;
  contact_photo_url?: string | null; // legacy fallback
  contact_is_online?: boolean;
  contact_last_seen?: string | null;
  contact_last_seen_text?: string | null;
  last_message_status?: 'sent' | 'delivered' | 'read' | null;
  last_delivered_at?: string | null;
  last_read_at?: string | null;
  sender_id?: number;
  receiver_id?: number;
  content: string;
  file_url?: string | null;
  type: string;
  unread_count: number;
  created_at: string;
};

export type ApiMessageReaction = {
  reaction: string;
  count: number;
  users: string[];
};

export type ApiChatMessage = {
  id: string;
  sender_id: string;
  receiver_id: string;
  type: string;
  content: string;
  file_url?: string;
  file_name?: string;
  file_size?: number;
  file_mime?: string;
  reply_to_id?: string;
  reply_content?: string;
  reply_sender_name?: string;
  reply_to?: {
    id: string;
    type: string;
    content: string;
    sender_id: string;
    sender_name?: string;
  };
  delivery_status?: string;
  read_at?: string;
  reactions?: ApiMessageReaction[];
  is_deleted?: number;
  created_at: string;
};

export type ApiGroup = {
  id: string;
  name: string;
  description?: string;
  type?: string;
  photo_url?: string;
  created_by?: string;
  members?: ApiGroupMember[];
  members_count?: number;
  created_at?: string;
};

export type ApiGroupMember = {
  id: string;
  name: string;
  username?: string;
  photo_url?: string;
  role?: string;
  joined_at?: string;
};

export type ApiGroupMessage = {
  id: string;
  group_id: string;
  sender_id: string;
  sender_name?: string;
  sender_photo?: string;
  type: string;
  content: string;
  file_url?: string;
  file_name?: string;
  file_size?: number;
  file_mime?: string;
  reply_to_id?: string;
  reply_content?: string;
  reply_sender_name?: string;
  reply_to?: {
    id: string;
    type: string;
    content: string;
    sender_id: string;
    sender_name?: string;
  };
  reactions?: ApiMessageReaction[];
  is_deleted?: number;
  created_at: string;
};

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// AUTH
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

export async function logoutApi(token: string) {
  return apiRequest<{ status: boolean; message: string }>('/api/auth/logout', {
    method: 'POST',
    token,
  });
}

export async function changePasswordApi(
  token: string,
  payload: {
    id: string | number;
    email: string;
    old_password: string;
    password: string;
    password_confirm: string;
  },
) {
  return apiRequest<{ status: boolean; message: string }>('/api/auth/change-password', {
    method: 'POST',
    token,
    body: payload,
  });
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// USER
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

export async function getMeApi(token: string) {
  return apiRequest<ApiResponse<ApiUser>>('/api/user/me', { token });
}

export async function getUserByIdApi(token: string, userId: string) {
  return apiRequest<ApiUser>(`/api/user/${userId}`, { token });
}

export async function updateProfileApi(
  token: string,
  payload: {
    name?: string;
    username?: string;
    phone?: string;
    bio?: string;
    occupation?: string;
    address?: string;
    photoUri?: string;
  },
) {
  const form = new FormData();
  if (payload.name       !== undefined) form.append('name',       payload.name);
  if (payload.username   !== undefined) form.append('username',   payload.username);
  if (payload.phone      !== undefined) form.append('phone',      payload.phone);
  if (payload.bio        !== undefined) form.append('bio',        payload.bio);
  if (payload.occupation !== undefined) form.append('occupation', payload.occupation);
  if (payload.address    !== undefined) form.append('address',    payload.address);
  if (payload.photoUri) {
    const rawExt = payload.photoUri.split('.').pop()?.split('?')[0] ?? 'jpg';
    const ext = rawExt.toLowerCase();
    form.append('photo', {
      uri: payload.photoUri,
      name: `photo.${ext}`,
      type: ext === 'png' ? 'image/png' : ext === 'webp' ? 'image/webp' : 'image/jpeg',
    } as unknown as Blob);
  }
  return apiRequest<ApiResponse<ApiUser>>('/api/user/update-profile', {
    method: 'POST',
    token,
    body: form,
  });
}

export async function toggle2FAApi(token: string, enabled: boolean) {
  return apiRequest<ApiResponse<{ '2FA': boolean }>>('/api/user/2fa', {
    method: 'POST',
    token,
    body: { enabled },
  });
}

export async function setOnlineApi(token: string) {
  return apiRequest<ApiResponse<{ is_online: boolean; last_seen: string }>>('/api/user/online', {
    method: 'POST',
    token,
  });
}

export async function setOfflineApi(token: string) {
  return apiRequest<ApiResponse<{ is_online: boolean; last_seen: string }>>('/api/user/offline', {
    method: 'POST',
    token,
  });
}

export async function getUserStatusApi(token: string, userId: string) {
  return apiRequest<ApiResponse<{ user_id: number; is_online: boolean; last_seen: string }>>(
    `/api/user/${userId}/status`,
    { token },
  );
}

export async function getBlockStatusApi(token: string, userId: string) {
  return apiRequest<{
    status: string;
    data: {
      user_id: number;
      blocked_status: 'none' | 'you_blocked' | 'you_are_blocked' | 'both_blocked';
      you_blocked: boolean;
      they_blocked: boolean;
    };
  }>(`/api/user/${userId}/block-status`, { token });
}

export async function blockUserApi(token: string, userId: string) {
  return apiRequest<{ status: boolean; message: string }>(`/api/user/block/${userId}`, {
    method: 'POST',
    token,
  });
}

export async function unblockUserApi(token: string, userId: string) {
  return apiRequest<{ status: boolean; message: string }>(`/api/user/unblock/${userId}`, {
    method: 'POST',
    token,
  });
}

export async function getBlockedUsersApi(token: string) {
  return apiRequest<ApiResponse<ApiUser[]>>('/api/user/blocked', { token });
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// CONTACTS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

export async function addContactApi(token: string, phone: string) {
  return apiRequest<ApiResponse<{ contact: ApiContact }>>('/api/contacts/add', {
    method: 'POST',
    token,
    body: { phone },
  });
}

export async function listContactsApi(token: string) {
  return apiRequest<ApiResponse<{ total: number; contacts: ApiContact[] }>>('/api/contacts/', {
    token,
  });
}

export async function getContactProfileApi(token: string, userId: string) {
  return apiRequest<ApiResponse<ApiContact>>(`/api/contacts/${userId}/profile`, { token });
}

export async function editContactNicknameApi(token: string, userId: string, nickname: string) {
  return apiRequest<ApiResponse<{ contact_user_id: number; nickname: string }>>(
    `/api/contacts/${userId}`,
    { method: 'PUT', token, body: { nickname } },
  );
}

export async function removeContactApi(token: string, userId: string) {
  return apiRequest<{ status: boolean; message: string }>(`/api/contacts/${userId}`, {
    method: 'DELETE',
    token,
  });
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 1-TO-1 MESSAGES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

export async function sendMessageApi(
  token: string,
  payload: {
    receiver_id: string | number;
    content: string;
    reply_to_id?: string | null;
  },
) {
  return apiRequest<ApiResponse<ApiChatMessage>>('/api/messages/send', {
    method: 'POST',
    token,
    body: payload,
  });
}

export async function sendFileMessageApi(
  token: string,
  payload: {
    receiver_id: string | number;
    type: 'image' | 'video' | 'voice' | 'document';
    fileUri: string;
    fileName: string;
    mimeType: string;
    caption?: string;
    reply_to_id?: string | null;
  },
) {
  const form = new FormData();
  form.append('receiver_id', String(payload.receiver_id));
  form.append('type', payload.type);
  form.append('file', {
    uri: payload.fileUri,
    name: payload.fileName,
    type: payload.mimeType,
  } as unknown as Blob);
  if (payload.caption)    form.append('content',     payload.caption);
  if (payload.reply_to_id) form.append('reply_to_id', String(payload.reply_to_id));
  return apiRequest<ApiResponse<ApiChatMessage>>('/api/messages/send', {
    method: 'POST',
    token,
    body: form,
  });
}

export async function getInboxApi(token: string) {
  return apiRequest<{ status: boolean; data: ApiConversation[] }>('/api/messages/inbox', { token });
}

export async function getConversationApi(token: string, userId: string, page = 1, limit = 50) {
  return apiRequest<ApiResponse<{ page: number; limit: number; messages: ApiChatMessage[] }>>(
    `/api/messages/${userId}?page=${page}&limit=${limit}`,
    { token },
  );
}

export async function deleteMessageApi(token: string, messageId: string) {
  return apiRequest<{ status: boolean; message: string }>(`/api/messages/${messageId}`, {
    method: 'DELETE',
    token,
  });
}

export async function replyToMessageApi(
  token: string,
  messageId: string,
  payload: { content: string },
) {
  return apiRequest<ApiResponse<ApiChatMessage>>(
    `/api/messages/${messageId}/reply`,
    { method: 'POST', token, body: payload },
  );
}

export async function reactToMessageApi(token: string, messageId: string, reaction: string) {
  return apiRequest<{ status: boolean; message: string }>(`/api/messages/${messageId}/react`, {
    method: 'POST',
    token,
    body: { reaction },
  });
}

export async function removeReactionApi(token: string, messageId: string) {
  return apiRequest<{ status: boolean; message: string }>(`/api/messages/${messageId}/react`, {
    method: 'DELETE',
    token,
  });
}

export async function getReactionsApi(token: string, messageId: string) {
  return apiRequest<ApiResponse<{ message_id: number; total: number; reactions: ApiMessageReaction[] }>>(
    `/api/messages/${messageId}/reactions`,
    { token },
  );
}

export async function markAsReadApi(
  token: string,
  payload: { message_ids?: number[]; sender_id?: number },
) {
  return apiRequest<{ status: boolean; message: string; data: { marked_count: number } }>(
    '/api/messages/read',
    { method: 'POST', token, body: payload },
  );
}

export async function getUnreadCountApi(token: string) {
  return apiRequest<ApiResponse<{ unread_count: number }>>('/api/messages/unread-count', { token });
}

export async function getSharedImagesApi(token: string, userId: string, page = 1) {
  return apiRequest<ApiResponse<ApiChatMessage[]>>(
    `/api/messages/${userId}/images?page=${page}&limit=50`, { token });
}

export async function getSharedVideosApi(token: string, userId: string, page = 1) {
  return apiRequest<ApiResponse<ApiChatMessage[]>>(
    `/api/messages/${userId}/videos?page=${page}&limit=50`, { token });
}

export async function getVoiceMessagesApi(token: string, userId: string, page = 1) {
  return apiRequest<ApiResponse<ApiChatMessage[]>>(
    `/api/messages/${userId}/voices?page=${page}&limit=50`, { token });
}

export async function getSharedDocumentsApi(token: string, userId: string, page = 1) {
  return apiRequest<ApiResponse<ApiChatMessage[]>>(
    `/api/messages/${userId}/documents?page=${page}&limit=50`, { token });
}

export async function getRepliesApi(token: string, userId: string, page = 1) {
  return apiRequest<ApiResponse<ApiChatMessage[]>>(
    `/api/messages/${userId}/replies?page=${page}&limit=50`, { token });
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// GROUPS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

export async function createGroupApi(
  token: string,
  payload: { name: string; description?: string; type?: string; member_ids: (string | number)[] },
) {
  return apiRequest<ApiResponse<ApiGroup>>('/api/groups/', {
    method: 'POST',
    token,
    body: payload,
  });
}

export async function listGroupsApi(token: string) {
  return apiRequest<ApiResponse<ApiGroup[]>>('/api/groups/', { token });
}

export async function getGroupApi(token: string, groupId: string) {
  return apiRequest<ApiResponse<ApiGroup>>(`/api/groups/${groupId}`, { token });
}

export async function updateGroupApi(
  token: string,
  groupId: string,
  payload: { name?: string; description?: string },
) {
  return apiRequest<ApiResponse<ApiGroup>>(`/api/groups/${groupId}`, {
    method: 'PUT',
    token,
    body: payload,
  });
}

export async function deleteGroupApi(token: string, groupId: string) {
  return apiRequest<{ status: boolean; message: string }>(`/api/groups/${groupId}`, {
    method: 'DELETE',
    token,
  });
}

export async function addGroupMemberApi(token: string, groupId: string, userId: string) {
  return apiRequest<{ status: boolean; message: string }>(`/api/groups/${groupId}/members`, {
    method: 'POST',
    token,
    body: { user_id: userId },
  });
}

export async function removeGroupMemberApi(token: string, groupId: string, userId: string) {
  return apiRequest<{ status: boolean; message: string }>(
    `/api/groups/${groupId}/members/${userId}`,
    { method: 'DELETE', token },
  );
}

export async function leaveGroupApi(token: string, groupId: string) {
  return apiRequest<{ status: boolean; message: string }>(`/api/groups/${groupId}/leave`, {
    method: 'POST',
    token,
  });
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// GROUP MESSAGES
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

export async function sendGroupMessageApi(
  token: string,
  groupId: string,
  payload: { content: string; reply_to_id?: string | null },
) {
  return apiRequest<ApiResponse<ApiGroupMessage>>(`/api/groups/${groupId}/messages/send`, {
    method: 'POST',
    token,
    body: { type: 'text', ...payload },
  });
}

export async function sendGroupFileMessageApi(
  token: string,
  groupId: string,
  payload: {
    type: 'image' | 'video' | 'voice' | 'document';
    fileUri: string;
    fileName: string;
    mimeType: string;
    caption?: string;
    reply_to_id?: string | null;
  },
) {
  const form = new FormData();
  form.append('type', payload.type);
  form.append('file', {
    uri: payload.fileUri,
    name: payload.fileName,
    type: payload.mimeType,
  } as unknown as Blob);
  if (payload.caption)     form.append('content',      payload.caption);
  if (payload.reply_to_id) form.append('reply_to_id',  String(payload.reply_to_id));
  return apiRequest<ApiResponse<ApiGroupMessage>>(`/api/groups/${groupId}/messages/send`, {
    method: 'POST',
    token,
    body: form,
  });
}

export async function getGroupMessagesApi(token: string, groupId: string, page = 1, limit = 50) {
  return apiRequest<ApiResponse<{ page: number; limit: number; messages: ApiGroupMessage[] }>>(
    `/api/groups/${groupId}/messages?page=${page}&limit=${limit}`,
    { token },
  );
}

export async function deleteGroupMessageApi(token: string, groupId: string, messageId: string) {
  return apiRequest<{ status: boolean; message: string }>(
    `/api/groups/${groupId}/messages/${messageId}`,
    { method: 'DELETE', token },
  );
}

export async function replyToGroupMessageApi(
  token: string,
  groupId: string,
  messageId: string,
  payload: { content: string },
) {
  return apiRequest<ApiResponse<ApiGroupMessage>>(
    `/api/groups/${groupId}/messages/${messageId}/reply`,
    { method: 'POST', token, body: payload },
  );
}

export async function reactToGroupMessageApi(
  token: string,
  groupId: string,
  messageId: string,
  reaction: string,
) {
  return apiRequest<{ status: boolean; message: string }>(
    `/api/groups/${groupId}/messages/${messageId}/react`,
    { method: 'POST', token, body: { reaction } },
  );
}

export async function removeGroupReactionApi(token: string, groupId: string, messageId: string) {
  return apiRequest<{ status: boolean; message: string }>(
    `/api/groups/${groupId}/messages/${messageId}/react`,
    { method: 'DELETE', token },
  );
}

export async function getGroupReactionsApi(token: string, groupId: string, messageId: string) {
  return apiRequest<ApiResponse<{ message_id: number; total: number; reactions: ApiMessageReaction[] }>>(
    `/api/groups/${groupId}/messages/${messageId}/reactions`,
    { token },
  );
}

export async function markGroupMessagesReadApi(token: string, groupId: string, messageIds: number[]) {
  return apiRequest<{ status: boolean; message: string }>(
    `/api/groups/${groupId}/messages/mark-read`,
    { method: 'POST', token, body: { message_ids: messageIds } },
  );
}

export async function getGroupMessageSeenByApi(token: string, groupId: string, messageId: string) {
  return apiRequest<ApiResponse<{
    message_id: number;
    seen_count: number;
    total_members: number;
    seen_by: { id: number; name: string; read_at: string }[];
  }>>(
    `/api/groups/${groupId}/messages/${messageId}/seen-by`,
    { token },
  );
}

export async function getGroupSharedImagesApi(token: string, groupId: string, page = 1) {
  return apiRequest<ApiResponse<ApiGroupMessage[]>>(
    `/api/groups/${groupId}/messages/images?page=${page}&limit=50`, { token });
}

export async function getGroupSharedVideosApi(token: string, groupId: string, page = 1) {
  return apiRequest<ApiResponse<ApiGroupMessage[]>>(
    `/api/groups/${groupId}/messages/videos?page=${page}&limit=50`, { token });
}

export async function getGroupVoiceMessagesApi(token: string, groupId: string, page = 1) {
  return apiRequest<ApiResponse<ApiGroupMessage[]>>(
    `/api/groups/${groupId}/messages/voices?page=${page}&limit=50`, { token });
}

export async function getGroupSharedDocumentsApi(token: string, groupId: string, page = 1) {
  return apiRequest<ApiResponse<ApiGroupMessage[]>>(
    `/api/groups/${groupId}/messages/documents?page=${page}&limit=50`, { token });
}

export async function getGroupRepliesApi(token: string, groupId: string, page = 1) {
  return apiRequest<ApiResponse<ApiGroupMessage[]>>(
    `/api/groups/${groupId}/messages/replies?page=${page}&limit=50`, { token });
}

// ─── Notifications ────────────────────────────────────────────────────────────

export type ApiNotification = {
  id: number;
  title: string;
  body: string;
  type: 'general' | 'alert' | 'message' | 'announcement' | 'update';
  action_url: string | null;
  is_read: boolean;
  read_at: string | null;
  created_at: string;
};

export async function getNotificationsApi(token: string, page = 1, limit = 20) {
  return apiRequest<ApiResponse<{
    notifications: ApiNotification[];
    unread_count: number;
    pagination: { total: number; page: number; limit: number; total_pages: number };
  }>>(`/api/notifications?page=${page}&limit=${limit}`, { token });
}

export async function getNotificationsUnreadCountApi(token: string) {
  return apiRequest<ApiResponse<{ unread_count: number }>>('/api/notifications/unread-count', { token });
}

export async function markNotificationReadApi(token: string, id: number) {
  return apiRequest<ApiResponse<{ unread_count: number }>>(`/api/notifications/${id}/read`, { method: 'POST', token });
}

export async function markAllNotificationsReadApi(token: string) {
  return apiRequest<ApiResponse<{ unread_count: number }>>('/api/notifications/read-all', { method: 'POST', token });
}
