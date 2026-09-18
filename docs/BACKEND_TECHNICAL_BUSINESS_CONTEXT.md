# Turnero — Backend: Contexto Técnico y de Negocio

> Documento pensado para que una IA (o un desarrollador nuevo) entienda con precisión qué hace el backend, sin necesidad de redescubrir todo el código fuente.

---

## 1. Resumen Ejecutivo

Turnero es un sistema de gestión de turnos con dos frentes:

- **Frontend público** (Next.js) para que clientes reserven sin login.
- **Backoffice interno** (Next.js) para empleados y administradores.

El **backend** es una API Laravel 11 con arquitectura en tres capas (Domain, Application, Infrastructure).

### Qué problema de negocio resuelve
- Clientes eligen servicio, fecha y horario; el sistema les asigna automáticamente un profesional disponible.
- El cliente recibe email de confirmación inmediato y recordatorios antes del turno.
- El cliente confirma su asistencia a través de un link único.
- El negocio gestiona servicios, profesionales, turnos y disponibilidad desde un panel interno.

---

## 2. Actores del Sistema

| Actor | Descripción | Autenticación |
|---|---|---|
| **Cliente público** | Reserva turno y consulta sus turnos por DNI. Confirma asistencia desde email/token. | Sin login (sin contraseña) |
| **Empleado (employee)** | Tiene horarios/breaks/bloqueos y servicios asignados. Ve y gestiona sus propios turnos. Configura su disponibilidad. | Sanctum token |
| **Admin (admin)** | Ve y gestiona todo: turnos, servicios, disponibilidad de cualquier profesional. | Sanctum token |

---

## 3. Reglas de Negocio Principales

1. **Email obligatorio** al reservar un turno.
2. Al reservar se genera un `confirmation_token` (UUID v4 único).
3. **Email de confirmación inmediato** al crear el turno.
4. **Recordatorios** (1 día antes, 1 hora antes, 30 min antes) usan el mismo link de confirmación.
5. El turno **no se agenda** si ningún profesional tiene disponibilidad en ese horario.
6. El cliente **no elige profesional**: el backend asigna `employee_id` automáticamente.
7. **Admin ve todos los turnos**. **Empleado solo ve sus propios turnos** (el backend fuerza `employee_id` al ID propio si el rol es `employee`).
8. **Crear/editar/borrar servicios es exclusivo de admin** (employee solo puede listar/ver).
9. **Admin gestiona disponibilidad de cualquier profesional** (horarios, breaks, días bloqueados). **Empleado solo configura la propia**.
10. La confirmación (`/confirmacion?token=...`) la resuelve el frontend llamando al backend con el token.

---

## 4. Modelo de Datos

### Tablas principales

| Tabla | Propósito | Campos clave |
|---|---|---|
| `users` | Empleados y admins | `id`, `name`, `email`, `password`, `role` (`employee` o `admin`) |
| `clients` | Clientes que reservan | `id`, `first_name`, `last_name`, `dni` (único), `phone`, `email` |
| `services` | Servicios ofrecidos | `id`, `name`, `duration_minutes`, `price`, `active` |
| `appointments` | Turnos agendados | `id`, `client_id`, `employee_id`, `service_id`, `scheduled_at`, `status`, `confirmation_token`, `remind_*` |
| `employee_services` | Pivot: qué servicios ofrece cada empleado | `user_id`, `service_id` |
| `employee_schedules` | Horarios semanales por empleado | `user_id`, `day_of_week` (0-6), `start_time`, `end_time` |
| `employee_breaks` | Breaks por empleado y día | `user_id`, `day_of_week` (0-6), `start_time`, `end_time` |
| `employee_blocked_dates` | Fechas puntuales bloqueadas | `user_id`, `date`, `reason` |
| `personal_access_tokens` | Tokens Sanctum para API auth | `tokenable_id`, `token` |
| `jobs` / `job_batches` / `failed_jobs` | Colas de Laravel para recordatorios | `queue`, `payload`, `available_at` |

