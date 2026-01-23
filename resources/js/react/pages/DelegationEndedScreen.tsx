import { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { getKioskWorkplaceId } from '../utils/kiosk';

const DelegationEndedScreen = () => {
    const { t } = useTranslation();
    const navigate = useNavigate();
    const location = useLocation();
    const { user, code } = location.state || {};
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        if (!user || !code) {
             console.error('Missing user or code for delegation ended screen');
             navigate('/');
             return;
        }

        const timer = setTimeout(() => {
            navigate('/');
        }, 10000);

        return () => clearTimeout(timer);
    }, [navigate, user, code]);

    const handleCheckOut = async () => {
        setIsLoading(true);
        const workplaceId = getKioskWorkplaceId();

        try {
            const response = await fetch('/api/kiosk/submit-code', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    code: code,
                    flow: 'regular',
                    workplace_id: workplaceId,
                })
            });

            const data = await response.json();

            if (!response.ok) {
                 throw new Error(data.message || 'Checkout failed');
            }

            // Navigate to success screen showing checkout
            navigate('/success', {
                state: {
                    type: 'checkout', // Force type checkout
                    name: user.name,
                    time: data.time || new Date().toLocaleTimeString()
                }
            });

        } catch (error) {
            console.error(error);
            // Stay here? Or show error?
            // If error, maybe code expired or something.
            // Just go to idle or show error.
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-background-light dark:bg-background-dark flex flex-col items-center justify-center p-4 font-display text-slate-900 dark:text-white">
             <div className="relative w-full max-w-[768px] bg-white dark:bg-[#1a202c] shadow-2xl flex flex-col items-center justify-center rounded-xl p-12 text-center border border-slate-200 dark:border-slate-800 gap-8">

                 <div className="w-24 h-24 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mb-4">
                     <span className="material-symbols-outlined text-6xl">flag_circle</span>
                 </div>

                 <h1 className="text-4xl font-bold">
                     {t('delegation.ended_title', 'Delegation Ended')}
                 </h1>

                 <p className="text-xl text-slate-500 dark:text-slate-400 max-w-md">
                     {t('delegation.ended_message', 'You have returned from delegation. You remain checked in.')}
                 </p>

                 <div className="flex flex-col gap-4 w-full max-w-sm mt-8">
                     <button
                        onClick={handleCheckOut}
                        disabled={isLoading}
                        className="w-full h-16 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xl font-bold flex items-center justify-center gap-3 transition-colors shadow-lg shadow-red-500/20"
                     >
                         <span className="material-symbols-outlined text-3xl">logout</span>
                         {isLoading ? t('common.processing', 'Processing...') : t('common.check_out', 'Check Out Now')}
                     </button>

                     <div className="w-full max-w-md flex flex-col items-center gap-4 mt-4">
                         <div className="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                             <div className="h-full bg-primary w-full rounded-full animate-[progress_10s_linear_forwards]" style={{ animation: 'progress 10s linear forwards' }}></div>
                         </div>
                         <p className="text-slate-400 dark:text-slate-500 text-sm md:text-base font-medium text-center">
                             {t('success.returning_home')}
                         </p>
                     </div>
                 </div>
             </div>
             <style>{`
                @keyframes progress {
                  from { width: 0%; }
                  to { width: 100%; }
                }
              `}</style>
        </div>
    );
};

export default DelegationEndedScreen;
