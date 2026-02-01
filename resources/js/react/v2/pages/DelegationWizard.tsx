import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';
import StepPlaces from '../components/delegation/StepPlaces';
import StepVehicle from '../components/delegation/StepVehicle';
import ConfirmModal from '../components/delegation/ConfirmModal';
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
  const [step, setStep] = useState(1);
  const [selectedPlaces, setSelectedPlaces] = useState<Place[]>([]);
  const [selectedVehicleId, setSelectedVehicleId] = useState<number | null>(null);
  const [showConfirm, setShowConfirm] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const handleStart = () => {
    setShowConfirm(true);
  };

  const handleConfirm = async (code: string) => {
    setIsLoading(true);
    try {
      const workplaceId = getKioskWorkplaceId();
      
      // 1. Verify Code to get User ID
      const verifyResponse = await axios.post('/api/kiosk/submit-code', {
         code,
         flow: 'delegation'
      });
      const userId = verifyResponse.data.user.id;

      // 2. Start Delegation for each place
      // (Execute sequentially to avoid race conditions on check-in if any)
      for (const place of selectedPlaces) {
         await axios.post('/api/delegations', {
            user_id: userId,
            workplace_id: workplaceId,
            place_id: place.google_place_id,
            name: place.name,
            address: place.address,
            latitude: place.latitude,
            longitude: place.longitude,
            photo_reference: place.photo_reference,
            vehicle_id: selectedVehicleId
         });
      }
      
      // Navigate to success or home
      // Maybe show a success message first?
      // I'll just go home for now.
      navigate('/'); 
    } catch (error: any) {
      console.error('Error starting delegation', error);
      alert('Eroare: ' + (error.response?.data?.message || 'Nu s-a putut porni delegația.'));
    } finally {
      setIsLoading(false);
      setShowConfirm(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-slate-200 dark:bg-slate-900 z-40 overflow-hidden flex items-center justify-center font-sans">
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
      {showConfirm && (
        <ConfirmModal 
            onConfirm={handleConfirm} 
            onCancel={() => setShowConfirm(false)} 
            isLoading={isLoading} 
        />
      )}
    </div>
  );
};

export default DelegationWizard;
