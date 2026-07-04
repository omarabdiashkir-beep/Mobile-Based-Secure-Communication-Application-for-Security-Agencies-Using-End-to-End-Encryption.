import React from 'react';
import { StyleSheet, View, type ViewProps } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { colors } from '../theme/colors';

type Props = ViewProps;

export function Screen({ style, children, ...rest }: Props) {
  return (
    <SafeAreaView style={styles.safe}>
      <View style={[styles.page, style]} {...rest}>
        {children}
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: colors.background,
  },
  page: {
    flex: 1,
    backgroundColor: colors.background,
  },
});
