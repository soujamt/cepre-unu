# UNIVERSIDAD NACIONAL DE UCAYALI
## CENTRO PREUNIVERSITARIO - CEPRE UNU
### MANUAL DE INSTALACION DEL SISTEMA

**Versión:** 1.0  
**Sistema:** CEPRE UNU  
**Dirigido a:** personal técnico, soporte, desarrollo e implementación  
**Fecha:** Junio 2026

---

## Índice

1. Introducción
2. Objetivo del documento
3. Alcance
4. Arquitectura general recomendada
5. Requisitos del sistema
6. Instalación local en Windows con Herd y MySQL
7. Verificación de la instalación local
8. Estructura recomendada de variables `.env`
9. Instalación en Ubuntu Server para producción
10. Configuración del servidor web Nginx
11. Configuración de permisos y almacenamiento
12. Configuración de base de datos en producción
13. Configuración final de Laravel en producción
14. Cola, cron y tareas recomendadas
15. Despliegue inicial y actualización del sistema
16. Checklist de validación final
17. Solución de problemas frecuentes
18. Recomendaciones de seguridad
19. Referencias oficiales consultadas

---

## 1. Introducción

El presente documento describe el procedimiento de instalación del sistema CEPRE UNU en dos escenarios distintos:

1. un entorno local de trabajo sobre Windows utilizando Laravel Herd y MySQL;
2. un entorno de producción sobre Ubuntu Server utilizando Nginx, PHP-FPM y MySQL.

La guía ha sido elaborada tomando como referencia la configuración real del proyecto y las prácticas recomendadas para aplicaciones Laravel con compilación frontend por Vite, almacenamiento público de archivos y uso de base de datos MySQL.

## 2. Objetivo del documento

Este manual tiene como objetivos:

1. facilitar una instalación ordenada y repetible del sistema;
2. reducir errores durante la puesta en marcha;
3. dejar claramente diferenciados los pasos de desarrollo local y producción;
4. servir como guía institucional de despliegue y mantenimiento inicial.

## 3. Alcance

Este documento cubre:

- instalación del sistema en Windows local;
- configuración de Herd;
- configuración de MySQL local;
- instalación del sistema en Ubuntu Server;
- configuración de Nginx;
- configuración de PHP-FPM;
- configuración de Laravel;
- pasos de despliegue inicial;
- validaciones posteriores a la instalación.

No cubre:

- balanceadores de carga;
- alta disponibilidad;
- contenedores Docker;
- integración CI/CD;
- hardening avanzado de infraestructura;
- copias de seguridad automatizadas a nivel institucional.

## 4. Arquitectura general recomendada

### 4.1. Ambiente local Windows

Componentes recomendados:

- Windows 10 o superior;
- Laravel Herd;
- Herd Pro para MySQL local integrado;
- Node.js incluido por Herd;
- PHP incluido por Herd;
- Composer incluido por Herd;
- proyecto Laravel servido en dominio `.test`.

### 4.2. Ambiente de producción Ubuntu

Componentes recomendados:

- Ubuntu Server 22.04 LTS o superior;
- Nginx;
- PHP 8.3 y PHP-FPM;
- Composer;
- Node.js 20;
- MySQL 8;
- Laravel en modo producción;
- SSL mediante Let’s Encrypt;
- Supervisor opcional para cola;
- cron para automatizaciones del framework, si luego se incorporan tareas programadas.

## 5. Requisitos del sistema

Según la configuración actual del proyecto, se recomienda trabajar con:

- PHP 8.3 o superior;
- Composer;
- Node.js 20 o superior;
- NPM;
- MySQL o MariaDB;
- extensiones comunes de PHP para Laravel:
  - `bcmath`
  - `ctype`
  - `curl`
  - `dom`
  - `fileinfo`
  - `mbstring`
  - `openssl`
  - `pdo`
  - `pdo_mysql`
  - `tokenizer`
  - `xml`
  - `zip`

El proyecto utiliza además:

- Laravel 13;
- Livewire 4;
- Flux UI 2;
- Tailwind CSS 4;
- Vite 7;
- `maatwebsite/excel`.

## 6. Instalación local en Windows con Herd y MySQL

## 6.1. Consideraciones importantes

Laravel Herd en Windows incluye PHP, Nginx, Composer, Node.js y otras utilidades de desarrollo. Sin embargo, la documentación oficial indica que la administración integrada de MySQL forma parte de `Herd Pro`. Por tanto:

