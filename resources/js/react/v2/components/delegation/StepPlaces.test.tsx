import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import StepPlaces from './StepPlaces';
import axios from 'axios';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import { savedPlacesMock, googlePredictionsMock, googlePlaceDetailsMock } from '../../mocks/placesMock';

// Mock Axios
vi.mock('axios');
const mockedAxios = axios as jest.Mocked<typeof axios>;

// Mock Google Maps Library
const mockFetchFields = vi.fn().mockResolvedValue(googlePlaceDetailsMock);

const mockAutocompleteService = {
    getPlacePredictions: vi.fn().mockImplementation((req, cb) => {
        if (cb) {
            cb(googlePredictionsMock, 'OK');
        }
        return Promise.resolve({ predictions: googlePredictionsMock });
    })
};

class MockAutocompleteServiceClass {
    getPlacePredictions = mockAutocompleteService.getPlacePredictions;
}

const mockPlaceConstructor = vi.fn(); // Spy to track calls

class MockPlaceClass {
    constructor(args: any) {
        mockPlaceConstructor(args);
    }
    fetchFields = mockFetchFields;
    displayName = googlePlaceDetailsMock.displayName;
    formattedAddress = googlePlaceDetailsMock.formattedAddress;
    location = {
        lat: () => 45.863,
        lng: () => 25.787
    };
    photos = googlePlaceDetailsMock.photos;
}

const mockPlacesLibrary = {
    AutocompleteService: MockAutocompleteServiceClass,
    Place: MockPlaceClass,
};

// Mock @vis.gl/react-google-maps
vi.mock('@vis.gl/react-google-maps', () => ({
    APIProvider: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    useMapsLibrary: (name: string) => {
        if (name === 'places') {
            return mockPlacesLibrary;
        }
        return null;
    }
}));

describe('StepPlaces Component', () => {
    const defaultProps = {
        selectedPlaces: [],
        onSelectionChange: vi.fn(),
        onNext: vi.fn(),
        onBack: vi.fn(),
    };

    beforeEach(() => {
        vi.clearAllMocks();
        mockedAxios.get.mockResolvedValue({ data: { data: savedPlacesMock } });
    });

    it('renders saved places from API', async () => {
        render(<StepPlaces {...defaultProps} />);

        await waitFor(() => {
            expect(screen.getByText('MATIKON')).toBeInTheDocument();
            expect(screen.getByText('MATION')).toBeInTheDocument();
        });
    });

    it('filters saved places when typing in search', async () => {
        render(<StepPlaces {...defaultProps} />);

        await waitFor(() => expect(screen.getByText('MATIKON')).toBeInTheDocument());

        const input = screen.getByPlaceholderText(/Introduceți orașul sau firma/i);

        // Filter by text present in one but not the other
        fireEvent.change(input, { target: { value: 'Depozit' } });

        // MATION (Depozit Logistic) should be visible
        expect(screen.getByText('MATION')).toBeInTheDocument();
        // MATIKON (Sediul Industrial) should be hidden
        expect(screen.queryByText('MATIKON')).not.toBeInTheDocument();
    });

    it('toggles selection of saved places', async () => {
        const onSelectionChange = vi.fn();
        render(<StepPlaces {...defaultProps} onSelectionChange={onSelectionChange} />);

        await waitFor(() => expect(screen.getByText('MATIKON')).toBeInTheDocument());

        fireEvent.click(screen.getByText('MATIKON'));

        expect(onSelectionChange).toHaveBeenCalledWith([savedPlacesMock[0]]);
    });

    it('shows Google suggestions when no saved place matches (or alongside)', async () => {
        render(<StepPlaces {...defaultProps} />);

        const input = screen.getByPlaceholderText(/Introduceți orașul sau firma/i);

        // Type something that triggers Google search
        await act(async () => {
             fireEvent.change(input, { target: { value: 'Dedeman' } });
        });

        // Wait for debounce and effect
        await waitFor(() => {
            expect(mockAutocompleteService.getPlacePredictions).toHaveBeenCalled();
        });

        // Expect suggestions to appear (we need to know how they render)
        await waitFor(() => {
            // Text is split in two divs: Main text and secondary text
            expect(screen.getAllByText('Dedeman').length).toBeGreaterThan(0);
            expect(screen.getByText('Strada Lunca Oltului, Sfântu Gheorghe')).toBeInTheDocument();
        });
    });

    it('adds Google place to list and selects it on click', async () => {
        const onSelectionChange = vi.fn();
        render(<StepPlaces {...defaultProps} onSelectionChange={onSelectionChange} />);

        const input = screen.getByPlaceholderText(/Introduceți orașul sau firma/i);
        fireEvent.change(input, { target: { value: 'Dedeman' } });

        await waitFor(() => {
            expect(screen.getByText('Strada Lunca Oltului, Sfântu Gheorghe')).toBeInTheDocument();
        });

        // Click the container or one of the text elements.
        // Since we have multiple elements, let's click the secondary text which is unique in our mock
        fireEvent.click(screen.getByText('Strada Lunca Oltului, Sfântu Gheorghe'));

        // Should call fetchFields
        await waitFor(() => {
            expect(mockPlaceConstructor).toHaveBeenCalled();
            expect(mockFetchFields).toHaveBeenCalled();
        });

        // Should call onSelectionChange with new place
        expect(onSelectionChange).toHaveBeenCalled();
        const newPlace = onSelectionChange.mock.calls[0][0][0];
        expect(newPlace.name).toBe('Dedeman');
        expect(newPlace.google_place_id).toBe('ChIJabcd'); // From prediction
    });
});
