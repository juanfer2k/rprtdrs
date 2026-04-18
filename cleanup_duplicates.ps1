param(
    [switch]$Apply
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$clonePath = Join-Path $projectRoot 'www\uploads'

if (-not (Test-Path $clonePath)) {
    Write-Output "No existe carpeta clonada: $clonePath"
    exit 0
}

Write-Output "Carpeta detectada para limpieza: $clonePath"

if (-not $Apply) {
    Write-Output "Modo simulación: usa -Apply para eliminar realmente"
    exit 0
}

Remove-Item -Recurse -Force $clonePath
Write-Output "Eliminado: $clonePath"