1. si se desea usar MySQL administrado desde Herd, se requiere `Herd Pro`;
2. si solo se dispone de Herd estándar, puede usarse un MySQL externo instalado por separado.

Este manual asume que se utilizará `Herd Pro` con MySQL local.

## 6.2. Instalar Laravel Herd

1. Descargar Laravel Herd para Windows desde su sitio oficial.
2. Ejecutar el instalador con privilegios de administrador.
3. Completar la instalación.
4. Abrir una nueva terminal y validar que Herd dejó disponibles los binarios principales.

Comandos de verificación:

```powershell
herd --version
php --version
composer --version
node --version
npm --version
```

Resultado esperado:

- los comandos deben responder correctamente;
- no debe aparecer error de binario no encontrado.

## 6.3. Mejorar el rendimiento de Herd en Windows

La documentación oficial de Herd indica que Windows Defender puede degradar el rendimiento si analiza continuamente la carpeta de configuración de Herd.

Se recomienda excluir:

```text
%USERPROFILE%\.config\herd
```

Esta exclusión ayuda a evitar lentitud innecesaria en proyectos locales.

## 6.4. Preparar la carpeta del proyecto

Herd sirve automáticamente los proyectos ubicados en rutas “parked” o directorios enlazados.

La ruta por defecto que Herd estaciona es:

```text
%USERPROFILE%\Herd
```

Opciones recomendadas:

1. colocar el proyecto dentro de `%USERPROFILE%\Herd`;
2. o enlazar manualmente el proyecto si está ubicado en otra carpeta.

Ejemplo recomendado:

```powershell
Set-Location -Path "$env:USERPROFILE\Herd"
git clone <URL_DEL_REPOSITORIO> cepre-unu
cd cepre-unu
```

Si el proyecto ya existe en otra carpeta:

```powershell
cd C:\ruta\del\proyecto\cepre-unu
herd link cepre-unu
```

Resultado esperado:

- el proyecto debe quedar accesible como `http://cepre-unu.test`;
- si se desea HTTPS local, se puede asegurar el sitio.

## 6.5. Asegurar el sitio local con HTTPS

Por defecto Herd sirve sitios sobre HTTP. Si se requiere trabajar con HTTPS local:

```powershell
herd secure cepre-unu
```

Luego el sitio podrá abrirse como:

```text
https://cepre-unu.test
```

Esto es especialmente útil cuando:

- se prueban redirecciones seguras;
- se usan callbacks;
- se requiere simular un entorno más cercano a producción.

## 6.6. Instalar y habilitar MySQL desde Herd Pro

1. Abrir Herd.
2. Ir al panel de servicios.
3. Seleccionar MySQL.
4. Instalar la versión recomendada.
5. Activar `autostart` si se desea que arranque con Herd.

La documentación de Herd indica que el servicio MySQL puede tener un puerto distinto para coexistir con otras instalaciones del equipo. El ejemplo oficial muestra `3307` como puerto local de la instancia administrada por Herd.

## 6.7. Crear la base de datos local

Crear una base de datos llamada:

```sql
cepre_unu
```

Puede hacerse desde:

- TablePlus;
- AdminerEvo;
- o consola MySQL.

Ejemplo por consola:

```sql
CREATE DATABASE cepre_unu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 6.8. Clonar el proyecto e instalar dependencias

Dentro de la carpeta del proyecto:

```powershell
composer install
npm install
```

Resultado esperado:

- se instalan dependencias PHP en `vendor/`;
- se instalan dependencias frontend en `node_modules/`.

## 6.9. Crear y configurar el archivo `.env`

Crear el entorno local a partir de la plantilla:

```powershell
Copy-Item .env.example .env
```

Editar `.env` con valores locales. Ejemplo recomendado para Herd Pro MySQL:

```env
APP_NAME="CEPRE UNU"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://cepre-unu.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=cepre_unu
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

