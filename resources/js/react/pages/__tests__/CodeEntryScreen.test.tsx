import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import CodeEntryScreen from '../CodeEntryScreen';
import { vi, describe, it, expect, beforeEach, afterEach } from 'vitest';

const mockedNavigate = vi.fn();
vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useNavigate: () => mockedNavigate,
        useLocation: () => ({ state: { flow: 'regular' } }),
    };
});

describe('CodeEntryScreen', () => {
    beforeEach(() => {
        global.fetch = vi.fn();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('fetches code length and submits code correctly', async () => {
        // Mock Config response
        (global.fetch as any)
            .mockResolvedValueOnce({
                ok: true,
                json: async () => ({ code_length: 4 }),
            })
            // Mock Submit Code response
            .mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    type: 'checkin',
                    user: { name: 'John Doe' },
                    time: '9:00 AM'
                }),
            });

        render(
            <MemoryRouter>
                <CodeEntryScreen />
            </MemoryRouter>
        );

        // Wait for config fetch
        await waitFor(() => expect(global.fetch).toHaveBeenCalledWith('/api/config'));

        // Enter 4 digits (as mocked config returns 4)
        fireEvent.click(screen.getByText('1'));
        fireEvent.click(screen.getByText('2'));
        fireEvent.click(screen.getByText('3'));
        fireEvent.click(screen.getByText('4'));

        // Verify submit code call
        await waitFor(() => {
            expect(global.fetch).toHaveBeenCalledWith('/api/kiosk/submit-code', expect.objectContaining({
                method: 'POST',
                body: expect.stringContaining('"code":"1234"'),
            }));
        });

        // Verify navigation
        await waitFor(() => {
            expect(mockedNavigate).toHaveBeenCalledWith('/success', expect.objectContaining({
                state: expect.objectContaining({
                    name: 'John Doe',
                    type: 'checkin'
                })
            }));
        });
    });
});
