# ========================================
# DETECCION AUTOMATICA DE RUTAS
# ========================================

# Obtener la ruta del proyecto automaticamente (donde esta este script)
$projectPath = $PSScriptRoot

# Si $PSScriptRoot esta vacio (ejecucion desde ISE o metodo antiguo)
if ([string]::IsNullOrEmpty($projectPath)) {
    $projectPath = Split-Path -Parent $MyInvocation.MyCommand.Definition
}

# Si aun esta vacio, usar carpeta actual
if ([string]::IsNullOrEmpty($projectPath)) {
    $projectPath = Get-Location
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Laravel Task Scheduler Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Ruta del proyecto detectada: $projectPath" -ForegroundColor Green
Write-Host ""

# ========================================
# DETECCION AUTOMATICA DE PHP
# ========================================

$phpPath = $null

Write-Host "Buscando PHP..." -ForegroundColor Yellow

# 1. Intentar encontrar PHP en PATH
$phpCommand = Get-Command php -ErrorAction SilentlyContinue

if ($phpCommand) {
    $phpPath = $phpCommand.Source
    Write-Host "[OK] PHP encontrado en PATH: $phpPath" -ForegroundColor Green
} else {
    Write-Host "PHP no esta en PATH, buscando en rutas comunes..." -ForegroundColor Yellow
    
    # 2. Buscar en rutas comunes
    $commonPaths = @(
        # Laragon
        "C:\laragon\bin\php\php-8.2.0-Win32-vs16-x64\php.exe",
        "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe",
        "C:\laragon\bin\php\php-8.0.30-Win32-vs16-x64\php.exe",
        
        # IIS / Produccion
        "C:\Program Files\PHP\v8.2\php.exe",
        "C:\Program Files\PHP\v8.1\php.exe",
        "C:\Program Files\PHP\v8.0\php.exe",
        "C:\Program Files\PHP\php.exe",

    )
    
    # Buscar tambien en subdirectorios de Laragon dinamicamente
    if (Test-Path "C:\laragon\bin\php") {
        $laragonPhpDirs = Get-ChildItem "C:\laragon\bin\php" -Directory -ErrorAction SilentlyContinue
        foreach ($dir in $laragonPhpDirs) {
            $phpExe = Join-Path $dir.FullName "php.exe"
            if (Test-Path $phpExe) {
                $commonPaths = @($phpExe) + $commonPaths
            }
        }
    }
    
    foreach ($path in $commonPaths) {
        if (Test-Path $path) {
            $phpPath = $path
            Write-Host "[OK] PHP encontrado en: $phpPath" -ForegroundColor Green
            break
        }
    }
}

# 3. Si no se encontro, pedir ruta manual
if (-not $phpPath -or -not (Test-Path $phpPath)) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Yellow
    Write-Host "  No se pudo encontrar PHP automaticamente" -ForegroundColor Yellow
    Write-Host "========================================" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Por favor, ingresa la ruta completa de php.exe" -ForegroundColor Cyan
    Write-Host "Ejemplo: C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe" -ForegroundColor Gray
    Write-Host ""
    
    $manualPath = Read-Host "Ruta de php.exe"
    
    if (Test-Path $manualPath) {
        $phpPath = $manualPath
        Write-Host "[OK] Ruta manual aceptada: $phpPath" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] Ruta invalida: $manualPath" -ForegroundColor Red
        Write-Host ""
        pause
        exit 1
    }
}

Write-Host ""

# ========================================
# VERIFICAR PRIVILEGIOS DE ADMINISTRADOR
# ========================================

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")

