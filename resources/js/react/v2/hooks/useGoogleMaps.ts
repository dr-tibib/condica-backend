import { useState, useEffect, useRef } from 'react';

const useGoogleMaps = (apiKey: string, libraries: string[] = ['places']) => {
  const [isLoaded, setIsLoaded] = useState(false);
  const [loadError, setLoadError] = useState<Error | null>(null);

  // Use ref to track if component is mounted to avoid state updates on unmounted component
  const isMounted = useRef(true);

  // Memoize libraries to avoid effect re-running due to array reference change
  const librariesRef = useRef(libraries);

  // Simple deep compare for libraries array
  if (JSON.stringify(librariesRef.current) !== JSON.stringify(libraries)) {
      librariesRef.current = libraries;
  }

  useEffect(() => {
    isMounted.current = true;
    return () => { isMounted.current = false; };
  }, []);

  useEffect(() => {
    if (!apiKey) {
      setLoadError(new Error('Google Maps API key is missing'));
      return;
    }

    const checkLibraryLoaded = () => {
      const google = (window as any).google;
      if (!google?.maps) return false;

      // If 'places' is requested, check for it
      if (librariesRef.current.includes('places') && !google.maps.places) return false;

      return true;
    };

    const loadLibraries = async () => {
        if (!isMounted.current) return;

        if ((window as any).google?.maps?.importLibrary) {
            try {
                await Promise.all(librariesRef.current.map(lib => (window as any).google.maps.importLibrary(lib)));
                if (isMounted.current) setIsLoaded(true);
            } catch (e) {
                console.error("Failed to import libraries", e);
                if (isMounted.current) setLoadError(new Error('Failed to import libraries'));
            }
        } else {
             // Fallback for immediate check if importLibrary is not available
             if (checkLibraryLoaded()) {
                 if (isMounted.current) setIsLoaded(true);
             }
        }
    };

    if (checkLibraryLoaded()) {
      setIsLoaded(true);
      return;
    }

    // If google.maps exists but libraries are missing, try importing them
    if ((window as any).google?.maps) {
        loadLibraries();
        return;
    }

    const callbackName = 'initGoogleMapsCallback';

    // Hook into global callback
    const originalCallback = (window as any)[callbackName];
    (window as any)[callbackName] = () => {
      if (typeof originalCallback === 'function') {
          originalCallback();
      }
      loadLibraries();
    };

    const existingScript = document.querySelector('script[src^="https://maps.googleapis.com/maps/api/js"]');
    if (existingScript) {
      existingScript.addEventListener('load', () => {
          loadLibraries();
      });

      // Polling fallback
      const intervalId = setInterval(() => {
          if (checkLibraryLoaded()) {
              if (isMounted.current) setIsLoaded(true);
              clearInterval(intervalId);
          } else if ((window as any).google?.maps) {
               loadLibraries();
               clearInterval(intervalId);
          }
      }, 500);

      setTimeout(() => clearInterval(intervalId), 10000);

      return;
    }

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=${librariesRef.current.join(',')}&loading=async&callback=${callbackName}`;
    script.async = true;
    script.defer = true;
    script.id = 'google-maps-script';

    script.onerror = (e) => {
        if (isMounted.current) setLoadError(new Error('Failed to load Google Maps script'));
    };

    document.body.appendChild(script);

    return () => {
      // We don't remove script tag
    };
  }, [apiKey, librariesRef.current]);

  return { isLoaded, loadError };
};

export default useGoogleMaps;
