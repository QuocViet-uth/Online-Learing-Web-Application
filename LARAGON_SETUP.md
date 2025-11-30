# Hướng dẫn cấu hình LearningWeb trên Laragon

## 1. Tạo Virtual Host trong Laragon

Laragon tự động tạo domain ảo theo tên thư mục. Domain của bạn sẽ là: **learningweb.test**

### Kiểm tra/Tạo Virtual Host:
1. Mở Laragon
2. Click chuột phải vào Laragon > Apache > Sites Directory
3. Đảm bảo thư mục `learningweb` có trong `C:\laragon\www\`
4. Click chuột phải vào Laragon > Apache > Restart All Services

Domain sẽ được tạo tự động: `http://learningweb.test`

## 2. Cấu hình Backend

### Tạo file .env cho Backend:
```bash
cd c:\laragon\www\learningweb\backend
copy env.example .env
```

### Chỉnh sửa file backend\.env:
```env
DB_PATH=online_learning.db
TZ=Asia/Ho_Chi_Minh

# Cập nhật URL callback cho payment gateway
VNPAY_RETURN_URL=http://learningweb.test/api/payment-callback/vnpay
MOMO_RETURN_URL=http://learningweb.test/api/payment-callback/momo
MOMO_NOTIFY_URL=http://learningweb.test/api/payment-callback/momo-notify

# Cập nhật Google OAuth redirect
GOOGLE_REDIRECT_URI=http://learningweb.test
```

### Khởi tạo Database:
```bash
cd c:\laragon\www\learningweb\backend
php database/init_sqlite.php
```

## 3. Cấu hình Frontend

### Tạo file .env cho Frontend:
```bash
cd c:\laragon\www\learningweb\frontend
copy env.example .env
```

### Chỉnh sửa file frontend\.env:
```env
VITE_API_URL=http://learningweb.test
NODE_ENV=production
```

### Build Frontend:
```bash
cd c:\laragon\www\learningweb\frontend
npm install
npm run build
```

## 4. Cấu trúc thư mục sau khi setup:

```
c:\laragon\www\learningweb\
├── .htaccess                    (đã tạo - routing chính)
├── backend/
│   ├── .htaccess               (đã tạo - routing API)
│   ├── .env                    (tạo từ env.example)
│   ├── online_learning.db      (tạo bằng init_sqlite.php)
│   └── ...
└── frontend/
    ├── .env                    (tạo từ env.example)
    ├── dist/                   (tạo bằng npm run build)
    └── ...
```

## 5. Truy cập website:

- **Frontend**: http://learningweb.test
- **API**: http://learningweb.test/api/...
- **Test API**: http://learningweb.test/api/get-stats

## 6. Troubleshooting:

### Nếu gặp lỗi 404:
1. Kiểm tra Apache Rewrite Module đã bật trong Laragon
2. Click chuột phải Laragon > Apache > httpd.conf
3. Tìm và bỏ comment (xóa #) dòng: `LoadModule rewrite_module modules/mod_rewrite.so`
4. Restart Apache

### Nếu frontend không load:
1. Đảm bảo đã chạy `npm run build` trong thư mục frontend
2. Kiểm tra thư mục `frontend/dist` đã có file build

### Nếu API không hoạt động:
1. Kiểm tra file `backend/.env` đã được tạo
2. Kiểm tra database đã được khởi tạo: `backend/online_learning.db`
3. Kiểm tra PHP extensions cần thiết: `php backend/check-php-extensions.php`

## 7. Development Mode (Optional):

Nếu muốn chạy frontend ở development mode:
```bash
cd frontend
npm run dev
```
Frontend sẽ chạy trên http://localhost:3000 với hot reload.

**Lưu ý**: Khi chạy dev mode, cập nhật `frontend/.env`:
```env
VITE_API_URL=http://learningweb.test
NODE_ENV=development
```
