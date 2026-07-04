import * as LocalAuthentication from 'expo-local-authentication';
import * as SecureStore from 'expo-secure-store';

const BIOMETRIC_ENABLED_KEY = 'biometric_enabled';
const USER_CREDENTIALS_KEY = 'user_credentials';
const APP_LOCK_ENABLED_KEY = 'app_lock_enabled';

export type BiometricAuthResult = {
    success: boolean;
    error?: string;
};

export type SavedCredentials = {
    username: string;
    password: string;
};

export const biometricUtils = {
    /**
     * Checks if the device has biometric hardware and if any records are enrolled.
     */
    async getAvailability() {
        try {
            const hasHardware = await LocalAuthentication.hasHardwareAsync();
            const supportedTypes = await LocalAuthentication.supportedAuthenticationTypesAsync() || [];
            const isEnrolled = await LocalAuthentication.isEnrolledAsync();

            let securityLevel = 0;
            try {
                securityLevel = await LocalAuthentication.getEnrolledLevelAsync();
            } catch (e) {
                console.warn('getEnrolledLevelAsync not supported', e);
            }

            const isFaceIdSupported = supportedTypes.includes(LocalAuthentication.AuthenticationType.FACIAL_RECOGNITION);
            const isFingerprintSupported = supportedTypes.includes(LocalAuthentication.AuthenticationType.FINGERPRINT);

            console.log('Biometric Availability Check:', {
                hasHardware,
                supportedTypes,
                isEnrolled,
                securityLevel,
                isFaceIdSupported
            });

            return {
                hasHardware,
                isEnrolled,
                supportedTypes,
                securityLevel,
                isFaceIdSupported,
                isFingerprintSupported,
                isAvailable: hasHardware && (isEnrolled || supportedTypes.length > 0),
            };
        } catch (error) {
            console.error('Error checking biometric availability:', error);
            return {
                hasHardware: false,
                isEnrolled: false,
                supportedTypes: [],
                securityLevel: 0,
                isFaceIdSupported: false,
                isFingerprintSupported: false,
                isAvailable: false,
            };
        }
    },

    /**
     * Simple biometric prompt.
     */
    async authenticate(promptMessage: string = 'Confirm your identity'): Promise<BiometricAuthResult> {
        try {
            console.log('Requesting Biometric Authentication...');
            const result = await LocalAuthentication.authenticateAsync({
                promptMessage,
                fallbackLabel: 'Use PIN/Passcode',
                disableDeviceFallback: false,
                requireConfirmation: false, // Android specific: skip extra "Confirm" button
            });
            console.log('Authentication Result:', result);

            if (result.success) {
                return { success: true };
            }

            // Map some common errors for better UX
            let errorMessage = 'Authentication failed';
            if (result.error === 'not_enrolled') {
                errorMessage = 'No biometrics enrolled on this device';
            } else if (result.error === 'not_available') {
                errorMessage = 'Biometrics not available';
            } else if (result.error === 'user_cancel' || (result.error as string) === 'app_cancel' || (result.error as string) === 'system_cancel') {
                return { success: false, error: 'user_cancel' };
            }

            return { success: false, error: errorMessage };
        } catch (error) {
            console.error('Biometric authentication error:', error);
            return { success: false, error: error instanceof Error ? error.message : 'Authentication failed' };
        }
    },

    /**
     * Checks if biometric login is enabled by the user in settings.
     */
    async isEnabled(): Promise<boolean> {
        try {
            const enabled = await SecureStore.getItemAsync(BIOMETRIC_ENABLED_KEY);
            return enabled === 'true';
        } catch {
            return false;
        }
    },

    /**
     * Enables biometric login and saves credentials securely.
     */
    async enable(credentials: SavedCredentials): Promise<boolean> {
        try {
            // Validate credentials before saving
            if (!credentials.username || !credentials.password) {
                return false;
            }
            await SecureStore.setItemAsync(USER_CREDENTIALS_KEY, JSON.stringify(credentials));
            await SecureStore.setItemAsync(BIOMETRIC_ENABLED_KEY, 'true');
            return true;
        } catch (error) {
            console.error('Failed to enable biometrics', error);
            return false;
        }
    },

    /**
     * Disables biometric login and removes stored credentials.
     */
    async disable(): Promise<void> {
        try {
            await SecureStore.deleteItemAsync(USER_CREDENTIALS_KEY);
            await SecureStore.setItemAsync(BIOMETRIC_ENABLED_KEY, 'false');
        } catch (error) {
            console.error('Failed to disable biometrics', error);
        }
    },

    // ── App Lock (separate from biometric login) ───────────────────────────

    async isAppLockEnabled(): Promise<boolean> {
        try {
            const v = await SecureStore.getItemAsync(APP_LOCK_ENABLED_KEY);
            return v === 'true';
        } catch { return false; }
    },

    async enableAppLock(): Promise<void> {
        await SecureStore.setItemAsync(APP_LOCK_ENABLED_KEY, 'true');
    },

    async disableAppLock(): Promise<void> {
        await SecureStore.setItemAsync(APP_LOCK_ENABLED_KEY, 'false');
    },

    /**
     * Retrieves stored credentials after a successful biometric authentication.
     */
    async getStoredCredentials(): Promise<SavedCredentials | null> {
        try {
            const credentialsJson = await SecureStore.getItemAsync(USER_CREDENTIALS_KEY);
            if (credentialsJson) {
                return JSON.parse(credentialsJson);
            }
            return null;
        } catch (error) {
            console.error('Failed to get stored credentials', error);
            return null;
        }
    },
};