URL_API_RENIEC=
TOKEN_API_RENIEC=
RENIEC_ALLOW_INSECURE_SSL=true
```

Notas:

1. si Herd usa otro puerto, debe colocarse ese valor real;
2. `RENIEC_ALLOW_INSECURE_SSL=true` solo es aceptable en local si el entorno de prueba lo necesita;
3. para desarrollo local, `APP_DEBUG=true` es correcto.

## 6.10. Generar la clave de aplicación

```powershell
php artisan key:generate
```

## 6.11. Ejecutar migraciones

```powershell
php artisan migrate --force
```

## 6.12. Ejecutar seeders

### Carga institucional base

```powershell
php artisan db:seed --no-interaction
```

### Configuración académica inicial

Si se desea dejar un ciclo inicial listo para operar:

```powershell
php artisan db:seed --class=AcademicBootstrapSeeder --no-interaction
```

### Datos demo o temporales

Si se desea cargar datos de demostración:

```powershell
php artisan db:seed --class=DevelopmentDataSeeder --no-interaction
```

Advertencia:

- no usar `DevelopmentDataSeeder` en producción si no se desean datos demo.

## 6.13. Crear el enlace de almacenamiento público

El sistema usa archivos públicos para expedientes y otros recursos almacenados. Laravel recomienda crear el enlace:

```powershell
php artisan storage:link
```

Resultado esperado:

- `public/storage` debe apuntar a `storage/app/public`.

## 6.14. Compilar assets frontend

Para desarrollo:

```powershell
npm run dev
```

Para una compilación estática:

```powershell
npm run build
```

## 6.15. Levantar el proyecto en modo desarrollo

Si se desea usar el flujo de desarrollo completo definido por Composer:

```powershell
composer run dev
```

Este flujo intenta iniciar:

- servidor Laravel;
- cola;
- logs con Pail;
- Vite.

Sin embargo, si el proyecto ya está siendo servido por Herd mediante Nginx local, normalmente bastará con:

1. dejar Herd activo;
2. ejecutar `npm run dev` si se trabajará con hot reload.

## 7. Verificación de la instalación local

Realizar las siguientes validaciones:

1. abrir `http://cepre-unu.test` o `https://cepre-unu.test`;
2. verificar que cargue el login;
3. verificar acceso a base de datos;
4. verificar que no haya error de Vite manifest;
5. verificar que exista `public/storage`;
6. verificar que el usuario inicial haya sido sembrado.

Usuario inicial esperado por el seeder base:

- usuario: `superadmin`

Si se ejecutó el seeder de desarrollo/demo, pueden existir usuarios adicionales.

## 8. Estructura recomendada de variables `.env`

## 8.1. Local Windows

```env
APP_NAME="CEPRE UNU"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://cepre-unu.test
APP_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=cepre_unu
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

URL_API_RENIEC=
TOKEN_API_RENIEC=
RENIEC_ALLOW_INSECURE_SSL=true
```

## 8.2. Producción Ubuntu

```env
APP_NAME="CEPRE UNU"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio
APP_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cepre_unu
DB_USERNAME=cepre_unu
DB_PASSWORD=CAMBIAR_ESTA_CLAVE

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

URL_API_RENIEC=https://tu-endpoint-reniec
TOKEN_API_RENIEC=tu_token_reniec
RENIEC_ALLOW_INSECURE_SSL=false
```

## 9. Instalación en Ubuntu Server para producción

Esta sección asume un servidor limpio Ubuntu Server 22.04 LTS o superior.

## 9.1. Datos de ejemplo usados en esta guía

Ajustar según el entorno real:

- dominio: `cepre.tu-dominio.edu.pe`
- ruta del proyecto: `/var/www/cepre-unu`
- usuario de despliegue opcional: `deploy`
- usuario del servicio web: `www-data`

## 9.2. Actualizar el servidor

```bash
sudo apt update
sudo apt upgrade -y
```

## 9.3. Instalar dependencias del sistema

```bash
sudo apt install -y nginx mysql-server unzip git curl supervisor software-properties-common
```

## 9.4. Instalar PHP 8.3 y extensiones necesarias

Si el repositorio del sistema no ofrece PHP 8.3 de forma directa, agregar el PPA de Ondrej:

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

Instalar PHP-FPM y extensiones:

```bash
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-mysql php8.3-bcmath php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip php8.3-intl php8.3-gd php8.3-common php8.3-opcache
```

## 9.5. Instalar Composer

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

## 9.6. Instalar Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

## 9.7. Crear la base de datos y el usuario MySQL

Ingresar a MySQL:

```bash
sudo mysql
```

Ejecutar:

```sql
CREATE DATABASE cepre_unu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cepre_unu'@'localhost' IDENTIFIED BY 'CAMBIAR_ESTA_CLAVE';
GRANT ALL PRIVILEGES ON cepre_unu.* TO 'cepre_unu'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 9.8. Desplegar el código del proyecto

Ejemplo:

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone <URL_DEL_REPOSITORIO> cepre-unu
sudo chown -R $USER:$USER /var/www/cepre-unu
cd /var/www/cepre-unu
```

## 9.9. Instalar dependencias del proyecto

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

## 9.10. Configurar el archivo `.env`

```bash
cp .env.example .env
```

Editar `.env` con los valores productivos reales.

## 9.11. Generar APP_KEY

```bash
php artisan key:generate
```

## 9.12. Ejecutar migraciones y seeders base

```bash
php artisan migrate --force
php artisan db:seed --no-interaction --force
```

Si se desea crear un ciclo académico inicial:

```bash
php artisan db:seed --class=AcademicBootstrapSeeder --no-interaction --force
```

No ejecutar `DevelopmentDataSeeder` en producción salvo que sea un entorno de pruebas internas.

## 10. Configuración del servidor web Nginx

Laravel debe ser servido desde el directorio `public/`, no desde la raíz del proyecto.

Crear:

```bash
sudo nano /etc/nginx/sites-available/cepre-unu
```

Contenido sugerido:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name cepre.tu-dominio.edu.pe;

    root /var/www/cepre-unu/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Habilitar el sitio:

```bash
sudo ln -s /etc/nginx/sites-available/cepre-unu /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 10.1. Configurar HTTPS con Let’s Encrypt

Instalar Certbot:

```bash
sudo apt install -y certbot python3-certbot-nginx
```

Emitir certificado:

```bash
sudo certbot --nginx -d cepre.tu-dominio.edu.pe
```

Resultado esperado:

- el sitio debe quedar accesible por HTTPS;
- Nginx debe redirigir correctamente a la configuración segura si así se elige.

## 11. Configuración de permisos y almacenamiento

Laravel necesita permisos correctos en:

- `storage/`
- `bootstrap/cache/`

Aplicar:

```bash
sudo chown -R www-data:www-data /var/www/cepre-unu
sudo find /var/www/cepre-unu -type f -exec chmod 644 {} \;
sudo find /var/www/cepre-unu -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/cepre-unu/storage
sudo chmod -R 775 /var/www/cepre-unu/bootstrap/cache
```

Crear el enlace público:

```bash
php artisan storage:link
```

## 12. Configuración de base de datos en producción

Validar que el `.env` tenga:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cepre_unu
DB_USERNAME=cepre_unu
DB_PASSWORD=CAMBIAR_ESTA_CLAVE
```

Verificar conexión:

```bash
php artisan migrate:status
```

## 13. Configuración final de Laravel en producción

## 13.1. Limpiar y regenerar cachés

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Notas importantes:

1. `config:cache` debe ejecutarse en producción, no durante desarrollo local;
2. una vez cacheada la configuración, Laravel ya no carga `.env` en cada request, por lo que todos los valores deben estar correctos antes de este paso.

## 13.2. Verificar modo producción

Confirmar:

```env
APP_ENV=production
APP_DEBUG=false
```

## 14. Cola, cron y tareas recomendadas

## 14.1. Estado actual del proyecto

En la revisión del proyecto actual:

1. no se encontraron jobs propios en `app/`;
2. no se observaron tareas programadas personalizadas en `routes/console.php`;
3. la aplicación, sin embargo, tiene configurado `QUEUE_CONNECTION=database`.

Por ello:

- la cola puede dejarse preparada como configuración recomendada;
- el cron también puede dejarse listo como práctica estándar;
- ambos pueden activarse plenamente cuando el proyecto lo necesite.

## 14.2. Supervisor para `queue:work` en producción

Instalar Supervisor:

```bash
sudo apt install -y supervisor
```

Crear archivo:

```bash
sudo nano /etc/supervisor/conf.d/cepre-unu-worker.conf
```

Contenido sugerido:

```ini
[program:cepre-unu-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/cepre-unu/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/cepre-unu/storage/logs/worker.log
stopwaitsecs=3600
```