### Relaciones

```
users 1──N employee_schedules
users 1──N employee_breaks
users 1──N employee_blocked_dates
users N──M services (via employee_services)
users 1──N appointments (como employee_id)

clients 1──N appointments

services 1──N appointments
services N──M users (via employee_services)

appointments N──1 clients (client_id)
appointments N──1 services (service_id)
appointments N──1 users (employee_id, nullable)
```

### Estados de turno (`appointments.status`)
- `scheduled`: recién creado, pendiente de confirmación.
- `confirmed`: el cliente confirmó asistencia.
- `cancelled`: cancelado (solo desde scheduled/confirmed).
- `completed`: el servicio se realizó (solo desde confirmed).
- `no_show`: el cliente no asistió (solo desde confirmed).

---

## 5. Arquitectura Técnica

El backend sigue **Clean Architecture / Hexagonal** adaptada a Laravel 11.

### Capas

#### `App\Domain` — Entidades puras, sin dependencias de framework

| Clase | Rol |
|---|---|
| `Appointment` | Entidad turno: `schedule()`, `confirm()`, `cancel()`, `complete()`, `markNoShow()`. Usa `ReminderPreferences` y `AppointmentStatus`. |
| `Client` | Entidad cliente: valida DNI (7-10 dígitos), teléfono, email. |
| `AppointmentStatus` | Enum PHP: `Scheduled`, `Confirmed`, `Cancelled`, `Completed`, `NoShow`. |
| `UserRole` | Enum PHP: `Employee`, `Admin`. |
| `ReminderPreferences` | Value object con flags: `remind1DayBefore`, `remind30MinsBefore`, `remind1HourBefore`. |
| `Shared/Events/AppointmentScheduled` | Domain event disparado al crear turno. |

#### `App\Application` — Casos de uso, DTOs, puertos

| Clase | Rol |
|---|---|
| `ScheduleAppointmentUseCase` | Orquesta la reserva: busca/crea cliente, asigna empleado, crea turno, persiste. |
| `ListAppointmentsUseCase` | Lista turnos con filtros pasando un `ListAppointmentsDTO`. |
| `GetAppointmentUseCase` | Obtiene un turno por ID. |
| `ChangeAppointmentStatusUseCase` | Cambia estado (confirm/cancel/complete/no_show). |
| `ConfirmAppointmentUseCase` | Confirma turno por `confirmation_token`. |
| `ListClientAppointmentsUseCase` | Lista turnos de un cliente por DNI. |
| `ListAvailableSlotsUseCase` | Calcula slots disponibles para un servicio en una fecha. |
| `LoginUseCase` / `LogoutUseCase` | Autenticación con Sanctum. |
| `*RepositoryInterface` (Ports) | Interfaces que el dominio necesita (desacopladas de Eloquent). |

#### `App\Infrastructure` — Implementaciones concretas

| Tipo | Ejemplos |
|---|---|
| Controllers | `ScheduleAppointmentController`, `AppointmentController`, `AvailabilityController`, `EmployeeAvailabilityController`, `ServiceController`, `LoginController`, etc. |
| Requests | `ScheduleAppointmentRequest`, `ListAppointmentsRequest`, `ChangeAppointmentStatusRequest`, `LoginRequest`, etc. |
| Eloquent Adapters | `EloquentAppointmentRepository`, `EloquentClientRepository`, `EloquentAvailabilityRepository`, etc. |
| Models | `AppointmentModel`, `ClientModel`, `ServiceModel`, `EmployeeScheduleModel`, `User`, etc. |
| Events | `LaravelAppointmentScheduled` (disparado al persistir turno). |
| Listeners | `SendAppointmentConfirmationEmail`, `ScheduleAppointmentReminders`. |
| Jobs | `SendAppointmentReminder` (encolado con delay). |
| Mail | `AppointmentNotificationMail` (Mailable de Laravel). |
| Middleware | `EnsureAdmin`, `EnsureEmployeeSelfOrAdmin`. |

