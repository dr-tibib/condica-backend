import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, Image } from 'react-native';
import { useRouter, Redirect } from 'expo-router';
import { useTranslation } from 'react-i18next';
import { SafeAreaView } from 'react-native-safe-area-context';
import { getKioskWorkplaceId } from '../src/utils/kiosk';
import { MaterialIcons } from '@expo/vector-icons';
import { format } from 'date-fns';
import { enUS, ro, hu, de } from 'date-fns/locale';

const API_URL = process.env.EXPO_PUBLIC_API_URL || 'http://10.0.2.2:8000/api';

const locales: Record<string, any> = {
    en: enUS,
    ro: ro,
    hu: hu,
    de: de
};

const IdleScreen = () => {
  const { t, i18n } = useTranslation();
  const router = useRouter();
  const [companyName, setCompanyName] = useState('Acme Corp HQ');
  const [logoUrl, setLogoUrl] = useState<string | null>(null);
  const [currentTime, setCurrentTime] = useState(new Date());
  const [isChecking, setIsChecking] = useState(true);
  const [shouldRedirectToLogin, setShouldRedirectToLogin] = useState(false);

  useEffect(() => {
    const checkKioskStatus = async () => {
        const workplaceId = await getKioskWorkplaceId();
        if (!workplaceId) {
            setShouldRedirectToLogin(true);
        } else {
            setIsChecking(false);
            fetchConfig();
        }
    };
    checkKioskStatus();
  }, []);

  const fetchConfig = async () => {
      try {
        const res = await fetch(`${API_URL}/config`);
        const data = await res.json();
        if (data.company_name) setCompanyName(data.company_name);
        if (data.logo_url) setLogoUrl(data.logo_url);
      } catch (err) {
          console.error('Failed to fetch config', err);
      }
  };

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 60000);

    return () => clearInterval(timer);
  }, []);

  const handleRegularFlow = () => {
    router.push({ pathname: '/code-entry', params: { flow: 'regular' } });
  };

  const handleDelegationFlow = () => {
    router.push({ pathname: '/code-entry', params: { flow: 'delegation' } });
  };

  const currentLocale = locales[i18n.language] || enUS;
  const dateString = format(currentTime, 'EEEE, MMM d, HH:mm', { locale: currentLocale });

  const changeLanguage = (lang: string) => {
      i18n.changeLanguage(lang);
  };

  if (shouldRedirectToLogin) {
      return <Redirect href="/login" />;
  }

  if (isChecking) {
      return null;
  }

  return (
    <SafeAreaView className="flex-1 bg-background-light dark:bg-background-dark justify-between p-8">
        <View className="items-center mt-10 space-y-6">
            <Image
                source={logoUrl ? { uri: logoUrl } : require('../assets/icon.png')}
                className="h-20 w-40"
                resizeMode="contain"
            />
            <Text className="text-3xl font-bold text-center text-[#111318] dark:text-white mt-6">
                {t('welcome', { company: companyName })}
            </Text>
        </View>

        <View className="items-center w-full gap-8">
            <TouchableOpacity
                onPress={handleRegularFlow}
                className="w-full max-w-xl h-32 bg-primary rounded-xl flex-row items-center justify-center shadow-lg active:opacity-90"
            >
                <MaterialIcons name="touch-app" size={40} color="white" />
                <Text className="text-white text-3xl font-bold ml-4">
                    {t('idle.tap_to_enter')}
                </Text>
            </TouchableOpacity>

            <TouchableOpacity
                onPress={handleDelegationFlow}
                className="w-full max-w-[280px] h-[60px] border-2 border-gray-300 dark:border-gray-600 rounded-xl items-center justify-center active:bg-gray-100 dark:active:bg-gray-800"
            >
                <Text className="text-[#111318] dark:text-white text-lg font-semibold">
                    {t('idle.delegation')}
                </Text>
            </TouchableOpacity>
        </View>

        <View className="items-center mb-8">
            <View className="flex-row items-center gap-2 opacity-80 mb-4">
                <MaterialIcons name="schedule" size={20} color={i18n.language === 'dark' ? 'white' : 'black'} />
                <Text className="text-xl font-medium text-[#111318] dark:text-white">
                    {dateString}
                </Text>
            </View>

            <View className="flex-row gap-4">
                {['en', 'ro', 'hu', 'de'].map((lang) => (
                    <TouchableOpacity key={lang} onPress={() => changeLanguage(lang)}>
                        <Text className={`text-lg font-bold ${i18n.language === lang ? 'text-primary' : 'text-gray-400'}`}>
                            {lang.toUpperCase()}
                        </Text>
                    </TouchableOpacity>
                ))}
            </View>
        </View>
    </SafeAreaView>
  );
};

export default IdleScreen;