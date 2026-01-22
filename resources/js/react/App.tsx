import { BrowserRouter as Router, Route, Routes } from 'react-router-dom';
import IdleScreen from './pages/IdleScreen';
import CodeEntryScreen from './pages/CodeEntryScreen';
import DelegationLocationsScreen from './pages/DelegationLocationsScreen';
import SearchLocationScreen from './pages/SearchLocationScreen';
import ConfirmLocationScreen from './pages/ConfirmLocationScreen';
import SuccessScreen from './pages/SuccessScreen';
import ErrorScreen from './pages/ErrorScreen';

function App() {
  return (
    <Router>
      <Routes>
        <Route path="/" element={<IdleScreen />} />
        <Route path="/code-entry" element={<CodeEntryScreen />} />
        <Route path="/delegation-locations" element={<DelegationLocationsScreen />} />
        <Route path="/search-location" element={<SearchLocationScreen />} />
        <Route path="/confirm-location" element={<ConfirmLocationScreen />} />
        <Route path="/success" element={<SuccessScreen />} />
        <Route path="/error" element={<ErrorScreen />} />
      </Routes>
    </Router>
  );
}

export default App;
