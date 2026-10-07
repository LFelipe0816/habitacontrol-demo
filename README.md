# HabitaControl (demo)

Aplicación web para **administrar residenciales y condominios**: unidades y personas, cobros y pagos, incidencias con seguimiento, avisos, control de visitas y estado de los servicios del residencial (planta, agua, filtros, cisterna).

> **Esto es una versión de demostración.** Todos los datos incluidos son ficticios (residenciales, personas, correos, teléfonos y RNC). Las cuentas de ejemplo usan la contraseña `password` y deben usarse **solo en local**.

## Qué incluye

| Módulo | Qué hace |
|---|---|
| **Residenciales** | Estructura por manzanas, edificios y calles; planes y administradora |
| **Unidades** | Propietarios, inquilinos y residentes, contratos de alquiler, vehículos y contacto de emergencia |
| **Cobros** | Cargos por unidad, pagos (efectivo, transferencia), libro contable, generación masiva y exportación |
| **Incidencias** | Flujo de estados, asignación, línea de tiempo, evidencias, comentarios y confirmación del vecino |
| **Avisos y votaciones** | Comunicados por audiencia, lectura confirmada y encuestas con un voto por usuario |
| **Seguridad** | Registro de visitas esperadas y salida |
| **Servicios** | Activos del residencial, rutinas y mantenimientos programados |
| **Usuarios y roles** | `superadmin`, `admin`, `junta`, `propietario`, `residente`, `seguridad`, `mantenimiento` con acceso por rol |

## Stack

- PHP 8.3, **Laravel**
- **Inertia.js + React + TypeScript**, Vite
- SQLite por defecto (configurable)
- Pruebas con PHPUnit: cobros y libro contable, flujo de incidencias, acceso por rol, gestión de usuarios, carga de datos de demostración

## Puesta en marcha

Requisitos: PHP 8.3+, Composer y Node.js 20+.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
npm install
npm run dev          # en una terminal
php artisan serve    # en otra
```

Abre <http://127.0.0.1:8000>.

### Cuentas de demostración

Todas con la contraseña `password`:

| Rol | Correo |
|---|---|
| Superadmin | `super@habitacontrol.local` |
| Administración | `admin@habitacontrol.local` |
| Junta directiva | `junta@habitacontrol.local` |
| Propietario | `propietario@habitacontrol.local` |
| Residente | `residente@habitacontrol.local` |
| Seguridad | `seguridad@habitacontrol.local` |
| Mantenimiento | `mantenimiento@habitacontrol.local` |

### Pruebas

```bash
php artisan test
```

## Estructura

```
app/Services/           lógica de dominio (Ledger, IncidentWorkflow, ServiceAssetWorkflow)
app/Http/Controllers/   controladores por módulo
database/seeders/       DemoSeeder + data/demo.json (datos ficticios)
resources/js/Pages/     pantallas de Inertia/React
docs/base-de-datos.md   referencia de tablas y relaciones
tests/Feature/          pruebas por flujo
```

La referencia de la base de datos, con el diagrama de relaciones, está en [docs/base-de-datos.md](docs/base-de-datos.md).

## Licencia

MIT. Ver [LICENSE](./LICENSE).
