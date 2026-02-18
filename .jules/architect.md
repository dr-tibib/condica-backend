# Architect's Journal

## Initialization
- Started refactoring project.
- Created `app/Infrastructure` directory structure.
- Created `app/Infrastructure/Persistence/Eloquent`.
- Created `app/Infrastructure/Presenters`.
- Created `app/Infrastructure/Services`.

## Structural Decisions
- **Dependency Injection**: Use interfaces for all external services (e.g., Google Places) and persistence (Repositories).
- **Clean Architecture**: Ensure Core entities and use cases are decoupled from Laravel framework.
