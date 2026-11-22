<#
.SYNOPSIS
    Levanta CEPRE UNU en Windows para usarlo desde otros equipos de la red local.

.DESCRIPTION
    Prepara el proyecto y lo sirve en http://<IP>:<PUERTO> con "php artisan serve".
    Pasos que realiza:
      1. verifica que php y npm esten disponibles (Laravel Herd los instala);
      2. verifica que la IP pertenezca a este equipo y avisa si es dinamica (DHCP);
      3. escribe APP_URL en el .env;
      4. compila los assets si faltan o estan desactualizados y elimina public/hot;
      5. crea el enlace public/storage si falta;
      6. regenera las caches de Laravel;
      7. crea la regla de firewall para el puerto (requiere administrador);
      8. abre minimizada la ventana de tareas programadas (schedule:work), que
         sincroniza las hojas DATA y PAGOS con el Drive cada 10 minutos si
         DRIVE_SYNC_URL y DRIVE_SYNC_TOKEN estan en el .env;
      9. inicia el servidor escuchando en todas las interfaces.

    Requisitos previos: haber ejecutado instalar-entorno.ps1 (o tener .env,
    vendor y la base de datos listos).

    IP ESTATICA: este script no cambia la configuracion de red. Para fijar la IP:
      Configuracion > Red e Internet > Ethernet (o Wi-Fi) > Asignacion de IP >
      Editar > Manual > IPv4: direccion, mascara, puerta de enlace y DNS.
    O bien, en una consola de administrador (ajuste los valores):
      netsh interface ipv4 set address name="Ethernet" static 192.168.1.50 255.255.255.0 192.168.1.1
      netsh interface ipv4 set dns name="Ethernet" static 192.168.1.1
    Tambien puede reservar la IP en el router (reserva DHCP por direccion MAC).

    LIMITACION: en Windows, "php artisan serve" atiende una peticion a la vez.
    Es adecuado para pocos usuarios simultaneos (por ejemplo, 3 a 5 gestores).

.PARAMETER Ip
    IP estatica de este equipo en la red local, por ejemplo 192.168.1.50.

.PARAMETER Puerto
    Puerto HTTP. Por defecto 8000.

.PARAMETER Compilar
    Reinstala las dependencias JS (npm ci) y fuerza "npm run build" aunque los
    assets ya existan. actualizar-sistema.ps1 ya lo hace al actualizar.

.EXAMPLE
    .\iniciar-red-local.ps1 -Ip 192.168.1.50

.EXAMPLE
    .\iniciar-red-local.ps1 -Ip 192.168.1.50 -Puerto 8080 -Compilar
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [ValidatePattern('^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$')]
    [string] $Ip,

    [ValidateRange(1, 65535)]
    [int] $Puerto = 8000,

    [switch] $Compilar
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

