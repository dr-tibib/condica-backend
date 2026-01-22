import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import IdleScreen from '../IdleScreen';
import { vi } from 'vitest';

const mockedNavigate = vi.fn();

vi.mock('react-router-dom', async () => {
    const actual = await vi.importActual('react-router-dom');
    return {
        ...actual,
        useNavigate: () => mockedNavigate,
    };
});


describe('IdleScreen', () => {
  it('renders the welcome message and buttons', () => {
    render(
      <MemoryRouter>
        <IdleScreen />
      </MemoryRouter>
    );

    expect(screen.getByText('Welcome to Acme Corp HQ')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Tap to Enter Your Code/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Delegation/i })).toBeInTheDocument();
  });

    it('navigates to the code entry screen with the correct flow when the primary button is clicked', () => {
        render(
            <MemoryRouter>
                <IdleScreen />
            </MemoryRouter>
        );

        const primaryButton = screen.getByRole('button', { name: /Tap to Enter Your Code/i });
        primaryButton.click();

        expect(mockedNavigate).toHaveBeenCalledWith('/code-entry', { state: { flow: 'regular' } });
    });

    it('navigates to the code entry screen with the correct flow when the secondary button is clicked', () => {
        render(
            <MemoryRouter>
                <IdleScreen />
            </MemoryRouter>
        );

        const secondaryButton = screen.getByRole('button', { name: 'Delegation' });
        secondaryButton.click();

        expect(mockedNavigate).toHaveBeenCalledWith('/code-entry', { state: { flow: 'delegation' } });
    });
});