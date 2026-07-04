import React from 'react';
import * as SecureStore from 'expo-secure-store';
import { loginApi, registerApi } from '../api/authApi';
import { biometricUtils } from '../utils/biometricUtils';
import type { AuthSession } from '../navigation/types';

const SESSION_KEY = 'auth_session';

type RegisterPayload = {
  name: string;
  email: string;
  password: string;
  phone?: string;
};

type AuthContextValue = {
  isHydrating: boolean;
  session: AuthSession | null;
  mustChangePassword: boolean;
  passwordExpired: boolean;
  signInWithPassword: (email: string, password: string) => Promise<{ success: boolean; error?: string }>;
  signInWithBiometrics: () => Promise<{ success: boolean; error?: string }>;
  registerAccount: (payload: RegisterPayload) => Promise<{ success: boolean; error?: string }>;
  clearPasswordFlags: () => void;
  signOut: () => Promise<void>;
};

const AuthContext = React.createContext<AuthContextValue | undefined>(undefined);

async function persistSession(session: AuthSession | null) {
  if (!session) {
    await SecureStore.deleteItemAsync(SESSION_KEY);
    return;
  }
  await SecureStore.setItemAsync(SESSION_KEY, JSON.stringify(session));
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [isHydrating, setIsHydrating] = React.useState(true);
  const [session, setSession] = React.useState<AuthSession | null>(null);
  const [mustChangePassword, setMustChangePassword] = React.useState(false);
  const [passwordExpired, setPasswordExpired] = React.useState(false);

  React.useEffect(() => {
    let mounted = true;
    const hydrate = async () => {
      try {
        const saved = await SecureStore.getItemAsync(SESSION_KEY);
        if (!mounted || !saved) return;
        setSession(JSON.parse(saved) as AuthSession);
      } catch (error) {
        console.warn('Failed to hydrate session', error);
      } finally {
        if (mounted) setIsHydrating(false);
      }
    };
    void hydrate();
    return () => { mounted = false; };
  }, []);

  const signInWithPassword = React.useCallback(async (email: string, password: string) => {
    const result = await loginApi(email.trim(), password);
    if (!result.session) {
      return { success: false, error: result.error || 'Sign in failed.' };
    }

    setSession(result.session);
    await persistSession(result.session);
    setMustChangePassword(result.mustChangePassword ?? false);
    setPasswordExpired(result.passwordExpired ?? false);

    return { success: true };
  }, []);

  const signInWithBiometrics = React.useCallback(async () => {
    const enabled = await biometricUtils.isEnabled();
    if (!enabled) {
      return { success: false, error: 'Biometric quick sign-in is not enabled yet.' };
    }

    const availability = await biometricUtils.getAvailability();
    if (!availability.hasHardware || !availability.isEnrolled) {
      return { success: false, error: 'Face or fingerprint is not ready on this device.' };
    }

    const auth = await biometricUtils.authenticate(
      availability.isFaceIdSupported ? 'Verify with Face ID' : 'Verify with fingerprint',
    );

    if (!auth.success) {
      return { success: false, error: auth.error || 'Biometric verification failed.' };
    }

    const credentials = await biometricUtils.getStoredCredentials();
    if (!credentials) {
      await biometricUtils.disable();
      return { success: false, error: 'Saved biometric credentials were not found. Please sign in with password.' };
    }

    const result = await loginApi(credentials.username, credentials.password);
    if (!result.session) {
      return { success: false, error: result.error || 'Quick sign-in failed. Please use your password.' };
    }

    setSession(result.session);
    await persistSession(result.session);
    setMustChangePassword(result.mustChangePassword ?? false);
    setPasswordExpired(result.passwordExpired ?? false);

    return { success: true };
  }, []);

  const registerAccount = React.useCallback(async (payload: RegisterPayload) => {
    const result = await registerApi(payload);
    if (!result.session) {
      return { success: false, error: result.error || 'Registration failed.' };
    }

    setSession(result.session);
    await persistSession(result.session);
    setMustChangePassword(result.mustChangePassword ?? false);

    return { success: true };
  }, []);

  const clearPasswordFlags = React.useCallback(() => {
    setMustChangePassword(false);
    setPasswordExpired(false);
  }, []);

  const signOut = React.useCallback(async () => {
    setSession(null);
    setMustChangePassword(false);
    setPasswordExpired(false);
    await persistSession(null);
  }, []);

  const value = React.useMemo<AuthContextValue>(
    () => ({
      isHydrating,
      session,
      mustChangePassword,
      passwordExpired,
      signInWithPassword,
      signInWithBiometrics,
      registerAccount,
      clearPasswordFlags,
      signOut,
    }),
    [isHydrating, session, mustChangePassword, passwordExpired,
     signInWithPassword, signInWithBiometrics, registerAccount, clearPasswordFlags, signOut],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const value = React.useContext(AuthContext);
  if (!value) throw new Error('useAuth must be used inside AuthProvider');
  return value;
}
