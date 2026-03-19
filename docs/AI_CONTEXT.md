# AI Development Context: Hierarchical Apartment Groups

## Purpose
This document provides context for future AI agents regarding the implementation of the hierarchical `ApartmentGroup` feature. It outlines the architectural decisions, database changes, and access control mechanisms introduced to support this feature.

## Feature Overview
The application allows administrators (`ROLE_ADMIN`) to group apartments and assign these groups to users (`ROLE_USER`). This ensures that users can only view and manage apartments that belong to their assigned groups. The grouping system is hierarchical, meaning a group can have a parent and multiple children. When a user is assigned to a parent group, they implicitly gain access to apartments in all its child groups.

## Key Components

### 1. Domain Layer
*   **`App\Domain\ApartmentGroup\ApartmentGroup`**: The core domain entity representing a group. It has a `name`, an optional `parent` group, and an `id`.
*   **`App\Domain\ApartmentGroup\ApartmentGroupRepositoryInterface`**: Defines the contract for fetching and persisting groups.

### 2. Infrastructure Layer
*   **`App\Infrastructure\Persistence\Doctrine\Entity\ApartmentGroup`**: The Doctrine ORM entity mapped to the `apartment_group` table. It implements a self-referencing `ManyToOne` relationship for the `parent` property, allowing infinite hierarchy.
*   **Relationships**:
    *   `Apartment` <-> `ApartmentGroup`: Many-to-Many. Mapped via `apartment_apartment_group` table.
    *   `User` <-> `ApartmentGroup`: Many-to-Many. Mapped via `user_apartment_group` table.
*   **`App\Infrastructure\Persistence\Doctrine\Repository\ApartmentGroupRepository`**: Implements the repository interface. It handles mapping between Doctrine entities and Domain objects (`toDomain()` and `persistDomain()`).
*   **`App\Infrastructure\Persistence\Doctrine\Repository\ApartmentRepository`**: Updated with a new method `findByGroupIds(array $groupIds)` to efficiently filter apartments based on a list of group IDs using a SQL `IN` clause.

### 3. Application Layer
*   **`App\Application\Apartment\Query\GetAllApartmentsQuery`**: Updated to accept an optional array of `$groupIds`. If provided, it delegates to `ApartmentRepository::findByGroupIds()`, otherwise it fetches all apartments.

### 4. Controller Layer (Access Control)
*   **`App\Controller\ApartmentController`**:
    *   `index()`: Retrieves the current user's assigned groups. It extracts the IDs of these groups and traverses down the hierarchy to collect IDs of child groups (currently implemented up to 3 levels deep for simplicity). It then passes these IDs to `GetAllApartmentsQuery` to filter the list. Super admins (`ROLE_ADMIN`) bypass this and see all.
    *   `edit()`: Before allowing an edit, it verifies if the apartment belongs to any of the user's assigned groups (or their children). If not, it throws an `AccessDeniedException`.
    *   *Note on Persistence*: In `edit()`, because mapping nested collections from Doctrine to Domain objects and back can be complex and fragile in the current Hexagonal Architecture setup, the `Many-to-Many` relationships for `ApartmentGroups` are flushed directly via the Doctrine `EntityManagerInterface` injected into the action, right before the Domain object is passed to `UpdateApartmentCommand`.
*   **`App\Controller\ApartmentGroupController`**: Standard CRUD controller for managing groups. Restricted to `ROLE_ADMIN`.
*   **`App\Controller\UserController`**: Standard CRUD controller for managing users and assigning them to groups. Restricted to `ROLE_ADMIN`.

### 5. Configuration
*   **`config/packages/security.yaml`**: Access control rules were updated. The main `/admin` area is now accessible to `ROLE_USER` (so they can see their apartments), but `/admin/users` and `/admin/apartment-groups` are strictly restricted to `ROLE_ADMIN`.

## Important Considerations for Future Modifications
*   **Hierarchy Depth**: The current access control logic in `ApartmentController` traverses the group hierarchy manually using nested loops (up to 3 levels deep). While sufficient for most use cases, if an extremely deep hierarchy is required, this should be refactored into a recursive repository method (e.g., using a recursive CTE in PostgreSQL) to avoid performance issues and hardcoded depth limits.
*   **Hexagonal Architecture Boundaries**: As noted, saving the Many-to-Many relationship in `ApartmentController::edit` currently relies directly on the Doctrine `EntityManager`. If strict adherence to the Domain layer is required for relationships in the future, the `Apartment` domain object and `UpdateApartmentCommand` will need to be refactored to fully support collection synchronization.
*   **Migrations**: When generating new migrations (`make:migration`), ensure it is run against an up-to-date local PostgreSQL database that has all previous migrations applied. Otherwise, Doctrine might attempt to recreate existing tables (like `apartment` or `user`), which will cause deployment failures.
