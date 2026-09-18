# Turnero Backend Guide

> **Contexto técnico y de negocio completo**: ver [`BACKEND_TECHNICAL_BUSINESS_CONTEXT.md`](./BACKEND_TECHNICAL_BUSINESS_CONTEXT.md) (arquitectura, actores, reglas de negocio, ciclo de vida de turnos, emails, autorización, tests, mapa de archivos).

## Overview
Turnero is a Laravel 11 backend for appointment scheduling with a clean layered architecture.

### What it can do
- Employee authentication with Sanctum.
- Public appointment scheduling for clients.
- Public active services list for the booking form.
- Deduplicate clients by DNI.
- Manage services in the back office.
- Manage appointments in the back office.
- Query a client’s appointments by DNI.
- Schedule reminder jobs for upcoming appointments.
- Email a confirmation link after booking and reuse the same link for reminders.

## Architecture
- `App\Domain`: pure PHP entities, enums and value objects.
- `App\Application`: DTOs, use cases and repository interfaces.
- `App\Infrastructure`: controllers, requests, Eloquent adapters, jobs, listeners and events.

## Authentication

### Login employee
`POST /api/auth/login`

Request body:
```json
{
  "email": "admin@turnero.com",
  "password": "secret"
}
```

Response:
```json
{
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@turnero.com",
    "role": "admin"
  },
  "token_type": "Bearer",
  "access_token": "..."
}
```

### Logout employee
`POST /api/auth/logout`

Header:
`Authorization: Bearer <token>`

## Public appointment scheduling
`POST /api/appointments`

Request body:
```json
{
  "first_name": "Ana",
  "last_name": "Perez",
  "dni": "30123456",
  "phone": "+54 11 5555-8888",
  "email": "ana@example.com",
  "service_id": 1,
  "scheduled_at": "2026-05-01 15:30:00",
  "remind_1_day_before": true,
  "remind_30_mins_before": false
}
```

Rules:
- If a client with the same DNI exists, the appointment is linked to that client.
- If the phone changed, the client phone is updated.
- `remind_1_hour_before` is always enabled.

### Confirm appointment
`GET /api/appointments/confirm/{token}`

The frontend confirmation page uses this endpoint when the user clicks the email link.

### Public active services
`GET /api/services/active`

## Client appointments by DNI
`GET /api/clients/{dni}/appointments`

Example:
`GET /api/clients/30123456/appointments`

## Services CRUD
Protected by `auth:sanctum`.

Write operations are admin-only:
- `POST /api/services`
- `PUT /api/services/{id}`
- `DELETE /api/services/{id}`

### List services
`GET /api/services`

### Create service
`POST /api/services`

Body:
```json
{
  "name": "Corte",
  "duration_minutes": 30,
  "price": 1200
}
```

### Show service
`GET /api/services/{id}`

### Update service
`PUT /api/services/{id}`

Body:
```json
{
  "name": "Corte premium",
  "duration_minutes": 45,
  "price": 1800,
  "active": true
}
```

### Delete service
`DELETE /api/services/{id}`

## Appointments CRUD
Protected by `auth:sanctum`.

### List appointments
`GET /api/appointments`

Query params:
- `scheduled_date`
- `status`
- `client_dni`
- `employee_id` (admin-only filter; employees are always scoped to self)
- `page`
- `per_page`

Response includes `client_name` and `client_dni` for display.

### Show appointment
`GET /api/appointments/{id}`

### Update appointment status
`PATCH /api/appointments/{id}`

Body:
```json
{
  "status": "confirmed"
}
```

Allowed statuses:
- `confirmed`
- `cancelled`
- `completed`
- `no_show`

## Reminder jobs
When an appointment is scheduled, the system emits `LaravelAppointmentScheduled`.
That event schedules `SendAppointmentReminder` jobs for:
- 1 day before
- 1 hour before
- 30 minutes before if requested

The immediate confirmation email and the reminder emails share the same confirmation link.

## Seed data
Run:
```bash
php artisan migrate --seed
```

Creates:
- 1 admin employee
- 5 services
- 20 clients
- 50 appointments