#### Registro de bindings

En `AppServiceProvider::register()` se enlazan las interfaces con sus implementaciones:

```php
AuthRepositoryInterface → EloquentAuthRepository
ClientRepositoryInterface → EloquentClientRepository
AppointmentRepositoryInterface → EloquentAppointmentRepository
ServiceRepositoryInterface → EloquentServiceRepository
AvailabilityRepositoryInterface → EloquentAvailabilityRepository
```

---

## 6. Ciclo de Vida de un Turno (End-to-End)

1. **Cliente elige servicio + fecha + slot** en el formulario público.
2. Frontend consulta `GET /api/availability?service_id=X&date=YYYY-MM-DD`.
3. Backend (`AvailabilityController` → `ListAvailableSlotsUseCase` → `EloquentAvailabilityRepository`) calcula slots de 30 min, marcando `available: true/false` según horarios, breaks, bloqueos y turnos existentes.
4. Cliente completa formulario y envía `POST /api/appointments` con: `first_name`, `last_name`, `dni`, `phone`, `email`, `service_id`, `scheduled_at`, `remind_*`.
5. `ScheduleAppointmentUseCase`:
   - Busca cliente por DNI (`EloquentClientRepository::findByDni`). Si no existe, crea un `Client` nuevo. Si existe, actualiza teléfono/email si cambiaron.
   - Persiste cliente.
   - Llama a `AvailabilityRepository::findAvailableEmployeeIdForServiceAt()`: busca un empleado que ofrezca el servicio, esté en horario, no tenga break, no tenga la fecha bloqueada y no tenga turno en ese slot.
   - Si no hay empleado → lanza `NoEmployeeAvailableException` → Controller devuelve 422.
   - Si hay empleado → crea `Appointment::schedule(clientId, employeeId, serviceId, scheduledAt, ReminderPreferences)`.
   - Persiste con `AppointmentRepository::save()` → se genera `confirmation_token` (UUID).
6. `EloquentAppointmentRepository::save()` dispara el evento de dominio `AppointmentScheduled`, que se traduce a `LaravelAppointmentScheduled`.
7. `EventServiceProvider` conecta el evento a dos listeners:
   - `SendAppointmentConfirmationEmail`: envía email de confirmación inmediato con el link `{FRONTEND_URL}/confirmacion?token={confirmation_token}`.
   - `ScheduleAppointmentReminders`: despacha jobs `SendAppointmentReminder` con delay para 1 día antes, 1 hora antes y 30 min antes (según preferencias).
8. Cliente recibe email, hace clic en el link de confirmación.
9. Frontend (ruta `/confirmacion?token=...`) llama a `GET /api/appointments/confirm/{token}`.
10. Backend (`ConfirmAppointmentUseCase`) busca el turno por token, llama a `$appointment->confirm()`, persiste. Si ya estaba confirmado, no hace nada.
11. Cuando llega el momento del delay, el queue worker ejecuta `SendAppointmentReminder::handle()`:
    - Saltea si `status` es `cancelled`, `completed` o `no_show`.
    - Envía email de recordatorio con el mismo link de confirmación.

---

## 7. Disponibilidad y Asignación Automática

Archivo clave: `app/Infrastructure/Persistence/Eloquent/Adapters/EloquentAvailabilityRepository.php`

### Cálculo de slots (`listAvailableSlotsForServiceOnDate`)

