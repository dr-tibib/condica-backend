import { useNavigate } from 'react-router-dom';

const DelegationLocationsScreen = () => {
    const navigate = useNavigate();

    const handleBack = () => {
        navigate('/code-entry', { state: { flow: 'delegation' } });
    };

    const handleSearch = () => {
        navigate('/search-location');
    };

    const handleSelectLocation = (location: any) => {
        console.log('Selected location:', location);
        navigate('/success');
    }

    const savedLocations = [
        { id: 1, name: 'Client Site Downtown', address: '123 Main St.', icon: 'domain' },
        { id: 2, name: 'Warehouse North', address: 'Industrial Park, Zone B', icon: 'warehouse' },
        { id: 3, name: 'Home Office', address: 'Remote', icon: 'home', fullWidth: true },
    ];


  return (
    <div className="bg-background-light dark:bg-background-dark font-display text-[#111318] dark:text-white overflow-x-hidden">
      <div className="min-h-screen flex justify-center w-full">
        <div className="w-full max-w-[768px] min-h-screen bg-white dark:bg-[#111621] flex flex-col relative shadow-xl">
          <header className="flex items-center justify-between px-8 py-6 pt-8">
            <button
              onClick={handleBack}
              aria-label="Go back"
              className="flex items-center justify-center w-12 h-12 rounded-full hover:bg-background-light dark:hover:bg-gray-800 transition-colors text-[#111318] dark:text-white"
            >
              <span className="material-symbols-outlined text-[28px]">arrow_back</span>
            </button>
            <div className="flex items-center gap-3 pl-1 pr-4 py-1 bg-background-light dark:bg-gray-800 rounded-full border border-gray-100 dark:border-gray-700">
              <div
                className="bg-center bg-no-repeat bg-cover rounded-full size-8 shrink-0"
                style={{ backgroundImage: 'url("https://lh3.googleusercontent.com/aida-public/AB6AXuBf4dqS5AfEJiGe5aj4wjJE7FkMHqIJ27l6lQDEYVCCofW3mhU0Ic2oyu1zyAkyzQiHksyPLNa2aEaaTvmxh9huEYJRICeteBeywY4qzPvN4fq1iLr89hlWdiZBvlpuvsgsihyu2TMHj7C2ogUGqiKYK5NHwoBhV6YTQ9Qm3H46vYnZCIoCdmfECHydK-JKnvDKcGA8YlHgJNQIiRqMMpMlpZXdtI7WaxyN3MvfjLsJkDnLZ457kcgA_YxekAQDmIbgXiw8JumYUwo")' }}
              ></div>
              <span className="text-sm font-bold text-[#111318] dark:text-white truncate max-w-[120px]">
                John D.
              </span>
            </div>
          </header>
          <main className="flex-1 flex flex-col px-8 pb-8">
            <div className="mt-4 mb-8">
              <h1 className="text-[#111318] dark:text-white text-[40px] font-black leading-tight tracking-[-0.033em]">
                Where are you going?
              </h1>
            </div>
            <div className="mb-10">
              <label className="relative flex w-full h-16 group">
                <div className="absolute inset-y-0 left-0 flex items-center pl-5 pointer-events-none z-10">
                  <span className="material-symbols-outlined text-[#616e89] dark:text-gray-400 group-focus-within:text-primary transition-colors text-[28px]">
                    search
                  </span>
                </div>
                <input
                  onFocus={handleSearch}
                  className="flex w-full h-full pl-14 pr-4 rounded-xl border-2 border-transparent bg-background-light dark:bg-gray-800 text-[#111318] dark:text-white text-lg placeholder:text-[#616e89] focus:outline-none focus:border-primary focus:ring-0 transition-all shadow-sm"
                  placeholder="Search address or client name"
                  type="text"
                />
              </label>
            </div>
            <div className="flex flex-col gap-4">
              <h3 className="text-[#111318] dark:text-white text-xl font-bold leading-tight tracking-[-0.015em] mb-2">
                Saved Locations
              </h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {savedLocations.map(location => (
                    <div
                        key={location.id}
                        onClick={() => handleSelectLocation(location)}
                        className={`group flex flex-col gap-4 p-5 rounded-xl border border-[#dbdee6] dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary hover:shadow-lg dark:hover:border-primary transition-all cursor-pointer ${location.fullWidth ? 'md:col-span-2' : ''}`}
                    >
                        <div className={`flex items-start justify-between ${location.fullWidth ? 'flex-row items-center' : ''}`}>
                            <div className="flex items-center justify-center w-12 h-12 rounded-lg bg-primary/10 text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                                <span className="material-symbols-outlined text-[24px]">{location.icon}</span>
                            </div>
                            {!location.fullWidth && <span className="material-symbols-outlined text-gray-300 group-hover:text-primary transition-colors">chevron_right</span>}

                            {location.fullWidth && (
                                <>
                                    <div className="flex flex-col gap-1 flex-1 ml-4">
                                        <h2 className="text-[#111318] dark:text-white text-lg font-bold leading-tight">{location.name}</h2>
                                        <p className="text-[#616e89] dark:text-gray-400 text-sm font-medium leading-normal">{location.address}</p>
                                    </div>
                                    <span className="material-symbols-outlined text-gray-300 group-hover:text-primary transition-colors">chevron_right</span>
                                </>
                            )}
                        </div>
                        {!location.fullWidth && (
                            <div className="flex flex-col gap-1">
                                <h2 className="text-[#111318] dark:text-white text-lg font-bold leading-tight">{location.name}</h2>
                                <p className="text-[#616e89] dark:text-gray-400 text-sm font-medium leading-normal">{location.address}</p>
                            </div>
                        )}
                    </div>
                ))}
              </div>
            </div>
            <div className="flex-1"></div>
          </main>
        </div>
      </div>
    </div>
  );
};

export default DelegationLocationsScreen;