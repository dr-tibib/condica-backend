import { render, screen, fireEvent, act } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import CodeEntryScreen from '../CodeEntryScreen';
import { vi } from 'vitest';
import * as api from '../../services/api';

const mockedNavigate = vi.fn();
vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useNavigate: () => mockedNavigate,
    };
});

describe('CodeEntryScreen', () => {
    it.skip('calls validateCode and navigates on successful code entry', async () => {
        const mockedValidateCode = vi.spyOn(api, 'validateCode').mockResolvedValue({ data: { has_delegation_permission: false, current_state: 'checked_in', full_name: 'John Doe' } });

        render(
            <MemoryRouter>
                <CodeEntryScreen />
            </MemoryRouter>
        );

        for (let i = 1; i <= 6; i++) {
            fireEvent.click(screen.getByText(i.toString()));
        }
        console.log('Buttons clicked');

        await vi.waitFor(() => {
            expect(mockedValidateCode).toHaveBeenCalledWith('123456');
        });

        await vi.waitFor(() => {
            expect(mockedNavigate).toHaveBeenCalledWith('/success', { state: { type: 'checked_in', name: 'John Doe' } });
        });
    });
});