Activar Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start "cepre-unu-worker:*"
```

Cuando haya despliegues nuevos:

```bash
php artisan queue:restart
```

## 14.3. Cron para el scheduler

Aun cuando el proyecto hoy no tenga tareas programadas personalizadas, es una buena práctica dejar configurado el scheduler de Laravel:

```bash
crontab -e
```

Agregar:

```cron
* * * * * cd /var/www/cepre-unu && php artisan schedule:run >> /dev/null 2>&1
```

## 15. Despliegue inicial y actualización del sistema

## 15.1. Despliegue inicial resumido

```bash
cd /var/www/cepre-unu
composer install --no-dev --optimize-autoloader
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --no-interaction --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload nginx
```

## 15.2. Actualización de una versión existente

```bash
cd /var/www/cepre-unu
git pull origin <rama-o-tag>
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
sudo systemctl reload nginx
```

## 16. Checklist de validación final

Validar como mínimo:

1. el dominio responde correctamente;
2. el login carga sin errores;
3. los assets CSS y JS cargan correctamente;
4. la base de datos conecta sin problemas;
5. `public/storage` existe;
6. `storage/` y `bootstrap/cache/` tienen permisos correctos;
7. el usuario inicial puede acceder;
8. si corresponde, la integración RENIEC responde correctamente;
9. Nginx y PHP-FPM están activos;
10. si se usa cola, Supervisor muestra el worker activo.

## 17. Solución de problemas frecuentes

## 17.1. Error de Vite manifest

Síntoma:

- error indicando que no se encuentra un archivo en el manifiesto de Vite.

Solución:

```bash
npm run build
```

En local, si se trabaja con servidor Vite:

```bash
npm run dev
```

## 17.2. Error de permisos en `storage` o `bootstrap/cache`

Síntoma:

- Laravel no puede escribir logs, caché o sesiones.

Solución:

```bash
sudo chown -R www-data:www-data /var/www/cepre-unu
sudo chmod -R 775 /var/www/cepre-unu/storage
sudo chmod -R 775 /var/www/cepre-unu/bootstrap/cache
```

## 17.3. Error de conexión a MySQL

Revisar:

1. que el servicio MySQL esté activo;
2. que el puerto sea correcto;
3. que el usuario y contraseña coincidan;
4. que la base de datos exista;
5. que el `.env` haya sido actualizado correctamente.

## 17.4. Error de archivos públicos no visibles

Verificar:

```bash
php artisan storage:link
```

## 17.5. Cola no procesa trabajos

Verificar:

1. `QUEUE_CONNECTION=database`;
2. tablas de cola migradas;
3. worker activo en Supervisor;
4. logs del worker;
5. reinicio de workers después de despliegue:

```bash
php artisan queue:restart
```

## 18. Recomendaciones de seguridad

1. nunca dejar `APP_DEBUG=true` en producción;
2. cambiar inmediatamente credenciales por defecto;
3. usar HTTPS en producción;
4. restringir el acceso SSH por llave cuando sea posible;
5. no ejecutar seeders de desarrollo en producción;
6. proteger el archivo `.env`;
7. mantener actualizado Ubuntu y dependencias del sistema;
8. limitar permisos de escritura solo a las carpetas necesarias;
9. realizar respaldos periódicos de base de datos y `storage/app/public`.

## 19. Referencias oficiales consultadas

Las siguientes fuentes oficiales respaldan este manual:

1. Laravel Herd para Windows:
   [https://herd.laravel.com/docs/windows/getting-started/installation](https://herd.laravel.com/docs/windows/getting-started/installation)
2. Herd Sites:
   [https://herd.laravel.com/docs/windows/getting-started/sites](https://herd.laravel.com/docs/windows/getting-started/sites)
3. Herd MySQL:
   [https://herd.laravel.com/docs/windows/herd-pro-services/mysql](https://herd.laravel.com/docs/windows/herd-pro-services/mysql)
4. Herd Secure Sites:
   [https://herd.laravel.com/docs/windows/sites/securing-sites](https://herd.laravel.com/docs/windows/sites/securing-sites)
5. Laravel Installation:
   [https://laravel.com/docs/13.x/installation](https://laravel.com/docs/13.x/installation)
6. Laravel Configuration:
   [https://laravel.com/docs/13.x/configuration](https://laravel.com/docs/13.x/configuration)
7. Laravel Filesystem:
   [https://laravel.com/docs/13.x/filesystem](https://laravel.com/docs/13.x/filesystem)
8. Laravel Queues:
   [https://laravel.com/docs/13.x/queues](https://laravel.com/docs/13.x/queues)

