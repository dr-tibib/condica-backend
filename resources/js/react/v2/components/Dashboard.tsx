import React, { useState, useEffect } from 'react';
import axios from 'axios';

interface DashboardData {
    latest_logins: {
        id: number;
        user: string;
        time: string;
        type: string;
    }[];
    on_leave: {
        id: number;
        user: string;
        until: string;
    }[];
    active_delegations: {
        id: number;
        user: string;
        destination: string;
        vehicle: string;
    }[];
}

const Dashboard = () => {
  const [data, setData] = useState<DashboardData>({
      latest_logins: [],
      on_leave: [],
      active_delegations: []
  });

  useEffect(() => {
    const fetchData = async () => {
      try {
        const response = await axios.get('/api/kiosk/dashboard');
        setData(response.data);
      } catch (error) {
        console.error('Failed to fetch dashboard data', error);
      }
    };

    fetchData();
    const interval = setInterval(fetchData, 30000); // Poll every 30 seconds
    return () => clearInterval(interval);
  }, []);

  const getEventConfig = (type: string) => {
      switch (type) {
          case 'check_in':
              return { icon: 'login', color: 'text-green-500' };
          case 'check_out':
              return { icon: 'logout', color: 'text-red-500' };
          case 'delegation_start':
              return { icon: 'flight_takeoff', color: 'text-blue-500' };
          case 'delegation_end':
              return { icon: 'flight_land', color: 'text-orange-500' };
          default:
              return { icon: 'circle', color: 'text-slate-400' };
      }
  };

  return (
    <div className="flex-grow grid grid-rows-2 gap-5 overflow-hidden h-full">
      <div className="grid grid-cols-2 gap-5 h-full">
        <div className="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden shadow-sm">
          <div className="bg-slate-50 dark:bg-slate-700/50 p-3 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-4">
            <span className="font-bold uppercase text-slate-500 dark:text-slate-400 text-sm tracking-wider">Ultimele logări:</span>
            <span className="material-symbols-outlined text-slate-400">history</span>
          </div>
          <div className="flex-grow overflow-y-auto scroll-hide">
            <table className="w-full text-left border-collapse">
              <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                  {data.latest_logins.map((login) => {
                    const { icon, color } = getEventConfig(login.type);
                    return (
                        <tr key={login.id} className="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td className="py-3 px-4 flex items-center gap-3">
                            <span className={`material-symbols-outlined ${color} font-bold`}>
                                {icon}
                            </span>
                            <span className="font-semibold">{login.user}</span>
                        </td>
                        <td className={`py-3 px-4 text-right font-mono font-bold ${color}`}>{login.time}</td>
                        </tr>
                    );
                  })}
                  {data.latest_logins.length === 0 && (
                      <tr><td colSpan={2} className="py-4 text-center text-slate-400">Nu există logări recente.</td></tr>
                  )}
              </tbody>
            </table>
          </div>
        </div>
        <div className="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden shadow-sm">
          <div className="bg-slate-50 dark:bg-slate-700/50 p-3 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-4">
            <span className="font-bold uppercase text-slate-500 dark:text-slate-400 text-sm tracking-wider">În concediu:</span>
            <span className="font-bold uppercase text-slate-500 dark:text-slate-400 text-sm tracking-wider">Până la:</span>
          </div>
          <div className="flex-grow overflow-y-auto scroll-hide">
             <table className="w-full text-left border-collapse">
                <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                    {data.on_leave.map((leave) => (
                        <tr key={leave.id} className="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td className="py-3 px-4 font-semibold">{leave.user}</td>
                            <td className="py-3 px-4 text-right font-mono text-slate-600 dark:text-slate-400 font-bold">{leave.until}</td>
                        </tr>
                    ))}
                    {data.on_leave.length === 0 && (
                      <tr><td colSpan={2} className="py-4 text-center text-slate-400">Nimeni în concediu.</td></tr>
                    )}
                </tbody>
             </table>
          </div>
        </div>
      </div>
      <div className="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden shadow-sm">
        <div className="bg-slate-100 dark:bg-slate-700/80 p-3 border-b border-slate-200 dark:border-slate-700 grid grid-cols-12 px-4">
          <div className="col-span-6 font-bold uppercase text-slate-500 dark:text-slate-400 text-sm tracking-wider">În delegație:</div>
          <div className="col-span-3 font-bold uppercase text-slate-500 dark:text-slate-400 text-sm tracking-wider">Destinație:</div>
          <div className="col-span-3 font-bold uppercase text-slate-500 dark:text-slate-400 text-sm tracking-wider text-right">Auto:</div>
        </div>
        <div className="flex-grow overflow-y-auto scroll-hide">
            <table className="w-full text-left border-collapse">
                <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                    {data.active_delegations.map((delegation) => (
                         <tr key={delegation.id} className="grid grid-cols-12 items-center hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td className="col-span-6 py-3 px-4 font-semibold">{delegation.user}</td>
                            <td className="col-span-3 py-3 px-4 font-medium text-slate-600 dark:text-slate-400">{delegation.destination}</td>
                            <td className="col-span-3 py-3 px-4 font-mono font-bold text-primary dark:text-blue-400 uppercase text-right">{delegation.vehicle}</td>
                        </tr>
                    ))}
                    {data.active_delegations.length === 0 && (
                      <tr><td colSpan={3} className="py-4 text-center text-slate-400 block w-full">Nimeni în delegație.</td></tr>
                    )}
                </tbody>
            </table>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
