# PowerShell script backup database
# Usage: .\backup-database.ps1 [backup_name]

param(
    [string]$BackupName = "backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
)

$BackupDir = ".\backend\database\backups"
$BackupFile = Join-Path $BackupDir "$BackupName.sql"

# Tạo thư mục backup nếu chưa tồn tại
if (-not (Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
}

Write-Host "Đang backup database..." -ForegroundColor Yellow

docker exec online_learning_mysql mysqldump -u root -prootpassword online_learning | Out-File -FilePath $BackupFile -Encoding UTF8

if ($LASTEXITCODE -eq 0) {
    $fileSize = (Get-Item $BackupFile).Length / 1KB
    Write-Host "✅ Backup thành công: $BackupFile" -ForegroundColor Green
    Write-Host "Kích thước file: $([math]::Round($fileSize, 2)) KB" -ForegroundColor Cyan
} else {
    Write-Host "❌ Backup thất bại!" -ForegroundColor Red
    exit 1
}

