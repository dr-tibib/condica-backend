import React, { useState } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import Clock from '../components/Clock';
import FlowSelector from '../components/FlowSelector';
import CodeInput from '../components/CodeInput';
import Dashboard from '../components/Dashboard';
import { getKioskWorkplaceId } from '../../utils/kiosk';

const Home = () => {
  const navigate = useNavigate();
  const [code, setCode] = useState('');
  const [selectedFlow, setSelectedFlow] = useState('regular');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [lastActionTime, setLastActionTime] = useState(Date.now());

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
      
      if (selectedFlow === 'delegation' && response.data.user && !response.data.type) {
        // Navigate to delegation wizard with user info
        navigate('/delegation', { state: { user: response.data.user } });
        return;
      }

      if (selectedFlow === 'concediu' && response.data.user) {
        navigate('/concediu', { state: { user: response.data.user, code } });
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
        <Clock />
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

      <Dashboard refreshTrigger={lastActionTime} refreshInterval={10000} />
    </div>
  );
};

export default Home;
