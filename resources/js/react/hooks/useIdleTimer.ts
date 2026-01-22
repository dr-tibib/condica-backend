import { useEffect, useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import useAppStore from '../store/appStore';

const useIdleTimer = (timeout: number) => {
  const navigate = useNavigate();
  const resetApp = useAppStore((state) => state.reset);
  const [timer, setTimer] = useState<number | null>(null);

  const resetTimer = useCallback(() => {
    if (timer) {
      clearTimeout(timer);
    }
    const newTimer = window.setTimeout(() => {
      resetApp();
      navigate('/');
    }, timeout * 1000);
    setTimer(newTimer);
  }, [navigate, resetApp, timeout, timer]);

  useEffect(() => {
    const events = ['mousemove', 'keydown', 'touchstart'];

    const handleActivity = () => {
      resetTimer();
    };

    events.forEach((event) => {
      window.addEventListener(event, handleActivity);
    });

    resetTimer();

    return () => {
      if (timer) {
        clearTimeout(timer);
      }
      events.forEach((event) => {
        window.removeEventListener(event, handleActivity);
      });
    };
  }, [resetTimer, timer]);

  return null;
};

export default useIdleTimer;