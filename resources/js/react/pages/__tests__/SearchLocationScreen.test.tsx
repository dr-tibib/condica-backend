import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import SearchLocationScreen from '../SearchLocationScreen';
import { vi, describe, it, expect, beforeEach, afterEach } from 'vitest';

// Mock navigation
const mockedNavigate = vi.fn();
const mockedLocationState = { user: { id: 1, name: 'Test User' } };

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useNavigate: () => mockedNavigate,
        useLocation: () => ({ state: mockedLocationState }),
    };
});

// Mock Google Places Autocomplete
vi.mock('react-google-places-autocomplete', () => {
    return {
        __esModule: true,
        default: ({ selectProps }: any) => {
            return (
                <button
                    data-testid="mock-select-location"
                    onClick={() => selectProps.onChange({
                        value: {
                            place_id: 'place_123',
                            structured_formatting: {
                                main_text: 'Mock Place',
                                secondary_text: 'Mock Address'
                            }
                        }
                    })}
                >
                    Select Mock Location
                </button>
            );
        },
        geocodeByPlaceId: vi.fn().mockResolvedValue([
            { geometry: { location: { lat: () => 10, lng: () => 20 } } }
        ]),
        getLatLng: vi.fn().mockResolvedValue({ lat: 10, lng: 20 }),
    };
});

describe('SearchLocationScreen', () => {
    afterEach(() => {
        vi.clearAllMocks();
    });

    it('navigates to confirm-location with user and location data when location is selected', async () => {
        render(
            <MemoryRouter>
                <SearchLocationScreen />
            </MemoryRouter>
        );

        const selectButton = screen.getByTestId('mock-select-location');
        fireEvent.click(selectButton);

        await waitFor(() => {
            expect(mockedNavigate).toHaveBeenCalledWith('/confirm-location', expect.objectContaining({
                state: expect.objectContaining({
                    user: mockedLocationState.user,
                    location: expect.objectContaining({
                        place_id: 'place_123',
                        name: 'Mock Place'
                    })
                })
            }));
        });
    });

    it('navigates back to delegation-locations with user data', async () => {
        render(
            <MemoryRouter>
                <SearchLocationScreen />
            </MemoryRouter>
        );

        const backButton = screen.getByText('arrow_back').closest('button');
        fireEvent.click(backButton!);

        expect(mockedNavigate).toHaveBeenCalledWith('/delegation-locations', expect.objectContaining({
            state: expect.objectContaining({
                user: mockedLocationState.user
            })
        }));
    });
});
