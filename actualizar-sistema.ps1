<#
.SYNOPSIS
    Actualiza CEPRE UNU en Windows a la version descargada, sin borrar datos.
.DESCRIPTION
    Se usa despues de bajar los cambios con "git pull origin main". A diferencia
    de instalar-entorno.ps1, NO borra la base de datos: solo aplica las
    migraciones nuevas.
    Pasos que realiza:
      1. verifica php (8.4 o superior y extensiones), composer y npm;
      2. respalda la base de datos en storage\app\respaldos si mysqldump esta
         disponible (si no, pide confirmar que ya se respaldo);
      3. instala las dependencias PHP (sin las de desarrollo si APP_ENV=production);
      4. aplica las migraciones pendientes (migrate --force);
      5. reinstala las dependencias JS (npm ci) y compila los assets;
      6. crea el enlace public/storage si falta y limpia las caches.
    Antes de ejecutarlo, cierre la ventana de iniciar-red-local. Al terminar,
    vuelva a iniciar el sistema con iniciar-red-local.bat.
.PARAMETER SinRespaldo
    No respalda la base de datos. Usar solo si ya se respaldo por otro medio.
.EXAMPLE
    .\actualizar-sistema.ps1
#>

[CmdletBinding()]
param(
    [switch] $SinRespaldo
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

function Assert-NodeCompatible {
    # Vite 8 requiere Node.js 20.19 o superior dentro de la serie 20, o 22.12 o superior.
    if (-not (Get-Command 'node' -ErrorAction SilentlyContinue)) {
        throw "No se encontro 'node' en el PATH. Instale Node.js 22 LTS (o use el de Laravel Herd) y vuelva a abrir la consola."
    }
    $version = ((& node -v) -replace '^v', '').Trim()
    $partes = $version.Split('.')
    $mayor = [int]$partes[0]
    $menor = [int]$partes[1]
    $compatible = ($mayor -eq 20 -and $menor -ge 19) -or ($mayor -eq 22 -and $menor -ge 12) -or ($mayor -ge 23)
    if (-not $compatible) {
        throw "Se requiere Node.js 20.19+ o 22.12+ y se encontro $version. Instale Node.js 22 LTS y vuelva a abrir la consola."
    }
    Write-Host "Node.js:        $version"
}

# 1. Requisitos ---------------------------------------------------------------
Write-Paso 'Verificando requisitos'
Assert-NodeCompatible
foreach ($programa in 'php', 'composer', 'npm') {
    if (-not (Get-Command $programa -ErrorAction SilentlyContinue)) {
        throw "No se encontro '$programa' en el PATH. Instale Laravel Herd o agregue '$programa' al PATH."
    }
}
& php -r "exit(version_compare(PHP_VERSION, '8.4.0', '>=') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) {
    throw "Se requiere PHP 8.4 o superior y se encontro $(& php -r 'echo PHP_VERSION;'). En Herd, elija PHP 8.4 y vuelva a abrir la consola."
}
$extensionesFaltantes = & php -r "echo implode(',', array_filter(['gd', 'zip', 'mbstring', 'dom', 'fileinfo', 'pdo_mysql'], fn (`$e) => ! extension_loaded(`$e)));"
if ($extensionesFaltantes) {
    throw "Faltan extensiones de PHP: $extensionesFaltantes. Activelas en el php.ini de Herd."
}
if (-not (Test-Path '.env')) {
    throw 'No existe el archivo .env. Para una instalacion nueva use instalar-entorno.bat.'
}

$entorno = Get-ValorEnv 'APP_ENV'
$baseDatos = Get-ValorEnv 'DB_DATABASE'
Write-Host "Entorno:        $entorno"
Write-Host "Base de datos:  $baseDatos"

# 2. Respaldo -----------------------------------------------------------------
if (-not $SinRespaldo) {
    Write-Paso "Respaldando la base de datos '$baseDatos'"
    if (Get-Command 'mysqldump' -ErrorAction SilentlyContinue) {
        $carpeta = Join-Path $PSScriptRoot 'storage\app\respaldos'
        New-Item -ItemType Directory -Force -Path $carpeta | Out-Null
        $archivo = Join-Path $carpeta "$baseDatos-$(Get-Date -Format 'yyyyMMdd-HHmmss').sql"
        # La clave va por variable de entorno para que no aparezca en la linea de comandos.
        $env:MYSQL_PWD = Get-ValorEnv 'DB_PASSWORD'
        try {
            Invoke-Comando 'mysqldump' @(
                '--single-transaction', '--routines', '--triggers',
                '-h', (Get-ValorEnv 'DB_HOST'), '-P', (Get-ValorEnv 'DB_PORT'),
                '-u', (Get-ValorEnv 'DB_USERNAME'),
                "--result-file=$archivo", $baseDatos
            )
        }
        finally {
            Remove-Item Env:\MYSQL_PWD -ErrorAction SilentlyContinue
        }
        Write-Host "Respaldo guardado en $archivo" -ForegroundColor Green
    }
    else {
        Write-Warning 'No se encontro mysqldump en el PATH; no se puede respaldar automaticamente.'
        $respuesta = Read-Host 'Ya respaldo la base de datos por otro medio? (s/N)'
        if ($respuesta -notin 's', 'S') {
            Write-Host 'Actualizacion cancelada. Respalde la base y vuelva a ejecutar.' -ForegroundColor Yellow
            exit 1
        }
    }
}

# 3. Dependencias PHP ---------------------------------------------------------
Write-Paso 'Instalando dependencias PHP'
$argumentosComposer = @('install', '--no-interaction', '--optimize-autoloader')
if ($entorno -eq 'production') {
    $argumentosComposer += '--no-dev'
}
Invoke-Comando 'composer' $argumentosComposer

# 4. Migraciones --------------------------------------------------------------
Write-Paso 'Aplicando migraciones pendientes'
Invoke-Comando 'php' @('artisan', 'optimize:clear', '--no-interaction')
Invoke-Comando 'php' @('artisan', 'migrate', '--force', '--no-interaction')

# 5. Frontend -----------------------------------------------------------------
Write-Paso 'Instalando dependencias JS y compilando assets'
Invoke-Comando 'npm' @('ci')
Invoke-Comando 'npm' @('run', 'build')
if (Test-Path 'public\hot') {
    Remove-Item 'public\hot' -Force
}

# 6. Storage y caches ---------------------------------------------------------
if (-not (Test-Path 'public\storage')) {
    Write-Paso 'Creando enlace public\storage'
    Invoke-Comando 'php' @('artisan', 'storage:link', '--no-interaction')
}
Invoke-Comando 'php' @('artisan', 'optimize:clear', '--no-interaction')

# Resumen ---------------------------------------------------------------------
Write-Host ''
Write-Host 'Actualizacion completada.' -ForegroundColor Green
Write-Host ''
Write-Host 'Siguiente paso: iniciar el sistema con iniciar-red-local.bat.'
