import type { NavigatorScreenParams } from '@react-navigation/native';

export type AuthSession = {
  token: string;
  userId: string;
  userName: string;
  email: string;
};

export type ConversationItem = {
  id: string;
  name: string;
  avatarUrl?: string;
  isGroup: boolean;
  preview: string;
  time: string;
  unread: number;
  online: boolean;
};

export type AuthStackParamList = {
  Splash: undefined;
  Onboarding: undefined;
  Login: undefined;
  Register: undefined;
  ForgotPassword: undefined;
};

export type AppTabParamList = {
  Chats: undefined;
  Groups: undefined;
  Notifications: undefined;
  Profile: undefined;
};

export type AppStackParamList = {
  HomeTabs: undefined;
  Profile: undefined;
  ChatDetail: {
    conversation: ConversationItem;
  };
  UserDetail: {
    userId: string;
    name: string;
    avatarUrl?: string;
  };
  GroupDetail: {
    groupId: string;
    name: string;
    avatarUrl?: string;
  };
  GroupChat: {
    groupId: string;
    name: string;
    avatarUrl?: string;
    membersCount?: number;
  };
  CreateGroup: undefined;
  NewChat: undefined;
  SharedMedia: {
    userId: string;
    name: string;
  };
};

export type RootStackParamList = {
  Auth: NavigatorScreenParams<AuthStackParamList>;
  App: NavigatorScreenParams<AppStackParamList>;
};
