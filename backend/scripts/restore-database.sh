#!/bin/bash
# Script restore database
# Usage: ./restore-database.sh <backup_file.sql>

if [ -z "$1" ]; then
    echo "❌ Vui lòng chỉ định file backup!"
    echo "Usage: ./restore-database.sh <backup_file.sql>"
    exit 1
fi

BACKUP_FILE="$1"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ File backup không tồn tại: $BACKUP_FILE"
    exit 1
fi

echo "⚠️  CẢNH BÁO: Thao tác này sẽ GHI ĐÈ toàn bộ dữ liệu hiện tại!"
read -p "Bạn có chắc chắn muốn tiếp tục? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Đã hủy restore."
    exit 0
fi

echo "Đang restore database từ: $BACKUP_FILE"
docker exec -i online_learning_mysql mysql -u root -prootpassword online_learning < "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✅ Restore thành công!"
else
    echo "❌ Restore thất bại!"
    exit 1
fi

