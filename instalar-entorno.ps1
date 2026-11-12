<#
.SYNOPSIS
    Instala CEPRE UNU en Windows y deja la base de datos limpia, lista para configurar.

.DESCRIPTION
    Prepara todo el entorno y recrea la base de datos solo con los catalogos:
      - seguridad: permisos, rol ADMINISTRADOR y usuario superadmin;
      - mantenedores: carreras, sedes (con sus carreras), modalidades, lugares de
        inscripcion (con sus modalidades), ofertas base, turnos, conceptos de pago
        y tipos de expediente;
      - catalogos generales: ubigeo, paises, nacionalidades, colegios, tipos de
        documento, estados civiles, lenguas, identidades etnicas y discapacidades.

    NO crea procesos, ofertas por proceso, aulas por proceso, convenios, reglas de
    pago, requisitos, inscripciones, personas ni importaciones del banco.

    ATENCION: borra todas las tablas de la base configurada en .env y las vuelve a
    crear. Pide confirmacion escribiendo el nombre de la base.

    Pasos:
      1. verifica php, composer y npm (Laravel Herd los instala);
      2. crea .env desde .env.red-local.example si no existe;
      3. instala dependencias PHP (sin las de desarrollo si APP_ENV=production);
      4. genera APP_KEY si falta;
      5. crea la base de datos si no existe y la recrea con los catalogos;
      6. crea el enlace public/storage;
      7. instala dependencias JS y compila los assets.

    Despues de instalar, use iniciar-red-local.ps1 para servir el sistema en la red.

.PARAMETER ConAulasDeEjemplo
    Agrega 20 aulas genericas ("Aula 001" a "Aula 020"). Por defecto no se crean:
    las aulas reales deben registrarse en Mantenedores > Aulas.

.PARAMETER SinConfirmar
    No pide confirmacion antes de borrar la base de datos. Usar con cuidado.

.EXAMPLE
    .\instalar-entorno.ps1

.EXAMPLE
    .\instalar-entorno.ps1 -ConAulasDeEjemplo
#>
[CmdletBinding()]
param(
    [switch] $ConAulasDeEjemplo,
    [switch] $SinConfirmar
)

$ErrorActionPreference = 'Stop'
Set-Location -Path $PSScriptRoot

function Write-Paso {
    param([string] $Mensaje)
    Write-Host ''
    Write-Host "==> $Mensaje" -ForegroundColor Cyan
}

function Invoke-Comando {
    param([string] $Programa, [string[]] $Argumentos)
    & $Programa @Argumentos
    if ($LASTEXITCODE -ne 0) {
        throw "Fallo el comando: $Programa $($Argumentos -join ' ')"
    }
}

function Get-ValorEnv {
    param([string] $Clave)
    $ruta = Join-Path $PSScriptRoot '.env'
    foreach ($linea in [System.IO.File]::ReadAllLines($ruta)) {
        if ($linea -match "^\s*$Clave\s*=\s*(.*)$") {
            return $Matches[1].Trim().Trim('"').Trim("'")
        }
    }
    return ''
}

# 1. Requisitos ---------------------------------------------------------------
Write-Paso 'Verificando requisitos'
foreach ($programa in 'php', 'composer', 'npm') {
    if (-not (Get-Command $programa -ErrorAction SilentlyContinue)) {
        throw "No se encontro '$programa' en el PATH. Instale Laravel Herd o agregue '$programa' al PATH."
    }
}
& php -r "exit(version_compare(PHP_VERSION, '8.3.0', '>=') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) {
    throw 'Se requiere PHP 8.3 o superior.'
}

# 2. .env ---------------------------------------------------------------------
Write-Paso 'Verificando archivo .env'
if (-not (Test-Path '.env')) {
    Copy-Item '.env.red-local.example' '.env'
    Write-Host 'Se creo .env a partir de .env.red-local.example.' -ForegroundColor Yellow
    Write-Host 'Revise los datos de MySQL (DB_*) y APP_URL antes de continuar.' -ForegroundColor Yellow
    Read-Host 'Presione Enter cuando haya guardado los cambios en .env'
}

