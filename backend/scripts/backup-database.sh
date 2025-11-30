#!/bin/bash
# Script backup database
# Usage: ./backup-database.sh [backup_name]

BACKUP_NAME=${1:-"backup_$(date +%Y%m%d_%H%M%S)"}
BACKUP_DIR="./backend/database/backups"
BACKUP_FILE="$BACKUP_DIR/$BACKUP_NAME.sql"

# Tạo thư mục backup nếu chưa tồn tại
mkdir -p "$BACKUP_DIR"

echo "Đang backup database..."
docker exec online_learning_mysql mysqldump -u root -prootpassword online_learning > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✅ Backup thành công: $BACKUP_FILE"
    echo "Kích thước file: $(du -h "$BACKUP_FILE" | cut -f1)"
else
    echo "❌ Backup thất bại!"
    exit 1
fi

