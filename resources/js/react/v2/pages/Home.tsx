import React, { useState } from 'react';
import axios from 'axios';
import Clock from '../components/Clock';
import FlowSelector from '../components/FlowSelector';
import CodeInput from '../components/CodeInput';
import Dashboard from '../components/Dashboard';
import { getKioskWorkplaceId } from '../../utils/kiosk';

const Home = () => {
  const [code, setCode] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const handleSubmit = async () => {
    setIsLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const workplaceId = getKioskWorkplaceId();
      const response = await axios.post('/api/kiosk/submit-code', {
        code,
        flow: 'regular',
        workplace_id: workplaceId,
      });
      
      setSuccess(response.data.message);
      setCode('');
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
      
      <FlowSelector onNormalClick={() => { /* Stay on Normal */ }} /> 

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

      <Dashboard />
    </div>
  );
};

export default Home;
