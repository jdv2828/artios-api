# Turnero

Backend API para sistema de gestión de turnos — Laravel 11, PHP 8.2+, arquitectura en 3 capas (Domain, Application, Infrastructure).

## Funcionalidades

- Autenticación de empleados y admin con Sanctum (Bearer token, roles Employee/Admin).
- Agendamiento público de turnos sin login (el sistema asigna automáticamente un profesional disponible).
- Gestión de turnos en back-office (cambio de estado, reprogramación con validación de disponibilidad).
- Confirmación de asistencia por email con token único.
- Recordatorios asíncronos (1 día antes, 1 hora antes, 30 min antes).
- Email de notificación al cliente al confirmar, cancelar o reprogramar un turno.
- Filtros de turnos por fecha, estado, DNI y nombre de cliente (prefijo).
- Gestión de servicios, profesionales, horarios, breaks y fechas bloqueadas.
- Endpoint de reprogramación con validación transaccional de disponibilidad.

## Cómo levantar

### Requisitos

- PHP 8.2+ con extensiones: pdo_mysql, mbstring, openssl, tokenizer, bdd, fileinfo, xml
- Composer
- MySQL (local, Docker, o cualquier servicio)

### Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### Base de datos

Configurá `DB_*` en `.env` apuntando a tu MySQL. Luego:

```bash
php artisan migrate:fresh --seed
```

Esto crea las tablas y carga datos de prueba:
- Admin: `admin@turnero.com` / `secret`
- Empleados: usuarios de fábrica, password `password`
- 5 servicios, 20 clientes, ~50 turnos de ejemplo

### Levantar

```bash
# API (puerto 8000)
php artisan serve --host=127.0.0.1 --port=8000

# Queue worker (necesario para recordatorios)
php artisan queue:work --sleep=1 --tries=1
```

### Tests

```bash
php artisan test
```

82 tests, 313 assertions. Usa SQLite in-memory (no toca la base de desarrollo).

## Documentación

| Documento | Contenido |
|---|---|
| [`docs/BACKEND_TECHNICAL_BUSINESS_CONTEXT.md`](docs/BACKEND_TECHNICAL_BUSINESS_CONTEXT.md) | Contexto técnico y de negocio completo |
| [`docs/BACKEND_GUIDE.md`](docs/BACKEND_GUIDE.md) | Guía de endpoints y payloads |
| [`docs/OPERATIONS.md`](docs/OPERATIONS.md) | Despliegue en stage/producción (VPS y Docker) |
