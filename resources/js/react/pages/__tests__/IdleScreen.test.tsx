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

declare global {
  interface Window {
    tenant: any;
  }
}

describe('IdleScreen', () => {
  const originalTenant = window.tenant;

  beforeEach(() => {
    // Mock fetch
    global.fetch = vi.fn(() =>
      Promise.resolve({
        json: () => Promise.resolve({}),
      })
    ) as any;
  });

  afterEach(() => {
    window.tenant = originalTenant;
    vi.restoreAllMocks();
  });

  it('renders the welcome message and buttons with default name', () => {
    delete window.tenant;
    render(
      <MemoryRouter>
        <IdleScreen />
      </MemoryRouter>
    );

    expect(screen.getByText('Welcome to Acme Corp HQ')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Tap to Enter Your Code/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Delegation/i })).toBeInTheDocument();
  });

  it('renders the welcome message with tenant company name', () => {
    window.tenant = { company_name: 'Dynamic Corp' };
    render(
      <MemoryRouter>
        <IdleScreen />
      </MemoryRouter>
    );

    expect(screen.getByText('Welcome to Dynamic Corp')).toBeInTheDocument();
  });

  it('updates company name from API', async () => {
    global.fetch = vi.fn(() =>
        Promise.resolve({
            json: () => Promise.resolve({ company_name: 'API Corp' }),
        })
    ) as any;

    render(
        <MemoryRouter>
            <IdleScreen />
        </MemoryRouter>
    );

    expect(await screen.findByText('Welcome to API Corp')).toBeInTheDocument();
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

    it('renders the default logo when no custom logo is provided', () => {
        delete window.tenant;
        render(
            <MemoryRouter>
                <IdleScreen />
            </MemoryRouter>
        );

        const logo = screen.getByAltText('Company Logo');
        expect(logo).toBeInTheDocument();
        expect(logo).toHaveAttribute('src', '/images/oak_soft_logo.svg');
        expect(logo).toHaveClass('h-20');
    });

    it('renders the custom logo when provided via API', async () => {
        const customLogoUrl = 'https://example.com/logo.png';
        global.fetch = vi.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ logo_url: customLogoUrl }),
            })
        ) as any;

        render(
            <MemoryRouter>
                <IdleScreen />
            </MemoryRouter>
        );

        const logo = await screen.findByAltText('Company Logo');
        expect(logo).toHaveAttribute('src', customLogoUrl);
    });
});
