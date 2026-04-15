param(
  [string]$HostName = "127.0.0.1",
  [string]$UserName = "root",
  [string]$Database = "workpulse",
  [string]$MySqlBin = "d:\wamp64\bin\mysql\mysql9.1.0\bin"
)

$ErrorActionPreference = "Stop"

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$backupDir = Join-Path $PSScriptRoot "..\storage\app\backups\mysql"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

$outFile = Join-Path $backupDir ("{0}-{1}.sql" -f $Database, $timestamp)

$mysqldump = Join-Path $MySqlBin "mysqldump.exe"
if (!(Test-Path $mysqldump)) {
  throw "mysqldump not found at $mysqldump"
}

Write-Host "Writing backup to $outFile"

& $mysqldump --host=$HostName --user=$UserName --databases $Database --routines --events --triggers --single-transaction --quick --set-gtid-purged=OFF | Out-File -Encoding utf8 $outFile

Write-Host "Done."

