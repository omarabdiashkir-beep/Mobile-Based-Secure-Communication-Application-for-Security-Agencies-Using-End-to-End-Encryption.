import {
  HubConnection,
  HubConnectionBuilder,
  HubConnectionState,
  LogLevel,
} from '@microsoft/signalr';
import { API_BASE_URL, getApiBaseUrls } from './config';

export type RealtimeMessage = {
  id: string;
  senderId: string;
  senderName: string;
  receiverId?: string;
  groupId?: string;
  message: string;
  timestamp: string;
  filePath?: string;
  fileName?: string;
  fileType?: string;
  fileUrl?: string;
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
  isDelivered?: boolean;
};

export type TypingEvent = {
  senderId: string;
  senderName?: string;
  receiverId?: string;
  groupId?: string;
  isTyping: boolean;
  activity?: 'typing' | 'recording';
};

type MessageHandler = (message: RealtimeMessage) => void;
type TypingHandler = (event: TypingEvent) => void;
type MessageUpdatedHandler = (message: RealtimeMessage) => void;
type PresenceHandler = (event: { userId: string; isOnline: boolean }) => void;
type ReadHandler = (event: { chatId: string; readBy: string; timestamp: string }) => void;
type DeliveryHandler = (event: { messageId: string; receiverId?: string; groupId?: string; status: string; timestamp: string }) => void;
type CallHandler = (event: { callId: string; callerId: string; receiverId?: string; groupId?: string; isVideo: boolean; startedAt: string }) => void;

class ChatRealtimeClient {
  private connection: HubConnection | null = null;

  private messageHandlers = new Set<MessageHandler>();

  private typingHandlers = new Set<TypingHandler>();

  private messageUpdatedHandlers = new Set<MessageUpdatedHandler>();

  private presenceHandlers = new Set<PresenceHandler>();

  private readHandlers = new Set<ReadHandler>();

  private deliveryHandlers = new Set<DeliveryHandler>();

  private callHandlers = new Set<CallHandler>();

  private token: string | null = null;

  private buildHubUrl(baseUrl: string) {
    return `${baseUrl.replace(/\/$/, '')}/chatHub`;
  }

  async connect(token: string) {
    this.token = token;

    if (this.connection?.state === HubConnectionState.Connected) {
      return;
    }

    if (this.connection?.state === HubConnectionState.Disconnected) {
      await this.connection.stop();
    }

    const candidates = [API_BASE_URL, ...getApiBaseUrls().filter((x: string) => x !== API_BASE_URL)];
    let lastError: unknown;

    for (const baseUrl of candidates) {
      try {
        const connection = new HubConnectionBuilder()
          .withUrl(this.buildHubUrl(baseUrl), {
            accessTokenFactory: () => this.token || '',
          })
          .withAutomaticReconnect()
          .configureLogging(LogLevel.None)
          .build();

        connection.on('ReceiveMessage', (message: RealtimeMessage) => {
          this.messageHandlers.forEach((handler) => handler(message));
        });

        connection.on('UserTyping', (event: TypingEvent) => {
          this.typingHandlers.forEach((handler) => handler(event));
        });

        connection.on('MessageUpdated', (message: RealtimeMessage) => {
          this.messageUpdatedHandlers.forEach((handler) => handler(message));
        });

        connection.on('UserStatusChanged', (event: { userId: string; isOnline: boolean }) => {
          this.presenceHandlers.forEach((handler) => handler(event));
        });

        connection.on('MessagesRead', (event: { chatId: string; readBy: string; timestamp: string }) => {
          this.readHandlers.forEach((handler) => handler(event));
        });

        connection.on('MessageDeliveryUpdated', (event: any) => {
          this.deliveryHandlers.forEach((handler) => handler(event));
        });

        connection.on('IncomingCall', (event: any) => {
          this.callHandlers.forEach((handler) => handler(event));
        });

        await connection.start();
        this.connection = connection;
        return;
      } catch (error) {
        lastError = error;
      }
    }

    throw lastError instanceof Error ? lastError : new Error('Failed to connect realtime');
  }

  async disconnect() {
    if (!this.connection) {
      return;
    }

    if (this.connection.state !== HubConnectionState.Disconnected) {
      await this.connection.stop();
    }
  }

  isConnected() {
    return this.connection?.state === HubConnectionState.Connected;
  }

  onMessage(handler: MessageHandler) {
    this.messageHandlers.add(handler);
    return () => this.messageHandlers.delete(handler);
  }

  onTyping(handler: TypingHandler) {
    this.typingHandlers.add(handler);
    return () => this.typingHandlers.delete(handler);
  }

  onMessageUpdated(handler: MessageUpdatedHandler) {
    this.messageUpdatedHandlers.add(handler);
    return () => this.messageUpdatedHandlers.delete(handler);
  }

  onPresence(handler: PresenceHandler) {
    this.presenceHandlers.add(handler);
    return () => this.presenceHandlers.delete(handler);
  }

  onMessagesRead(handler: ReadHandler) {
    this.readHandlers.add(handler);
    return () => this.readHandlers.delete(handler);
  }

  onMessageDeliveryUpdated(handler: DeliveryHandler) {
    this.deliveryHandlers.add(handler);
    return () => this.deliveryHandlers.delete(handler);
  }

  onIncomingCall(handler: CallHandler) {
    this.callHandlers.add(handler);
    return () => this.callHandlers.delete(handler);
  }

  async sendDirectMessage(receiverId: string, message: string, fileData?: { filePath: string; fileName: string; fileType: string }) {
    if (!this.connection || !this.isConnected()) {
      throw new Error('Realtime connection is not ready');
    }
    await this.connection.invoke(
      'SendMessage',
      receiverId,
      message,
      fileData?.filePath || null,
      fileData?.fileName || null,
      fileData?.fileType || null
    );
  }

  async sendGroupMessage(groupId: string, message: string, fileData?: { filePath: string; fileName: string; fileType: string }) {
    if (!this.connection || !this.isConnected()) {
      throw new Error('Realtime connection is not ready');
    }
    await this.connection.invoke(
      'SendGroupMessage',
      groupId,
      message,
      fileData?.filePath || null,
      fileData?.fileName || null,
      fileData?.fileType || null
    );
  }

  async sendTyping(receiverId: string, isTyping: boolean, groupId?: string, activity: 'typing' | 'recording' = 'typing') {
    if (!this.connection || !this.isConnected()) {
      return;
    }
    await this.connection.invoke('SendTyping', receiverId, isTyping, groupId || null, activity);
  }
}

export const chatRealtime = new ChatRealtimeClient();
