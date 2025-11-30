# Script tạo php.ini cho Windows
# Chạy với quyền Administrator nếu cần

$phpDir = "C:\php"
$extDir = "$phpDir\ext"

Write-Host "=== Tạo file php.ini ===" -ForegroundColor Cyan

# Kiểm tra thư mục PHP
if (-not (Test-Path $phpDir)) {
    Write-Host "❌ Không tìm thấy thư mục PHP tại: $phpDir" -ForegroundColor Red
    Write-Host "Vui lòng cập nhật đường dẫn PHP trong script này." -ForegroundColor Yellow
    exit 1
}

# Tìm file php.ini-development hoặc php.ini-production
$iniTemplate = $null
if (Test-Path "$phpDir\php.ini-development") {
    $iniTemplate = "$phpDir\php.ini-development"
    Write-Host "✓ Tìm thấy php.ini-development" -ForegroundColor Green
} elseif (Test-Path "$phpDir\php.ini-production") {
    $iniTemplate = "$phpDir\php.ini-production"
    Write-Host "✓ Tìm thấy php.ini-production" -ForegroundColor Green
} else {
    Write-Host "❌ Không tìm thấy file template php.ini" -ForegroundColor Red
    exit 1
}

# Copy template thành php.ini
if (-not (Test-Path "$phpDir\php.ini")) {
    Copy-Item $iniTemplate "$phpDir\php.ini"
    Write-Host "✓ Đã tạo file php.ini từ template" -ForegroundColor Green
} else {
    Write-Host "⚠ File php.ini đã tồn tại" -ForegroundColor Yellow
}

# Đọc file php.ini
$iniContent = Get-Content "$phpDir\php.ini" -Raw

# Bật extension pdo_mysql
if ($iniContent -match ";extension=pdo_mysql") {
    $iniContent = $iniContent -replace ";extension=pdo_mysql", "extension=pdo_mysql"
    Write-Host "✓ Đã bật extension=pdo_mysql" -ForegroundColor Green
} elseif ($iniContent -notmatch "extension=pdo_mysql") {
    # Thêm extension nếu chưa có
    $iniContent += "`n; PDO MySQL Extension`nextension=pdo_mysql`n"
    Write-Host "✓ Đã thêm extension=pdo_mysql" -ForegroundColor Green
} else {
    Write-Host "✓ extension=pdo_mysql đã được bật" -ForegroundColor Green
}

# Kiểm tra extension_dir
if ($iniContent -match ';extension_dir = "ext"') {
    $iniContent = $iniContent -replace ';extension_dir = "ext"', 'extension_dir = "ext"'
    Write-Host "✓ Đã bật extension_dir" -ForegroundColor Green
}

# Ghi lại file
Set-Content "$phpDir\php.ini" -Value $iniContent -NoNewline

Write-Host "`n✅ Hoàn tất! Vui lòng restart PHP server." -ForegroundColor Green
Write-Host "`nKiểm tra lại bằng lệnh:" -ForegroundColor Cyan
Write-Host "php -r `"echo extension_loaded('pdo_mysql') ? 'Enabled' : 'NOT ENABLED';`"" -ForegroundColor Yellow








