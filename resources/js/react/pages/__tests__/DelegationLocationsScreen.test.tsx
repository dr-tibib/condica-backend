import { render, screen, waitFor } from '@testing-library/react';
import { MemoryRouter, Routes, Route } from 'react-router-dom';
import DelegationLocationsScreen from '../DelegationLocationsScreen';
import { vi, describe, it, expect, beforeEach, afterEach } from 'vitest';

describe('DelegationLocationsScreen', () => {
    beforeEach(() => {
        global.fetch = vi.fn();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('fetches and displays saved locations', async () => {
        (global.fetch as any).mockResolvedValue({
            ok: true,
            json: async () => ({
                data: [
                    { place_id: '1', name: 'Client Site Downtown', address: '123 Main St.', latitude: 10, longitude: 20 },
                ]
            }),
        });

        render(
            <MemoryRouter initialEntries={[{ pathname: '/delegation-locations', state: { user: { id: 1, name: 'Test User' } } }]}>
                <Routes>
                    <Route path="/delegation-locations" element={<DelegationLocationsScreen />} />
                </Routes>
            </MemoryRouter>
        );

        await waitFor(() => {
            expect(screen.getByText('Client Site Downtown')).toBeInTheDocument();
        });

        expect(global.fetch).toHaveBeenCalledWith('/api/delegations?user_id=1');
    });
});
