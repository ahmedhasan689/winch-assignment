# WINCH Driver Assignment System

> Domain-driven Laravel 12 backend + Vue 3 frontend implementing concurrency-safe automatic driver assignment for transport orders.

This project is a technical assignment for **WINCH Engineering** : a transport / logistics system where pending orders are auto-matched to the nearest available driver, under realistic race conditions.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Quick Start](#quick-start)
- [Project Structure (DDD)](#project-structure-ddd)
- [API Endpoints](#api-endpoints)
- [Architectural Decisions](#architectural-decisions)
- [Race Condition Handling](#race-condition-handling)
- [Testing](#testing)
- [Frontend (Vue Dispatcher)](#frontend-vue-dispatcher)
- [Difficulties Encountered](#difficulties-encountered)
- [Out of Scope (Consciously)](#out-of-scope-consciously)
- [Future Improvements](#future-improvements)
- [Part 2 : Architectural Decision Document](#part-2)

---

## Tech Stack

| Layer | Technology | Why |
|------|-----------|-----|
| Backend Framework | Laravel 12 | Required by the assignment |
| Frontend | Vue 3 (Composition API) + Vite | Required by the assignment |
| Styling | Tailwind CSS v4 | Modern, CSS-based config, no `postcss.config.js` needed |
| Database | MySQL 8 | Required by the assignment; reflects WINCH production |
| HTTP Client | Axios | Standard in Laravel, browser + Node compatible |
| Testing | PHPUnit 11 | Laravel's default, familiar to any Laravel reviewer |
| PHP | 8.2+ | Required for readonly classes and modern enums |
| Concurrency Primitive | MySQL `SELECT ... FOR UPDATE` | Pessimistic locking inside `DB::transaction` |

---

## Quick Start

### Prerequisites

- PHP **8.2+**
- Composer **2.x**
- Node.js **20+** and npm
- MySQL **8.x**

### 1. Clone & install dependencies

```bash
git clone https://github.com/ahmedhasan689/winch-assignment.git
cd winch-assignment

composer install
npm install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your MySQL connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=winch_assignment
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Create the databases (production + testing)

```sql
CREATE DATABASE winch_assignment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE winch_assignment_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Migrate + seed

```bash
php artisan migrate --seed
php artisan migrate --env=testing
```

The seeders generate **10 drivers** (5 available, 3 busy, 2 offline) and **18 orders** (10 pending, 3 assigned, 5 completed), all coordinates within the Riyadh bounding box.

### 5. Run the application

Open **two terminals**:

```bash
# Terminal 1 : Laravel backend
php artisan serve

# Terminal 2 : Vite dev server (assets / HMR)
npm run dev
```

Open **http://127.0.0.1:8000** in your browser.

---

## Project Structure (DDD)

The project follows the **layered DDD structure mandated by the assignment**:

```
src/
├── Domain/                          # Pure business logic
│   ├── Order/
│   │   ├── Actions/                 # Single-purpose use cases (e.g. AssignOrderAction)
│   │   ├── Contracts/               # Interfaces, the bounded context's public gates
│   │   ├── Services/                # Implementations (repositories, matchers)
│   │   ├── DataTransferObjects/     # Immutable command/filter objects
│   │   ├── Enums/                   # OrderStatus with behavior
│   │   ├── Exceptions/              # Domain exceptions
│   │   ├── Models/Entities/         # Eloquent entities
│   │   ├── Providers/               # OrderServiceProvider, binds contracts
│   │   └── Observers/
│   ├── Driver/                      # Same structure, different bounded context
│   └── Shared/
│       └── ValueObjects/            # Coordinates (Haversine distance)
│
└── Presentation/
    └── Dispatcher/                  # Single CpanelName for the dispatch dashboard
        ├── Controllers/             # Thin HTTP entry points
        ├── Requests/                # Validation + DTO conversion
        ├── Resources/               # JSON output shapers
        ├── Routes/api.php           # Panel-scoped routes
        ├── Views/                   # Panel-scoped Blade views
        └── Providers/               # Registers the view namespace
```

### Key principle

> Each bounded context (`Order`, `Driver`) exposes only **Contracts** to the outside. Other code depends on `OrderRepositoryContract`, never on `EloquentOrderRepository` directly. The DI container resolves the implementation via `OrderServiceProvider`.

This satisfies the assignment's explicit rule:
> *"No domain depends on another except through a Contract, no direct calls to Actions or Services from another domain."*

---

## API Endpoints

### 1. Assign an order : auto-match nearest available driver

```http
POST /api/orders/{order}/assign
```

**Response 200**
```json
{
    "data": {
        "id": 3,
        "status": "assigned",
        "customer": { "name": "...", "phone": "..." },
        "pickup": { "lat": 24.71, "lng": 46.67 },
        "dropoff": { "lat": 24.81, "lng": 46.79 },
        "driver": {
            "id": 4,
            "name": "...",
            "status": "busy",
            "current_location": { "lat": 24.70, "lng": 46.65 }
        },
        "assigned_at": "2026-05-22T10:15:33+00:00"
    }
}
```

**Response 409 : order already assigned**
```json
{
    "message": "Order #3 is already assigned and cannot be reassigned.",
    "error_code": "ORDER_ALREADY_ASSIGNED"
}
```

**Response 409 : no driver available**
```json
{
    "message": "No driver is currently available to take this order.",
    "error_code": "NO_AVAILABLE_DRIVER"
}
```

### 2. List orders for a driver : with filters & pagination

```http
GET /api/drivers/{driver}/orders?status=assigned&per_page=15&page=1
```

Returns a paginated `OrderResource` collection with Laravel's standard `meta` and `links` sections.

### 3. List all orders (used by the dispatcher dashboard)

```http
GET /api/orders?status=pending&per_page=50
```

> Not in the original spec, added to support the Vue dashboard. Documented as a conscious extension.

---

## Architectural Decisions

### 1. DDD layered structure mandated by the assignment

Two top-level layers: `Domain/` (business rules) and `Presentation/` (HTTP I/O). All other concerns (data access, configuration, matching) live as **Services inside Domain**, each behind a `Contract`.

### 2. Rich domain entities, not anemic models

Business rules live on the entity itself:

```php
$order->assignTo($driver);   // throws OrderAlreadyAssignedException if not pending
$driver->markBusy();
```

The Action only orchestrates, it never makes the decision itself.

### 3. Pragmatic Eloquent inside Domain

A strict DDD purist would use POPO entities + repository mappers. I chose to use Eloquent inside `Domain/X/Models/Entities/` because:
- The project size doesn't justify mapper boilerplate (× 3 classes per entity).
  - Eloquent is still **hidden behind `RepositoryContract`**, no external code calls `Order::find()` directly.
- Decisive separation through interfaces is the actual purpose of DDD here; mappers would be over-engineering.

### 4. Auto-matching by nearest available driver (Haversine)

The endpoint `POST /api/orders/{id}/assign` takes no `driver_id`, the system picks the closest available driver with no active order. The pluggable contract `DriverMatcherContract` allows swapping the strategy (e.g., highest-rated, fewest-trips-today) without touching `AssignOrderAction`.

### 5. Tailwind v4 with CSS-based configuration

No `tailwind.config.js` or `postcss.config.js`. A single `@import "tailwindcss";` in `app.css` and the `@tailwindcss/vite` plugin in `vite.config.js`. Less boilerplate, faster builds.

### 6. Per-panel ServiceProvider for views

Instead of dumping every Blade into `resources/views/`, each Cpanel (`Dispatcher`) carries its own `Views/` folder, registered via `DispatcherServiceProvider`. Future panels (Admin, DriverApp) stay self-contained.

---

## Race Condition Handling

The most critical correctness concern of the system: two dispatchers must not assign the same order to different drivers simultaneously.

### Strategy: Pessimistic Locking

```php
DB::transaction(function () use ($command) {
    // 1) Lock order first (consistent order prevents deadlocks)
    $order = $this->orderRepository->findForUpdate($command->orderId);

    // 2) Lock the chosen driver
    $driver = $this->driverRepository->findForUpdate($driver->id);

    // 3) Domain rules : throws OrderAlreadyAssignedException if not pending
    $order->assignTo($driver);
    $driver->markBusy();

    // 4) Persist atomically; rollback on any failure
    $this->orderRepository->save($order);
    $this->driverRepository->save($driver);
}, attempts: 3);   // Auto-retry on transient deadlocks
```

### Why pessimistic over optimistic

| Concern | Pessimistic (chosen) | Optimistic (alternative) |
|--------|----------------------|--------------------------|
| Correctness under contention | Guaranteed first-come-first-served | Requires retry loop, possible starvation |
| Code clarity | Linear, easy to read | Adds version column + conflict handling |
| Read-heavy workloads | Not relevant (writes are short) | Better, but our hot path is the **write** |

For a transport-assignment domain, where a wrong assignment ships a driver to the wrong address, **correctness beats theoretical throughput**.

### Defenses in depth

1. **Lock ordering** (`order` → `driver`) eliminates deadlock cycles between transactions.
2. **`attempts: 3`** retries gracefully on the rare deadlock MySQL still surfaces.
3. **Domain invariants** (`Order::assignTo` throws) catch any logical race that slips through.

Proven by `tests/Feature/RaceConditionTest.php`, 10 concurrent PHP processes hit the same order; only one succeeds, nine receive **409 Conflict**.

---

## Testing

### Run all tests

```bash
php artisan test
```

### What's tested

| Test | Layer | Proves |
|------|------|--------|
| `CoordinatesTest` (5 cases) | Unit | Haversine math + lat/lng validation |
| `OrderAssignmentTest::it_assigns_pending_order_to_nearest_available_driver` | Feature | Happy path: matcher picks closest driver |
| `OrderAssignmentTest::it_returns_409_when_no_driver_is_available` | Feature | Graceful "no driver" response |
| `OrderAssignmentTest::it_returns_409_when_order_is_already_assigned` | Feature | Sequential double-assignment is rejected |
| `DriverOrdersTest::it_lists_driver_orders_with_filter_and_pagination` | Feature | Filter DTO + pagination meta |
| `RaceConditionTest::only_one_process_succeeds_when_assigning_concurrently` | Feature (concurrent) | Pessimistic lock actually prevents double-assignment under real concurrency |

**Total: 10 tests, ~3 seconds end-to-end.**

### Testing philosophy

Tests are intentionally **focused, not exhaustive**. Each one proves a property that would otherwise be a claim. Resource shape, factory states, and trivial validation are covered by manual inspection.

### Testing the race condition

The race condition test launches **10 real PHP processes** via `Symfony Process` against a shared MySQL test database, then parses their stdout. It works on Windows because it uses processes rather than `pcntl_fork`.

A small helper command : `php artisan race-test:assign {orderId}`, exists solely to be invoked by the test. It is not exposed to the dispatcher.

---

## Frontend (Vue Dispatcher)

Single-page app served at `/`, mounted on a Blade shell in `src/Presentation/Dispatcher/Views/dispatcher.blade.php`.

### Features

- Two tabs: **Pending** and **Assigned** with live counters
- Pending tab: table of orders with an `Assign` button per row
- Assigned tab: table with order + driver columns (driver name, phone, status badge, current location, `assigned_at`)
- Toast feedback for success / 409 errors
- Tailwind v4 for styling, Axios for API calls

### Build for production

```bash
npm run build
```

Assets are emitted to `public/build/`. Blade's `@vite` directive automatically uses them in production mode (`APP_ENV=production`).

---

## Difficulties Encountered

| Challenge                                                                                                                                                                        | Resolution |
|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| **Custom model namespaces broke factory auto-resolution** : Laravel looks for factories under `App\Models\` by default.                                                          | Overrode `newFactory()` on each entity to return the right factory class. Documented in `Driver.php` and `Order.php`. |
| **Race condition test on Windows** : `pcntl_fork` is Linux-only; I needed cross-platform true concurrency.                                                                       | Used `Symfony\Component\Process\Process` to spawn real `php artisan` subprocesses. Each one opens its own DB connection, exercising the MySQL lock manager. |
| **Tailwind v4 setup differs from v3** : `npx tailwindcss init` fails because the CLI moved; `@tailwind components` no longer exists.                                             | Switched to `@tailwindcss/vite` plugin with a single `@import "tailwindcss";` in CSS. No config files needed. |
| **Refactor mid-flight** : initial structure used a 4-layer Clean Architecture (Application + Infrastructure). The assignment requires a 2-layer DDD (`Domain` + `Presentation`). | Collapsed Application use cases into `Domain/X/Actions/`, moved Eloquent repositories into `Domain/X/Services/`. The contract layer (`Contracts/`) stayed unchanged, which proved the value of interface-first design. |

---

## Out of Scope (Consciously)

These were either explicitly outside the assignment or deliberately deferred. Each is documented here per the assignment's instruction to "document what you didn't complete and why":

- **Order lifecycle beyond assignment** : `in_progress`, `completed`, `cancelled` transitions are in the enum but have no endpoints. Domain methods could be added in a few lines (`Order::complete()`, `Order::cancel()`), but the assignment only required assignment.
- **Authentication / authorization** : no `auth:sanctum` middleware. The dispatcher panel is open. Real WINCH would gate this behind a Sanctum token.
- **Order completion releases driver** : closely related to the lifecycle above; not implemented since there is no completion endpoint.
- **Driver location updates over time** : drivers' coordinates are static after seeding. A real system would update them via a separate `PATCH /drivers/{id}/location` endpoint hit by the mobile app.
- **Domain events** : `OrderAssigned`, `OrderCompleted` could fire side-effects (notify driver, update analytics). Skeleton folder `Domain/Order/Observers/` exists; no listener registered yet.
- **Redis read-through cache** : analyzed in Part 2 as a future scaling lever, not implemented because the assignment explicitly warns against jumping to Redis.

---

## Future Improvements

If granted more time:

1. **Switch to event-driven flow** : `AssignOrderAction` dispatches `OrderAssigned`. Listeners handle: driver notification, ETA estimation, analytics.
2. **Spatial indexing** : replace `DECIMAL(10,7)` lat/lng pair with `POINT NOT NULL SRID 4326` + spatial R-Tree index. Enables `ST_Distance_Sphere` queries on millions of rows.
3. **Multi-criteria matching** : vehicle type, driver rating, completed trips today. The `DriverMatcherContract` already supports this, a new `MultiCriteriaDriverMatcher` is a single binding change.
4. **WebSockets via Reverb** : push assignment events to the dispatcher UI for real-time updates without polling.
5. **Optimistic locking variant** : for read-heavy regions, expose a separate flow that uses a `version` column. Requested only for non-critical batch operations.

---

## Part 2

The architectural decision document for the second part of the assignment lives in [`docs/PART_2_ARCHITECTURAL_DECISION.md`](docs/PART_2_ARCHITECTURAL_DECISION.md).

---

## License

Internal assignment for WINCH Engineering. Not for public reuse.
