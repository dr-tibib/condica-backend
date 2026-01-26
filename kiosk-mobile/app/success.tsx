import React, { useEffect, useRef } from 'react';
import { View, Text, Animated } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { SafeAreaView } from 'react-native-safe-area-context';
import { MaterialIcons } from '@expo/vector-icons';

const successTypes = {
  checkin: {
    color: '#22c55e',
    icon: 'check-circle',
    titleKey: 'success.checkin.title',
    messageKey: 'success.checkin.message',
  },
  checkout: {
    color: '#3b82f6',
    icon: 'check',
    titleKey: 'success.checkout.title',
    messageKey: 'success.checkout.message',
  },
  'delegation-start': {
    color: '#f97316',
    icon: 'check',
    titleKey: 'success.delegation_start.title',
    messageKey: 'success.delegation_start.message',
  },
  'delegation-end': {
    color: '#22c55e',
    icon: 'check',
    titleKey: 'success.delegation_end.title',
    messageKey: 'success.delegation_end.message',
  },
};

const SuccessScreen = () => {
  const { t } = useTranslation();
  const router = useRouter();
  const params = useLocalSearchParams();
  const type = (params.type as string) || 'checkin';
  const name = (params.name as string) || 'User';
  const time = (params.time as string) || '';

  const config = successTypes[type as keyof typeof successTypes] || successTypes.checkin;
  const progress = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.timing(progress, {
      toValue: 100,
      duration: 4000,
      useNativeDriver: false,
    }).start();

    const timer = setTimeout(() => {
      router.replace('/');
    }, 4000);

    return () => clearTimeout(timer);
  }, []);

  const widthInterpolated = progress.interpolate({
      inputRange: [0, 100],
      outputRange: ['0%', '100%']
  });

  return (
    <SafeAreaView className="flex-1 bg-background-light dark:bg-background-dark items-center justify-center p-4">
       <View className="bg-white dark:bg-[#1e293b] w-full max-w-[768px] rounded-lg shadow-2xl overflow-hidden py-12 px-8 items-center">

            <View className="mb-10 items-center justify-center">
                 <View className="rounded-full p-8" style={{ backgroundColor: `${config.color}20` }}>
                      <MaterialIcons name={config.icon as any} size={120} color={config.color} />
                 </View>
            </View>

            <View className="items-center mb-12 space-y-4">
                 <Text className="text-[#111318] dark:text-white text-3xl font-bold text-center">
                     {t(config.titleKey)}
                 </Text>
                 <Text className="text-primary text-2xl font-bold text-center">
                     {name}
                 </Text>
                 <Text className="text-slate-500 dark:text-slate-400 text-xl font-medium">
                     {time}
                 </Text>
                 <Text className="text-slate-600 dark:text-slate-300 text-lg text-center mt-2">
                     {t(config.messageKey)}
                 </Text>
            </View>

            <View className="w-full max-w-md items-center gap-4">
                 <View className="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                     <Animated.View
                        className="h-full bg-primary rounded-full"
                        style={{ width: widthInterpolated }}
                     />
                 </View>
                 <Text className="text-slate-400 dark:text-slate-500 text-sm font-medium">
                     {t('success.returning_home')}
                 </Text>
            </View>
       </View>
    </SafeAreaView>
  );
};

export default SuccessScreen;