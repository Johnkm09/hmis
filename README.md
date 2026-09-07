# 🏨 Hotel Management Information System (HMIS) API

A production-oriented Hotel Management Information System (HMIS) REST API built with Laravel for managing hotel operations, including rooms, guests, reservations, hotel operations, billing, payments, and reporting.

The system provides a structured backend for managing the complete hotel workflow, from room and guest management through reservations, check-in and check-out, billing, payments, and operational reporting.

---

## 📌 Overview

HMIS is a backend-first hotel management platform designed around real-world hotel workflows.

The system follows a modular architecture where each major business domain is independently organized while sharing common infrastructure such as authentication, authorization, validation, database access, testing, logging, and API standards.

The project is intentionally built using production-oriented engineering practices rather than treating each feature as a simple CRUD implementation.

### Core workflow

```text
Guest
  │
  ▼
Reservation
  │
  ├── Room
  │
  ├── Check-in / Check-out
  │
  ▼
Billing
  │
  ▼
Payment
  │
  ▼
Invoice
```

The system also provides operational and management reporting based on hotel activity.

---

# ✨ Key Features

## 🔐 Authentication & Authorization

* User registration
* Login
* Logout
* Laravel Sanctum authentication
* Protected API routes
* Role-based access control
* Permission-based authorization
* Laravel Policies
* Module-level permissions
* Secure password handling
* Authentication and authorization testing

---

## 🏨 Hotel Management

### Room Types

* Create room types
* Update room types
* Delete room types
* View room types
* Room type validation
* Authorization
* Pagination
* API Resources

### Rooms

* Create rooms
* Update rooms
* Delete rooms
* View rooms
* Room type relationships
* Room status management
* Room availability
* Filtering
* Sorting
* Pagination
* Authorization

---

## 👤 Guest Management

* Guest registration
* Guest profiles
* Guest updates
* Guest deletion
* Guest search
* Filtering
* Sorting
* Pagination
* Guest/reservation relationships
* Validation
* Authorization

---

## 📅 Reservation Management

Reservations form the central business workflow of the system.

Features include:

* Create reservations
* Update reservations
* Cancel reservations
* Reservation status
* Guest association
* Room association
* Check-in date
* Check-out date
* Availability validation
* Reservation conflict prevention
* Database transactions
* Authorization
* Reservation history

The reservation workflow is designed to protect data integrity when multiple users attempt to reserve rooms.

---

## 🛎️ Hotel Operations

### Check-in

* Verify reservation
* Validate room availability
* Check guest information
* Update room status
* Record check-in

### Check-out

* Validate active stay
* Calculate outstanding charges
* Complete checkout
* Update room status
* Record checkout

### Room Status

Rooms can transition between operational states such as:

```text
Available
Reserved
Occupied
Cleaning
Maintenance
Out of Service
```

State transitions are controlled by business rules rather than allowing arbitrary updates.

---

# 💰 Billing & Payments

The billing system manages charges generated during a guest's stay.

### Billing

* Invoice creation
* Invoice items
* Accommodation charges
* Additional charges
* Tax calculations
* Discounts
* Invoice status
* Outstanding balances
* Payment history

### Payments

* Payment recording
* Payment status
* Payment methods
* Payment references
* Transaction history
* Payment reconciliation

### M-Pesa

The system is designed to support **Safaricom Daraja API** integration for M-Pesa payments.

Example workflow:

```text
Customer
   │
   ▼
STK Push Request
   │
   ▼
M-Pesa
   │
   ▼
Callback
   │
   ▼
Payment Verification
   │
   ▼
Invoice Updated
```

External payment integrations are isolated from core business logic to make the payment layer easier to test and maintain.

---

# 📊 Reporting & Analytics

The reporting layer provides management-level information derived from operational data.

### Occupancy Reports

* Current occupancy
* Occupancy rates
* Room utilization
* Available rooms
* Occupied rooms

### Revenue Reports

