export type ChatItem = {
  id: string;
  name: string;
  preview: string;
  time: string;
  unread: number;
  online: boolean;
};

export type Message = {
  id: string;
  from: 'me' | 'them';
  kind: 'text' | 'image' | 'voice' | 'video' | 'file';
  value: string;
  fileUrl?: string;
  fileName?: string;
  replyToMessageId?: string;
  replyToMessage?: {
    id: string;
    senderId: string;
    senderName: string;
    message: string;
    fileType?: string;
    fileName?: string;
  };
  forwardedFromMessageId?: string;
  isForwarded?: boolean;
  isDeletedForEveryone?: boolean;
  deletedForEveryoneAt?: string;
  voiceDurationSeconds?: number;
  voiceWaveformJson?: string;
  reactions?: Array<{
    id: string;
    userId: string;
    userName: string;
    emoji: string;
    createdAt: string;
  }>;
  time: string;
  isRead?: boolean;
  isDelivered?: boolean;
  deliveryStatus?: 'sent' | 'delivered' | 'read';
};
