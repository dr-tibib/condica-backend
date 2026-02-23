import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getAdminToken, setKioskWorkplaceId, clearAdminToken } from '../../utils/kiosk';

interface Workplace {
  id: number;
  name: string;
  address: string | null;
}

const KioskSetupScreen = () => {
  const [workplaces, setWorkplaces] = useState<Workplace[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const navigate = useNavigate();

  useEffect(() => {
    const fetchWorkplaces = async () => {
      const token = getAdminToken();
      if (!token) {
        navigate('/login');
        return;
      }

      try {
        const response = await fetch('/api/workplaces', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
        });

        if (!response.ok) {
           if (response.status === 401) {
               clearAdminToken();
               navigate('/login');
               return;
           }
           throw new Error('Failed to fetch workplaces');
        }

        const data = await response.json();
        setWorkplaces(data.data || []);
      } catch (err: any) {
        setError(err.message);
      } finally {
        setIsLoading(false);
      }
    };

    fetchWorkplaces();
  }, [navigate]);

  const handleSelectWorkplace = (workplace: Workplace) => {
    setKioskWorkplaceId(workplace.id);
    clearAdminToken(); // Security: Clear admin token after setup
    navigate('/');
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 font-display">
      <div className="max-w-3xl mx-auto">
        <div className="text-center mb-12">
          <h1 className="text-3xl font-extrabold text-gray-900 dark:text-white">
            Setup Kiosk
          </h1>
          <p className="mt-4 text-lg text-gray-600 dark:text-gray-400">
            Select the workplace for this kiosk
          </p>
        </div>

        {error && (
          <div className="mb-8 bg-red-50 dark:bg-red-900/20 p-4 rounded-md text-red-700 dark:text-red-300 text-center">
            {error}
          </div>
        )}

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          {workplaces.map((workplace) => (
            <button
              key={workplace.id}
              onClick={() => handleSelectWorkplace(workplace)}
              className="relative rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-primary dark:hover:border-primary focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary transition-all text-left"
            >
              <div className="flex-shrink-0">
                 <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                    <span className="material-symbols-outlined">domain</span>
                 </div>
              </div>
              <div className="flex-1 min-w-0">
                  <span className="absolute inset-0" aria-hidden="true" />
                  <p className="text-sm font-medium text-gray-900 dark:text-white">
                    {workplace.name}
                  </p>
                  <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {workplace.address || 'No address'}
                  </p>
              </div>
            </button>
          ))}

          {workplaces.length === 0 && !error && (
               <div className="col-span-full text-center text-gray-500 py-12">
                   No workplaces found.
               </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default KioskSetupScreen;
