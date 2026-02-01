import React from 'react';

const Dashboard = () => {
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
                  {/* Placeholder rows */}
                  <tr className="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                    <td className="py-3 px-4 flex items-center gap-3">
                        <span className="material-symbols-outlined text-green-500 font-bold">login</span>
                        <span className="font-semibold">Demo User</span>
                    </td>
                    <td className="py-3 px-4 text-right font-mono text-green-500 font-bold">12:00</td>
                  </tr>
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
                    <tr className="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td className="py-3 px-4 font-semibold">Demo User</td>
                        <td className="py-3 px-4 text-right font-mono text-slate-600 dark:text-slate-400 font-bold">25.10.2023</td>
                    </tr>
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
                     <tr className="grid grid-cols-12 items-center hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                        <td className="col-span-6 py-3 px-4 font-semibold">Demo User</td>
                        <td className="col-span-3 py-3 px-4 font-medium text-slate-600 dark:text-slate-400">Bucharest</td>
                        <td className="col-span-3 py-3 px-4 font-mono font-bold text-primary dark:text-blue-400 uppercase text-right">B-123-ABC</td>
                    </tr>
                </tbody>
            </table>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;
