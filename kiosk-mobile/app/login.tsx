import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, ActivityIndicator } from 'react-native';
import { useRouter } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { setAdminToken } from '../src/utils/kiosk';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import '../src/i18n';

const API_URL = process.env.EXPO_PUBLIC_API_URL || 'http://10.0.2.2:8000/api';

const LoginScreen = () => {
  const { t } = useTranslation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const router = useRouter();

  const handleLogin = async () => {
    setError(null);
    setIsLoading(true);

    try {
      const response = await fetch(`${API_URL}/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          email,
          password,
          device_name: 'kiosk-setup-mobile',
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || (data.email && data.email[0]) || 'Login failed');
      }

      await setAdminToken(data.token);
      router.replace('/setup-kiosk');
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-gray-50 dark:bg-gray-900 justify-center p-6">
      <View className="items-center mb-8">
        <MaterialIcons name="admin-panel-settings" size={64} color="#3b82f6" />
        <Text className="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
          Kiosk Admin Login
        </Text>
      </View>

      <View className="bg-white dark:bg-gray-800 p-6 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <View className="mb-4">
          <Text className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Email address
          </Text>
          <TextInput
            value={email}
            onChangeText={setEmail}
            autoCapitalize="none"
            keyboardType="email-address"
            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          />
        </View>

        <View className="mb-6">
          <Text className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Password
          </Text>
          <TextInput
            value={password}
            onChangeText={setPassword}
            secureTextEntry
            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          />
        </View>

        {error && (
          <View className="mb-6 rounded-md bg-red-50 dark:bg-red-900/30 p-4 flex-row items-center">
            <MaterialIcons name="error" size={20} color="#f87171" />
            <View className="ml-3 flex-1">
              <Text className="text-sm font-medium text-red-800 dark:text-red-200">
                Login failed
              </Text>
              <Text className="mt-1 text-sm text-red-700 dark:text-red-300">
                {error}
              </Text>
            </View>
          </View>
        )}

        <TouchableOpacity
          onPress={handleLogin}
          disabled={isLoading}
          className={`w-full flex-row justify-center py-2 px-4 border border-transparent rounded-md shadow-sm bg-primary ${isLoading ? 'opacity-50' : ''}`}
        >
          {isLoading ? (
             <ActivityIndicator color="white" />
          ) : (
            <Text className="text-sm font-medium text-white">Sign in</Text>
          )}
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
};

export default LoginScreen;