1. Verifica que el servicio exista y esté activo.
2. Calcula empleados que ofrecen ese servicio (via `employee_services`).
3. Genera una grilla de slots de **30 minutos** fijos desde 06:00 a 22:00.
4. Para cada empleado y cada slot:
   - Verifica que el slot caiga dentro de su horario (`employee_schedules`).
   - Descarta el slot si está en un break (`employee_breaks`).
   - Descarta el slot si la fecha está bloqueada (`employee_blocked_dates`).
   - Descarta el slot si otro turno lo ocupa (`appointments` con mismo `employee_id` y fecha).
   - Marca el slot como `available` solo si ningún empleado está disponible (el primer empleado que puede, "gana").

### Asignación de empleado (`findAvailableEmployeeIdForServiceAt`)

- Itera empleados que ofrecen el servicio.
- Verifica: horario ok, no break, no bloqueo, sin turno conflictivo.
- Retorna el primer `employee_id` disponible.
- Si ninguno, retorna `null` → se lanza `NoEmployeeAvailableException`.

---

## 8. Autenticación y Autorización

### Login

```
POST /api/auth/login
Body: { "email": "...", "password": "..." }
Response: { user: { id, name, email, role }, token_type: "Bearer", access_token: "..." }
```
- Rate limit: 5 intentos por minuto.
- Validación: `LoginRequest` requiere email válido + password string.

### Logout

```
POST /api/auth/logout
Header: Authorization: Bearer <token>
```
- Revoca el token Sanctum actual.

### Roles y Middleware

Los roles vienen de `App\Domain\User\UserRole` (enum: `Employee`, `Admin`).
El campo `role` en la tabla `users` se castea a ese enum en el modelo `User`.

| Middleware | Alias | Regla |
|---|---|---|
| `EnsureAdmin` | `role.admin` | Solo deja pasar si `$user->role === UserRole::Admin`. Si no: 403. |
| `EnsureEmployeeSelfOrAdmin` | `employee.self-or-admin` | Admin pasa siempre. Employee solo pasa si el `{id}` de la ruta coincide con `$user->id`. Si no: 403. |

### Aplicación en rutas

| Ruta | Auth | Middleware extra |
|---|---|---|
| `GET /api/employees` | `auth:sanctum` | — |
| `GET/PUT /api/employees/{id}/availability` | `auth:sanctum` | `employee.self-or-admin` |
| `POST/DELETE /api/employees/{id}/blocked-dates` | `auth:sanctum` | `employee.self-or-admin` |
| `POST/PUT/DELETE /api/services` | `auth:sanctum` | `role.admin` |
| `GET /api/appointments` (y resto CRUD) | `auth:sanctum` | — (el scoping por empleado se hace en `ListAppointmentsRequest::toDto()`) |

### Scoping de turnos por rol

En `ListAppointmentsRequest::toDto()`:
- Si el usuario autenticado tiene rol `Employee`, se fuerza `employeeId = $user->id` en el DTO, **ignorando cualquier `employee_id` pasado por query string**.
- Si es `Admin`, se respeta el `employee_id` del query string (o `null` para ver todos).

---

## 9. Emails y Recordatorios

### Arquitectura de envío

```
AppointmentScheduled (Domain Event)
  └─ LaravelAppointmentScheduled (Infrastructure Event, disparado en EloquentAppointmentRepository::save)
       ├─ SendAppointmentConfirmationEmail (Listener)
       │    └─ Mail::to(client.email)->send(AppointmentNotificationMail "confirmation")
       └─ ScheduleAppointmentReminders (Listener)
            ├─ SendAppointmentReminder "1_day" (dispatched con delay: scheduling time minus 1 day)
            ├─ SendAppointmentReminder "1_hour" (dispatched con delay: scheduling time minus 1 hour)
            └─ SendAppointmentReminder "30_mins" (dispatched con delay: scheduling time minus 30 min, si habilitado)
```

### Archivos involucrados

