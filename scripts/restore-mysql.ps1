param(
  [Parameter(Mandatory=$true)]
  [string]$SqlFile,
  [string]$HostName = "127.0.0.1",
  [string]$UserName = "root",
  [string]$MySqlBin = "d:\wamp64\bin\mysql\mysql9.1.0\bin"
)

$ErrorActionPreference = "Stop"

if (!(Test-Path $SqlFile)) {
  throw "SQL file not found: $SqlFile"
}

$mysql = Join-Path $MySqlBin "mysql.exe"
if (!(Test-Path $mysql)) {
  throw "mysql.exe not found at $mysql"
}

Write-Host "Restoring from $SqlFile"

Get-Content -Raw $SqlFile | & $mysql --host=$HostName --user=$UserName

Write-Host "Done."

