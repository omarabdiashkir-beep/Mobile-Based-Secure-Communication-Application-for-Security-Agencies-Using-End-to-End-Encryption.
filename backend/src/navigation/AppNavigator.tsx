import React from 'react';
import { AppState } from 'react-native';
import { TransitionPresets, createStackNavigator } from '@react-navigation/stack';
import { AppTabs } from './AppTabs';
import { ChatDetailScreen } from '../screens/ChatDetailScreen';
import { UserDetailScreen } from '../screens/UserDetailScreen';
import { GroupDetailScreen } from '../screens/GroupDetailScreen';
import { GroupChatScreen } from '../screens/GroupChatScreen';
import { CreateGroupScreen } from '../screens/CreateGroupScreen';
import { NewChatScreen } from '../screens/NewChatScreen';
import { SharedMediaScreen } from '../screens/SharedMediaScreen';
import type { AppStackParamList, AuthSession } from './types';
import { chatRealtime } from '../api/chatRealtime';
import { setOnlineApi, setOfflineApi } from '../api/chatApi';

const Stack = createStackNavigator<AppStackParamList>();

type Props = {
  session: AuthSession;
};

export function AppNavigator({ session }: Props) {
  React.useEffect(() => {
    let mounted = true;

    const startRealtime = async () => {
      if (!session.token) return;
      try {
        await chatRealtime.connect(session.token);
        void setOnlineApi(session.token);
      } catch {
        if (!mounted) return;
      }
    };

    void startRealtime();

    // Call online/offline based on app foreground/background state
    const appStateSub = AppState.addEventListener('change', (nextState) => {
      if (!session.token) return;
      if (nextState === 'active') {
        // App came to foreground
        void setOnlineApi(session.token);
      } else if (nextState === 'background' || nextState === 'inactive') {
        // App went to background or lost focus
        void setOfflineApi(session.token);
      }
    });

    return () => {
      mounted = false;
      appStateSub.remove();
      void chatRealtime.disconnect();
      void setOfflineApi(session.token);
    };
  }, [session.token]);

  return (
    <Stack.Navigator
      screenOptions={{
        headerShown: false,
        gestureEnabled: true,
        ...TransitionPresets.SlideFromRightIOS,
      }}
      initialRouteName="HomeTabs"
    >
      <Stack.Screen name="HomeTabs">
        {(props) => (
          <AppTabs
            session={session}
            onOpenChat={(conversation) => props.navigation.navigate('ChatDetail', { conversation })}
          />
        )}
      </Stack.Screen>
      <Stack.Screen name="ChatDetail">
        {(props) => <ChatDetailScreen {...props} session={session} />}
      </Stack.Screen>
      <Stack.Screen name="UserDetail" component={UserDetailScreen} />
      <Stack.Screen name="GroupChat">
        {(props) => <GroupChatScreen {...props} session={session} />}
      </Stack.Screen>
      <Stack.Screen name="GroupDetail">
        {(props) => <GroupDetailScreen {...props} session={session} />}
      </Stack.Screen>
      <Stack.Screen name="CreateGroup" options={{ ...TransitionPresets.ModalPresentationIOS }}>
        {(props) => <CreateGroupScreen {...props} session={session} />}
      </Stack.Screen>
      <Stack.Screen name="SharedMedia" component={SharedMediaScreen} />
      <Stack.Screen name="NewChat" options={{ ...TransitionPresets.ModalPresentationIOS }}>
        {(props) => (
          <NewChatScreen
            {...props}
            session={session}
            onOpenChat={(conversation) => {
              props.navigation.replace('ChatDetail', { conversation });
            }}
          />
        )}
      </Stack.Screen>
    </Stack.Navigator>
  );
}