| Archivo | Propósito |
|---|---|
| `AppointmentNotificationMail.php` | Mailable que construye el email (usa markdown blade). |
| `emails/appointment-notification.blade.php` | Plantilla del email: nombre, servicio, fecha/hora, botón "Confirmar asistencia". |
| `SendAppointmentConfirmationEmail.php` | Listener: envía email de confirmación inmediato. |
| `SendAppointmentReminder.php` | Job encolado: envía email de recordatorio. Saltea `cancelled`, `completed`, `no_show`. |
| `ScheduleAppointmentReminders.php` | Listener: despacha los jobs de recordatorio con delay si la fecha no pasó. |

### Link de confirmación

Formato: `{FRONTEND_URL}/confirmacion?token={confirmation_token}`

Donde `FRONTEND_URL` se toma de `config('app.frontend_url')`, definido en `.env` como:

```env
FRONTEND_URL=http://localhost:3000
```

Confirmación y recordatorios usan **exactamente el mismo link**.

### Cómo funciona en local

Hoy el `.env` local tiene `MAIL_MAILER=log`. Los emails se escriben en:

```
turnero/storage/logs/laravel.log
```

### Cómo pasar a envío real

Configurar en `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="Turnero"
```

Proveedores compatibles: Amazon SES, Mailgun, Postmark, SendGrid (vía SMTP o drivers nativos).

---

## 10. Endpoints API (Catálogo)

### Públicos (sin auth)

| Método | Ruta | Descripción | Rate Limit |
|---|---|---|---|
| `POST` | `/api/auth/login` | Login empleado/admin | 5/min |
| `POST` | `/api/appointments` | Reservar turno | 10/min |
| `GET` | `/api/appointments/confirm/{token}` | Confirmar turno por token | 10/min |
| `GET` | `/api/clients/{dni}/appointments` | Turnos de un cliente por DNI | 10/min |
| `GET` | `/api/services/active` | Servicios activos (para booking) | 30/min |
| `GET` | `/api/availability` | Slots disponibles (`?service_id=&date=`) | 30/min |

### Protegidos (`auth:sanctum`)

| Método | Ruta | Acceso |
|---|---|---|
| `GET` | `/api/employees` | Admin y employee |
| `GET` | `/api/employees/{id}/availability` | Admin (cualquier ID) o employee (solo propio) |
| `PUT` | `/api/employees/{id}/availability` | Admin (cualquier ID) o employee (solo propio) |
| `POST` | `/api/employees/{id}/blocked-dates` | Admin (cualquier ID) o employee (solo propio) |
| `DELETE` | `/api/employees/{id}/blocked-dates/{date}` | Admin (cualquier ID) o employee (solo propio) |
| `GET` | `/api/appointments` | Admin (todos, filtrable por `employee_id`) o employee (solo propios) |
| `GET` | `/api/appointments/{id}` | Admin y employee |
| `PATCH` | `/api/appointments/{id}` | Admin y employee (cambiar estado) |
| `GET` | `/api/services` | Admin y employee |
| `GET` | `/api/services/{id}` | Admin y employee |
| `POST` | `/api/auth/logout` | Admin y employee |

### Admin-only (`auth:sanctum` + `role.admin`)

| Método | Ruta |
|---|---|
| `POST` | `/api/services` |
| `PUT` | `/api/services/{id}` |
| `DELETE` | `/api/services/{id}` |

---

## 11. Seeds de Datos de Prueba

Archivo: `database/seeders/DatabaseSeeder.php`

Ejecutar:

```bash
php artisan migrate:fresh --seed
```

### Lo que crea

| Elemento | Cantidad | Detalle |
|---|---|---|
| Admin | 1 | `admin@turnero.com` / `secret` |
| Empleados | 3 | Factory, contraseña `password`, cada uno con todos los servicios |
| Servicios | 5 | Corte de cabello, Tintura, Barba, Manicura, Pedicura. Todos activos. |
| Clientes | 20 | Factory |
| Turnos para hoy | ~10 | Con `employee_id` asignado, estados variados. Horas entre 09:00 y 18:00, minutos 00 o 30. |
| Turnos históricos/futuros | ~40 | Distribuidos entre -3 días y +2 semanas. |
| Employee services | 3×5 | Cada empleado vinculado a los 5 servicios. |
| Employee schedules | Por empleado: Lun-Vie 09-18, Sáb 09-13 | — |
| Employee breaks | Por empleado: Lun-Vie 12-13 | — |

