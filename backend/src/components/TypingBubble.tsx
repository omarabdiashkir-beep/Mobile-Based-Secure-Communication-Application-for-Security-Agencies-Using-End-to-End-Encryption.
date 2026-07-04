import React, { useEffect, useRef } from 'react';
import { Animated, StyleSheet, Text, View } from 'react-native';
import { colors } from '../theme/colors';

type Props = {
  senderName?: string;
  activity?: 'typing' | 'recording';
};

function Dot({ delay }: { delay: number }) {
  const anim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.timing(anim, { toValue: -6, duration: 280, useNativeDriver: true }),
        Animated.timing(anim, { toValue: 0, duration: 280, useNativeDriver: true }),
        Animated.delay(600 - delay),
      ]),
    );
    loop.start();
    return () => loop.stop();
  }, [anim, delay]);

  return (
    <Animated.View style={[styles.dot, { transform: [{ translateY: anim }] }]} />
  );
}

export function TypingBubble({ senderName, activity = 'typing' }: Props) {
  return (
    <View style={styles.row}>
      <View style={styles.bubble}>
        {senderName ? <Text style={styles.name}>{senderName}</Text> : null}
        <View style={styles.dots}>
          <Dot delay={0} />
          <Dot delay={160} />
          <Dot delay={320} />
        </View>
        {activity === 'recording' ? (
          <Text style={styles.label}>recording…</Text>
        ) : null}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    alignSelf: 'flex-start',
    paddingHorizontal: 12,
    paddingBottom: 6,
  },
  bubble: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    borderTopLeftRadius: 4,
    paddingHorizontal: 14,
    paddingVertical: 10,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06,
    shadowRadius: 2,
    elevation: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  name: {
    color: colors.primary,
    fontSize: 11,
    fontWeight: '700',
    marginRight: 4,
  },
  dots: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    height: 20,
  },
  dot: {
    width: 7,
    height: 7,
    borderRadius: 4,
    backgroundColor: colors.muted,
  },
  label: {
    color: colors.muted,
    fontSize: 11,
    marginLeft: 4,
  },
});
