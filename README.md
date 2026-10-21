# 📘 README — Sistema de Gestión de Flotilla Vehicular

Sistema web completo para la **administración de flotas vehiculares y el control del mantenimiento preventivo y correctivo**. Permite registrar vehículos, programar y dar seguimiento a órdenes de mantenimiento, y generar reportes de costos por vehículo, tipo y período.

Backend en **Laravel 12 + Sanctum** · Frontend en **Angular 20 (Standalone Components + Signals)** · Base de datos **MySQL**.

---

## 📋 Tabla de contenido

1. [Descripción del proyecto](#-descripción-del-proyecto)
2. [Problema que resuelve](#-problema-que-resuelve)
3. [Alcance funcional](#-alcance-funcional)
4. [Reglas de negocio](#-reglas-de-negocio)
5. [Roles y permisos](#-roles-y-permisos)
6. [Flujos principales](#-flujos-principales)
7. [Arquitectura técnica](#-arquitectura-técnica)
8. [Modelo de datos](#-modelo-de-datos)
9. [Seguridad](#-seguridad)
10. [Requisitos previos](#-requisitos-previos)
11. [Estructura del proyecto](#-estructura-del-proyecto)
12. [Instalación del backend](#-instalación-del-backend)
13. [Instalación del frontend](#-instalación-del-frontend)
14. [Credenciales por defecto](#-credenciales-por-defecto)
15. [Ejecución en desarrollo](#-ejecución-en-desarrollo)
16. [Endpoints principales](#-endpoints-principales)
17. [Solución de problemas](#-solución-de-problemas)

---

## 🚗 Descripción del proyecto

**Flotilla** es una aplicación empresarial orientada a **empresas con flota de vehículos** (transporte, logística, servicios, gobiernos, constructoras) que necesitan controlar el estado mecánico de sus unidades y el historial de mantenimiento, así como los costos asociados a cada vehículo a lo largo del tiempo.

El sistema está diseñado bajo tres pilares:

1. **Control vehicular** — saber en todo momento qué vehículos existen, en qué estado operativo están (activo, en taller, baja) y cuándo requieren servicio.
2. **Gestión de mantenimiento** — registrar cada intervención (preventiva o correctiva) con folio, fechas, odómetro, costo y notas de seguimiento; con un flujo de estados controlado.
3. **Análisis de costos** — reportes agregados por vehículo, por tipo de mantenimiento y por periodo, que permiten tomar decisiones sobre renovación de unidades, presupuesto y priorización de servicios.

### Características diferenciadoras

- **Folios autogenerados** con código + año + numeración independiente por tipo (ej. `PRE-2026-0001` para preventivos, `COR-2026-0001` para correctivos), libres de colisión concurrente gracias a bloqueo transaccional.
- **Detección automática** de vehículos que requieren servicio preventivo (por kilometraje o por tiempo), sin necesidad de que el usuario lo consulte manualmente.
- **Flujo de estados blindado**: una orden no puede pasar a "cerrada" sin haber pasado por "en proceso", ni volver atrás; se registra automáticamente fecha de inicio y fecha de cierre.
- **Auditoría de cambios**: las notas de cambio de estatus se anexan al historial de la orden con timestamp y el nuevo estado, formando una bitácora legible.
- **Autorización por rol** en el frontend (UI condicional) y en el backend (políticas de Laravel).
- **Interfaz unificada** con modales en la misma página (sin recargas), feedback visual inmediato, filtros reactivos y paginación server-side.

### Casos de uso típicos

| Actor | Uso |
|---|---|
| **Jefe de flota / Administrador** | Registra vehículos nuevos, da de baja unidades obsoletas, revisa reportes de costos mensuales para presupuesto, autoriza cancelaciones de órdenes. |
| **Capturista / Encargado de taller** | Programa mantenimientos, registra el avance de las órdenes, anota observaciones técnicas, cierra la orden al terminar con costo real. |


---

## 🎯 Problema que resuelve

En empresas con flota, el mantenimiento suele gestionarse con:

- Hojas de cálculo aisladas por unidad o por taller.
- Recordatorios manuales que se olvidan (vehículos que pasan de 20 000 km sin servicio).
- Reportes de costos dispersos que no permiten comparar unidades.
- Ausencia de historial auditable: "¿cuándo fue el último cambio de aceite de la unidad ECO-007?".

**Flotilla centraliza todo esto en una sola aplicación** con:

- Registro único de vehículos con odómetro, estado y fecha de alta.
- Órdenes de mantenimiento con folio, transiciones controladas y bitácora de notas.
- Alerta automática de unidades que superan **10 000 km** o **180 días** desde su último preventivo cerrado.
- Reporte de costos con desglose por vehículo → tipo de mantenimiento, filtrable por periodo, estado y fecha de referencia (creación / cierre / programación).

El resultado: **menos tiempo en Excel, más tiempo en decisiones**.

---

## 🧩 Alcance funcional

### Módulo 1 — Autenticación y sesión

- Login con email/contraseña con **hash bcrypt** y comparación en tiempo constante (mitigación de timing attacks).
- Emisión de **token Bearer** (Laravel Sanctum) con opción "recordarme" (persiste en `localStorage` vs `sessionStorage`).
- Endpoint `/me` para rehidratar el usuario al recargar la página.
- Cierre de sesión que **revoca** el token activo en el servidor.
- Interceptor HTTP que adjunta el token a cada request y **redirige al login** al recibir `401`.
- Guard de rutas (`authGuard`) que bloquea rutas privadas si no hay sesión, y `guestGuard` que evita volver al login si ya está autenticado.

### Módulo 2 — Vehículos

- Listado paginado con búsqueda por **placa** o **número económico** y filtro por **estatus**.
- Alta con validación de unicidad (`placa` + `numero_economico`), año entre 1900 y año actual + 1, odómetro ≥ 0.
- Edición con validación de unicidad **excluyendo el propio registro**.
- Baja lógica (`estatus=baja`), que excluye el vehículo de selectores y del reporte "requiere servicio".
- Vista dedicada **"Vehículos por servicio"** que lista únicamente los que cumplen la regla RN-07.

### Módulo 3 — Órdenes de mantenimiento

- Listado paginado con:
  - Búsqueda por folio o descripción.
  - Filtros por estado, tipo, vehículo, rango de fechas de programación.
  - Ordenamiento por folio, prioridad, estado, fecha programada, fecha de creación.
- Alta con **folio autogenerado** (`PRE-YYYY-NNNN` / `COR-YYYY-NNNN`).
- Edición de campos operativos (descripción, odómetro, prioridad, costo estimado, notas, asignación).
- **Cambio de estatus** con transiciones validadas server-side, timestamps automáticos y anexo de notas al historial.
- **Modal de detalle** con desglose completo (vehículo, mantenimiento, costos, notas, autor).
- **Modal de cambio de estatus** que sólo muestra las transiciones permitidas para el rol del usuario.

### Módulo 4 — Reportes de costos

- Filtros por rango de fechas, campo de fecha de referencia (`created_at`, `fecha_cierre`, `fecha_inicio`, `fecha_programada`), estado, vehículo e inclusión de canceladas.
- **KPIs globales**: total de órdenes y costo total agregado en el período.
- **Desglose por tipo** (preventivo / correctivo) con barra proporcional.
- **Desglose por vehículo** expandible: cada fila abre un sublistado por tipo con órdenes y costo.
- Exportable vía curl/Postman (endpoint REST puro JSON).

### Módulo 5 — Dashboard

- Panel de bienvenida con nombre y rol del usuario.
- Acceso rápido al cierre de sesión.

---

## 📐 Reglas de negocio

El sistema implementa las siguientes reglas (referenciadas en el código como RN-XX):

| Regla | Descripción |
|---|---|
| **RN-01** | `placa` y `numero_economico` son únicos a nivel sistema. |
| **RN-02** | Un vehículo no puede tener más de una orden activa (`abierta` o `en_proceso`) a la vez. |
| **RN-03** | Un vehículo dado de baja (`estatus=baja`) no puede recibir nuevas órdenes. |
| **RN-04** | El odómetro de ingreso de una orden debe ser **mayor o igual** al odómetro actual registrado del vehículo. |
| **RN-05** | Al pasar a estado `cerrada`, la orden registra automáticamente `fecha_cierre` y permite capturar `costo` real. |
| **RN-06** | Los vehículos con `estatus=baja` se excluyen de todos los selectores de creación de órdenes. |
| **RN-07** | Un vehículo **requiere servicio preventivo** si han pasado **> 10 000 km** o **> 180 días** desde su último preventivo cerrado (o desde `fecha_alta` si no hay historial). |
| **RN-08** | "Dar de baja" un vehículo es una operación de sólo administrador y equivale a `estatus=baja` (soft delete lógico, no físico). |
| **RN-09** | Sólo el administrador puede cancelar una orden. Los capturistas pueden avanzar `abierta → en_proceso → cerrada`, pero no cancelar. |
| **RN-10** | Una orden `cerrada` o `cancelada` no puede volver a editarse ni cambiarse de estado (estado final). |
| **RN-11** | El folio de una orden **no se puede modificar** una vez creado y no se reutiliza nunca (incluso si la orden se elimina lógicamente). |

### Máquina de estados de una orden

```
                   ┌──────────────────┐
                   │     abierta      │
                   └────────┬─────────┘
                            │
              ┌─────────────┼─────────────┐
              ▼             │             ▼
       ┌────────────┐       │      ┌──────────────┐
       │ en_proceso │       │      │  cancelada   │
       └─────┬──────┘       │      └──────────────┘
             │              │             ▲
             ▼              │             │
        ┌──────────┐        │             │
        │ cerrada  │        │             │
        └──────────┘        │             │
             ▲              │             │
             │              │             │
             └──────────────┴─────────────┘
              (cancelable desde abierta o en_proceso)
```

Transiciones permitidas:

| Estado actual | Siguientes permitidos |
|---|---|
| `abierta` | `en_proceso`, `cancelada` |
| `en_proceso` | `cerrada`, `cancelada` |
| `cerrada` | — (final) |
| `cancelada` | — (final) |

---

## 👥 Roles y permisos

El sistema define **dos roles** (`roles` table) y aplica autorización en **dos capas**: middleware Sanctum en Laravel y directivas de UI en Angular.

| Acción | Administrador | Capturista |
|---|:---:|:---:|
| Ver vehículos | ✅ | ✅ |
| Crear vehículo | ✅ | ❌ |
| Editar vehículo | ✅ | ❌ |
| Dar de baja vehículo | ✅ | ❌ |
| Ver órdenes | ✅ | ✅ |
| Crear orden | ✅ | ✅ |
| Editar orden (no cerrada) | ✅ | ✅ |
| Cambiar estatus (`abierta → en_proceso → cerrada`) | ✅ | ✅ |
| **Cancelar** orden | ✅ | ❌ |
| Ver reporte de costos | ✅ | ✅ |

La diferencia se refleja visualmente:

- Los botones **"Registrar vehículo"**, **"Editar"** y **"Dar de baja"** sólo aparecen para administradores.
- En el modal de cambio de estatus, la opción **"Cancelada"** sólo aparece si el usuario es administrador.

---

## 🔄 Flujos principales

### Flujo A — Alta de vehículo (Administrador)

```
Dashboard → Vehículos → "Registrar vehículo"
  → Completa placa, N° económico, marca, modelo, año, odómetro, estatus
  → POST /api/vehicles
  → Vehículo aparece en la tabla con `requiere_servicio: false/true`
```

### Flujo B — Programar mantenimiento (Administrador / Capturista)

```
Órdenes → "Nueva orden"
  → Selecciona vehículo (excluye dados de baja)
  → Elige tipo (preventivo / correctivo)
  → Ingresa descripción, odómetro de ingreso (validado ≥ odómetro actual), fecha programada
  → POST /api/orders → folio autogenerado (PRE-2026-0001)
  → Orden aparece con estado "Abierta"
```

### Flujo C — Avanzar una orden

```
Órdenes → botón ✓ en una orden "Abierta"
  → Modal muestra transiciones disponibles ("En proceso", "Cancelada")
  → Selecciona "En proceso" → PATCH /api/orders/{id}/status
  → Se registra fecha_inicio automáticamente
  → Estado pasa a "En proceso"

Más tarde: botón ✓ → "Cerrada"
  → Se solicita costo final y fecha de cierre
  → PATCH /api/orders/{id}/status
  → Se registra fecha_cierre, costo_real, y la nota se anexa al historial
```

### Flujo D — Reporte de costos

```
Reportes → ajusta filtros (fechas, estado, incluir canceladas)
  → GET /api/v1/reports/costs?from=...&to=...
  → KPIs + gráfico por tipo + tabla por vehículo (expandible)
```

### Flujo E — Detección de servicio requerido

```
Vehículos → "Por servicio"
  → GET /api/vehicles/due-for-service
  → Backend filtra con SQL:
      odometro_actual - ultimo_preventivo.odometro > 10000
      OR DATEDIFF(NOW(), ultimo_preventivo.fecha_cierre) > 180
      OR (sin historial) fecha_alta hace más de 180 días
```

---

## 🏗️ Arquitectura técnica

### Backend — Laravel 12

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/       AuthController, VehiculoController,
│   │   │                          OrdenController, ReportController
│   │   ├── Requests/Orden/        StoreOrdenRequest, UpdateOrdenRequest,
│   │   │                          UpdateOrdenStatusRequest
│   │   ├── Requests/Vehiculo/     StoreVehiculoRequest, UpdateVehiculoRequest
│   │   └── Resources/             OrdenResource, VehiculoResource
│   ├── Models/                    Usuario, Rol, Vehiculo, Orden,
│   │                              MantenimientoHistorial, VehiculoEstatusHistorial
│   ├── Policies/                  OrdenPolicy, VehiculoPolicy
│   └── Providers/                 AppServiceProvider (registro de policies)
├── database/
│   ├── migrations/                Esquema completo
│   └── seeders/                   RolSeeder, UsuarioSeeder,
│                                  VehiculoSeeder, OrdenSeeder
├── routes/api.php                 Endpoints REST protegidos con Sanctum
└── config/auth.php                Guard 'sanctum' → provider 'usuarios'
```

**Características técnicas:**

- **API REST pura** (JSON) sin vistas Blade.
- Autenticación por **tokens Bearer** (Sanctum) — sin cookies, ideal para SPA.
- **Form Requests** para validación desacoplada y **Resources** para transformación de respuestas.
- **Policies** registradas en `AppServiceProvider` para autorización granular.
- **Soft deletes** en `Vehiculo` y `Orden` (borrado lógico).
- **Transacciones con bloqueo** (`lockForUpdate`) para folios concurrentes.
- **`casts` explícitos** para fechas, decimales e integers.
- **Validación en tiempo constante** para credenciales (mitigación de user enumeration).

### Frontend — Angular 20

```
frontend/
└── src/app/
    ├── core/
    │   ├── guards/                authGuard, guestGuard
    │   ├── interceptors/          authInterceptor (Bearer + 401 handling)
    │   ├── models/                auth, order, vehicle, report
    │   └── services/              auth, orders, vehicles, reports
    ├── features/
    │   ├── auth/login/            LoginComponent
    │   ├── dashboard/             DashboardComponent
    │   ├── vehicles/
    │   │   ├── vehicles-list/
    │   │   └── vehicles-form-modal/
    │   ├── orders/
    │   │   ├── orders-list/
    │   │   ├── orders-form-modal/
    │   │   ├── orders-detail-modal/
    │   │   └── orders-status-modal/
    │   └── reports/costs-report/  CostsReportComponent
    ├── app.config.ts              Providers globales
    └── app.routes.ts              Rutas con lazy loading + guards
```

**Características técnicas:**

- **Standalone components** (sin NgModules).
- **Signals + computed** para estado reactivo (sin NgRx).
- **Nueva sintaxis de control flow** `@if` / `@for` / `@else if`.
- **Reactive Forms** con validación tipada.
- **HttpInterceptorFn funcional** (nueva API de Angular 17+).
- **Lazy loading** de rutas por feature.
- **Modales HTML puros** sin librerías externas (menor bundle size).
- **Estilos encapsulados** por componente, diseño responsive, soporte `prefers-reduced-motion`.

---

## 🗃️ Modelo de datos

```
┌──────────┐       ┌──────────┐       ┌────────────┐
│  roles   │◄──────│ usuarios │──────►│  ordenes   │
└──────────┘  1:N  └──────────┘  1:N  └────────────┘
                         │                  │
                         │                  │ N:1
                         │                  ▼
                         │           ┌────────────┐
                         │           │ vehiculos  │
                         │           └────────────┘
                         │                  │
                         │                  │ 1:N
                         │                  ▼
                         │      ┌─────────────────────┐
                         │      │ mantenimientos_     │
                         │      │ historial           │
                         │      └─────────────────────┘
                         │
                         └──────►┌──────────────────────┐
                                 │ vehiculo_estatus_    │
                                 │ historial            │
                                 └──────────────────────┘
```

| Tabla | Columnas clave |
|---|---|
| `roles` | `nombre` (unique) |
| `usuarios` | `nombre`, `email` (unique), `password`, `rol_id` (FK), `activo` |
| `vehiculos` | `placa` (unique), `numero_economico` (unique), `marca`, `modelo`, `anio`, `odometro_actual`, `estatus`, `fecha_alta` |
| `ordenes` | `folio` (unique), `vehiculo_id`, `creado_por_id`, `asignado_a_id`, `tipo`, `status`, `prioridad`, `descripcion`, `odometro_ingreso`, `fecha_programada`, `fecha_inicio`, `fecha_cierre`, `costo`, `costo_estimado`, `costo_real` |
| `mantenimientos_historial` | `vehiculo_id`, `orden_id` (unique), `tipo`, `odometro`, `fecha`, `costo` |
| `vehiculo_estatus_historial` | `vehiculo_id`, `estatus`, `motivo`, `usuario_id`, `fecha_inicio`, `fecha_fin` |
| `personal_access_tokens` | Tabla estándar de Sanctum para tokens Bearer |

**Índices relevantes:**
- `ordenes(vehiculo_id, status)` → RN-02
- `ordenes(status, tipo)` → agrupaciones del reporte
- `ordenes(fecha_programada)` → filtros por rango
- `vehiculos(estatus, odometro_actual)` → scope due-for-service

---

## 🔒 Seguridad

| Capa | Medida |
|---|---|
| **Contraseñas** | Hash bcrypt con coste 12 (ajustable vía `config/hashing.php`). |
| **Login** | Comparación con hash "dummy" cuando el usuario no existe, para igualar el tiempo de respuesta y evitar enumeración por timing. |
| **Tokens** | Sanctum con revocación explícita al cerrar sesión (`PersonalAccessToken::delete()`). |
| **Autorización** | Policies de Laravel para `Orden` y `Vehiculo`, complementadas con reglas de UI. |
| **Validación** | Form Requests con reglas `Rule::in`, `Rule::unique()->ignore($id)`, `exists:`, `in:`, `numeric`, `date`, `max`. |
| **SQL Injection** | Uso exclusivo de Eloquent / Query Builder con bindings. Nombres de columnas dinámicos pasan por whitelist (`SORTABLE`). |
| **CORS** | Restringido a `http://localhost:4200` en desarrollo. |
| **Rate limiting** | `throttle:5,1` en `/login` para frenar fuerza bruta. |
| **Mass assignment** | `$fillable` explícito en cada modelo. |
| **Soft deletes** | Vehículos y órdenes dados de baja no se destruyen físicamente. |
| **Frontend** | Token sólo se adjunta al backend real (`environment.apiUrl`), nunca a terceros. `clearSession()` limpia ambos storages en `401`. |

---

## 📦 Requisitos previos

| Herramienta | Versión mínima | Verificar con |
|---|---|---|
| PHP | 8.2+ | `php -v` |
| Composer | 2.x | `composer -V` |
| MySQL / MariaDB | 8.0+ / 10.4+ | `mysql --version` |
| Node.js | 20.x LTS o superior | `node -v` |
| npm | 10.x+ | `npm -v` |
| Angular CLI | 20.x+ | `ng version` (instalar con `npm i -g @angular/cli`) |

**Extensiones PHP requeridas:**

```
php_pdo_mysql  php_mbstring  php_openssl  php_tokenizer  php_xml
php_ctype      php_json     php_bcmath   php_fileinfo
```

---

## 📁 Estructura del proyecto

```
prueba_amstrong/
├── backend/          → API REST (Laravel 12)
└── frontend/         → SPA (Angular 20)
```

---

## ⚙️ Instalación del backend

### 1. Entrar a la carpeta

```bash
cd backend
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Crear el archivo `.env`

**Windows (PowerShell):**
```powershell
Copy-Item .env.example .env
```

**Linux / macOS:**
```bash
cp .env.example .env
```

### 4. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 5. Configurar `.env`

```env
APP_NAME="Flotilla API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=backend
DB_USERNAME=root
DB_PASSWORD=

AUTH_GUARD=web
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:4200,127.0.0.1,127.0.0.1:4200
SESSION_DRIVER=file
```

### 6. Crear la base de datos

```sql
CREATE DATABASE backend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Verificar `config/auth.php`

Los guards `web` y `sanctum` deben apuntar al provider `usuarios`:

```php
'guards' => [
    'web'     => ['driver' => 'session', 'provider' => 'usuarios'],
    'sanctum' => ['driver' => 'sanctum', 'provider' => 'usuarios'],
],

'providers' => [
    'usuarios' => ['driver' => 'eloquent', 'model' => App\Models\Usuario::class],
    'users'    => ['driver' => 'eloquent', 'model' => App\Models\User::class],
],
```

> ⚠️ Si `web` apunta a un provider que no existe → `SessionGuard::__construct(): Argument #2 ($provider) must be of type UserProvider, null given`.

### 8. Migrar y sembrar la base de datos

```bash
php artisan migrate:fresh --seed
```

Ejecuta en orden:

| Seeder | Contenido |
|---|---|
| `RolSeeder` | 2 roles (`administrador`, `capturista`) |
| `UsuarioSeeder` | 2 usuarios (ver [credenciales](#-credenciales-por-defecto)) |
| `VehiculoSeeder` | 20 vehículos de ejemplo |
| `OrdenSeeder` | 20 órdenes (5 abiertas, 3 en proceso, 10 cerradas, 2 canceladas) |

### 9. Limpiar cachés

```bash
php artisan optimize:clear
```

### 10. Levantar el servidor

```bash
php artisan serve
```

API disponible en **`http://localhost:8000/api`**.

---

## 🎨 Instalación del frontend

### 1. Entrar a la carpeta

```bash
cd frontend
```

### 2. Instalar dependencias

```bash
npm install
```

### 3. Configurar el entorno

Verifica `src/environments/environment.ts`:

```typescript
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000/api',
};
```

### 4. Verificar el interceptor HTTP

En `src/app/app.config.ts`:

```typescript
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { authInterceptor } from './core/interceptors/auth-interceptor';

export const appConfig: ApplicationConfig = {
  providers: [
    // ...
    provideHttpClient(withInterceptors([authInterceptor])),
    // ...
  ],
};
```

> Sin esto, las peticiones **no llevan** el header `Authorization: Bearer …` y el backend devuelve 401.

### 5. Levantar el servidor

```bash
npm start
# o
ng serve
```

Frontend disponible en **`http://localhost:4200`**.

---

## 🔑 Credenciales por defecto

| Rol | Email | Password |
|---|---|---|
| **Administrador** | `admin@test.com` | `password` |
| **Capturista** | `capturista@test.com` | `password` |

---

## ▶️ Ejecución en desarrollo

### Terminal 1 — Backend

```bash
cd backend
php artisan serve
# → http://localhost:8000
```

### Terminal 2 — Frontend

```bash
cd frontend
npm start
# → http://localhost:4200
```

### Flujo típico de prueba

1. Login con `admin@test.com` / `password`.
2. `/vehicles` → 20 vehículos con badge de estatus y columna "Servicio".
3. `/vehicles/due-for-service` → vehículos que cumplen RN-07.
4. `/orders` → 20 órdenes; probar filtros, crear, editar, cambiar estatus.
5. `/reports/costs` → reporte con costo real (no `$0.00`).

---

## 🌐 Endpoints principales

Todos requieren `Authorization: Bearer <token>` excepto `/login`.

### Autenticación

| Método | Ruta | Descripción |
|---|---|---|
| `POST` | `/api/login` | Login → `token` + `user` |
| `GET` | `/api/me` | Usuario autenticado |
| `POST` | `/api/logout` | Revoca el token actual |

### Vehículos

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/vehicles` | Listado paginado |
| `GET` | `/api/vehicles/due-for-service` | RN-07 |
| `GET` | `/api/vehicles/{id}` | Detalle |
| `POST` | `/api/vehicles` | Crear (admin) |
| `PUT` | `/api/vehicles/{id}` | Editar (admin) |
| `DELETE` | `/api/vehicles/{id}` | Baja lógica (admin) |

### Órdenes

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/orders` | Listado paginado |
| `GET` | `/api/orders/{id}` | Detalle |
| `POST` | `/api/orders` | Crear (folio autogenerado) |
| `PUT` | `/api/orders/{id}` | Editar |
| `PATCH` | `/api/orders/{id}/status` | Cambiar estatus (transiciones validadas) |

### Reportes

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/v1/reports/costs` | Costos por tipo y vehículo |

**Query params:**

```
?from=YYYY-MM-DD&to=YYYY-MM-DD
&status=abierta|en_proceso|cerrada|cancelada
&include_cancelled=1
&date_field=created_at|fecha_cierre|fecha_inicio|fecha_programada
&vehiculo_id=<id>
```

---

## 🛠️ Solución de problemas

### Backend

#### `SessionGuard::__construct(): Argument #2 ($provider) must be of type UserProvider, null given`

`config/auth.php` apunta a un provider inexistente. Verifica que `web` y `sanctum` usen `'provider' => 'usuarios'` **y** que el array `providers` tenga la clave `usuarios`. Luego:

```bash
php artisan config:clear
php artisan optimize:clear
```

#### `Target class [Database\Seeders\OrdenSeeder] does not exist`

Falta el archivo seeder. Créalo con:

```bash
php artisan make:seeder OrdenSeeder
```

Pega su contenido y ejecuta:

```bash
composer dump-autoload -o
php artisan db:seed --class=OrdenSeeder
```

#### `Unknown column 'estatus' in 'field list'`

Quedó basura de una migración anterior. Solución rápida:

```bash
php artisan migrate:fresh --seed
```

O, si tienes datos productivos:

```sql
ALTER TABLE ordenes DROP COLUMN estatus;
```

#### `Undefined constant "App\Models\status"` o `Call to undefined relationship [vehiculo]`

El modelo `Orden.php` está corrupto (falta un método o un nombre de columna sin comillas). Reemplaza el archivo completo y:

```bash
composer dump-autoload -o
php artisan optimize:clear
php -r "opcache_reset();"
```

#### `$request->user()` llega `null` aunque envíes el token

1. Las rutas deben estar en `Route::middleware('auth:sanctum')`.
2. El frontend debe enviar el header. Verifica en DevTools → Network.

#### "Placa ya existe" al editar un vehículo

El binding del route está mal. En `routes/api.php`:

```php
Route::apiResource('vehicles', VehiculoController::class)
    ->parameters(['vehicles' => 'vehiculo']);
```

El parámetro debe coincidir con la firma del controlador (`Vehiculo $vehiculo`).

### Frontend

#### `Cannot read properties of undefined (reading 'cls')`

Algún template accede a `o.estatus` (propiedad vieja) cuando el backend devuelve `status`. Ejecuta:

```bash
grep -rn "estatus" src/
```

Y cambia cualquier `o.estatus` / `order.estatus` / `formControlName="estatus"` por `status`.

#### `TS2305: Module '"./order"' has no exported member ...`

`src/app/core/models/order.ts` quedó incompleto. Debe exportar `OrderType`, `OrderPayload`, `PaginatedResponse`, `PaginatedMeta`, `OrderStatusPayload`, `OrderFilters`.

#### `Unclosed '{' on line 14 does not match ')'`

**Error de sintaxis PHP**, no del frontend. Corre:

```bash
cd backend
php -l app/Http/Controllers/Api/ReportController.php
```

El archivo que falle tiene un `{` o `}` desbalanceado.

#### El reporte muestra "No se pudo generar el reporte"

Abre DevTools → Network → fila `costs` → pestaña **Response**. Verás el error real:

- **422** por `include_cancelled=false` (string) → Laravel espera `0`/`1`.
- **500** por `ParseError` → ver el punto anterior.
- **401** por token expirado → cerrar y volver a iniciar sesión.

#### `npm start` no refleja cambios

```powershell
Remove-Item -Recurse -Force .angular/cache -ErrorAction SilentlyContinue
npm start
```

Y en el navegador **Ctrl+Shift+R**.

#### `CORS policy: No 'Access-Control-Allow-Origin' header`

Verifica `backend/config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['http://localhost:4200'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

Y limpia caché:

```bash
php artisan config:clear
```

---

## 🧰 Comandos útiles

### Backend

```bash
# Reiniciar base de datos con datos de ejemplo
php artisan migrate:fresh --seed

# Correr un seeder específico
php artisan db:seed --class=OrdenSeeder

# Ver rutas registradas
php artisan route:list --path=api

# Limpiar todas las cachés
php artisan optimize:clear

# Lint de un archivo específico
php -l app/Http/Controllers/Api/ReportController.php

# Shell interactivo
php artisan tinker
```

### Frontend

```bash
# Servidor de desarrollo
npm start

# Compilar para producción
npm run build

# Tests unitarios
npm test

# Lint
ng lint
```

---

## 📝 Notas finales

- **Folios**: se generan automáticamente como `PRE-YYYY-NNNN` (preventivo) o `COR-YYYY-NNNN` (correctivo), con numeración independiente por tipo y año.
- **Cierre de orden**: al pasar a `cerrada` se guarda `fecha_cierre` y el costo se replica en `costo_real`.
- **Reporte de costos**: calcula con `COALESCE(costo_real, costo, 0)` para incluir todas las órdenes con costo registrado.
- **Bajas**: los vehículos en `baja` se excluyen de `/vehicles/due-for-service` y del selector del modal de órdenes.
- **RN-07**: marca un vehículo como "requiere servicio" si han pasado **más de 10 000 km** o **más de 180 días** desde su último preventivo cerrado (o desde `fecha_alta` si no tiene historial).

---

**Stack:** Laravel 12 · Sanctum · MySQL 8 · Angular 20 · Signals · Reactive Forms · TypeScript 5.5+
