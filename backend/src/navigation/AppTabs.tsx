import React from 'react';
import { Icon } from '../components/Icon';
import { Image, Pressable, StyleSheet, Text, View } from 'react-native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import type { BottomTabBarProps } from '@react-navigation/bottom-tabs';
import { ChatsScreen } from '../screens/ChatsScreen';
import { GroupsScreen } from '../screens/GroupsScreen';
import { NotificationsScreen } from '../screens/NotificationsScreen';
import { ProfileScreen } from '../screens/ProfileScreen';
import type { AppTabParamList, AuthSession, ConversationItem } from './types';
import { colors } from '../theme/colors';
import { useAuth } from '../context/AuthContext';
import { getUnreadCountApi, getMeApi, getNotificationsUnreadCountApi } from '../api/chatApi';

const Tab = createBottomTabNavigator<AppTabParamList>();

type Props = {
  session: AuthSession;
  onOpenChat: (conversation: ConversationItem) => void;
};

function TabBar({ state, navigation, session, notifCount, setNotifCount }: BottomTabBarProps & {
  session: AuthSession;
  notifCount: number;
  setNotifCount: (n: number) => void;
}) {
  const [unreadCount, setUnreadCount] = React.useState(0);
  const [myPhoto, setMyPhoto] = React.useState<string | null>(null);

  React.useEffect(() => {
    const fetchUnread = async () => {
      try {
        const resp = await getUnreadCountApi(session.token);
        setUnreadCount((resp as any)?.data?.unread_count ?? 0);
      } catch { /* silent */ }
    };
    const fetchNotifUnread = async () => {
      try {
        const resp = await getNotificationsUnreadCountApi(session.token);
        setNotifCount((resp as any)?.data?.unread_count ?? 0);
      } catch { /* silent */ }
    };
    const fetchMe = async () => {
      try {
        const resp = await getMeApi(session.token);
        const d = (resp as any)?.data ?? (resp as any)?.user ?? resp;
        if (d?.photo_url) setMyPhoto(d.photo_url);
      } catch { /* silent */ }
    };
    void fetchUnread();
    void fetchNotifUnread();
    void fetchMe();

    // Poll messages unread every 30s, notifications unread every 30s
    const i1 = setInterval(() => void fetchUnread(), 30_000);
    const i2 = setInterval(() => void fetchNotifUnread(), 30_000);
    return () => { clearInterval(i1); clearInterval(i2); };
  }, [session.token]);

  const tabs = [
    { name: 'Chats',         label: 'Chats',    icon: 'chatbubble',     iconOff: 'chatbubble-outline' },
    { name: 'Groups',        label: 'Groups',   icon: 'people',         iconOff: 'people-outline' },
    { name: 'Notifications', label: 'Alerts',   icon: 'notifications',  iconOff: 'notifications-outline' },
    { name: 'Profile',       label: 'You',      icon: 'person',         iconOff: 'person-outline' },
  ];

  return (
    <View style={s.bar}>
      {state.routes.map((route, index) => {
        const focused = state.index === index;
        const tab = tabs[index];
        const isProfile = route.name === 'Profile';
        const isChats = route.name === 'Chats';
        const isNotif = route.name === 'Notifications';

        return (
          <Pressable
            key={route.key}
            style={s.tabItem}
            onPress={() => navigation.navigate(route.name)}
          >
            <View style={[s.oval, focused && s.ovalActive]}>
              <View style={s.iconWrap}>
                {isProfile && myPhoto ? (
                  <>
                    <Image source={{ uri: myPhoto }} style={s.profilePhoto} />
                    <View style={s.onlineDot} />
                  </>
                ) : (
                  <Icon
                    name={(focused ? tab.icon : tab.iconOff) as any}
                    size={26}
                    color={focused ? '#111' : '#8696A0'}
                  />
                )}
                {isChats && unreadCount > 0 && (
                  <View style={s.badge}>
                    <Text style={s.badgeText}>{unreadCount > 99 ? '99+' : unreadCount}</Text>
                  </View>
                )}
                {isNotif && notifCount > 0 && (
                  <View style={s.badge}>
                    <Text style={s.badgeText}>{notifCount > 99 ? '99+' : notifCount}</Text>
                  </View>
                )}
              </View>
            </View>
            <Text style={[s.label, focused && s.labelActive]}>{tab.label}</Text>
          </Pressable>
        );
      })}
    </View>
  );
}

export function AppTabs({ session, onOpenChat }: Props) {
  const { signOut } = useAuth();
  const [notifCount, setNotifCount] = React.useState(0);

  return (
    <Tab.Navigator
      tabBar={(props) => (
        <TabBar {...props} session={session} notifCount={notifCount} setNotifCount={setNotifCount} />
      )}
      screenOptions={{ headerShown: false, tabBarHideOnKeyboard: true }}
    >
      <Tab.Screen name="Chats">
        {(props) => <ChatsScreen {...props} session={session} onOpenChat={onOpenChat} />}
      </Tab.Screen>
      <Tab.Screen name="Groups">
        {(props) => <GroupsScreen {...props} session={session} />}
      </Tab.Screen>
      <Tab.Screen name="Notifications">
        {() => (
          <NotificationsScreen
            session={session}
            onUnreadCountChange={setNotifCount}
          />
        )}
      </Tab.Screen>
      <Tab.Screen name="Profile">
        {() => <ProfileScreen session={session} onLogout={() => void signOut()} />}
      </Tab.Screen>
    </Tab.Navigator>
  );
}

const s = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderTopWidth: 1,
    borderTopColor: '#E9EDEF',
    paddingHorizontal: 8,
    paddingTop: 8,
    paddingBottom: 12,
  },
  tabItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 2,
    gap: 3,
  },
  oval: {
    width: 72,
    height: 52,
    borderRadius: 26,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'transparent',
  },
  ovalActive: { backgroundColor: '#E8E8E8' },
  iconWrap: { position: 'relative' },
  badge: {
    position: 'absolute', top: -5, right: -10,
    minWidth: 17, height: 17, borderRadius: 9,
    backgroundColor: colors.accent,
    alignItems: 'center', justifyContent: 'center',
    paddingHorizontal: 3,
    borderWidth: 2, borderColor: '#fff',
  },
  badgeText: { color: '#fff', fontSize: 9, fontWeight: '800' },
  profilePhoto: { width: 26, height: 26, borderRadius: 13 },
  onlineDot: {
    position: 'absolute', bottom: -1, right: -1,
    width: 9, height: 9, borderRadius: 5,
    backgroundColor: '#22C55E',
    borderWidth: 1.5, borderColor: '#fff',
  },
  label: { color: '#8696A0', fontSize: 11, fontWeight: '600' },
  labelActive: { color: '#111', fontWeight: '700' },
});
