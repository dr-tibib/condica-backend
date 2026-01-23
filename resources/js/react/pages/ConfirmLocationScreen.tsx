import { useLocation, useNavigate } from 'react-router-dom';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { getKioskWorkplaceId } from '../utils/kiosk';

const ConfirmLocationScreen = () => {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const location = useLocation();
    const { location: selectedLocation, user } = location.state || {};
    const [isLoading, setIsLoading] = useState(false);

    const handleBack = () => {
        navigate(-1);
    };

    const handleStartDelegation = async () => {
        if (!user || !selectedLocation) {
             console.error('Missing user or location data');
             return;
        }

        setIsLoading(true);

        try {
            const workplaceId = getKioskWorkplaceId();

            const response = await fetch('/api/delegations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    user_id: user.id,
                    place_id: selectedLocation.place_id,
                    name: selectedLocation.name,
                    address: selectedLocation.address,
                    latitude: selectedLocation.latitude,
                    longitude: selectedLocation.longitude,
                    workplace_id: workplaceId,
                    // device_info?
                })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || t('confirm.delegation_failed'));
            }

           navigate('/success', {
               state: {
                   type: data.type,
                   name: data.user.name,
                   time: data.time
               }
            });

        } catch (error) {
            console.error(error);
            // Ideally show error to user
        } finally {
            setIsLoading(false);
        }
    };

  return (
    <div className="relative flex h-screen w-full flex-col bg-background-light dark:bg-background-dark group/design-root overflow-hidden font-display">
      <header className="flex items-center justify-between whitespace-nowrap border-b border-solid border-[#dbdee6] dark:border-[#2e3440] px-6 py-4 bg-white dark:bg-[#1a202c]">
        <div className="flex items-center gap-4 text-[#111318] dark:text-white">
          <div className="size-6 flex items-center justify-center text-primary">
            <span className="material-symbols-outlined">domain</span>
          </div>
          <h2 className="text-[#111318] dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">{t('confirm.workplace_presence')}</h2>
        </div>
        <div className="flex items-center gap-9">
          <button onClick={handleBack} className="text-[#111318] dark:text-white text-sm font-medium leading-normal flex items-center gap-2 hover:text-primary transition-colors">
            <span className="material-symbols-outlined text-[20px]">arrow_back</span>
            {t('common.back')}
          </button>
        </div>
      </header>
      <div className="flex flex-col flex-1 overflow-y-auto px-6 py-8 sm:px-12 md:px-24 justify-start items-center">
        <div className="w-full max-w-[600px] flex flex-col gap-6">
          <div className="flex flex-col gap-2 text-center sm:text-left">
            <h1 className="text-[#111318] dark:text-white text-3xl sm:text-4xl font-black leading-tight tracking-[-0.033em]">{t('confirm.title')}</h1>
            <p className="text-[#616e89] dark:text-[#94a3b8] text-base font-normal leading-normal">{t('confirm.subtitle')}</p>
          </div>
          <div className="flex flex-col rounded-xl shadow-sm border border-[#dbdee6] dark:border-[#2e3440] bg-white dark:bg-[#1a202c] overflow-hidden">
            <div
              className="w-full h-48 bg-center bg-no-repeat bg-cover relative"
              style={{ backgroundImage: 'url("https://lh3.googleusercontent.com/aida-public/AB6AXuBm6gaZNMsn0EISpi7egdH-kEsMjoCwSQcokxwE6wsfC6CAMeRRGoIja2pVDzm0BH8Y8zjA3_V51lyKbgquzjpbezUP5dbADolH2uXb4X-zDcyNdkfo32RDochQ7_U9-I6qZmZI0Tp452GfG_4kmOThKNyHbVuTocnd8iN9bvPRUo7WlTpYOPpjyIuQ7FtuisavHB4VP-XO9ztDTp4fPtdn3nkpvW_l_J06Ohu6MedSbPRIrSkiZ4nKZ4EstYj86WlP7AZWV2gKuxE")' }}
            >
              <div className="absolute inset-0 bg-black/10"></div>
              <div className="absolute bottom-4 right-4 bg-white dark:bg-[#1a202c] p-2 rounded-lg shadow-md flex items-center gap-2">
                <span className="material-symbols-outlined text-primary">my_location</span>
                <span className="text-xs font-bold text-[#111318] dark:text-white">{t('confirm.current_location')}</span>
              </div>
            </div>
            <div className="flex flex-col gap-4 p-6">
              <div className="flex items-start justify-between gap-4">
                <div className="flex gap-4">
                  <div className="flex-shrink-0 size-12 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                    <span className="material-symbols-outlined text-2xl">{selectedLocation?.icon || 'storefront'}</span>
                  </div>
                  <div className="flex flex-col gap-1">
                    <h3 className="text-[#111318] dark:text-white text-xl font-bold leading-tight">{selectedLocation?.name || t('confirm.no_location')}</h3>
                    <p className="text-[#616e89] dark:text-[#94a3b8] text-base font-normal">{selectedLocation?.address || ''}</p>
                  </div>
                </div>
                {selectedLocation?.distance && (
                    <div className="hidden sm:flex items-center gap-1 text-primary bg-primary/10 px-3 py-1 rounded-full text-sm font-semibold whitespace-nowrap">
                        <span className="material-symbols-outlined text-lg">near_me</span>
                        {selectedLocation.distance}
                    </div>
                )}
              </div>
              {selectedLocation?.distance && (
                <div className="flex sm:hidden items-center gap-1 text-primary text-sm font-semibold">
                    <span className="material-symbols-outlined text-lg">near_me</span>
                    {selectedLocation.distance} {t('confirm.away', 'away')}
                </div>
              )}
            </div>
          </div>
          <div className="bg-white dark:bg-[#1a202c] rounded-xl border border-[#dbdee6] dark:border-[#2e3440] p-4">
            <label className="flex items-center gap-4 cursor-pointer group">
              <div className="relative flex items-center">
                <input className="peer h-6 w-6 rounded border-2 border-[#dbdee6] bg-transparent text-primary checked:bg-primary checked:border-primary focus:ring-0 focus:ring-offset-0 transition-all cursor-pointer appearance-none checked:after:content-[''] checked:after:absolute checked:after:left-[7px] checked:after:top-[3px] checked:after:w-[8px] checked:after:h-[13px] checked:after:border-white checked:after:border-r-2 checked:after:border-b-2 checked:after:rotate-45" type="checkbox" />
              </div>
              <div className="flex flex-col">
                <p className="text-[#111318] dark:text-white text-base font-medium leading-normal group-hover:text-primary transition-colors">{t('confirm.save_future')}</p>
                <p className="text-[#616e89] dark:text-gray-400 text-sm font-normal">{t('confirm.remember_location')}</p>
              </div>
            </label>
          </div>
        </div>
      </div>
      <div className="w-full bg-white dark:bg-[#1a202c] border-t border-[#dbdee6] dark:border-[#2e3440] px-6 py-6 sm:px-12 flex justify-center mt-auto z-10">
        <div className="w-full max-w-[600px] flex flex-col sm:flex-row gap-4">
          <button
            onClick={handleStartDelegation}
            disabled={isLoading}
            className="flex-1 min-w-[120px] cursor-pointer items-center justify-center overflow-hidden rounded-xl h-14 px-6 bg-primary hover:bg-blue-700 dark:hover:bg-blue-600 text-white text-lg font-bold leading-normal tracking-[0.015em] transition-all shadow-md active:scale-[0.98] disabled:opacity-70"
          >
            {isLoading ? t('common.processing') : t('confirm.start_delegation')}
          </button>
          <button onClick={handleBack} disabled={isLoading} className="sm:flex-none sm:w-auto flex-1 min-w-[100px] cursor-pointer items-center justify-center overflow-hidden rounded-xl h-14 px-6 bg-transparent hover:bg-background-light dark:hover:bg-[#2d3748] text-[#616e89] dark:text-[#94a3b8] text-base font-medium leading-normal tracking-[0.015em] transition-all border border-transparent hover:border-[#dbdee6] dark:hover:border-[#4a5568]">
            {t('common.cancel')}
          </button>
        </div>
      </div>
    </div>
  );
};

export default ConfirmLocationScreen;
