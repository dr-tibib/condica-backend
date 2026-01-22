import { useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

const CodeEntryScreen = () => {
  const [code, setCode] = useState('');
  const navigate = useNavigate();
  const location = useLocation();
  const { flow } = location.state || { flow: 'regular' };

  useEffect(() => {
    if (code.length === 6) {
      // Auto-submit on 6 digits
      console.log(`Submitting code: ${code} for flow: ${flow}`);
      // Here you would typically make an API call
      // For now, we'll just navigate to the success screen
      navigate('/success');
    }
  }, [code, flow, navigate]);

  const handleKeyPress = (key: string) => {
    if (code.length < 6) {
      setCode(code + key);
    }
  };

  const handleDelete = () => {
    setCode(code.slice(0, -1));
  };

  const handleBack = () => {
    navigate('/');
  };

  return (
    <div className="font-display bg-background-light dark:bg-background-dark text-slate-900 dark:text-white min-h-screen w-full flex flex-col items-center justify-center p-4">
      <div className="relative w-full max-w-[768px] h-full min-h-[800px] md:h-auto md:aspect-[3/4] max-h-[1024px] bg-white dark:bg-[#1a202c] shadow-2xl flex flex-col rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800">
        <header className="flex items-center justify-between p-6 md:p-10 absolute top-0 left-0 w-full z-10">
          <button
            onClick={handleBack}
            className="group flex items-center justify-center w-14 h-14 rounded-full bg-slate-50 dark:bg-slate-800 hover:bg-primary/10 dark:hover:bg-slate-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-background-dark"
          >
            <span className="material-symbols-outlined text-3xl text-slate-700 dark:text-slate-200 group-hover:text-primary transition-colors">
              arrow_back
            </span>
          </button>
        </header>
        <main className="flex-1 flex flex-col items-center justify-center px-4 w-full h-full pt-12 pb-8">
          <div className="flex flex-col items-center gap-3 mb-12">
            <div className="w-16 h-16 mb-4 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
              <span className="material-symbols-outlined text-4xl">lock_person</span>
            </div>
            <h1 className="text-3xl md:text-[32px] font-bold text-slate-900 dark:text-white tracking-tight text-center leading-tight">
              Enter Your Employee Code
            </h1>
            <p className="text-slate-500 dark:text-slate-400 text-lg font-medium text-center max-w-sm">
              Enter your 6-digit access PIN
            </p>
          </div>
          <div className="mb-14 flex items-center justify-center">
            <div className="flex items-center justify-center gap-4 md:gap-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 px-8 py-5 h-[80px] w-auto min-w-[320px] md:min-w-[400px] shadow-sm">
              {[...Array(6)].map((_, i) => (
                <div
                  key={i}
                  className={`w-4 h-4 md:w-5 md:h-5 rounded-full transition-all duration-300 ${
                    i < code.length
                      ? 'bg-primary shadow-sm shadow-primary/50 scale-110'
                      : 'bg-slate-300 dark:bg-slate-600'
                  }`}
                ></div>
              ))}
            </div>
          </div>
          <div className="w-full max-w-[400px] grid grid-cols-3 gap-4 md:gap-6 mx-auto">
            {[...Array(9)].map((_, i) => (
              <button
                key={i + 1}
                onClick={() => handleKeyPress((i + 1).toString())}
                className="group relative w-full aspect-square max-w-[100px] max-h-[100px] mx-auto flex items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 border-b-4 border-slate-200 dark:border-slate-900 active:border-b-0 active:translate-y-1 transition-all duration-100 focus:outline-none focus:ring-2 focus:ring-primary/50"
              >
                <span className="text-3xl md:text-4xl font-semibold text-slate-800 dark:text-white group-hover:scale-110 transition-transform">
                  {i + 1}
                </span>
              </button>
            ))}
            <div className="w-full max-w-[100px] mx-auto"></div>
            <button
              onClick={() => handleKeyPress('0')}
              className="group relative w-full aspect-square max-w-[100px] max-h-[100px] mx-auto flex items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 border-b-4 border-slate-200 dark:border-slate-900 active:border-b-0 active:translate-y-1 transition-all duration-100 focus:outline-none focus:ring-2 focus:ring-primary/50"
            >
              <span className="text-3xl md:text-4xl font-semibold text-slate-800 dark:text-white group-hover:scale-110 transition-transform">
                0
              </span>
            </button>
            <button
              onClick={handleDelete}
              className="group relative w-full aspect-square max-w-[100px] max-h-[100px] mx-auto flex items-center justify-center rounded-2xl hover:bg-red-50 dark:hover:bg-red-900/10 text-slate-400 hover:text-red-500 dark:text-slate-500 dark:hover:text-red-400 transition-colors focus:outline-none focus:ring-2 focus:ring-red-200"
            >
              <span className="material-symbols-outlined text-[40px] group-active:scale-90 transition-transform">
                backspace
              </span>
            </button>
          </div>
        </main>
      </div>
    </div>
  );
};

export default CodeEntryScreen;