import { BrowserRouter as Router, Route, Routes, Navigate } from 'react-router-dom';
import Home from './v2/pages/Home';
import DelegationWizard from './v2/pages/DelegationWizard';
import LeaveWizard from './v2/pages/LeaveWizard';
import DelegationSchedule from './v2/pages/DelegationSchedule';
import ShiftCorrection from './v2/pages/ShiftCorrection';
import LateStartConfirm from './v2/pages/LateStartConfirm';
import DelegationCancel from './v2/pages/DelegationCancel';
import DelegationPlaceSelectorTest from './v2/pages/DelegationPlaceSelectorTest';
import LoginScreen from './v2/pages/LoginScreen';
import KioskSetupScreen from './v2/pages/KioskSetupScreen';
import { getKioskWorkplaceId } from './utils/kiosk';

const KioskGuard = ({ children }: { children: JSX.Element }) => {
  const workplaceId = getKioskWorkplaceId();

  if (!workplaceId) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/login" element={<LoginScreen />} />
        <Route path="/setup-kiosk" element={<KioskSetupScreen />} />
        
        <Route path="/" element={<KioskGuard><Home /></KioskGuard>} />
        <Route path="/delegation" element={<KioskGuard><DelegationWizard /></KioskGuard>} />
        <Route path="/delegation-test" element={<DelegationPlaceSelectorTest />} />
        <Route path="/leave" element={<KioskGuard><LeaveWizard /></KioskGuard>} />
        <Route path="/delegation-schedule" element={<KioskGuard><DelegationSchedule /></KioskGuard>} />
        <Route path="/shift-correction" element={<KioskGuard><ShiftCorrection /></KioskGuard>} />
        <Route path="/late-start-confirm" element={<KioskGuard><LateStartConfirm /></KioskGuard>} />
        <Route path="/delegation-cancel" element={<KioskGuard><DelegationCancel /></KioskGuard>} />
      </Routes>
    </Router>
  );
}

export default App;
