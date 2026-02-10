import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useNavigate, useLocation } from 'react-router-dom';
import Clock from '../components/Clock';
import FlowSelector from '../components/FlowSelector';
import CodeInput from '../components/CodeInput';
import Dashboard, { DashboardData } from '../components/Dashboard';
import PresenceModal from '../components/PresenceModal';
import { getKioskWorkplaceId } from '../../utils/kiosk';

const Home = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [code, setCode] = useState('');
  const [selectedFlow, setSelectedFlow] = useState('regular');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [lastActionTime, setLastActionTime] = useState(Date.now());
  const [isPresenceModalOpen, setIsPresenceModalOpen] = useState(false);
  const [dashboardData, setDashboardData] = useState<DashboardData>({
    latest_logins: [],
    on_leave: [],
    active_delegations: []
  });

  useEffect(() => {
    if (location.state?.success) {
      setSuccess(location.state.success);
      navigate(location.pathname, { replace: true, state: {} });
      setTimeout(() => setSuccess(null), 3000);
    }
  }, [location.state, navigate, location.pathname]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const response = await axios.get('/api/kiosk/dashboard');
        setDashboardData(response.data);
      } catch (error) {
        console.error('Failed to fetch dashboard data', error);
      }
    };

    fetchData();
    const interval = setInterval(fetchData, 10000);
    return () => clearInterval(interval);
  }, [lastActionTime]);

  const handleSubmit = async () => {
    setIsLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const workplaceId = getKioskWorkplaceId();
      const response = await axios.post('/api/kiosk/submit-code', {
        code,
        flow: selectedFlow,
        workplace_id: workplaceId,
      });
      
      if (selectedFlow === 'delegation' && response.data.employee && !response.data.type) {
        // Navigate to delegation wizard with employee info
        navigate('/delegation', { state: { employee: response.data.employee } });
        return;
      }

      if (selectedFlow === 'concediu' && response.data.employee) {
        navigate('/concediu', { state: { employee: response.data.employee, code } });
        return;
      }

      if (response.data.type === 'delegation_end_schedule_required') {
        navigate('/delegation-schedule', { state: { ...response.data, code } });
        return;
      }

      setSuccess(response.data.message);
      setLastActionTime(Date.now());
      setCode('');
      setSelectedFlow('regular');
      setTimeout(() => setSuccess(null), 3000);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Error processing code');
      setTimeout(() => setError(null), 3000);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="flex flex-col p-6 gap-5 h-screen w-screen overflow-hidden bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-sans">
      <header className="flex justify-between items-center bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div className="flex items-center gap-3">
          <div className="bg-primary text-white font-black text-4xl px-3 py-1 rounded">HD</div>
          <div className="text-primary dark:text-blue-400 font-extrabold text-3xl tracking-tight uppercase">Hidraulica</div>
        </div>
        <div className="flex items-center gap-6">
          {dashboardData.stats && (
            <button
              onClick={() => setIsPresenceModalOpen(true)}
              className="flex items-center gap-4 text-xl font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700/50 px-4 py-2 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer"
            >
              <div className="flex items-center gap-2">
                <span className="material-symbols-outlined text-green-500">how_to_reg</span>
                <span>{dashboardData.stats.present_count}</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="material-symbols-outlined text-blue-500">flight_takeoff</span>
                <span>{dashboardData.stats.active_delegations_count}</span>
              </div>
              <div className="text-slate-300 dark:text-slate-600">/</div>
              <div className="flex items-center gap-2">
                <span className="material-symbols-outlined text-slate-400">groups</span>
                <span>{dashboardData.stats.total_employees}</span>
              </div>
            </button>
          )}
          <Clock />
        </div>
      </header>
      
      <FlowSelector
        selectedFlow={selectedFlow}
        onSelectFlow={setSelectedFlow}
      />

      <CodeInput 
        value={code} 
        onChange={setCode} 
        onSubmit={handleSubmit} 
        isLoading={isLoading} 
      />

      {(error || success) && (
        <div className={`p-4 rounded-xl text-center text-2xl font-bold ${error ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'}`}>
            {error || success}
        </div>
      )}

      <Dashboard data={dashboardData} />

      <PresenceModal
        isOpen={isPresenceModalOpen}
        onClose={() => setIsPresenceModalOpen(false)}
      />
    </div>
  );
};

export default Home;