### Estados de turnos seed (distribución variada)

`cancelled`, `confirmed`, `no_show`, `scheduled`, `completed`.

---

## 12. Testing

### Ejecutar tests

```bash
php artisan test
```

### Configuración de tests

Archivo: `.env.testing`

- Usa **SQLite in-memory** (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).
- Esto aísla los tests: no tocan la base MySQL de desarrollo.
- `APP_KEY` incluida para que funcione el cifrado.
- `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`.

### Tests existentes (63 tests, 229 aserciones)

| Suite | Archivo | Qué prueba |
|---|---|---|
| Unit | `AppointmentTest` | Transiciones de estado del dominio. |
| Unit | `SendAppointmentReminderTest` | Que el job saltea estados finales y usa el link correcto. |
| Unit | `ScheduleAppointmentRemindersTest` | Que el listener despacha jobs con los delays correctos según preferencias. |
| Unit | UseCases (`ScheduleAppointmentUseCaseTest`, etc.) | Lógica de negocio con mocks de repositorios. |
| Feature | `AppointmentCrudTest` | CRUD de turnos con filtros, incluye `client_dni` y `client_name` en response. |
| Feature | `AppointmentAuthorizationTest` | Admin ve todos; employee ve solo propios; admin puede filtrar por `employee_id`. |
| Feature | `ScheduleAppointmentTest` | Reserva pública: crea cliente, asigna empleado, valida payload. |
| Feature | `ConfirmAppointmentTest` | Confirmación por token. |
| Feature | `ClientAppointmentsTest` | Turnos por DNI de cliente. |
| Feature | `AvailabilityTest` | Slots con breaks/bookings/bloqueos. |
| Feature | `ServiceCrudTest` / `ServiceAuthorizationTest` | CRUD de servicios, restricción admin. |
| Feature | `EmployeeAvailabilityCrudTest` | CRUD de disponibilidad de empleado. |
| Feature | `EmployeeBlockedDatesAuthorizationTest` | Bloqueo/desbloqueo de fechas con restricción de rol. |
| Feature | `LoginLogoutTest` | Login/logout con Sanctum. |

---

## 13. Variables de Entorno Críticas

| Variable | Propósito | Local (dev) |
|---|---|---|
| `APP_URL` | URL base del backend | `http://127.0.0.1:8000` |
| `FRONTEND_URL` | URL del frontend para links de confirmación | `http://localhost:3000` |
| `DB_CONNECTION` | Driver de base de datos | `mysql` |
| `DB_HOST` | Host de MySQL | `127.0.0.1` |
| `DB_PORT` | Puerto de MySQL | `3307` |
| `DB_DATABASE` | Nombre de la base | `turnero` |
| `QUEUE_CONNECTION` | Driver de colas | `database` |
| `MAIL_MAILER` | Driver de email | `log` (local) |
| `APP_LOCALE` | Idioma por defecto | `es` |
| `APP_FALLBACK_LOCALE` | Idioma de respaldo | `es` |

---

## 14. Guía para Otra IA (o desarrollador)

Antes de modificar el backend, tené en cuenta:

