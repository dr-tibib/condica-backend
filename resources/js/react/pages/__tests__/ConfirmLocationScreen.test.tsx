import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import ConfirmLocationScreen from '../ConfirmLocationScreen';
import { vi, describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockedNavigate = vi.fn();
vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useNavigate: () => mockedNavigate,
        useLocation: () => ({
            state: {
                location: { id: 10, name: 'Test Loc', address: '123 Test', icon: 'test' },
                user: { id: 99, name: 'John Doe' }
            }
        }),
    };
});

describe('ConfirmLocationScreen', () => {
    beforeEach(() => {
        global.fetch = vi.fn();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('starts delegation and navigates to success', async () => {
        (global.fetch as any).mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                type: 'delegation-start',
                user: { name: 'John Doe' },
                time: '10:00 AM'
            }),
        });

        render(
            <MemoryRouter>
                <ConfirmLocationScreen />
            </MemoryRouter>
        );

        // Check if location details are displayed
        expect(screen.getByText('Test Loc')).toBeInTheDocument();

        // Click Start Delegation
        fireEvent.click(screen.getByText('Start Delegation'));

        // Verify API call
        await waitFor(() => {
            expect(global.fetch).toHaveBeenCalledWith('/api/kiosk/delegation', expect.objectContaining({
                method: 'POST',
                body: expect.stringContaining('"user_id":99'),
            }));
            expect(global.fetch).toHaveBeenCalledWith('/api/kiosk/delegation', expect.objectContaining({
                body: expect.stringContaining('"workplace_id":10'),
            }));
        });

        // Verify navigation
        await waitFor(() => {
            expect(mockedNavigate).toHaveBeenCalledWith('/success', expect.objectContaining({
                state: expect.objectContaining({
                    type: 'delegation-start',
                    name: 'John Doe'
                })
            }));
        });
    });
});