if (-not $isAdmin) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "  ERROR: Se requieren privilegios de Administrador" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Por favor:" -ForegroundColor Yellow
    Write-Host "  1. Cierra esta ventana" -ForegroundColor White
    Write-Host "  2. Click derecho en PowerShell" -ForegroundColor White
    Write-Host "  3. Selecciona 'Ejecutar como administrador'" -ForegroundColor White
    Write-Host "  4. Ejecuta: cd `"$projectPath`"" -ForegroundColor White
    Write-Host "  5. Ejecuta: .\setup-scheduler.ps1" -ForegroundColor White
    Write-Host ""
    pause
    exit 1
}

# ========================================
# VERIFICAR ARCHIVOS
# ========================================

$artisanPath = "$projectPath\artisan"

Write-Host "Verificando archivos..." -ForegroundColor Yellow

if (-not (Test-Path $phpPath)) {
    Write-Host "[ERROR] PHP no encontrado en: $phpPath" -ForegroundColor Red
    pause
    exit 1
}

if (-not (Test-Path $artisanPath)) {
    Write-Host "[ERROR] Artisan no encontrado en: $artisanPath" -ForegroundColor Red
    Write-Host ""
    Write-Host "Asegurate de que este script este en la raiz del proyecto Laravel" -ForegroundColor Yellow
    Write-Host "Ruta actual: $projectPath" -ForegroundColor Gray
    Write-Host ""
    pause
    exit 1
}

Write-Host "[OK] PHP: $phpPath" -ForegroundColor Green
Write-Host "[OK] Artisan: $artisanPath" -ForegroundColor Green
Write-Host "[OK] Proyecto: $projectPath" -ForegroundColor Green
Write-Host ""

# ========================================
# CONFIGURAR TAREA PROGRAMADA
# ========================================

$taskName = "Laravel Scheduler - TaxoChile API"

Write-Host "Configurando tarea programada..." -ForegroundColor Yellow

$action = New-ScheduledTaskAction `
    -Execute $phpPath `
    -Argument "`"$artisanPath`" schedule:run" `
    -WorkingDirectory $projectPath

$trigger = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 1) `
    -RepetitionDuration (New-TimeSpan -Days 9999)

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Hours 1) `
    -MultipleInstances IgnoreNew

$principal = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -RunLevel Highest

# ========================================
# REGISTRAR TAREA
# ========================================

try {
    $existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    if ($existingTask) {
        Write-Host "Eliminando tarea existente..." -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    }

    Register-ScheduledTask `
        -TaskName $taskName `
        -Action $action `
        -Trigger $trigger `
        -Settings $settings `
        -Principal $principal `
        -Description "Ejecuta el Laravel Task Scheduler cada minuto. Proyecto: $projectPath | PHP: $phpPath"

    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "  [OK] Tarea creada exitosamente!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "Detalles:" -ForegroundColor Cyan
    Write-Host "  Nombre      : $taskName" -ForegroundColor White
    Write-Host "  Frecuencia  : Cada 1 minuto" -ForegroundColor White
    Write-Host "  Usuario     : SYSTEM" -ForegroundColor White
    Write-Host "  PHP         : $phpPath" -ForegroundColor White
    Write-Host "  Proyecto    : $projectPath" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Verificando comandos programados..." -ForegroundColor Yellow
    Write-Host ""
    
    & $phpPath $artisanPath schedule:list
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  Comandos utiles" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Ver estado:" -ForegroundColor Yellow
    Write-Host "  Get-ScheduledTask '$taskName' | Get-ScheduledTaskInfo" -ForegroundColor White
    Write-Host ""
    Write-Host "Ejecutar manualmente:" -ForegroundColor Yellow
    Write-Host "  cd `"$projectPath`"" -ForegroundColor White
    Write-Host "  php artisan schedule:run" -ForegroundColor White
    Write-Host ""
    Write-Host "Ver logs:" -ForegroundColor Yellow
    Write-Host "  Get-Content `"$projectPath\storage\logs\scheduler\move-images.log`" -Tail 50" -ForegroundColor White
    Write-Host ""
    Write-Host "Desinstalar:" -ForegroundColor Yellow
    Write-Host "  Unregister-ScheduledTask -TaskName '$taskName' -Confirm:`$false" -ForegroundColor White
    Write-Host ""

} catch {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "  ERROR" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ""
    pause
    exit 1
}

pause