* Daily revenue
* Monthly revenue
* Revenue by room
* Revenue by reservation
* Payment summaries

### Guest Reports

* Guest statistics
* Guest history
* Frequent guests
* Reservation history

Reports are designed around query efficiency and appropriate database aggregation rather than loading unnecessary records into application memory.

---

# 🤖 AI Assistant

The system is designed to support an AI-powered hotel assistant for selected hotel operations and information queries.

Potential capabilities include:

* Hotel information queries
* Room availability assistance
* Reservation assistance
* Guest-facing questions
* Operational information

The AI layer is isolated from the core application so that failures or changes in the external AI provider do not compromise core hotel operations.

---

# 🏗️ Architecture

HMIS follows a layered backend architecture.

```text
                         HTTP Request
                              │
                              ▼
                    ┌──────────────────┐
                    │ Authentication   │
                    │    Sanctum       │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ Authorization    │
                    │ Policies / RBAC  │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │    Controller    │
                    │ HTTP concerns    │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ Service Layer    │
                    │ Business Logic   │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ Repository       │
                    │ Interface        │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ Repository       │
                    │ Implementation   │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │      MySQL       │
                    └──────────────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │  API Resource    │
                    └──────────────────┘
```

This separation keeps HTTP handling, business logic, data access, authorization, and API presentation independently organized.

---

# 🧩 Design Principles

The project applies practical backend engineering principles including:

* Separation of concerns
* Single Responsibility Principle
* Dependency Inversion
* Encapsulation
* Explicit domain boundaries
* Thin controllers
* Reusable services
* Repository abstraction
* Centralized validation
* Consistent API responses
* Database integrity
* Testability

The architecture is intentionally pragmatic rather than introducing abstractions where they provide no value.

---

# 📦 Module Architecture

Each major domain follows a consistent structure.

```text
Model
Migration
Repository Interface
Repository Implementation
Service
Form Request
API Resource
Policy
Controller
Routes
Feature Tests
Unit Tests
API Documentation
```

This consistency makes the codebase easier to understand and extend.

---

# 📁 Project Structure

A simplified application structure:

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           ├── Auth/
│   │           ├── RoomType/
│   │           ├── Room/
│   │           ├── Guest/
│   │           ├── Reservation/
│   │           ├── Billing/
│   │           ├── Payment/
│   │           └── Report/
│   │
│   ├── Requests/
│   │   └── Api/
│   │       └── V1/
│   │
│   └── Resources/
│       └── Api/
│           └── V1/
│
├── Models/
│
├── Policies/
│
├── Repositories/
│   ├── Interfaces
│   └── Implementations
│
├── Services/
│
├── Jobs/
│
├── Notifications/
│
├── Events/
│
└── Providers/

database/
├── migrations/
├── seeders/
└── factories/

routes/
├── api.php
└── auth.php

tests/
├── Feature/
└── Unit/
```

---

# 🔗 Domain Model

The primary domain relationships are:

```text
Room Type
    │
    └──────< Room
                │
                └──────< Reservation >────── Guest
                              │
                              ├──── Check-in
                              │
                              ├──── Check-out
                              │
                              ▼
                           Invoice
                              │
                              └────< Payment
```

This allows the system to model the complete guest lifecycle from reservation through payment and checkout.

---

# 🌐 API Design

The API follows REST principles and is versioned.

```text
/api/v1/...
```

Example endpoints:

```http
POST   /api/v1/register
POST   /api/v1/login
POST   /api/v1/logout

GET    /api/v1/room-types
POST   /api/v1/room-types
GET    /api/v1/room-types/{id}
PUT    /api/v1/room-types/{id}
DELETE /api/v1/room-types/{id}

GET    /api/v1/rooms
POST   /api/v1/rooms
GET    /api/v1/rooms/{id}

GET    /api/v1/guests
POST   /api/v1/guests
GET    /api/v1/guests/{id}
PUT    /api/v1/guests/{id}
DELETE /api/v1/guests/{id}

