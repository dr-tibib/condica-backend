import { useState, useEffect } from 'react';

const useGoogleMaps = (apiKey: string, libraries: string[] = ['places']) => {
  const [isLoaded, setIsLoaded] = useState(false);
  const [loadError, setLoadError] = useState<Error | null>(null);

  useEffect(() => {
    if (!apiKey) {
        setLoadError(new Error('Google Maps API key is missing'));
        return;
    }

    // Check if script is already loaded
    if ((window as any).google?.maps) {
      setIsLoaded(true);
      return;
    }

    // Check if script is currently loading
    const existingScript = document.querySelector('script[src^="https://maps.googleapis.com/maps/api/js"]');
    if (existingScript) {
       existingScript.addEventListener('load', () => setIsLoaded(true));
       existingScript.addEventListener('error', (e) => setLoadError(new Error('Failed to load Google Maps script')));
       return;
    }

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=${libraries.join(',')}`;
    script.async = true;
    script.defer = true;
    script.id = 'google-maps-script';

    script.onload = () => setIsLoaded(true);
    script.onerror = (e) => setLoadError(new Error('Failed to load Google Maps script'));

    document.body.appendChild(script);

    return () => {
      // We generally don't remove the script as other components might use it
    };
  }, [apiKey, libraries]);

  return { isLoaded, loadError };
};

export default useGoogleMaps;
