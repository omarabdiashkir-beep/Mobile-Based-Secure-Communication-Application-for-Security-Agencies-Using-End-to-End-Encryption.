import React from 'react';
import { AppState, type AppStateStatus } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { AuthNavigator } from './AuthNavigator';
import { AppNavigator } from './AppNavigator';
import { SplashScreen } from '../screens/SplashScreen';
import { BiometricLockScreen } from '../screens/BiometricLockScreen';
import { ForceChangePasswordScreen } from '../screens/ForceChangePasswordScreen';
import { useAuth } from '../context/AuthContext';
import { biometricUtils } from '../utils/biometricUtils';

export function RootNavigator() {
  const { isHydrating, session, mustChangePassword, passwordExpired } = useAuth();
  const [locked, setLocked] = React.useState(false);
  const lastState = React.useRef<AppStateStatus>(AppState.currentState);
  // Timestamp when the app went to background (null = still in foreground)
  const backgroundedAt = React.useRef<number | null>(null);
  // How long (ms) the app must be in the background before locking
  const LOCK_AFTER_MS = 30_000;

  // On first login → lock immediately (cold open)
  React.useEffect(() => {
    if (!session) { setLocked(false); return; }
    void biometricUtils.isAppLockEnabled().then((on) => { if (on) setLocked(true); });
  }, [session?.userId]);

  // Track background time; only lock if away long enough
  React.useEffect(() => {
    if (!session) return;
    const sub = AppState.addEventListener('change', async (next) => {
      const prev = lastState.current;
      lastState.current = next;

      if ((next === 'background' || next === 'inactive') && prev === 'active') {
        backgroundedAt.current = Date.now();
      }

      if (next === 'active' && backgroundedAt.current !== null) {
        const away = Date.now() - backgroundedAt.current;
        backgroundedAt.current = null;
        if (away >= LOCK_AFTER_MS) {
          const on = await biometricUtils.isAppLockEnabled();
          if (on) setLocked(true);
        }
      }
    });
    return () => sub.remove();
  }, [session?.userId]);

  if (isHydrating) return <SplashScreen />;

  // Force password change before accessing the app
  if (session && (mustChangePassword || passwordExpired)) {
    return <ForceChangePasswordScreen isExpired={passwordExpired && !mustChangePassword} />;
  }

  return (
    <NavigationContainer>
      {session ? <AppNavigator session={session} /> : <AuthNavigator />}
      {locked && session && (
        <BiometricLockScreen onUnlocked={() => setLocked(false)} />
      )}
    </NavigationContainer>
  );
}
