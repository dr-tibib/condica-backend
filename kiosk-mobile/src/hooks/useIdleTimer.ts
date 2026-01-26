import { useRef, useCallback, useEffect } from 'react';
import { PanResponder } from 'react-native';
import { useRouter } from 'expo-router';
import useAppStore from '../store/appStore';

const useIdleTimer = (timeout: number) => {
  const router = useRouter();
  const resetApp = useAppStore((state) => state.reset);
  const timerRef = useRef<NodeJS.Timeout | null>(null);

  const resetTimer = useCallback(() => {
    if (timerRef.current) {
      clearTimeout(timerRef.current);
    }
    timerRef.current = setTimeout(() => {
      resetApp();
      router.replace('/');
    }, timeout * 1000);
  }, [router, resetApp, timeout]);

  const panResponder = useRef(
    PanResponder.create({
      onStartShouldSetPanResponderCapture: () => {
        resetTimer();
        return false;
      },
      onMoveShouldSetPanResponderCapture: () => {
         resetTimer();
         return false;
      },
    })
  ).current;

  useEffect(() => {
    resetTimer();
    return () => {
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, [resetTimer]);

  return panResponder;
};

export default useIdleTimer;