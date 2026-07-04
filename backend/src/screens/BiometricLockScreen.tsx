import React, { useEffect, useRef, useState } from 'react';
import { Icon } from '../components/Icon';
import {
  ActivityIndicator,
  Animated,
  Pressable,
  StatusBar,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { biometricUtils } from '../utils/biometricUtils';
import { colors } from '../theme/colors';

type Props = {
  onUnlocked: () => void;
};

export function BiometricLockScreen({ onUnlocked }: Props) {
  const [checking, setChecking] = useState(true);
  const [failed, setFailed] = useState(false);
  const [isFaceId, setIsFaceId] = useState(false);
  const shakeAnim = useRef(new Animated.Value(0)).current;
  const pulseAnim = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    void init();
  }, []);

  const init = async () => {
    const avail = await biometricUtils.getAvailability();
    setIsFaceId(avail.isFaceIdSupported);
    setChecking(false);
    void triggerAuth(avail.isFaceIdSupported);
  };

  const shake = () => {
    Animated.sequence([
      Animated.timing(shakeAnim, { toValue: 12, duration: 60, useNativeDriver: true }),
      Animated.timing(shakeAnim, { toValue: -12, duration: 60, useNativeDriver: true }),
      Animated.timing(shakeAnim, { toValue: 8, duration: 50, useNativeDriver: true }),
      Animated.timing(shakeAnim, { toValue: -8, duration: 50, useNativeDriver: true }),
      Animated.timing(shakeAnim, { toValue: 0, duration: 50, useNativeDriver: true }),
    ]).start();
  };

  const pulse = () => {
    Animated.sequence([
      Animated.timing(pulseAnim, { toValue: 1.15, duration: 120, useNativeDriver: true }),
      Animated.timing(pulseAnim, { toValue: 1, duration: 120, useNativeDriver: true }),
    ]).start();
  };

  const triggerAuth = async (faceId?: boolean) => {
    setFailed(false);
    pulse();
    const result = await biometricUtils.authenticate(
      (faceId ?? isFaceId) ? 'Unlock SecureComm with Face ID' : 'Unlock SecureComm with fingerprint'
    );
    if (result.success) {
      onUnlocked();
    } else if (result.error !== 'user_cancel') {
      setFailed(true);
      shake();
    }
  };

  if (checking) {
    return (
      <View style={s.root}>
        <StatusBar barStyle="light-content" backgroundColor="#0F172A" />
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <View style={s.root}>
      <StatusBar barStyle="light-content" backgroundColor="#0F172A" />

      {/* Logo / App name */}
      <View style={s.top}>
        <View style={s.logoCircle}>
          <Icon name="lock-closed" size={28} color="#fff" />
        </View>
        <Text style={s.appName}>SecureComm</Text>
        <Text style={s.tagline}>Locked for your protection</Text>
      </View>

      {/* Biometric icon */}
      <View style={s.mid}>
        <Animated.View style={[s.bioCircle, { transform: [{ scale: pulseAnim }, { translateX: shakeAnim }] }]}>
          <Icon
            name={isFaceId ? 'scan' : 'finger-print'}
            size={56}
            color={failed ? '#EF4444' : colors.primary}
          />
        </Animated.View>

        {failed ? (
          <Text style={s.failedText}>Not recognised. Try again.</Text>
        ) : (
          <Text style={s.hintText}>
            {isFaceId ? 'Look at your phone to unlock' : 'Touch the sensor to unlock'}
          </Text>
        )}
      </View>

      {/* Actions */}
      <View style={s.bottom}>
        <Pressable style={s.primaryBtn} onPress={() => void triggerAuth()} android_ripple={{ color: '#fff3' }}>
          <Icon name={isFaceId ? 'scan-outline' : 'finger-print-outline'} size={20} color="#fff" />
          <Text style={s.primaryBtnText}>
            {isFaceId ? 'Use Face ID' : 'Use Fingerprint'}
          </Text>
        </Pressable>

        <Pressable style={s.altBtn} onPress={() => void triggerAuth()} android_ripple={{ color: '#0001' }}>
          <Text style={s.altBtnText}>Use PIN / Passcode</Text>
        </Pressable>
      </View>
    </View>
  );
}

const s = StyleSheet.create({
  root: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: '#0F172A',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 80,
    paddingBottom: 60,
    paddingHorizontal: 32,
    zIndex: 9999,
  },
  top: { alignItems: 'center', gap: 10 },
  logoCircle: {
    width: 64, height: 64, borderRadius: 32,
    backgroundColor: colors.primary,
    alignItems: 'center', justifyContent: 'center',
    marginBottom: 6,
  },
  appName: { color: '#fff', fontSize: 26, fontWeight: '800', letterSpacing: 0.3 },
  tagline: { color: 'rgba(255,255,255,0.45)', fontSize: 13 },

  mid: { alignItems: 'center', gap: 20 },
  bioCircle: {
    width: 120, height: 120, borderRadius: 60,
    backgroundColor: 'rgba(255,255,255,0.07)',
    alignItems: 'center', justifyContent: 'center',
    borderWidth: 1.5, borderColor: 'rgba(255,255,255,0.12)',
  },
  hintText: { color: 'rgba(255,255,255,0.55)', fontSize: 14, textAlign: 'center' },
  failedText: { color: '#EF4444', fontSize: 14, fontWeight: '600', textAlign: 'center' },

  bottom: { width: '100%', gap: 12 },
  primaryBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10,
    backgroundColor: colors.primary,
    height: 52, borderRadius: 16,
  },
  primaryBtnText: { color: '#fff', fontSize: 15, fontWeight: '700' },
  altBtn: {
    height: 48, borderRadius: 16,
    borderWidth: 1, borderColor: 'rgba(255,255,255,0.18)',
    alignItems: 'center', justifyContent: 'center',
  },
  altBtnText: { color: 'rgba(255,255,255,0.65)', fontSize: 14, fontWeight: '600' },
});
