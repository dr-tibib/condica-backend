import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, ActivityIndicator } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';
import { getKioskWorkplaceId } from '../src/utils/kiosk';
import useIdleTimer from '../src/hooks/useIdleTimer';

const API_URL = process.env.EXPO_PUBLIC_API_URL || 'http://10.0.2.2:8000/api';

const CodeEntryScreen = () => {
  const { t } = useTranslation();
  const router = useRouter();
  const params = useLocalSearchParams();
  const flow = params.flow || 'regular';

  const [code, setCode] = useState('');
  const [codeLength, setCodeLength] = useState(3);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useIdleTimer(30);

  useEffect(() => {
    fetch(`${API_URL}/config`)
      .then((res) => res.json())
      .then((data) => {
        if (data.code_length) {
            setCodeLength(data.code_length);
        }
      })
      .catch((err) => console.error('Failed to fetch config', err));
  }, []);

  useEffect(() => {
    if (code.length === codeLength) {
      submitCode(code);
    }
  }, [code, codeLength]);

  const submitCode = async (submittedCode: string) => {
    setIsLoading(true);
    setError(null);

    try {
        const workplaceId = await getKioskWorkplaceId();

        const response = await fetch(`${API_URL}/kiosk/submit-code`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                code: submittedCode,
                flow: flow,
                workplace_id: workplaceId,
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || t('code_entry.invalid_code'));
        }

        if (data.type === 'delegation_end') {
            router.push({
                pathname: '/delegation-ended',
                params: {
                    user: JSON.stringify(data.user),
                    code: submittedCode
                }
            });
            return;
        }

        if (flow === 'regular') {
            router.push({
                pathname: '/success',
                params: {
                    type: data.type,
                    name: data.user.name,
                    time: data.time
                }
            });
        } else if (flow === 'delegation') {
            if (data.is_delegated) {
                setError(t('code_entry.already_delegated', 'You are already in a delegation. Please check out first.'));
                setCode('');
                return;
            }
            router.push({
                pathname: '/delegation-locations',
                params: { user: JSON.stringify(data.user) }
            });
        }

    } catch (err: any) {
        console.error(err);
        setError(err.message);
        setCode('');
    } finally {
        setIsLoading(false);
    }
  };

  const handleKeyPress = (key: string) => {
    if (code.length < codeLength && !isLoading) {
      setCode(code + key);
    }
  };

  const handleDelete = () => {
    if (!isLoading) {
        setCode(code.slice(0, -1));
        setError(null);
    }
  };

  const handleBack = () => {
    router.back();
  };

  return (
    <SafeAreaView className="flex-1 bg-background-light dark:bg-background-dark p-4">
      <View className="flex-1 max-w-[768px] mx-auto w-full bg-white dark:bg-[#1a202c] rounded-xl shadow-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
        <View className="p-6 flex-row items-center justify-between">
          <TouchableOpacity
            onPress={handleBack}
            className="w-14 h-14 rounded-full bg-slate-50 dark:bg-slate-800 items-center justify-center"
          >
            <MaterialIcons name="arrow-back" size={28} color="#334155" />
          </TouchableOpacity>
        </View>

        <View className="flex-1 items-center justify-center px-4 pb-8">
            <View className="items-center mb-8">
                <View className="w-16 h-16 mb-4 rounded-2xl bg-primary/10 items-center justify-center">
                    <MaterialIcons name="lock-person" size={40} color="#3b82f6" />
                </View>
                <Text className="text-3xl font-bold text-slate-900 dark:text-white text-center mb-2">
                    {t('code_entry.title')}
                </Text>
                <Text className="text-slate-500 dark:text-slate-400 text-lg text-center">
                    {t('code_entry.subtitle', { length: codeLength })}
                </Text>
            </View>

            {error && (
                <View className="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg w-full max-w-sm">
                    <Text className="text-red-600 dark:text-red-400 text-center font-medium">
                        {error}
                    </Text>
                </View>
            )}

            <View className="mb-10 flex-row items-center justify-center gap-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 px-8 py-5 h-20 w-auto min-w-[300px]">
                {[...Array(codeLength)].map((_, i) => (
                    <View
                        key={i}
                        className={`w-4 h-4 rounded-full ${
                            i < code.length
                            ? 'bg-primary scale-110'
                            : 'bg-slate-300 dark:bg-slate-600'
                        }`}
                    />
                ))}
            </View>

            <View className="w-full max-w-[360px] flex-row flex-wrap justify-between gap-y-4">
                {[1, 2, 3, 4, 5, 6, 7, 8, 9].map((num) => (
                    <TouchableOpacity
                        key={num}
                        onPress={() => handleKeyPress(num.toString())}
                        disabled={isLoading}
                        className="w-[100px] h-[100px] items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 border-b-4 border-slate-200 dark:border-slate-900 active:border-b-0 active:translate-y-1"
                    >
                        <Text className="text-3xl font-semibold text-slate-800 dark:text-white">
                            {num}
                        </Text>
                    </TouchableOpacity>
                ))}

                <View className="w-[100px]" />

                <TouchableOpacity
                        onPress={() => handleKeyPress('0')}
                        disabled={isLoading}
                        className="w-[100px] h-[100px] items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 border-b-4 border-slate-200 dark:border-slate-900 active:border-b-0 active:translate-y-1"
                    >
                        <Text className="text-3xl font-semibold text-slate-800 dark:text-white">
                            0
                        </Text>
                </TouchableOpacity>

                <TouchableOpacity
                        onPress={handleDelete}
                        disabled={isLoading}
                        className="w-[100px] h-[100px] items-center justify-center rounded-2xl hover:bg-red-50"
                    >
                         <MaterialIcons name="backspace" size={40} color="#94a3b8" />
                </TouchableOpacity>
            </View>
        </View>
      </View>
    </SafeAreaView>
  );
};

export default CodeEntryScreen;