GET    /api/v1/reservations
POST   /api/v1/reservations
GET    /api/v1/reservations/{id}
PUT    /api/v1/reservations/{id}
DELETE /api/v1/reservations/{id}
```

The API uses appropriate HTTP status codes and standardized response structures.

---

# 🔎 Filtering, Sorting & Pagination

The API supports controlled querying through **Spatie Query Builder**.

Examples:

```http
GET /api/v1/guests?filter[first_name]=John
GET /api/v1/guests?filter[country]=Kenya
GET /api/v1/guests?sort=last_name
GET /api/v1/guests?per_page=20
```

Only explicitly allowed filters and sorts are exposed to the API.

This prevents unrestricted query behavior while providing flexible resource discovery.

---

# 🛡️ Security

Security is treated as a core application concern.

The API implements or is designed around:

* Sanctum authentication
* Policy-based authorization
* Role-based access control
* Permission-based access control
* Form Request validation
* Mass-assignment protection
* Secure password hashing
* Environment-based secrets
* API rate limiting
* Database constraints
* Transactional operations
* Controlled query parameters
* Secure production configuration

Sensitive credentials and API keys are never committed to the repository.

---

# 🗄️ Database Design

MySQL is used as the primary relational database.

The database uses:

* Foreign keys
* Indexes
* Unique constraints
* Appropriate column types
* Nullable fields where appropriate
* Database-level referential integrity
* Transactions for multi-step operations

Business relationships are represented through relational constraints rather than relying solely on application-level assumptions.

---

# ⚡ Caching

Redis is used where caching provides a measurable benefit.

Potential cached data includes:

* Frequently requested hotel configuration
* Room availability data
* Reporting data
* Other expensive read operations

Cache invalidation is tied to relevant domain changes to prevent stale operational information.

---

# 🔄 Background Jobs

Long-running or non-critical operations can be processed asynchronously using Laravel queues.

Examples:

* Notifications
* Emails
* Report generation
* External API processing
* AI-related operations
* Other time-consuming tasks

Example architecture:

```text
API Request
    │
    ▼
Create Job
    │
    ▼
Queue
    │
    ▼
Redis
    │
    ▼
Worker
    │
    ▼
External Service / Database
```

This prevents expensive operations from unnecessarily blocking API requests.

---

# 🔔 Events & Notifications

The system can use Laravel events and notifications to decouple domain events from secondary operations.

Examples:

```text
Reservation Created
        │
        ├── Send confirmation
        ├── Update availability
        └── Trigger notification
```

This keeps the primary reservation workflow focused on its core business operation.

---

# 🧪 Testing

Testing is a major part of the project.

The test suite covers both application behavior and individual components.

### Feature Tests

Feature tests verify complete API workflows including:

* Authentication
* Authorization
* CRUD operations
* Validation
* HTTP responses
* Database changes
* Filtering
* Sorting
* Pagination
* Business rules
* Reservation conflicts
* Payment workflows

### Unit Tests

Unit tests verify isolated application components such as:

* Services
* Repository behavior
* Business calculations
* Domain-specific logic

Run the complete test suite:

```bash
php artisan test
```

---

# 📈 Test Strategy

The project follows a practical testing pyramid:

```text
                 ┌─────────────┐
                 │   Feature   │
                 │    Tests    │
                 └──────┬──────┘
                        │
                ┌───────┴───────┐
                │  Unit Tests   │
                └───────────────┘
```

Critical business workflows receive integration/feature coverage because correctness at the API and database level is more important than testing implementation details alone.

---

# 🚦 Continuous Integration

GitHub Actions automatically validates changes before they are merged into the main branch.

The CI pipeline:

```text
Pull Request
     │
     ▼
GitHub Actions
     │
     ├── Install PHP
     ├── Install Composer dependencies
     ├── Start MySQL
     ├── Prepare environment
     ├── Run migrations
     └── Run tests
              │
              ▼
        Pass / Fail
