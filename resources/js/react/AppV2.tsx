import { BrowserRouter as Router, Route, Routes, Navigate } from 'react-router-dom';
import Home from './v2/pages/Home';
import DelegationWizard from './v2/pages/DelegationWizard';
import LoginScreen from './pages/LoginScreen';
import KioskSetupScreen from './pages/KioskSetupScreen';
import { getKioskWorkplaceId } from './utils/kiosk';

const KioskGuard = ({ children }: { children: JSX.Element }) => {
  const workplaceId = getKioskWorkplaceId();

  if (!workplaceId) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

function AppV2() {
  return (
    <Router>
      <Routes>
        <Route path="/login" element={<LoginScreen />} />
        <Route path="/setup-kiosk" element={<KioskSetupScreen />} />
        
        <Route path="/" element={<KioskGuard><Home /></KioskGuard>} />
        <Route path="/delegation" element={<KioskGuard><DelegationWizard /></KioskGuard>} />
      </Routes>
    </Router>
  );
}

export default AppV2;
