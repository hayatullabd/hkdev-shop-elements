param(
    [switch]$Strict
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Path $PSScriptRoot -Parent
$auditScript = Join-Path $root "scripts\migration-audit.php"

$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    Write-Error "php is not installed or not in PATH."
    exit 127
}

if ($Strict) {
    & php $auditScript --strict
} else {
    & php $auditScript
}
