import React from 'react';
import Keyboard from 'react-simple-keyboard';
import 'react-simple-keyboard/build/css/index.css';

interface VirtualKeyboardProps {
  onChange: (input: string) => void;
  onKeyPress?: (button: string) => void;
  inputName?: string;
  layoutName?: string;
}

const VirtualKeyboard: React.FC<VirtualKeyboardProps> = ({ 
  onChange, 
  onKeyPress, 
  inputName = "default",
  layoutName = "default" 
}) => {
  return (
    <div className="fixed bottom-0 left-0 w-full bg-slate-100 dark:bg-slate-900 p-4 shadow-[0_-20px_50px_rgba(0,0,0,0.3)] z-[1000] border-t border-slate-300 dark:border-slate-700 animate-slide-up">
      <div className="max-w-5xl mx-auto">
        <Keyboard
          onChange={onChange}
          onKeyPress={onKeyPress}
          inputName={inputName}
          layoutName={layoutName}
          theme="hg-theme-default hg-layout-default custom-keyboard"
          layout={{
            default: [
              "Q W E R T Y U I O P",
              "A S D F G H J K L",
              "Z X C V B N M {bksp}",
              "{space} {enter}"
            ]
          }}
          display={{
            "{bksp}": "Șterge",
            "{enter}": "Gata",
            "{space}": "Spațiu"
          }}
          buttonTheme={[
            {
              class: "kb-button-enter",
              buttons: "{enter}"
            },
            {
              class: "kb-button-bksp",
              buttons: "{bksp}"
            }
          ]}
        />
      </div>
      <style>{`
        .custom-keyboard {
          background-color: transparent !important;
          font-family: inherit !important;
        }
        .hg-button {
          height: 60px !important;
          font-size: 20px !important;
          font-weight: 700 !important;
          border-radius: 12px !important;
          background: white !important;
          border-bottom: 4px solid #cbd5e1 !important;
          margin: 4px !important;
          display: flex !important;
          align-items: center !important;
          justify-content: center !important;
          transition: all 0.1s active !important;
        }
        .dark .hg-button {
          background: #334155 !important;
          border-bottom: 4px solid #0f172a !important;
          color: white !important;
        }
        .hg-button:active {
          border-bottom: 0 !important;
          transform: translateY(4px) !important;
        }
        .kb-button-enter {
          background: #2563eb !important;
          color: white !important;
          border-bottom: 4px solid #1e40af !important;
          flex: 2 !important;
        }
        .kb-button-bksp {
          background: #ef4444 !important;
          color: white !important;
          border-bottom: 4px solid #b91c1c !important;
        }
        @keyframes slide-up {
          from { transform: translateY(100%); }
          to { transform: translateY(0); }
        }
      `}</style>
    </div>
  );
};

export default VirtualKeyboard;
