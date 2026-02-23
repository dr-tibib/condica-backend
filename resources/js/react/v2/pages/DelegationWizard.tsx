import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import axios from 'axios';
import StepPlaces from '../components/delegation/StepPlaces';
import StepVehicle from '../components/delegation/StepVehicle';
import { getKioskWorkplaceId } from '../../utils/kiosk';

interface Place {
  id?: number;
  google_place_id: string;
  name: string;
  address?: string;
  photo_reference?: string;
  latitude?: number;
  longitude?: number;
}

const DelegationWizard = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [step, setStep] = useState(1);
  const [selectedPlaces, setSelectedPlaces] = useState<Place[]>([]);
  const [selectedVehicleId, setSelectedVehicleId] = useState<number | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  // Retrieve employee from navigation state
  const employee = location.state?.employee;

  useEffect(() => {
    if (!employee) {
        navigate('/');
    }
  }, [employee, navigate]);

  if (!employee) return null;

  const handleStart = async () => {
    setIsLoading(true);
    try {
      const workplaceId = getKioskWorkplaceId();
      const employeeId = employee.id;

      // Start Delegation with all places in one request
      await axios.post('/api/delegations', {
          employee_id: employeeId,
          workplace_id: workplaceId,
          places: selectedPlaces.map(p => ({
              name: p.name,
              place_id: p.google_place_id,
              address: p.address,
              latitude: p.latitude,
              longitude: p.longitude,
              photo_reference: p.photo_reference
          })),
          vehicle_id: selectedVehicleId
      });
      
      navigate('/'); 
    } catch (error: any) {
      console.error('Error starting delegation', error);
      alert('Eroare: ' + (error.response?.data?.message || 'Nu s-a putut porni delegația.'));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-slate-200 dark:bg-slate-900 z-40 overflow-y-auto font-sans flex flex-col items-center justify-start md:justify-center p-0 md:p-4">
      <div className="w-full max-w-5xl min-h-full md:min-h-0 flex items-center justify-center">
        {step === 1 && (
          <StepPlaces 
              selectedPlaces={selectedPlaces} 
              onSelectionChange={setSelectedPlaces} 
              onNext={() => setStep(2)} 
              onBack={() => navigate('/')} 
          />
        )}
        {step === 2 && (
          <StepVehicle 
              selectedPlaces={selectedPlaces} 
              selectedVehicleId={selectedVehicleId} 
              onSelectVehicle={setSelectedVehicleId} 
              onBack={() => setStep(1)} 
              onStart={handleStart} 
          />
        )}
      </div>
      {isLoading && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div className="bg-white p-6 rounded-xl text-xl font-bold">Se procesează...</div>
        </div>
      )}
    </div>
  );
};

export default DelegationWizard;
