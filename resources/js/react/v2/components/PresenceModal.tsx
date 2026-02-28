import React, { useEffect, useState, useMemo, useRef } from 'react';
import axios from 'axios';
import VirtualKeyboard from './VirtualKeyboard';

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

type StatusFilter = 'all' | 'present' | 'leave' | 'delegation';

const PresenceModal = ({ isOpen, onClose }: PresenceModalProps) => {
    const [employees, setEmployees] = useState<EmployeeStatus[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [selectedStatus, setSelectedStatus] = useState<StatusFilter>('all');
    const [showKeyboard, setShowKeyboard] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (isOpen) {
            fetchEmployees();
            // Reset state when opening
            setSearch('');
            setSelectedStatus('all');
            setShowKeyboard(false);
        }
    }, [isOpen]);

    const fetchEmployees = async () => {
        setIsLoading(true);
        try {
            const response = await axios.get('/api/kiosk/employees/status');
            setEmployees(response.data.data || []);
        } catch (error) {
            console.error('Failed to fetch employee status', error);
            setEmployees([]);
        } finally {
            setIsLoading(false);
        }
    };

    const filteredEmployees = useMemo(() => {
        const filtered = employees.filter(emp => {
            const name = emp.name || '';
            const matchesSearch = name.toLowerCase().includes(search.toLowerCase());
            const matchesStatus = selectedStatus === 'all' || emp.status === selectedStatus;
            return matchesSearch && matchesStatus;
        });

        // Sort by last name (assuming the last word is the family name)
        return [...filtered].sort((a, b) => {
            const nameA = (a.name || '').trim();
            const nameB = (b.name || '').trim();
            
            const partsA = nameA.split(' ');
            const partsB = nameB.split(' ');
            
            const lastNameA = partsA.length > 1 ? partsA[partsA.length - 1] : partsA[0];
            const lastNameB = partsB.length > 1 ? partsB[partsB.length - 1] : partsB[0];
            
            return lastNameA.localeCompare(lastNameB, 'ro', { sensitivity: 'base' });
        });
    }, [employees, search, selectedStatus]);

    const getStatusConfig = (status: string) => {
        switch (status) {
            case 'present':
                return { icon: 'check_circle', color: 'text-green-500', bg: 'bg-green-50 dark:bg-green-900/20', label: 'Prezent' };
            case 'leave':
                return { icon: 'beach_access', color: 'text-purple-500', bg: 'bg-purple-50 dark:bg-purple-900/20', label: 'În Concediu' };
            case 'delegation':
                return { icon: 'flight', color: 'text-blue-500', bg: 'bg-blue-50 dark:bg-blue-900/20', label: 'În Delegație' };
            default: // absent
                return { icon: 'cancel', color: 'text-slate-300', bg: 'bg-slate-50 dark:bg-slate-800', label: 'Absent' };
        }
    };

    const handleKeyboardChange = (input: string) => {
        setSearch(input);
        if (inputRef.current) {
            inputRef.current.focus();
        }
    };

    const toggleStatusFilter = (status: StatusFilter) => {
        setSelectedStatus(prev => prev === status ? 'all' : status);
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-md p-0 transition-opacity duration-300">
            <div className={`bg-white dark:bg-slate-900 w-full h-full md:w-[1000px] md:h-[740px] md:rounded-3xl shadow-2xl flex flex-col overflow-hidden border border-slate-200 dark:border-slate-700 transition-all duration-300 ${showKeyboard ? 'md:-translate-y-24' : ''}`}>

                {/* Header */}
                <div className="px-6 py-4 md:px-8 md:py-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-900 z-10 shrink-0">
                    <h2 className="text-2xl md:text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                        <span className="material-symbols-outlined text-4xl md:text-5xl text-primary">groups</span>
                        Status Prezență
                    </h2>
                    <button
                        onClick={onClose}
                        className="w-12 h-12 md:w-14 md:h-14 flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl transition-all text-slate-600 dark:text-slate-300 active:scale-95"
                    >
                        <span className="material-symbols-outlined text-3xl md:text-4xl">close</span>
                    </button>
                </div>

                {/* Search & Stats Box */}
                <div className="px-6 py-4 md:px-8 md:py-5 border-b border-slate-50 dark:border-slate-800/50 bg-slate-50/30 dark:bg-slate-800/10">
                    <div className="flex flex-col md:flex-row gap-4 items-stretch md:items-center">
                        <div className="relative flex-1">
                            <span className="material-symbols-outlined absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 text-2xl md:text-3xl">search</span>
                            <input
                                ref={inputRef}
                                type="text"
                                placeholder="Caută un angajat..."
                                value={search}
                                onFocus={() => setShowKeyboard(true)}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-16 pr-14 py-4 md:py-5 rounded-2xl bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 text-xl md:text-2xl text-slate-800 dark:text-white focus:border-primary focus:ring-4 focus:ring-primary/10 outline-none transition-all placeholder:text-slate-400 font-bold shadow-sm"
                            />
                            {search && (
                                 <button 
                                    onClick={() => { setSearch(''); setShowKeyboard(true); inputRef.current?.focus(); }}
                                    className="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 active:scale-90"
                                 >
                                    <span className="material-symbols-outlined text-2xl md:text-3xl">cancel</span>
                                 </button>
                            )}
                        </div>
                        
                        <div className="flex gap-2 h-[68px] md:h-[76px]">
                             <button 
                                onClick={() => toggleStatusFilter('present')}
                                className={`flex items-center gap-3 px-4 rounded-2xl transition-all border-2 active:scale-95 ${selectedStatus === 'present' ? 'bg-green-500 border-green-600 text-white shadow-lg' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300'}`}
                             >
                                <span className={`material-symbols-outlined text-3xl md:text-4xl ${selectedStatus === 'present' ? 'text-white' : 'text-green-500'}`}>check_circle</span>
                                <div className="text-left leading-none">
                                    <div className="text-xl md:text-2xl font-black">{employees.filter(e => e.status === 'present').length}</div>
                                    <div className="text-[10px] font-bold uppercase tracking-tight opacity-70">Prezenți</div>
                                </div>
                             </button>

                             <button 
                                onClick={() => toggleStatusFilter('delegation')}
                                className={`flex items-center gap-3 px-4 rounded-2xl transition-all border-2 active:scale-95 ${selectedStatus === 'delegation' ? 'bg-blue-500 border-blue-600 text-white shadow-lg' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300'}`}
                             >
                                <span className={`material-symbols-outlined text-3xl md:text-4xl ${selectedStatus === 'delegation' ? 'text-white' : 'text-blue-500'}`}>flight</span>
                                <div className="text-left leading-none">
                                    <div className="text-xl md:text-2xl font-black">{employees.filter(e => e.status === 'delegation').length}</div>
                                    <div className="text-[10px] font-bold uppercase tracking-tight opacity-70">Delegație</div>
                                </div>
                             </button>

                             <button 
                                onClick={() => toggleStatusFilter('leave')}
                                className={`flex items-center gap-3 px-4 rounded-2xl transition-all border-2 active:scale-95 ${selectedStatus === 'leave' ? 'bg-purple-500 border-purple-600 text-white shadow-lg' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300'}`}
                             >
                                <span className={`material-symbols-outlined text-3xl md:text-4xl ${selectedStatus === 'leave' ? 'text-white' : 'text-purple-500'}`}>beach_access</span>
                                <div className="text-left leading-none">
                                    <div className="text-xl md:text-2xl font-black">{employees.filter(e => e.status === 'leave').length}</div>
                                    <div className="text-[10px] font-bold uppercase tracking-tight opacity-70">Concediu</div>
                                </div>
                             </button>
                        </div>
                    </div>
                </div>

                {/* Content Area */}
                <div className="flex-1 overflow-y-auto px-6 md:px-8 py-6 scroll-smooth bg-white dark:bg-slate-900">
                    {isLoading ? (
                        <div className="flex flex-col justify-center items-center h-64 gap-4">
                            <div className="animate-spin rounded-full h-16 w-16 border-t-4 border-primary"></div>
                            <p className="text-slate-400 font-bold">Se încarcă...</p>
                        </div>
                    ) : filteredEmployees.length > 0 ? (
                        <div className="grid grid-cols-2 gap-4 md:gap-6 pb-20">
                            {filteredEmployees.map((employee) => {
                                const { icon, color, bg, label } = getStatusConfig(employee.status);
                                return (
                                    <div 
                                        key={employee.id} 
                                        className={`flex items-center gap-4 md:gap-5 p-4 md:p-5 rounded-3xl border-2 transition-all shadow-sm ${selectedStatus !== 'all' ? 'border-primary/20 bg-primary/5' : 'border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40'}`}
                                    >
                                        <div className="relative shrink-0">
                                            <img
                                                src={employee.avatar}
                                                alt={employee.name}
                                                className="w-16 h-16 md:w-20 md:h-20 rounded-2xl object-cover shadow-md bg-white border-2 border-white dark:border-slate-700"
                                                onError={(e) => {
                                                    (e.target as HTMLImageElement).src = `https://ui-avatars.com/api/?name=${encodeURIComponent(employee.name)}&background=random&size=128`;
                                                }}
                                            />
                                            <div className={`absolute -bottom-1 -right-1 bg-white dark:bg-slate-900 rounded-xl shadow-sm p-1 border border-slate-100 dark:border-slate-800`}>
                                                <span className={`material-symbols-outlined ${color} text-xl md:text-2xl block`}>{icon}</span>
                                            </div>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <h3 className="font-black text-lg md:text-xl text-slate-800 dark:text-white truncate leading-tight">{employee.name}</h3>
                                            <div className="flex items-center gap-1.5 mb-1">
                                                <p className={`text-[10px] md:text-xs font-black truncate ${color} uppercase tracking-wider`}>
                                                    {label}
                                                </p>
                                            </div>
                                            {employee.details && (
                                                <div className="text-[10px] md:text-xs text-slate-500 dark:text-slate-400 truncate font-bold bg-slate-100 dark:bg-slate-800/80 px-2 py-1 rounded-lg inline-block max-w-full">
                                                    {employee.details}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="flex flex-col justify-center items-center h-64 text-slate-400">
                            <span className="material-symbols-outlined text-6xl opacity-20 mb-4">person_search</span>
                            <p className="text-xl font-bold">Nu am găsit rezultate.</p>
                            <button 
                                onClick={() => { setSearch(''); setSelectedStatus('all'); }}
                                className="mt-4 px-6 py-2 bg-primary text-white rounded-xl font-bold active:scale-95"
                            >
                                Resetează
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {showKeyboard && (
                <div className="fixed bottom-0 left-0 right-0 z-[1000] animate-in fade-in slide-in-from-bottom-20 duration-300">
                    <VirtualKeyboard 
                        onChange={handleKeyboardChange} 
                        onKeyPress={(button) => {
                            if (button === "{enter}") setShowKeyboard(false);
                        }}
                    />
                    <button 
                        onClick={() => setShowKeyboard(false)}
                        className="fixed bottom-[280px] right-8 z-[1001] bg-slate-800/90 backdrop-blur text-white px-5 py-2.5 rounded-2xl font-bold shadow-2xl active:scale-95 flex items-center gap-2 border border-slate-700"
                    >
                        <span className="material-symbols-outlined text-xl">keyboard_hide</span>
                        Ascunde
                    </button>
                </div>
            )}
        </div>
    );
};

export default PresenceModal;

