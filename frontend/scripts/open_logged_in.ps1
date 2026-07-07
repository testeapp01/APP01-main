param(
  [string]$Email = 'admin@example.com',
  [string]$Password = 'secret',
  [string]$ApiUrl = 'http://127.0.0.1:8000/api/v1/auth/login',
  [string]$Redirect = 'http://localhost:5173/#/'
)

Write-Output "Logging in as $Email to $ApiUrl"
try {
  $body = @{ email = $Email; password = $Password } | ConvertTo-Json
  $resp = Invoke-RestMethod -Method Post -Uri $ApiUrl -ContentType 'application/json' -Body $body -ErrorAction Stop
} catch {
  Write-Error "Login failed: $_"
  exit 1
}

if (-not $resp.token) {
  Write-Error "No token returned from API"
  exit 1
}

$token = $resp.token
$tokenJson = $token | ConvertTo-Json

$tmp = Join-Path $env:TEMP 'hf_login.html'
$html = "<!doctype html><html><head><meta charset='utf-8'/></head><body><script>const t = $tokenJson; localStorage.setItem('hf_token', t); window.location.href='$Redirect';</script></body></html>"

Set-Content -Path $tmp -Value $html -Encoding UTF8
Write-Output "Opening browser to $Redirect with token set in localStorage"
Start-Process $tmp
