# Hướng dẫn Backup và Restore Database

## Vấn đề: Dữ liệu bị mất khi restart

**Nguyên nhân:**
- File `database_schema.sql` có lệnh `DROP DATABASE` - sẽ xóa toàn bộ dữ liệu nếu chạy lại
- Nếu xóa Docker volume `mysql_data`, dữ liệu sẽ mất

**Giải pháp:**
1. ✅ Đã tạo `database_init.sql` - không có DROP DATABASE, chỉ tạo nếu chưa tồn tại
2. ✅ Đã cập nhật `docker-compose.yml` để dùng file mới
3. ✅ Tạo script backup/restore để bảo vệ dữ liệu

## Cách sử dụng

### 1. Backup Database

**Windows (PowerShell):**
```powershell
cd backend/scripts
.\backup-database.ps1
# Hoặc với tên tùy chỉnh:
.\backup-database.ps1 -BackupName "backup_before_update"
```

**Linux/Mac:**
```bash
cd backend/scripts
chmod +x backup-database.sh
./backup-database.sh
# Hoặc với tên tùy chỉnh:
./backup-database.sh backup_before_update
```

File backup sẽ được lưu tại: `backend/database/backups/`

### 2. Restore Database

**Windows (PowerShell):**
```powershell
cd backend/scripts
.\restore-database.ps1 -BackupFile "..\database\backups\backup_20241121_143000.sql"
```

**Linux/Mac:**
```bash
cd backend/scripts
./restore-database.sh ../database/backups/backup_20241121_143000.sql
```

### 3. Lưu ý quan trọng

⚠️ **KHÔNG XÓA VOLUME `mysql_data`:**
```bash
# ❌ KHÔNG chạy lệnh này nếu muốn giữ dữ liệu:
docker-compose down -v

# ✅ Chỉ restart container (giữ lại dữ liệu):
docker-compose restart mysql
# hoặc
docker-compose down
docker-compose up -d
```

⚠️ **KHÔNG chạy lại `database_schema.sql`** nếu đã có dữ liệu:
- File này có `DROP DATABASE` - sẽ xóa hết dữ liệu
- Chỉ dùng khi muốn reset hoàn toàn database

✅ **Dùng `database_init.sql`** cho lần đầu khởi tạo:
- File này chỉ tạo database/tables nếu chưa tồn tại
- An toàn khi restart container

## Khuyến nghị

1. **Backup thường xuyên** trước khi:
   - Cập nhật schema
   - Chạy migration scripts
   - Thử nghiệm tính năng mới

2. **Kiểm tra volume tồn tại:**
```bash
docker volume ls | grep mysql_data
```

3. **Xem thông tin volume:**
```bash
docker volume inspect online_learn_mysql_data
```

