$env:JWT_SECRET = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
$env:REFRESH_TOKEN_KEY = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
Set-Location -LiteralPath $PSScriptRoot
php -S 127.0.0.1:8006 -t public public/index.php