```

Pull requests cannot be considered complete until the automated test suite passes.

---

# 🔀 Git Workflow

Development follows a feature-branch and Pull Request workflow.

```text
master
   │
   ├── features/room
   │
   ├── features/guest
   │
   ├── features/reservation
   │
   ├── features/operations
   │
   ├── features/billing
   │
   └── features/reporting
```

Typical workflow:

```text
Create feature branch
        ↓
Develop feature
        ↓
Write tests
        ↓
Run tests locally
        ↓
Commit changes
        ↓
Push branch
        ↓
Open Pull Request
        ↓
GitHub Actions
        ↓
Code review / verification
        ↓
Merge into master
```

This keeps the main branch stable and provides a traceable development history.

---

# 🐳 Docker

The application is containerized using Docker to provide a consistent development and deployment environment.

The Docker environment is designed around services such as:

```text
┌───────────────────────────────┐
│           Docker              │
│                               │
│  ┌─────────┐   ┌──────────┐  │
│  │  Nginx  │──▶│ PHP-FPM  │  │
│  └─────────┘   └────┬─────┘  │
│                     │        │
│          ┌──────────┴──────┐ │
│          │                 │ │
│      ┌───▼───┐         ┌──▼─┐│
│      │ MySQL │         │Redis││
│      └───────┘         └────┘│
│                               │
└───────────────────────────────┘
```

Docker provides:

* Reproducible development environments
* Consistent PHP configuration
* Isolated database services
* Redis integration
* Easier onboarding
* Production-like local infrastructure

---

# 🌍 Deployment Architecture

The application is designed to support deployment to a cloud environment.

A typical production architecture:

```text
                  Internet
                     │
                     ▼
              ┌─────────────┐
              │ Load Balancer│
              └──────┬──────┘
                     │
              ┌──────▼──────┐
              │    Nginx    │
              └──────┬──────┘
                     │
              ┌──────▼──────┐
              │  Laravel    │
              │   PHP-FPM   │
              └──────┬──────┘
                     │
          ┌──────────┼──────────┐
          ▼          ▼          ▼
       MySQL       Redis      Queue
                              Workers
```

Production deployment will prioritize:

* HTTPS
* Environment-based configuration
* Secure secrets
* Database backups
* Logging
* Queue workers
* Cache management
* Health checks
* Application monitoring

---

# 📚 API Documentation

API documentation is generated using **Scribe**.

Documentation includes:

* Authentication requirements
* Endpoints
* Parameters
* Request bodies
* Response structures
* Validation requirements
* Filtering
* Sorting
* Pagination
* API versioning

Generate documentation with:

```bash
php artisan scribe:generate
```

Then access:

```text
/docs
```

---

# 🧰 Technology Stack

### Backend

* PHP
* Laravel

### Database

* MySQL
* Redis

### Authentication & Authorization

* Laravel Sanctum
* Spatie Laravel Permission
* Laravel Policies

### API

* REST
* API Resources
* Form Requests
* Spatie Query Builder
* Scribe

### Testing

* Pest
* PHPUnit

### Infrastructure

* Docker
* Docker Compose
* Nginx
* PHP-FPM
* Redis

### CI/CD

* GitHub Actions
* Git
* GitHub

### Integrations

* Safaricom Daraja API
* OpenAI API

---

# 🚀 Local Development

## Requirements

For non-containerized development:

* PHP 8.2+
* Composer
* MySQL
* Node.js/npm where required by Laravel tooling

For the containerized environment:

* Docker
* Docker Compose

---

## Installation

Clone the repository:

```bash
git clone <repository-url>

