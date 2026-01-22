import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import DelegationLocationsScreen from '../DelegationLocationsScreen';
import { vi } from 'vitest';
import * as api from '../../services/api';

vi.mock('../../services/api', () => ({
    apiService: {
        get: vi.fn(),
    },
}));

describe('DelegationLocationsScreen', () => {
    it('fetches and displays saved locations', async () => {
        (api.apiService.get as any).mockResolvedValue({
            data: {
                company: [{ id: 1, name: 'Client Site Downtown', address: '123 Main St.', icon: 'domain' }],
                personal: [],
            },
        });

        render(
            <MemoryRouter>
                <DelegationLocationsScreen />
            </MemoryRouter>
        );

        await vi.waitFor(() => {
            expect(screen.getByText('Client Site Downtown')).toBeInTheDocument();
        });
    });
});