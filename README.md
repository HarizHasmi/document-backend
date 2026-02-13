# Document Management Backend API

Backend service for ABC Corporation's document management system.

This API provides:
- JWT-like token authentication using Laravel Sanctum personal access tokens
- Role-based access control (Admin, Manager, Employee) using Spatie Permission
- Department and category master data
- Document upload, metadata update, delete, search, filter, and download
- Deterministic seed data for demo/testing
- Feature test coverage for auth and core RBAC/document rules

## Tech stack

- Laravel 12
- PHP 8.2+
- PostgreSQL
- Laravel Sanctum
- Spatie Laravel Permission
- Laravel Storage (local disk)
- PHPUnit

## Core features

### Authentication
- Register: `POST /api/v1/register`
- Login: `POST /api/v1/login`
- Logout: `POST /api/v1/logout`
- Current user: `GET /api/v1/user`

### Document management
- List/search/filter: `GET /api/v1/documents`
- Details: `GET /api/v1/documents/{id}`
- Upload: `POST /api/v1/documents`
- Update metadata: `PATCH /api/v1/documents/{id}`
- Delete: `DELETE /api/v1/documents/{id}`
- Download: `GET /api/v1/documents/{id}/download`

### Master data
- Departments: `GET /api/v1/departments`
- Categories: `GET /api/v1/categories`

## Access control rules

### Roles
- **Admin**: full access to all documents
- **Manager**:
  - view public + own department documents + own uploads
  - upload only to own department
  - update/delete own uploaded documents
- **Employee**:
  - view public documents only
  - download allowed documents
  - cannot upload/update/delete

### Access levels
- `public`: visible to all authenticated users
- `department`: visible to managers in that department (and admin)
- `private`: visible to uploader and admin

## Validation rules

### Document upload
- Allowed file types: `pdf`, `docx`, `xlsx`, `jpg`, `png`
- Max size: `10MB` (`max:10240` KB)
- Required fields:
  - `title`
  - `file`
  - `category_id`
  - `department_id`
  - `access_level` (`public|department|private`)
- Optional field:
  - `description`

## Response shape

Most endpoints return a consistent JSON envelope:

```json
{
  "status": "success",
  "message": "Human readable message",
  "data": {}
}
```

Document list also includes pagination + result count:
- `result_count`
- `data`
- `links`
- `meta`

## Quick start

### 1) Install dependencies

```bash
composer install
```

### 2) Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Set DB values in `.env`:
- `DB_CONNECTION=pgsql`
- `DB_HOST=...`
- `DB_PORT=5432`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

Optional CORS origins:
- `CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173`

### 3) Run migrations and seed data

```bash
php artisan migrate:fresh --seed
```

### 4) Run API server

```bash
php artisan serve
```

Base URL:
- `http://127.0.0.1:8000/api/v1`

## Seeded data

### Departments (5)
- Human Resources (HR)
- Finance
- Information Technology (IT)
- Marketing
- Operations

### Categories (6)
- Policy
- Report
- Template
- Guide
- Form
- Other

### Users (12)
All seeded user passwords are:
- `password`

Default logins:
- Admin: `ahmad.faizal@abc-corp.com`
- Managers:
  - `nur.aisyah.rahman@abc-corp.com`
  - `muhammad.amirul.hakim@abc-corp.com`
  - `siti.hajar.zulkifli@abc-corp.com`
  - `farhan.iqbal.ismail@abc-corp.com`
- Employees:
  - `nurul.syafiqah.ahmad@abc-corp.com`
  - `aiman.danish.razak@abc-corp.com`
  - `hafizah.binti.omar@abc-corp.com`
  - `irfan.adli.azman@abc-corp.com`
  - `puteri.balqis.roslan@abc-corp.com`
  - `zulhilmi.anuar@abc-corp.com`
  - `nadia.sofea.iskandar@abc-corp.com`

### Documents (30)
- Spread across all 5 departments
- Mix of all categories
- Access levels ratio:
  - 50% public (15)
  - 30% department (9)
  - 20% private (6)
- Deterministic titles/descriptions
- Dummy files written to storage (`documents/seeded/*`)

## API examples

### Login

`POST /api/v1/login`

```json
{
  "email": "ahmad.faizal@abc-corp.com",
  "password": "password"
}
```

Use returned token in header:

```http
Authorization: Bearer <token>
```

### Search and filter documents

`GET /api/v1/documents?search=policy&category_id=1&department_id=2&per_page=10`

### Upload document (multipart/form-data)

Fields:
- `title`
- `description` (optional)
- `file` (binary)
- `category_id`
- `department_id`
- `access_level`

## Testing

Run test suite:

```bash
php artisan test
```

Feature tests include required scenarios:
- registration
- login
- logout
- manager upload allowed
- employee upload blocked
- search
- category filtering
- admin delete any document
- additional RBAC visibility checks

## Factories and seeders

Seeders orchestrate deterministic outputs while using factory states.

Useful factory states include:

### UserFactory
- `admin($departmentId = null)`
- `manager($departmentId = null)`
- `employee($departmentId = null)`
- `inDepartment($departmentId)`

### DocumentFactory
- access states: `publicAccess()`, `departmentAccess()`, `privateAccess()`
- category states: `policy()`, `report()`, `template()`, `guide()`, `form()`, `other()`
- helpers: `forDepartment()`, `uploadedBy()`, `titled()`, `fileType()`

## Bruno testing quick guide

Create a Bruno environment:
- `baseUrl = http://127.0.0.1:8000/api/v1`
- `token =`

Flow:
1. `POST {{baseUrl}}/login`
2. Save `data.token` to `token`
3. Call authenticated endpoints with:
   - `Authorization: Bearer {{token}}`

## Notes

- Storage disk used for documents: `local`
- Document metadata is editable; file binary is not replaced by update endpoint
- Policy + controller checks jointly enforce RBAC rules
