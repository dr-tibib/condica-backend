import React, { useEffect, useState, useMemo } from 'react';
import axios from 'axios';

interface EmployeeStatus {
    id: number;
    name: string;
    avatar: string;
    status: 'present' | 'leave' | 'delegation' | 'absent';
    details: string | null;
}

interface PresenceModalProps {
    isOpen: boolean;
    onClose: () => void;
}

const PresenceModal = ({ isOpen, onClose }: PresenceModalProps) => {
    const [employees, setEmployees] = useState<EmployeeStatus[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [search, setSearch] = useState('');

    useEffect(() => {
        if (isOpen) {
            fetchEmployees();
        }
    }, [isOpen]);

    const fetchEmployees = async () => {
        setIsLoading(true);
        try {
            const response = await axios.get('/api/kiosk/employees/status');
            setEmployees(response.data.data);
        } catch (error) {
            console.error('Failed to fetch employee status', error);
        } finally {
            setIsLoading(false);
        }
    };

    const filteredEmployees = useMemo(() => {
        return employees.filter(emp =>
            emp.name.toLowerCase().includes(search.toLowerCase())
        );
    }, [employees, search]);

    const getStatusConfig = (status: string) => {
        switch (status) {
            case 'present':
                return { icon: 'check_circle', color: 'text-green-500', bg: 'bg-green-50 dark:bg-green-900/20' };
            case 'leave':
                return { icon: 'beach_access', color: 'text-purple-500', bg: 'bg-purple-50 dark:bg-purple-900/20' };
            case 'delegation':
                return { icon: 'flight', color: 'text-blue-500', bg: 'bg-blue-50 dark:bg-blue-900/20' };
            default: // absent
                return { icon: 'cancel', color: 'text-slate-300', bg: 'bg-slate-50 dark:bg-slate-800' };
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 transition-opacity duration-300">
            <div className="bg-white dark:bg-slate-900 w-full max-w-6xl h-[85vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-slate-200 dark:border-slate-700 transition-transform duration-300">

                {/* Header */}
                <div className="p-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 z-10">
                    <h2 className="text-3xl font-bold text-slate-800 dark:text-white flex items-center gap-3">
                        <span className="material-symbols-outlined text-4xl text-primary">groups</span>
                        Status Prezență Angajați
                    </h2>
                    <button
                        onClick={onClose}
                        className="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors text-slate-500 dark:text-slate-400"
                    >
                        <span className="material-symbols-outlined text-3xl">close</span>
                    </button>
                </div>

                {/* Search & Stats */}
                <div className="p-6 pb-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="relative">
                        <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
                        <input
                            type="text"
                            placeholder="Caută angajat..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full pl-12 pr-4 py-4 rounded-xl bg-slate-100 dark:bg-slate-800 border-none text-lg text-slate-800 dark:text-white focus:ring-2 focus:ring-primary outline-none transition-all placeholder:text-slate-400"
                            autoFocus
                        />
                    </div>
                    <div className="flex gap-3 overflow-x-auto pb-2 scroll-hide">
                         <div className="flex items-center gap-2 px-4 py-2 bg-green-50 dark:bg-green-900/20 rounded-lg whitespace-nowrap">
                            <span className="material-symbols-outlined text-green-500">check_circle</span>
                            <span className="font-bold text-green-700 dark:text-green-300">
                                {employees.filter(e => e.status === 'present').length} Prezenți
                            </span>
                         </div>
                         <div className="flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg whitespace-nowrap">
                            <span className="material-symbols-outlined text-blue-500">flight</span>
                            <span className="font-bold text-blue-700 dark:text-blue-300">
                                {employees.filter(e => e.status === 'delegation').length} Delegație
                            </span>
                         </div>
                         <div className="flex items-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg whitespace-nowrap">
                            <span className="material-symbols-outlined text-purple-500">beach_access</span>
                            <span className="font-bold text-purple-700 dark:text-purple-300">
                                {employees.filter(e => e.status === 'leave').length} Concediu
                            </span>
                         </div>
                    </div>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-6 pt-2">
                    {isLoading ? (
                        <div className="flex justify-center items-center h-full">
                            <div className="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-primary"></div>
                        </div>
                    ) : filteredEmployees.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            {filteredEmployees.map((employee) => {
                                const { icon, color, bg } = getStatusConfig(employee.status);
                                return (
                                    <div key={employee.id} className={`flex items-center gap-4 p-4 rounded-xl border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all ${bg}`}>
                                        <div className="relative shrink-0">
                                            <img
                                                src={employee.avatar}
                                                alt={employee.name}
                                                className="w-16 h-16 rounded-full object-cover shadow-sm bg-white"
                                                onError={(e) => {
                                                    (e.target as HTMLImageElement).src = `https://ui-avatars.com/api/?name=${encodeURIComponent(employee.name)}&background=random`;
                                                }}
                                            />
                                            <div className={`absolute -bottom-1 -right-1 bg-white dark:bg-slate-900 rounded-full p-1`}>
                                                <span className={`material-symbols-outlined ${color} text-xl block`}>{icon}</span>
                                            </div>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <h3 className="font-bold text-lg text-slate-800 dark:text-white truncate">{employee.name}</h3>
                                            <p className={`text-sm font-bold truncate ${color} uppercase tracking-wide`}>
                                                {employee.status === 'present' ? 'Prezent' :
                                                 employee.status === 'leave' ? 'În Concediu' :
                                                 employee.status === 'delegation' ? 'În Delegație' : 'Absent'}
                                            </p>
                                            {employee.details && (
                                                <p className="text-xs text-slate-500 dark:text-slate-400 truncate mt-1 font-medium">
                                                    {employee.details}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="flex flex-col justify-center items-center h-full text-slate-400">
                            <span className="material-symbols-outlined text-6xl mb-4 block opacity-50">search_off</span>
                            <p className="text-xl">Nu am găsit angajați.</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default PresenceModal;