$entorno = Get-ValorEnv 'APP_ENV'
$baseDatos = Get-ValorEnv 'DB_DATABASE'
$servidorDb = "$(Get-ValorEnv 'DB_HOST'):$(Get-ValorEnv 'DB_PORT')"
$usuarioDb = Get-ValorEnv 'DB_USERNAME'

if (-not $baseDatos) {
    throw 'DB_DATABASE esta vacio en .env.'
}

Write-Host "Entorno:        $entorno"
Write-Host "Base de datos:  $baseDatos"
Write-Host "Servidor MySQL: $servidorDb (usuario $usuarioDb)"

# Confirmacion ----------------------------------------------------------------
if (-not $SinConfirmar) {
    Write-Host ''
    Write-Warning "Se BORRARAN todas las tablas de la base '$baseDatos' y se crearan de nuevo."
    $confirmacion = Read-Host "Escriba el nombre de la base ($baseDatos) para continuar"
    if ($confirmacion -cne $baseDatos) {
        Write-Host 'Operacion cancelada. No se modifico la base de datos.' -ForegroundColor Yellow
        exit 1
    }
}

# 3. Dependencias PHP ---------------------------------------------------------
Write-Paso 'Instalando dependencias PHP'
$argumentosComposer = @('install', '--no-interaction', '--optimize-autoloader')
if ($entorno -eq 'production') {
    $argumentosComposer += '--no-dev'
}
Invoke-Comando 'composer' $argumentosComposer

# 4. APP_KEY ------------------------------------------------------------------
if (-not (Get-ValorEnv 'APP_KEY')) {
    Write-Paso 'Generando APP_KEY'
    Invoke-Comando 'php' @('artisan', 'key:generate', '--force', '--no-interaction')
}

# 5. Base de datos ------------------------------------------------------------
Write-Paso "Preparando la base de datos '$baseDatos'"
Invoke-Comando 'php' @('artisan', 'optimize:clear', '--no-interaction')
# migrate --force crea la base si no existe. Si la base ya existe con un esquema
# antiguo puede fallar; no importa, porque migrate:fresh la recrea a continuacion.
& php artisan migrate --force --no-interaction
if ($LASTEXITCODE -ne 0) {
    Write-Host 'La migracion inicial fallo; se continuara con la recreacion completa.' -ForegroundColor Yellow
}

Write-Paso 'Recreando tablas y cargando catalogos (puede tardar unos minutos)'
Invoke-Comando 'php' @('artisan', 'migrate:fresh', '--seed', '--force', '--no-interaction')

if ($ConAulasDeEjemplo) {
    Write-Paso 'Cargando aulas de ejemplo'
    Invoke-Comando 'php' @('artisan', 'db:seed', '--class=AulaSeeder', '--force', '--no-interaction')
}

# 6. Storage ------------------------------------------------------------------
if (-not (Test-Path 'public\storage')) {
    Write-Paso 'Creando enlace public\storage'
    Invoke-Comando 'php' @('artisan', 'storage:link', '--no-interaction')
}

# 7. Frontend -----------------------------------------------------------------
Write-Paso 'Instalando dependencias JS y compilando assets'
Invoke-Comando 'npm' @('ci')
Invoke-Comando 'npm' @('run', 'build')
if (Test-Path 'public\hot') {
    Remove-Item 'public\hot' -Force
}

# Resumen ---------------------------------------------------------------------
Write-Host ''
Write-Host 'Instalacion completada.' -ForegroundColor Green
Write-Host ''
Write-Host 'Usuario inicial: superadmin (contrasena inicial en MANUAL_USUARIO_CEPRE.md, seccion 5).'
Write-Host 'Cambie la contrasena al ingresar por primera vez.'
Write-Host ''
Write-Host 'Siguientes pasos:'
if (-not $ConAulasDeEjemplo) {
    Write-Host '  1. Registrar las aulas en Mantenedores > Aulas.'
}
else {
    Write-Host '  1. Revisar las aulas de ejemplo en Mantenedores > Aulas.'
}
Write-Host '  2. Configurar el proceso en Configuracion Academica: Procesos, Ofertas, Aulas,'
Write-Host '     Convenios, Reglas de pago y Requisitos (ver FLUJO_PROCESOS_CEPRE.md).'
Write-Host '  3. Iniciar el sistema en la red local con iniciar-red-local.bat.'
