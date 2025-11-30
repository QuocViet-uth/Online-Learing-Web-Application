# Script khắc phục lỗi PDO MySQL
# Tự động tìm và sửa file php.ini

Write-Host "=== Khắc phục lỗi PDO MySQL ===" -ForegroundColor Cyan

# Tìm vị trí PHP
$phpExe = (Get-Command php).Source
$phpDir = Split-Path $phpExe -Parent

Write-Host "PHP Directory: $phpDir" -ForegroundColor Yellow

# Tìm file php.ini
$iniFile = $null
$possibleIniPaths = @(
    "$phpDir\php.ini",
    "$env:WINDIR\php.ini",
    "$env:USERPROFILE\php.ini"
)

foreach ($path in $possibleIniPaths) {
    if (Test-Path $path) {
        $iniFile = $path
        Write-Host "✓ Tìm thấy php.ini tại: $path" -ForegroundColor Green
        break
    }
}

# Nếu không tìm thấy, tạo từ template
if (-not $iniFile) {
    Write-Host "⚠ Không tìm thấy php.ini" -ForegroundColor Yellow
    
    $template = "$phpDir\php.ini-development"
    if (-not (Test-Path $template)) {
        $template = "$phpDir\php.ini-production"
    }
    
    if (Test-Path $template) {
        $iniFile = "$phpDir\php.ini"
        Copy-Item $template $iniFile
        Write-Host "✓ Đã tạo php.ini từ template" -ForegroundColor Green
    } else {
        Write-Host "❌ Không tìm thấy template php.ini" -ForegroundColor Red
        Write-Host "`nVui lòng:" -ForegroundColor Yellow
        Write-Host "1. Tạo file php.ini tại: $phpDir\php.ini" -ForegroundColor White
        Write-Host "2. Thêm dòng: extension=pdo_mysql" -ForegroundColor White
        Write-Host "3. Đảm bảo extension_dir trỏ đúng thư mục ext" -ForegroundColor White
        exit 1
    }
}

# Đọc và sửa file php.ini
$content = Get-Content $iniFile -Raw

# Bật extension_dir nếu bị comment
if ($content -match ';extension_dir\s*=') {
    $content = $content -replace ';extension_dir\s*=', 'extension_dir ='
    Write-Host "✓ Đã bật extension_dir" -ForegroundColor Green
}

# Bật pdo_mysql
$modified = $false
if ($content -match ";extension=pdo_mysql") {
    $content = $content -replace ";extension=pdo_mysql", "extension=pdo_mysql"
    $modified = $true
    Write-Host "✓ Đã bật extension=pdo_mysql" -ForegroundColor Green
} elseif ($content -notmatch "extension=pdo_mysql") {
    # Thêm extension nếu chưa có
    $content += "`n; PDO MySQL Extension`nextension=pdo_mysql`n"
    $modified = $true
    Write-Host "✓ Đã thêm extension=pdo_mysql" -ForegroundColor Green
} else {
    Write-Host "✓ extension=pdo_mysql đã được bật" -ForegroundColor Green
}

# Ghi lại file nếu có thay đổi
if ($modified) {
    Set-Content $iniFile -Value $content -NoNewline
    Write-Host "`n✅ Đã cập nhật file php.ini" -ForegroundColor Green
} else {
    Write-Host "`n⚠ Không có thay đổi nào" -ForegroundColor Yellow
}

# Kiểm tra extension file
$extDir = "$phpDir\ext"
if (-not (Test-Path "$extDir\php_pdo_mysql.dll")) {
    Write-Host "`n⚠ Cảnh báo: Không tìm thấy php_pdo_mysql.dll tại $extDir" -ForegroundColor Yellow
    Write-Host "Vui lòng tải PHP với extension MySQL hoặc cài đặt XAMPP/WAMP" -ForegroundColor Yellow
}

Write-Host "`n📝 Vui lòng RESTART PHP server để áp dụng thay đổi!" -ForegroundColor Cyan
Write-Host "`nKiểm tra lại:" -ForegroundColor Cyan
Write-Host "php -r `"echo extension_loaded('pdo_mysql') ? 'Enabled ✓' : 'NOT ENABLED ✗';`"" -ForegroundColor Yellow