- **No romper la arquitectura por capas**: si la lógica es de negocio, va en Domain o Application, nunca directo en un Controller.
- **No saltarse UseCases**: si creás un endpoint nuevo que hace algo de negocio, creá su UseCase correspondiente.
- **No filtrar turnos manualmente en el frontend**: el backend ya forza `employee_id` según el rol en `ListAppointmentsRequest`. El frontend para empleados no necesita enviar filtro de empleado.
- **No exponer datos internos en endpoints públicos**: los endpoints sin auth (`/api/appointments` público, `/api/services/active`) solo deben devolver lo necesario para el cliente.
- **No duplicar lógica de disponibilidad**: todo el cálculo de slots y asignación de empleado debe vivir en `EloquentAvailabilityRepository`. Si necesitás exponerlo en otro formato, creá un endpoint que lo consuma.
- **Si tocás emails**: revisá `AppointmentNotificationMail`, `SendAppointmentConfirmationEmail` y `SendAppointmentReminder`. El link de confirmación se arma concatenando `FRONTEND_URL` + `/confirmacion?token=` + `confirmation_token`.
- **Si tocás reservas**: el flujo pasa por `ScheduleAppointmentUseCase`, que dispara eventos. Si agregás lógica post-reserva, agregala como listener del evento, no en el UseCase directamente.
- **Si tocás autorización**: las reglas de quién puede qué están en middlewares (`EnsureAdmin`, `EnsureEmployeeSelfOrAdmin`) y en `ListAppointmentsRequest::toDto()`. Mantené esa centralización.
- **Si agregás migraciones**: usá los comandos estándar de Laravel (`php artisan make:migration`). Las tablas nuevas deben seguir el naming snake_case y tener timestamps.
- **Siempre corré `php artisan test`** antes de dar por terminado un cambio. Los tests usan SQLite y no afectan tu base local.

---

## 15. Mapa de Archivos Clave

