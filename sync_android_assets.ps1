param(
    [switch]$WhatIf
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$source = Join-Path $projectRoot 'www'
$target = Join-Path $projectRoot 'android\app\src\main\assets\public'

if (-not (Test-Path $source)) {
    throw "No existe la carpeta fuente: $source"
}

New-Item -ItemType Directory -Path $target -Force | Out-Null

$robocopyArgs = @(
    $source,
    $target,
    '/MIR',
    '/XD', '.git', 'android', 'www\uploads', 'node_modules',
    '/XF', 'error_log', 'debug_log.txt'
)

if ($WhatIf) {
    $robocopyArgs += '/L'
}

& robocopy @robocopyArgs | Out-Null

$code = $LASTEXITCODE
if ($code -ge 8) {
    throw "robocopy falló con código $code"
}

Write-Output "Sincronización completada. Código robocopy: $code"
