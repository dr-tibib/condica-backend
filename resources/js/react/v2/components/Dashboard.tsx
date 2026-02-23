import React from 'react';
import { useTranslation } from 'react-i18next';

export interface DashboardData {
    latest_logins: {
        id: number;
        employee: string;
        time: string;
        type: string;
    }[];
    on_leave: {
        id: number;
        employee: string;
        until: string;
    }[];
    active_delegations: {
        id: number;
        employee: string;
        destination: string;
        vehicle: string;
    }[];
    stats?: {
        total_employees: number;
        present_count: number;
        active_delegations_count: number;
    };
}

interface DashboardProps {
    data: DashboardData;
}

const Dashboard = ({ data }: DashboardProps) => {
  const { t } = useTranslation();
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
    <div className="flex-grow flex flex-col md:grid md:grid-rows-2 gap-4 md:gap-5 min-h-0 h-full overflow-y-auto md:overflow-hidden">
      <div className="flex flex-col md:grid md:grid-cols-2 gap-4 md:gap-5 md:min-h-0">
        <div className="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden shadow-sm min-h-[250px] md:min-h-0">
          <div className="bg-slate-50 dark:bg-slate-700/50 p-2 md:p-3 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-4">
            <span className="font-bold uppercase text-slate-500 dark:text-slate-400 text-xs md:text-sm tracking-wider">{t('idle.latest_logins', 'Ultimele logări:')}</span>
            <span className="material-symbols-outlined text-slate-400 text-lg md:text-2xl">history</span>
          </div>
          <div className="flex-grow overflow-y-auto scroll-hide min-h-0">
            <table className="w-full text-left border-collapse">
              <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                  {data?.latest_logins?.map((login) => {
                    const { icon, color } = getEventConfig(login.type);
                    return (
                        <tr key={login.id} className="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td className="py-2 md:py-3 px-3 md:px-4 flex items-center gap-2 md:gap-3">
                            <span className={`material-symbols-outlined ${color} font-bold text-lg md:text-2xl`}>
                                {icon}
                            </span>
                            <span className="font-semibold text-sm md:text-base">{login.employee}</span>
                        </td>
                        <td className={`py-2 md:py-3 px-3 md:px-4 text-right font-mono font-bold text-xs md:text-base ${color}`}>{login.time}</td>
                        </tr>
                    );
                  })}
                  {(!data?.latest_logins || data.latest_logins.length === 0) && (
                      <tr><td colSpan={2} className="py-4 text-center text-slate-400 text-sm">{t('idle.no_recent_logins', 'Nu există logări recente.')}</td></tr>
                  )}
              </tbody>
            </table>
          </div>
        </div>
        <div className="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden shadow-sm min-h-[250px] md:min-h-0">
          <div className="bg-slate-50 dark:bg-slate-700/50 p-2 md:p-3 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center px-4">
            <span className="font-bold uppercase text-slate-500 dark:text-slate-400 text-xs md:text-sm tracking-wider">{t('idle.on_leave', 'În concediu:')}</span>
            <span className="font-bold uppercase text-slate-500 dark:text-slate-400 text-xs md:text-sm tracking-wider">{t('idle.until', 'Până la:')}</span>
          </div>
          <div className="flex-grow overflow-y-auto scroll-hide min-h-0">
             <table className="w-full text-left border-collapse">
                <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                    {data?.on_leave?.map((leave) => (
                        <tr key={leave.id} className="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td className="py-2 md:py-3 px-3 md:px-4 font-semibold text-sm md:text-base">{leave.employee}</td>
                            <td className="py-2 md:py-3 px-3 md:px-4 text-right font-mono text-slate-600 dark:text-slate-400 font-bold text-xs md:text-base">{leave.until}</td>
                        </tr>
                    ))}
                    {(!data?.on_leave || data.on_leave.length === 0) && (
                      <tr><td colSpan={2} className="py-4 text-center text-slate-400 text-sm">{t('idle.no_one_on_leave', 'Nimeni în concediu.')}</td></tr>
                    )}
                </tbody>
             </table>
          </div>
        </div>
      </div>
      <div className="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 flex flex-col overflow-hidden shadow-sm min-h-[300px] md:min-h-0 mb-4 md:mb-0">
        <div className="bg-slate-100 dark:bg-slate-700/80 p-2 md:p-3 border-b border-slate-200 dark:border-slate-700 grid grid-cols-12 px-4">
          <div className="col-span-6 font-bold uppercase text-slate-500 dark:text-slate-400 text-xs md:text-sm tracking-wider">{t('idle.in_delegation', 'În delegație:')}</div>
          <div className="col-span-3 font-bold uppercase text-slate-500 dark:text-slate-400 text-xs md:text-sm tracking-wider">{t('idle.destination', 'Destinație:')}</div>
          <div className="col-span-3 font-bold uppercase text-slate-500 dark:text-slate-400 text-xs md:text-sm tracking-wider text-right">{t('idle.vehicle', 'Auto:')}</div>
        </div>
        <div className="flex-grow overflow-y-auto scroll-hide min-h-0">
            <table className="w-full text-left border-collapse">
                <tbody className="divide-y divide-slate-100 dark:divide-slate-700">
                    {data?.active_delegations?.map((delegation) => (
                         <tr key={delegation.id} className="grid grid-cols-12 items-center hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td className="col-span-6 py-2 md:py-3 px-3 md:px-4 font-semibold text-sm md:text-base">{delegation.employee}</td>
                            <td className="col-span-3 py-2 md:py-3 px-3 md:px-4 font-medium text-slate-600 dark:text-slate-400 text-xs md:text-sm">{delegation.destination}</td>
                            <td className="col-span-3 py-2 md:py-3 px-3 md:px-4 font-mono font-bold text-primary dark:text-blue-400 uppercase text-right text-xs md:text-sm">{delegation.vehicle}</td>
                        </tr>
                    ))}
                    {(!data?.active_delegations || data.active_delegations.length === 0) && (
                      <tr><td colSpan={3} className="py-4 text-center text-slate-400 block w-full text-sm">{t('idle.no_one_in_delegation', 'Nimeni în delegație.')}</td></tr>
                    )}
                </tbody>
            </table>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
