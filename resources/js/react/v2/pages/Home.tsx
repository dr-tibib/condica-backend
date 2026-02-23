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
    if (!code) return;
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
      
      const data = response.data;

      // Handle State Machine Transitions for V2
      if (data.type === 'correction_required') {
          navigate('/shift-correction', { state: { ...data, code } });
          return;
      }

      if (data.type === 'late_start_confirm') {
          navigate('/late-start-confirm', { state: { ...data, code } });
          return;
      }

      if (data.type === 'delegation_cancel_confirm') {
          navigate('/delegation-cancel', { state: { ...data, code } });
          return;
      }

      if (data.type === 'delegation_refinement_required') {
          navigate('/delegation-schedule', { state: { ...data, code, next_step: selectedFlow } });
          return;
      }

      if (data.type === 'leave_screen') {
          navigate('/leave', { state: { employee: data.employee, code } });
          return;
      }

      if (data.type === 'delegation_wizard') {
          navigate('/delegation', { state: { employee: data.employee } });
          return;
      }

      // Simple flows (checkin, checkout, etc)
      if (data.type === 'checkin' || data.type === 'checkout' || data.type === 'delegation_end_shift_start') {
          setSuccess(data.message);
          setLastActionTime(Date.now());
          setCode('');
          setSelectedFlow('regular');
          setTimeout(() => setSuccess(null), 3000);
          return;
      }

      // Fallback for direct responses
      if (selectedFlow === 'delegation' && data.employee) {
          navigate('/delegation', { state: { employee: data.employee } });
      } else if (selectedFlow === 'leave' && data.employee) {
          navigate('/leave', { state: { employee: data.employee, code } });
      } else {
          setSuccess(data.message || 'Operațiune reușită');
          setCode('');
          setLastActionTime(Date.now());
          setTimeout(() => setSuccess(null), 3000);
      }

    } catch (err: any) {
      setError(err.response?.data?.message || 'Eroare la procesarea codului');
      setCode('');
      setTimeout(() => setError(null), 3000);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="flex flex-col p-2.5 md:p-6 gap-3 md:gap-5 min-h-screen md:h-screen w-full md:overflow-hidden bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-sans">
      <header className="flex flex-col md:flex-row justify-between items-center gap-2.5 md:gap-3 bg-white dark:bg-slate-800 p-2.5 md:p-5 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
        <div className="flex items-center gap-2 md:gap-3">
          <div className="bg-primary text-white font-black text-xl md:text-4xl px-2 md:px-3 py-1 rounded">HD</div>
          <div className="text-primary dark:text-blue-400 font-extrabold text-lg md:text-3xl tracking-tight uppercase">Hidraulica</div>
        </div>
        <div className="flex items-center justify-between md:justify-end gap-2 md:gap-4 w-full md:w-auto">
          {dashboardData.stats && (
            <button
              onClick={() => setIsPresenceModalOpen(true)}
              className="flex items-center gap-1.5 md:gap-4 text-[10px] md:text-xl font-bold text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-700/30 px-2 md:px-4 py-1.5 md:py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer"
            >
              <div className="flex items-center gap-1 md:gap-2">
                <span className="material-symbols-outlined text-green-500 text-base md:text-2xl">how_to_reg</span>
                <span>{dashboardData.stats.present_count}</span>
              </div>
              <div className="flex items-center gap-1 md:gap-2">
                <span className="material-symbols-outlined text-blue-500 text-base md:text-2xl">flight_takeoff</span>
                <span>{dashboardData.stats.active_delegations_count}</span>
              </div>
              <div className="text-slate-300 dark:text-slate-600 hidden md:block">/</div>
              <div className="flex items-center gap-1 md:gap-2">
                <span className="material-symbols-outlined text-slate-400 text-base md:text-2xl">groups</span>
                <span>{dashboardData.stats.total_employees}</span>
              </div>
            </button>
          )}
          <div className="scale-90 md:scale-100 origin-right">
            <Clock />
          </div>
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
        <div className={`p-4 rounded-xl text-center text-lg md:text-2xl font-bold ${error ? 'bg-red-100 text-red-600 border border-red-200' : 'bg-green-100 text-green-600 border border-green-200'}`}>
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