cd hmis
```

Install dependencies:

```bash
composer install
```

Create environment configuration:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure the database and other services in `.env`.

Run migrations and seed initial data:

```bash
php artisan migrate --seed
```

Start the application:

```bash
php artisan serve
```

---

# 🐳 Docker Installation

Build and start the containers:

```bash
docker compose up -d --build
```

Run migrations:

```bash
docker compose exec app php artisan migrate --seed
```

Run tests:

```bash
docker compose exec app php artisan test
```

Stop the environment:

```bash
docker compose down
```

The exact Docker commands may evolve with the final infrastructure configuration.

---

# 🧪 Development Commands

Run tests:

```bash
php artisan test
```

Run migrations:

```bash
php artisan migrate
```

Refresh the database:

```bash
php artisan migrate:fresh --seed
```

Generate API documentation:

```bash
php artisan scribe:generate
```

Clear application caches:

```bash
php artisan optimize:clear
```

---

# 🗺️ Development Roadmap

## Phase 1 — Authentication ✅

* Registration
* Login
* Logout
* Sanctum authentication
* API Resources
* Standardized API responses

## Phase 2 — Hotel Setup ✅

* Room Types
* Rooms
* Relationships
* CRUD
* Validation
* Authorization
* Filtering
* Sorting
* Pagination
* Tests
* API documentation

## Phase 3 — Guest Management ✅

* Guest CRUD
* Validation
* Authorization
* API Resources
* Filtering
* Sorting
* Pagination
* Feature tests
* API documentation

## Phase 4 — Reservations 🚧

* Reservation CRUD
* Guest relationships
* Room relationships
* Availability checking
* Conflict prevention
* Reservation status
* Database transactions
* Tests
* API documentation

## Phase 5 — Hotel Operations 📋

* Check-in
* Check-out
* Room status
* Occupancy handling
* Operational workflows

## Phase 6 — Billing & Payments 📋

* Invoices
* Invoice items
* Payments
* Payment status
* Payment history
* M-Pesa integration

## Phase 7 — Reporting 📋

* Occupancy reports
* Revenue reports
* Guest reports
* Operational analytics

## Phase 8 — Infrastructure & Production 📋

* Docker
* Redis
* Queues
* Background workers
* Notifications
* Logging
* Health checks
* CI/CD
* Production deployment

## Phase 9 — Advanced Features 📋

* AI hotel assistant
* Advanced caching
* Audit logging
* Advanced reporting
* Performance optimization
* Production hardening

---

# 📐 Engineering Practices

The project emphasizes:

### Maintainability

* Modular organization
* Consistent naming
* Separation of concerns
* Reusable services
* Clear domain boundaries

### Reliability

* Automated testing
* Database constraints
* Transactions
* Validation
* CI checks

### Security

* Authentication
* Authorization
* Input validation
* Secure configuration
* Permission boundaries

### Scalability

* Stateless API architecture
* Pagination
* Efficient queries
* Caching
* Queues
* Background processing

### Observability

* Structured application logging
* Error handling
* Health checks
* Operational monitoring

---

# 📈 Performance Considerations

Performance considerations include:

* Database indexing
* Eager loading where appropriate
* Pagination for collection endpoints
* Query optimization
* Redis caching
* Queue-based background processing
* Avoiding unnecessary database queries
* Efficient reporting queries

The application favors measuring and addressing actual bottlenecks rather than prematurely optimizing every component.

---

# 📝 API Response Philosophy

The API uses a consistent response format to make client integration predictable.

Successful responses provide:

* Request status
* Human-readable message where appropriate
* Resource data
* Pagination metadata where applicable

Validation and application errors use appropriate HTTP status codes and structured error responses.

---

# 🧑‍💻 Development Philosophy

HMIS is intentionally developed incrementally.

Each major domain is:

```text
Designed
   ↓
Implemented
   ↓
Validated
   ↓
Tested
   ↓
Documented
   ↓
Reviewed
   ↓
Merged
```

This prevents the project from becoming a collection of disconnected CRUD endpoints and allows each business capability to be integrated safely into the larger system.

---

# 📄 License

This project is licensed under the MIT License.
