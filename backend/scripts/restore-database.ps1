# PowerShell script restore database
# Usage: .\restore-database.ps1 <backup_file.sql>

param(
    [Parameter(Mandatory=$true)]
    [string]$BackupFile
)

if (-not (Test-Path $BackupFile)) {
    Write-Host "❌ File backup không tồn tại: $BackupFile" -ForegroundColor Red
    exit 1
}

Write-Host "⚠️  CẢNH BÁO: Thao tác này sẽ GHI ĐÈ toàn bộ dữ liệu hiện tại!" -ForegroundColor Yellow
$confirm = Read-Host "Bạn có chắc chắn muốn tiếp tục? (yes/no)"

if ($confirm -ne "yes") {
    Write-Host "Đã hủy restore." -ForegroundColor Yellow
    exit 0
}

Write-Host "Đang restore database từ: $BackupFile" -ForegroundColor Yellow

Get-Content $BackupFile | docker exec -i online_learning_mysql mysql -u root -prootpassword online_learning

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Restore thành công!" -ForegroundColor Green
} else {
    Write-Host "❌ Restore thất bại!" -ForegroundColor Red
    exit 1
}

