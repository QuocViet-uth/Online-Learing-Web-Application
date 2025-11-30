# HƯỚNG DẪN DEPLOY MIỄN PHÍ

## Stack deploy:
- **Frontend**: Vercel (miễn phí unlimited)
- **Backend + Database**: Railway (free $5 credit/tháng)

---

## BƯỚC 1: Push code lên GitHub

### 1.1. Tạo repository mới trên GitHub
- Vào https://github.com/new
- Tên repo: `learningweb`
- Chọn **Private** (để bảo mật)
- Không tick "Initialize with README"

### 1.2. Push code từ máy local
```powershell
cd C:\laragon\www\learningweb

# Khởi tạo git (nếu chưa có)
git init

# Add remote
git remote add origin https://github.com/YOUR_USERNAME/learningweb.git

# Add files
git add .

# Commit
git commit -m "Initial commit for deployment"

# Push
git branch -M main
git push -u origin main
```

---

## BƯỚC 2: Deploy Backend + Database lên Railway

### 2.1. Đăng ký Railway
- Vào https://railway.app
- Click "Start a New Project"
- Login bằng GitHub

### 2.2. Tạo MySQL Database
- Click "+ New"
- Chọn "Database" → "Add MySQL"
- Railway sẽ tự động tạo database

### 2.3. Deploy Backend
- Click "+ New" 
- Chọn "GitHub Repo"
- Chọn repository `learningweb`
- Railway sẽ tự động detect và build

### 2.4. Thêm Environment Variables
Click vào service backend → "Variables" → Add:

```env
DB_HOST=<từ MySQL service>
DB_NAME=railway
DB_USER=root
DB_PASSWORD=<từ MySQL service>
DB_PORT=3306

# Google OAuth (nếu có)
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=https://your-backend.railway.app/api/auth/google/callback

# Payment Gateway
PAYMENT_API_KEY=your_payment_key
PAYMENT_SECRET=your_payment_secret
```

**Lấy thông tin MySQL:**
- Click vào MySQL service
- Tab "Connect"
- Copy: MYSQLHOST, MYSQLUSER, MYSQLPASSWORD

### 2.5. Import Database
```powershell
# Từ máy local, connect tới Railway MySQL
mysql -h <RAILWAY_MYSQL_HOST> -u root -p<PASSWORD> railway < backend/database/database_init.sql
```

### 2.6. Lấy Backend URL
- Click vào backend service
- Tab "Settings" → "Generate Domain"
- Copy URL: `https://learningweb-production.up.railway.app`

---

## BƯỚC 3: Deploy Frontend lên Vercel

### 3.1. Cập nhật API URL trong Frontend
Sửa file `frontend/src/config.js` (hoặc nơi định nghĩa API_URL):

```javascript
const API_URL = import.meta.env.VITE_API_URL || 'https://learningweb-production.up.railway.app';
export default API_URL;
```

### 3.2. Deploy lên Vercel
- Vào https://vercel.com
- Click "Add New" → "Project"
- Import repository `learningweb`
- Configure:
  - **Framework Preset**: Vite
  - **Root Directory**: `frontend`
  - **Build Command**: `npm run build`
  - **Output Directory**: `dist`
  
### 3.3. Thêm Environment Variables
Settings → Environment Variables:

```env
VITE_API_URL=https://learningweb-production.up.railway.app
```

### 3.4. Deploy
- Click "Deploy"
- Đợi 2-3 phút
- Copy URL: `https://learningweb.vercel.app`

---

## BƯỚC 4: Cấu hình CORS

Sửa `backend/config/headers.php`:

```php
$allowed_origins = [
    'http://localhost:5173',
    'http://learningweb.test',
    'https://learningweb.vercel.app',  // Thêm domain Vercel
];
```

Push code:
```powershell
git add .
git commit -m "Update CORS for production"
git push
```

Railway sẽ tự động redeploy.

---

## BƯỚC 5: Test Production

### 5.1. Test Backend
```powershell
curl https://learningweb-production.up.railway.app/api/get-courses.php
```

### 5.2. Test Frontend
- Mở: `https://learningweb.vercel.app`
- Login với: `admin` / `admin123`
- Kiểm tra các chức năng

---

## CHI PHÍ & GIỚI HẠN

### Railway (Free Tier)
- ✅ **$5 credit/tháng** (đủ chạy 24/7)
- ✅ 512MB RAM
- ✅ 1GB Disk
- ⚠️ Hết credit thì service sleep

### Vercel (Free)
- ✅ **Unlimited** bandwidth
- ✅ Unlimited sites
- ✅ Automatic HTTPS
- ✅ Global CDN

---

## XỬ LÝ KHI HẾT RAILWAY CREDIT

**Option 1: Thêm credit card**
- Railway cho thêm $5/tháng miễn phí khi verify card

**Option 2: Deploy backend lên Render**
- Render free tier: 750 giờ/tháng
- Tương tự Railway nhưng khác platform

**Option 3: Optimize để tiết kiệm**
- Giảm dyno size
- Enable sleep khi không dùng
- Dùng Railway credits hiệu quả hơn

---

## CẬP NHẬT CODE SAU KHI DEPLOY

### Cập nhật Frontend:
```powershell
cd C:\laragon\www\learningweb
git add .
git commit -m "Update frontend"
git push
```
Vercel tự động build & deploy trong 1 phút.

### Cập nhật Backend:
```powershell
git add .
git commit -m "Update backend"
git push
```
Railway tự động deploy trong 2-3 phút.

---

## TÓM TẮT NHANH

1. **Push code lên GitHub**
2. **Railway**: Tạo MySQL → Deploy backend → Add env vars → Import database
3. **Vercel**: Import repo → Set root=frontend → Add VITE_API_URL → Deploy
4. **Update CORS** trong backend/config/headers.php
5. **Test** trên production URLs

**Thời gian:** ~30 phút
**Chi phí:** $0/tháng (trong giới hạn free tier)

---

## HỖ TRỢ

Nếu gặp lỗi:
1. Check Railway logs: Service → Deployments → Click build → View logs
2. Check Vercel logs: Deployments → Click build → View Function Logs
3. Check browser console: F12 → Console → Network tab
