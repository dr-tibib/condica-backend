import "../global.css";
import { Slot } from "expo-router";
import { SafeAreaProvider } from "react-native-safe-area-context";

export default function Layout() {
  return (
    <SafeAreaProvider style={{ flex: 1, backgroundColor: '#f9fafb' }}>
      <Slot />
    </SafeAreaProvider>
  );
}