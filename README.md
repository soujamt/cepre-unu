# CEPRE UNU

Sistema de gestión académica y de inscripciones del Centro Preuniversitario de la Universidad Nacional de Ucayali (CEPRE UNU). El personal configura cada proceso, registra y valida los pagos del banco e inscribe a los postulantes; los postulantes también pueden inscribirse en línea con su voucher.

## Tecnologías

| Capa | Herramientas |
|---|---|
| Backend | Laravel 13, PHP 8.4, MySQL |
| Frontend | Inertia 3, React 19, TypeScript, Tailwind CSS 4 |
| Componentes | shadcn/ui (Radix), sonner, íconos Keyline |
| Rutas tipadas | Laravel Wayfinder |
| Documentos | DomPDF (ficha y carnet con QR), Laravel Excel |
| Pruebas | Pest 5, `tsc --noEmit` en la integración continua |

## Lo más destacado

- **Asistente de inscripción en 4 pasos** (pago, datos personales, datos generales y postulación). Lo usan el personal y el portal público. Su estado vive en la sesión del servidor y el navegador solo puede cambiar los campos del formulario y llamar a las acciones permitidas. El pago validado, la persona y el paso actual los decide siempre el servidor.
- **Pagos bancarios**: importación del TXT del banco con previsualización, validación de cuotas y pagos manuales. Cada pago queda libre o se asocia a una inscripción.
- **Portal del postulante**: inscripción con el DNI que pagó, revisión del personal antes de activarla, y ficha y carnet descargables con enlaces firmados.
- **Permisos por rol**: el menú, los botones y las rutas se filtran con los mismos permisos.
- **Interfaz** con tema claro y oscuro, tablas con filtros en la URL, formularios en paneles laterales y confirmaciones en diálogos.

## Estructura del frontend

```
resources/js/
├── pages/          Pantallas de Inertia, agrupadas por área
├── components/     Componentes propios y de shadcn/ui (components/ui)
├── layouts/        Layout de la aplicación, de acceso y del portal
├── hooks/          usePermiso, useTema, useCatalogo…
├── lib/            Navegación, íconos y utilidades
└── routes/         Rutas tipadas que genera Wayfinder
```

## Requisitos

- PHP 8.4 o superior
- Composer
- Node.js 20.19 o superior
- NPM
- MySQL/MariaDB

## Instalación Inicial

### 1. Instalar dependencias

```bash
composer install
npm ci
```

### 2. Crear y configurar el archivo `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Variables mínimas a revisar en `.env`:

```env
APP_NAME="CEPRE UNU"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cepre_unu
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

## Configuración RENIEC

El sistema usa el servicio RENIEC desde `config/services.php`.

Configurar en `.env`:

```env
URL_API_RENIEC=https://tu-endpoint-reniec
TOKEN_API_RENIEC=tu_token_reniec
RENIEC_ALLOW_INSECURE_SSL=false
```

Notas:

- `TOKEN_API_RENIEC` es la variable principal.
- También se acepta `API_RENIEC_KEY` como respaldo si el entorno ya usa ese nombre.
- `RENIEC_ALLOW_INSECURE_SSL=true` solo debería usarse en entornos locales con certificados no válidos.

## Base de Datos

### 1. Ejecutar migraciones

```bash
php artisan migrate --force
```

### 2. Crear enlace de almacenamiento público

El sistema guarda archivos de expedientes en disco `public`.

```bash
php artisan storage:link
```

## Seeders

El proyecto separa la carga inicial en grupos para no subir datos que no correspondan al ambiente.

### Seed base institucional

Carga catálogos generales y catálogos base del sistema:

- permisos
- tipos de documento
- tipos de discapacidad
- tipos de colegio
- estado civil
- países
- nacionalidades
- ubigeos
- identidades étnicas
- lenguas nativas
- lenguas extranjeras
- colegios
- turnos
- conceptos de pago
- tipos de expediente

Comando:

```bash
php artisan db:seed --no-interaction
```

Este comando ejecuta:

- `BaseCatalogSeeder`
- `BaseOfertaCatalogSeeder`
- `InitialAccessSeeder`

### Seed base de oferta

Forma parte de la base institucional y contiene:

- sedes
- carreras
- modalidades de admisión

Cada proceso define después su propia oferta (modalidades y carreras por sede) desde `Configuración académica > Oferta del proceso`.

La fuente principal está en:

- `database/seeders/Support/CepreOfertaCatalog.php`

### Seed de configuración académica inicial

Este seeder ya no corre por defecto. Debe ejecutarse solo si deseas dejar un ciclo inicial listo para operar.

Incluye:

- el proceso inicial
- su oferta: modalidades y carreras por sede
- las reglas de pago del sistema anterior (cuota única, fraccionado, parentesco UNU, beca y media beca)

Comando:

```bash
php artisan db:seed --class=AcademicBootstrapSeeder --no-interaction
```

### Seed de datos temporales o de desarrollo

No ejecutar en producción si no deseas datos genéricos.

Actualmente incluye el proceso `2026-1` con 50 inscripciones de demostración, sus pagos, aulas y el usuario `gestor.cepre`.

Comando:

```bash
php artisan db:seed --class=DevelopmentDataSeeder --no-interaction
```

Para cargar (o borrar) postulantes ficticios sobre una base ya configurada:

```bash
php artisan cepre:datos-prueba
```

## Usuario Inicial

El seeder `InitialAccessSeeder` crea un usuario administrador inicial:

- usuario: `superadmin`
- correo: `superadmin@unu.edu.pe`

Importante:

- cambiar la contraseña inmediatamente después del primer ingreso
- si el ambiente es productivo, no dejar credenciales por defecto activas más tiempo del necesario

## Compilación de Frontend

Para desarrollo:

```bash
npm run dev
```

Para pruebas o producción:

```bash
npm run build
```

## Levantar el Proyecto en Desarrollo

Puedes usar el flujo completo definido en Composer:

```bash
composer run dev
```

Esto inicia:

- servidor Laravel
- cola
- logs con Pail
- Vite

## Orden Recomendado Para Un Ambiente Nuevo

### Ambiente de pruebas limpio

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan db:seed --no-interaction
php artisan db:seed --class=AcademicBootstrapSeeder --no-interaction
npm run build
```

### Ambiente de producción sin ciclo académico precargado

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan db:seed --no-interaction
npm run build
```

## Verificación Rápida

Después de la instalación inicial, validar:

- acceso al login
- compilación correcta de assets
- conexión a base de datos
- existencia del enlace `public/storage`
- funcionamiento de la consulta RENIEC

## Pruebas

```bash
php artisan test --compact
npm run types:check
```

## Mantenimiento Útil

Limpiar cachés:

```bash
php artisan optimize:clear
```

Volver a compilar frontend:

```bash
npm run build
```