| Tema | Archivo |
|---|---|
| Rutas API | `routes/api.php` |
| Reserva pública (controller) | `app/Infrastructure/Http/Controllers/Appointment/ScheduleAppointmentController.php` |
| Reserva pública (use case) | `app/Application/Appointment/UseCases/ScheduleAppointmentUseCase.php` |
| Request de reserva | `app/Infrastructure/Http/Requests/Appointment/ScheduleAppointmentRequest.php` |
| DTO de reserva | `app/Application/Appointment/DTO/ScheduleAppointmentDTO.php` |
| Disponibilidad (controller) | `app/Infrastructure/Http/Controllers/AvailabilityController.php` |
| Disponibilidad (use case) | `app/Application/Availability/UseCases/ListAvailableSlotsUseCase.php` |
| Disponibilidad (repo) | `app/Infrastructure/Persistence/Eloquent/Adapters/EloquentAvailabilityRepository.php` |
| Contrato de disponibilidad | `app/Application/Availability/Ports/AvailabilityRepositoryInterface.php` |
| Turnos CRUD (controller) | `app/Infrastructure/Http/Controllers/Appointment/AppointmentController.php` |
| Turnos CRUD (repo) | `app/Infrastructure/Persistence/Eloquent/Adapters/EloquentAppointmentRepository.php` |
| Turnos CRUD (contrato) | `app/Application/Appointment/Ports/AppointmentRepositoryInterface.php` |
| List appointments (request) | `app/Infrastructure/Http/Requests/Appointment/ListAppointmentsRequest.php` |
| List appointments (DTO) | `app/Application/Appointment/DTO/ListAppointmentsDTO.php` |
| List appointments (use case) | `app/Application/Appointment/UseCases/ListAppointmentsUseCase.php` |
| Cambio de estado (use case) | `app/Application/Appointment/UseCases/ChangeAppointmentStatusUseCase.php` |
| Confirmación por token (controller) | `app/Infrastructure/Http/Controllers/Appointment/ConfirmAppointmentController.php` |
| Confirmación por token (use case) | `app/Application/Appointment/UseCases/ConfirmAppointmentUseCase.php` |
| Turnos por DNI (controller) | `app/Infrastructure/Http/Controllers/Appointment/ClientAppointmentsController.php` |
| Dominio Appointment | `app/Domain/Appointment/Appointment.php` |
| Dominio Client | `app/Domain/Client/Client.php` |
| Enum AppointmentStatus | `app/Domain/Appointment/AppointmentStatus.php` |
| Enum UserRole | `app/Domain/User/UserRole.php` |
| Value object ReminderPreferences | `app/Domain/Appointment/ReminderPreferences.php` |
| Domain event AppointmentScheduled | `app/Domain/Shared/Events/AppointmentScheduled.php` |
| Infra event | `app/Infrastructure/Events/LaravelAppointmentScheduled.php` |
| Email confirmación (listener) | `app/Infrastructure/Listeners/SendAppointmentConfirmationEmail.php` |
| Recordatorios (listener) | `app/Infrastructure/Listeners/ScheduleAppointmentReminders.php` |
| Recordatorio (job) | `app/Infrastructure/Jobs/SendAppointmentReminder.php` |
| Mailable | `app/Infrastructure/Mail/AppointmentNotificationMail.php` |
| Template email | `resources/views/emails/appointment-notification.blade.php` |
| Login (controller) | `app/Infrastructure/Http/Controllers/Auth/LoginController.php` |
| Login (use case) | `app/Application/Auth/UseCases/LoginUseCase.php` |
| Auth repository (contrato) | `app/Application/Auth/Ports/AuthRepositoryInterface.php` |
| Auth repository (impl) | `app/Infrastructure/Persistence/Eloquent/Adapters/EloquentAuthRepository.php` |
| Clientes repo (contrato) | `app/Application/Appointment/Ports/ClientRepositoryInterface.php` |
| Clientes repo (impl) | `app/Infrastructure/Persistence/Eloquent/Adapters/EloquentClientRepository.php` |
| Servicios repo (contrato) | `app/Application/Service/Ports/ServiceRepositoryInterface.php` |
| Servicios repo (impl) | `app/Infrastructure/Persistence/Eloquent/Adapters/EloquentServiceRepository.php` |
| Servicios controller | `app/Infrastructure/Http/Controllers/ServiceController.php` |
| Servicios públicos controller | `app/Infrastructure/Http/Controllers/PublicServiceController.php` |
| Empleados CRUD (controller) | `app/Infrastructure/Http/Controllers/Employee/EmployeeController.php` |
| Disponibilidad empleado (controller) | `app/Infrastructure/Http/Controllers/Employee/EmployeeAvailabilityController.php` |
| Fechas bloqueadas (controller) | `app/Infrastructure/Http/Controllers/Employee/EmployeeBlockedDatesController.php` |
| Middleware admin | `app/Infrastructure/Http/Middleware/EnsureAdmin.php` |
| Middleware self-or-admin | `app/Infrastructure/Http/Middleware/EnsureEmployeeSelfOrAdmin.php` |
| Service Provider (bindings) | `app/Providers/AppServiceProvider.php` |
| Event Service Provider | `app/Providers/EventServiceProvider.php` |
| Seeder | `database/seeders/DatabaseSeeder.php` |
| Factory appointments | `database/factories/AppointmentModelFactory.php` |
| .env.testing | `.env.testing` |
| Config mail | `config/mail.php` |
| Bootstrap (middleware alias) | `bootstrap/app.php` |
| Modelo User | `app/Models/User.php` |
| Modelo AppointmentModel | `app/Infrastructure/Persistence/Eloquent/Models/AppointmentModel.php` |
| Modelo ClientModel | `app/Infrastructure/Persistence/Eloquent/Models/ClientModel.php` |
| Modelo ServiceModel | `app/Infrastructure/Persistence/Eloquent/Models/ServiceModel.php` |
| Modelo EmployeeScheduleModel | `app/Infrastructure/Persistence/Eloquent/Models/EmployeeScheduleModel.php` |
| Modelo EmployeeBreakModel | `app/Infrastructure/Persistence/Eloquent/Models/EmployeeBreakModel.php` |
| Modelo EmployeeBlockedDateModel | `app/Infrastructure/Persistence/Eloquent/Models/EmployeeBlockedDateModel.php` |
