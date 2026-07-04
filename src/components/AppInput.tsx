import React, { useState } from 'react';
import { Icon } from './Icon';
import { Pressable, StyleSheet, Text, TextInput, type TextInputProps, View } from 'react-native';
import { colors } from '../theme/colors';

type Props = TextInputProps & {
  label: string;
  icon?: string;
  rightLabel?: string;
  onRightPress?: () => void;
  containerStyle?: any;
};

export function AppInput({ label, icon, rightLabel, onRightPress, containerStyle, ...rest }: Props) {
  const [focused, setFocused] = useState(false);
  return (
    <View style={[s.wrap, containerStyle]}>
      <Text style={s.label}>{label}</Text>
      <View style={[s.row, focused && s.rowFocused]}>
        {icon ? (
          <Icon
            name={icon as any}
            size={18}
            color={focused ? colors.primary : colors.muted}
            style={{ marginLeft: 14, marginRight: 4 }}
          />
        ) : null}
        <TextInput
          style={s.input}
          placeholderTextColor={colors.muted}
          onFocus={() => setFocused(true)}
          onBlur={() => setFocused(false)}
          {...rest}
        />
        {rightLabel ? (
          <Pressable onPress={onRightPress} style={s.rightBtn}>
            <Text style={s.rightLabel}>{rightLabel}</Text>
          </Pressable>
        ) : null}
      </View>
    </View>
  );
}

const s = StyleSheet.create({
  wrap:  { marginTop: 14 },
  label: { fontSize: 12, fontWeight: '700', color: colors.muted, marginBottom: 6, letterSpacing: 0.4 },
  row: {
    flexDirection: 'row', alignItems: 'center',
    height: 52, borderRadius: 14,
    borderWidth: 1.5, borderColor: colors.border,
    backgroundColor: '#fff',
  },
  rowFocused: { borderColor: colors.primary, backgroundColor: colors.primaryLight },
  input: {
    flex: 1, height: '100%',
    paddingHorizontal: 14,
    color: colors.text, fontSize: 15,
  },
  rightBtn: { paddingHorizontal: 14 },
  rightLabel: { color: colors.primary, fontSize: 12, fontWeight: '800' },
});