function Set-ValorEnv {
    param([string] $Clave, [string] $Valor)
    $ruta = Join-Path $PSScriptRoot '.env'
    $lineas = [System.IO.File]::ReadAllLines($ruta)
    $encontrada = $false
    for ($i = 0; $i -lt $lineas.Length; $i++) {
        if ($lineas[$i] -match "^\s*$Clave\s*=") {
            $lineas[$i] = "$Clave=$Valor"
            $encontrada = $true
        }
    }
    if (-not $encontrada) {
        $lineas += "$Clave=$Valor"
    }
    # UTF-8 sin BOM: un BOM al inicio rompe la lectura del .env.
    [System.IO.File]::WriteAllLines($ruta, $lineas, (New-Object System.Text.UTF8Encoding $false))
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

function Test-AssetsDesactualizados {
    # Los assets se recompilan si falta el manifest o si algun archivo del frontend
    # es mas nuevo que el (por ejemplo, despues de un git pull).
    $manifest = 'public\build\manifest.json'
    if (-not (Test-Path $manifest)) {
        return $true
    }
    $fechaBuild = (Get-Item $manifest).LastWriteTime
    # Wayfinder genera las rutas del frontend desde routes y los controladores.
    $fuentes = @(Get-ChildItem -Path 'resources', 'routes', 'app\Http\Controllers' -Recurse -File -ErrorAction SilentlyContinue)
    $fuentes += @(Get-Item -Path 'package.json', 'package-lock.json', 'vite.config.ts' -ErrorAction SilentlyContinue)
    $masNuevo = $fuentes | Where-Object { $_.LastWriteTime -gt $fechaBuild } | Select-Object -First 1
    return ($null -ne $masNuevo)
}

# 1. Requisitos ---------------------------------------------------------------
Write-Paso 'Verificando requisitos'
foreach ($programa in 'php', 'npm') {
    if (-not (Get-Command $programa -ErrorAction SilentlyContinue)) {
        throw "No se encontro '$programa' en el PATH. Instale Laravel Herd o agregue '$programa' al PATH."
    }
}
Assert-NodeCompatible
if (-not (Test-Path '.env')) {
    throw 'No existe el archivo .env. Ejecute primero instalar-entorno.ps1.'
}
if (-not (Test-Path 'vendor')) {
    throw 'No existe la carpeta vendor. Ejecute primero instalar-entorno.ps1.'
}
& php -r "exit(version_compare(PHP_VERSION, '8.4.0', '>=') ? 0 : 1);"
if ($LASTEXITCODE -ne 0) {
    throw 'Se requiere PHP 8.4 o superior. En Herd, elija PHP 8.4 y vuelva a abrir la consola.'
}

# 2. IP -----------------------------------------------------------------------
Write-Paso "Verificando la IP $Ip"
$direccion = Get-NetIPAddress -AddressFamily IPv4 -IPAddress $Ip -ErrorAction SilentlyContinue |
    Select-Object -First 1
if (-not $direccion) {
    $ipsActuales = (Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
        Where-Object { $_.IPAddress -ne '127.0.0.1' } |
        Select-Object -ExpandProperty IPAddress) -join ', '
    Write-Warning "La IP $Ip no esta asignada a este equipo. IPs actuales: $ipsActuales"
    Write-Warning 'Configure la IP estatica (ver la ayuda: Get-Help .\iniciar-red-local.ps1 -Full).'
    $respuesta = Read-Host 'Desea continuar de todos modos? (s/N)'
    if ($respuesta -notin 's', 'S') {
        exit 1
    }
}
else {
    if ($direccion.PrefixOrigin -eq 'Dhcp') {
        Write-Warning "La IP $Ip fue asignada por DHCP y podria cambiar. Se recomienda fijarla."
    }
    $perfil = Get-NetConnectionProfile -InterfaceIndex $direccion.InterfaceIndex -ErrorAction SilentlyContinue
    if ($perfil -and $perfil.NetworkCategory -eq 'Public') {
        Write-Warning "La red '$($perfil.Name)' esta marcada como Publica: Windows bloqueara las conexiones."
        Write-Warning 'Cambiela a Privada en Configuracion > Red e Internet > Propiedades de la red.'
    }
}

# 3. APP_URL ------------------------------------------------------------------
$url = "http://${Ip}:$Puerto"
Write-Paso "Configurando APP_URL=$url"
Set-ValorEnv -Clave 'APP_URL' -Valor $url

# 4. Assets -------------------------------------------------------------------
Write-Paso 'Preparando assets del frontend'
if (Test-Path 'public\hot') {
    # Si existe, los navegadores de la red intentarian cargar Vite desde 127.0.0.1.
    Remove-Item 'public\hot' -Force
    Write-Host 'Eliminado public\hot (servidor de desarrollo de Vite).'
}
if ($Compilar -or (Test-AssetsDesactualizados)) {
    # npm ci instala las versiones exactas de package-lock.json, que pueden
    # haber cambiado al actualizar el codigo. Tambien se recompila si los
    # archivos del frontend son mas nuevos que el build (por ejemplo tras un git pull).
    # Antes se limpian las caches: Wayfinder genera las rutas del frontend leyendo las
    # rutas de Laravel, y una cache de rutas vieja romperia la compilacion.
    Invoke-Comando 'php' @('artisan', 'optimize:clear', '--no-interaction')
    Invoke-Comando 'npm' @('ci')
    Invoke-Comando 'npm' @('run', 'build')
}
else {
    Write-Host 'Assets ya compilados. Use -Compilar para regenerarlos.'
}

# 5. Storage ------------------------------------------------------------------
if (-not (Test-Path 'public\storage')) {
    Write-Paso 'Creando enlace public\storage'
    Invoke-Comando 'php' @('artisan', 'storage:link', '--no-interaction')
}

# 6. Caches -------------------------------------------------------------------
Write-Paso 'Regenerando caches de Laravel'
Invoke-Comando 'php' @('artisan', 'optimize:clear', '--no-interaction')
Invoke-Comando 'php' @('artisan', 'optimize', '--no-interaction')

# 7. Firewall -----------------------------------------------------------------
Write-Paso "Verificando firewall para el puerto $Puerto"
$nombreRegla = "CEPRE UNU (TCP $Puerto)"
if (Get-NetFirewallRule -DisplayName $nombreRegla -ErrorAction SilentlyContinue) {
    Write-Host "La regla '$nombreRegla' ya existe."
}
else {
    $identidad = [Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
    $esAdministrador = $identidad.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
    if ($esAdministrador) {
        New-NetFirewallRule -DisplayName $nombreRegla -Direction Inbound -Protocol TCP `
            -LocalPort $Puerto -Action Allow -Profile Private, Domain | Out-Null
        Write-Host "Regla '$nombreRegla' creada (perfiles Privado y Dominio)."
    }
    else {
        Write-Warning 'No se pudo crear la regla de firewall: ejecute este script como administrador una vez,'
        Write-Warning 'o cree la regla manualmente en una consola de administrador:'
        Write-Host "  New-NetFirewallRule -DisplayName '$nombreRegla' -Direction Inbound -Protocol TCP -LocalPort $Puerto -Action Allow -Profile Private,Domain"
    }
}

# 8. Tareas programadas -------------------------------------------------------
# cmd.exe resuelve php aunque sea un .bat (como el de Laravel Herd).
Write-Paso 'Iniciando tareas programadas (sincronizacion con el Drive)'
$tareas = Start-Process -FilePath 'cmd.exe' -ArgumentList '/c', 'php artisan schedule:work' `
    -WorkingDirectory $PSScriptRoot -WindowStyle Minimized -PassThru
Write-Host "Ventana minimizada 'schedule:work' abierta. Se cierra al detener el servidor."

# 9. Servidor -----------------------------------------------------------------
Write-Paso 'Iniciando servidor'
Write-Host ''
Write-Host "  Acceso desde la red local: $url" -ForegroundColor Green
Write-Host "  Acceso desde este equipo:  http://127.0.0.1:$Puerto" -ForegroundColor Green
Write-Host '  Presione Ctrl+C para detener.' -ForegroundColor Yellow
Write-Host ''
try {
    Invoke-Comando 'php' @('artisan', 'serve', '--host=0.0.0.0', "--port=$Puerto", '--no-reload')
}
finally {
    if ($tareas -and -not $tareas.HasExited) {
        & taskkill.exe /PID $tareas.Id /T /F | Out-Null
    }
}
