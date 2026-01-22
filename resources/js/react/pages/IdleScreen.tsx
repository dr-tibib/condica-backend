import { useNavigate } from 'react-router-dom';

declare global {
  interface Window {
    tenant: any;
  }
}

/**
 * Implements the design from docs/design/idle_screen
 */
const IdleScreen = () => {
  const navigate = useNavigate();
  const companyName = window.tenant?.company_name || 'Acme Corp HQ';

  const handleRegularFlow = () => {
    navigate('/code-entry', { state: { flow: 'regular' } });
  };

  const handleDelegationFlow = () => {
    navigate('/code-entry', { state: { flow: 'delegation' } });
  };

  return (
    <div className="bg-background-light dark:bg-background-dark text-[#111318] dark:text-white font-display antialiased h-screen w-full overflow-hidden flex flex-col">
      {/* Main Layout Container */}
      <div className="flex-1 flex flex-col items-center justify-between w-full h-full p-8 md:p-12 max-w-[768px] mx-auto">
        {/* Header Section */}
        <div className="flex-1 flex flex-col justify-end items-center w-full pb-10">
          <div className="flex flex-col items-center gap-6">
            {/* Logo Placeholder */}
            <div
              className="w-32 h-32 rounded-full bg-gray-200 dark:bg-gray-800 flex items-center justify-center shadow-inner bg-cover bg-center"
              style={{
                backgroundImage:
                  'url("https://lh3.googleusercontent.com/aida-public/AB6AXuBWTGyXVsBCawokhZtu8ExKPmG4iPl720F4uZ8-geruRKCf1T-E6aGdSA2_FNADQ7hjqNUV7l0zqfWzQeug3aGmr2KcmKiAltxPDoxvkKLOZalLc4Lj7fiCOGD7ueDdecaLy096qa_-tIQ97kuKeto46DEEoIc12WRyTlGaFVviPWIu6mlA0yPnFciPFznRICxIBDCv5chIHqQW7cUdOzgTneKVdF3IR1lcRGJqnHU_CM1_s8tVkkuYL9a2x8qIuTbNM7iAW5BhYTM")',
              }}
            ></div>
            {/* Welcome Title */}
            <h1 className="text-3xl md:text-[32px] font-bold leading-tight tracking-tight text-center text-[#111318] dark:text-white">
              Welcome to {companyName}
            </h1>
          </div>
        </div>

        {/* Action Section */}
        <div className="flex-[2] flex flex-col items-center justify-center w-full gap-8">
          {/* Primary Action Button */}
          <button
            onClick={handleRegularFlow}
            className="group flex w-full max-w-[500px] h-[120px] cursor-pointer items-center justify-center gap-4 overflow-hidden rounded-xl bg-primary hover:bg-blue-600 active:bg-blue-700 transition-all duration-200 shadow-lg shadow-blue-500/20 active:scale-[0.98]"
          >
            <span className="material-symbols-outlined text-4xl text-white">touch_app</span>
            <span className="text-white text-2xl md:text-3xl font-bold leading-normal tracking-wide">
              Tap to Enter Your Code
            </span>
          </button>

          {/* Secondary Action Button */}
          <button
            onClick={handleDelegationFlow}
            className="flex w-full max-w-[280px] h-[60px] cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200"
          >
            <span className="text-[#111318] dark:text-white text-lg font-semibold leading-normal tracking-wide">
              Delegation
            </span>
          </button>
        </div>

        {/* Footer Section */}
        <div className="flex-1 flex flex-col justify-end items-center w-full pb-8">
          <div className="flex items-center gap-2 opacity-80">
            <span className="material-symbols-outlined text-xl">schedule</span>
            <p className="text-lg md:text-xl font-medium text-center">
              14:32 Monday, Jan 22
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default IdleScreen;