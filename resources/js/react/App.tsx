import { BrowserRouter as Router, Route, Routes } from 'react-router-dom';
import { Navigate } from 'react-router-dom';
import IdleScreen from './pages/IdleScreen';
import CodeEntryScreen from './pages/CodeEntryScreen';
import DelegationLocationsScreen from './pages/DelegationLocationsScreen';
import SearchLocationScreen from './pages/SearchLocationScreen';
import ConfirmLocationScreen from './pages/ConfirmLocationScreen';
import SuccessScreen from './pages/SuccessScreen';
import ErrorScreen from './pages/ErrorScreen';
import LoginScreen from './pages/LoginScreen';
import KioskSetupScreen from './pages/KioskSetupScreen';
import DelegationEndedScreen from './pages/DelegationEndedScreen';
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

        <Route path="/" element={<KioskGuard><IdleScreen /></KioskGuard>} />
        <Route path="/code-entry" element={<KioskGuard><CodeEntryScreen /></KioskGuard>} />
        <Route path="/delegation-locations" element={<KioskGuard><DelegationLocationsScreen /></KioskGuard>} />
        <Route path="/search-location" element={<KioskGuard><SearchLocationScreen /></KioskGuard>} />
        <Route path="/confirm-location" element={<KioskGuard><ConfirmLocationScreen /></KioskGuard>} />
        <Route path="/success" element={<KioskGuard><SuccessScreen /></KioskGuard>} />
        <Route path="/delegation-ended" element={<KioskGuard><DelegationEndedScreen /></KioskGuard>} />
        <Route path="/error" element={<KioskGuard><ErrorScreen /></KioskGuard>} />
      </Routes>
    </Router>
  );
}

export default App;
