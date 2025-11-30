# API Documentation - LearningWeb

Base URL: `http://learningweb.test/api`

## 🔐 Authentication APIs

### 1. Login
- **Endpoint:** `/auth/login.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "username": "admin",
    "password": "admin123"
  }
  ```

### 2. Logout
- **Endpoint:** `/auth/logout.php`
- **Method:** POST

### 3. Register
- **Endpoint:** `/auth/register.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "username": "newuser",
    "password": "password123",
    "email": "user@example.com",
    "full_name": "User Name",
    "role": "student"
  }
  ```

### 4. Google OAuth
- **Endpoint:** `/auth/google.php`
- **Method:** GET

---

## 👤 User Management APIs

### 5. Get All Users
- **Endpoint:** `/users.php`
- **Method:** GET
- **Test:** `curl http://learningweb.test/api/users.php`

### 6. Get User by ID
- **Endpoint:** `/users.php?id={id}`
- **Method:** GET
- **Test:** `curl http://learningweb.test/api/users.php?id=1`

### 7. Get Current User
- **Endpoint:** `/get-current-user.php`
- **Method:** GET
- **Test:** `curl http://learningweb.test/api/get-current-user.php`

### 8. Create User
- **Endpoint:** `/create-user.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "username": "newuser",
    "password": "password",
    "email": "email@example.com",
    "full_name": "Full Name",
    "role": "student"
  }
  ```

### 9. Update User
- **Endpoint:** `/update-user.php`
- **Method:** PUT
- **Body:**
  ```json
  {
    "id": 1,
    "full_name": "Updated Name",
    "email": "newemail@example.com"
  }
  ```

### 10. Update User Full Name
- **Endpoint:** `/update-user-fullname.php`
- **Method:** PUT

### 11. Delete User
- **Endpoint:** `/delete-user.php`
- **Method:** DELETE
- **Body:** `{ "id": 1 }`

### 12. Upload Avatar
- **Endpoint:** `/upload-avatar.php`
- **Method:** POST
- **Content-Type:** multipart/form-data

---

## 📚 Course Management APIs

### 13. Get All Courses
- **Endpoint:** `/get-courses.php`
- **Method:** GET
- **Test:** `curl http://learningweb.test/api/get-courses.php`

### 14. Create Course
- **Endpoint:** `/create-course.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "course_name": "Course Name",
    "title": "Course Title",
    "description": "Description",
    "price": 100000,
    "teacher_id": 2,
    "start_date": "2025-01-01",
    "end_date": "2025-06-30"
  }
  ```

### 15. Update Course
- **Endpoint:** `/update-course.php`
- **Method:** PUT

### 16. Update Course Status
- **Endpoint:** `/update-course-status.php`
- **Method:** PUT
- **Body:**
  ```json
  {
    "id": 1,
    "status": "active"
  }
  ```

### 17. Delete Course
- **Endpoint:** `/delete-course.php`
- **Method:** DELETE

### 18. Get Course Performance
- **Endpoint:** `/get-course-performance.php?course_id={id}`
- **Method:** GET

---

## 📖 Lesson Management APIs

### 19. Get Lessons
- **Endpoint:** `/get-lessons.php?course_id={id}`
- **Method:** GET
- **Test:** `curl http://learningweb.test/api/get-lessons.php?course_id=1`

### 20. Create Lesson
- **Endpoint:** `/create-lesson.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "course_id": 1,
    "title": "Lesson Title",
    "content": "Lesson content",
    "lesson_order": 1
  }
  ```

### 21. Update Lesson
- **Endpoint:** `/update-lesson.php`
- **Method:** PUT

### 22. Delete Lesson
- **Endpoint:** `/delete-lesson.php`
- **Method:** DELETE

### 23. Mark Lesson Complete
- **Endpoint:** `/mark-lesson-complete.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "lesson_id": 1
  }
  ```

---

## 📝 Assignment Management APIs

### 24. Get Assignments
- **Endpoint:** `/assignments.php?course_id={id}`
- **Method:** GET

### 25. Create Assignment
- **Endpoint:** `/create-assignment.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "course_id": 1,
    "title": "Assignment Title",
    "description": "Description",
    "due_date": "2025-12-31",
    "max_score": 100
  }
  ```

### 26. Update Assignment
- **Endpoint:** `/update-assignment.php`
- **Method:** PUT

### 27. Delete Assignment
- **Endpoint:** `/delete-assignment.php`
- **Method:** DELETE

### 28. Submit Assignment
- **Endpoint:** `/submit-assignment.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "assignment_id": 1,
    "submission_text": "My submission"
  }
  ```

### 29. Get Assignment Submissions
- **Endpoint:** `/get-assignment-submissions.php?assignment_id={id}`
- **Method:** GET

### 30. Get My Submission
- **Endpoint:** `/get-my-submission.php?assignment_id={id}`
- **Method:** GET

### 31. Grade Submission
- **Endpoint:** `/grade-submission.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "submission_id": 1,
    "score": 85,
    "feedback": "Good work"
  }
  ```

---

## 🎯 Quiz APIs

### 32. Get Quiz Questions
- **Endpoint:** `/get-quiz-questions.php?quiz_id={id}`
- **Method:** GET

### 33. Submit Quiz
- **Endpoint:** `/submit-quiz.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "quiz_id": 1,
    "answers": [
      {"question_id": 1, "answer": "A"},
      {"question_id": 2, "answer": "B"}
    ]
  }
  ```

### 34. Get My Quiz Submission
- **Endpoint:** `/get-my-quiz-submission.php?quiz_id={id}`
- **Method:** GET

---

## 📊 Enrollment APIs

### 35. Enroll in Course
- **Endpoint:** `/enrollments/enroll.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "course_id": 1
  }
  ```

### 36. Get My Enrollments
- **Endpoint:** `/enrollments/my-enrollments.php`
- **Method:** GET

### 37. Get Student Progress
- **Endpoint:** `/get-student-progress.php?course_id={id}`
- **Method:** GET

### 38. Get Student Course Performance
- **Endpoint:** `/get-student-course-performance.php?course_id={id}`
- **Method:** GET

---

## 💰 Payment APIs

### 39. Get Payments
- **Endpoint:** `/payments.php`
- **Method:** GET

### 40. Create Payment
- **Endpoint:** `/create-payment.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "course_id": 1,
    "amount": 100000,
    "payment_method": "vnpay"
  }
  ```

### 41. Confirm Payment
- **Endpoint:** `/confirm-payment.php`
- **Method:** POST

### 42. Cancel Payment
- **Endpoint:** `/cancel-payment.php`
- **Method:** POST

### 43. Delete Payment
- **Endpoint:** `/delete-payment.php`
- **Method:** DELETE

### 44. Payment Callback - VNPay
- **Endpoint:** `/payment-callback/vnpay.php`
- **Method:** GET

### 45. Payment Callback - MoMo
- **Endpoint:** `/payment-callback/momo.php`
- **Method:** POST

### 46. Payment QR Codes
- **Endpoint:** `/payment-qr-codes.php`
- **Method:** GET

---

## 🎟️ Coupon APIs

### 47. Get Coupons
- **Endpoint:** `/coupons.php`
- **Method:** GET

### 48. Create Coupon
- **Endpoint:** `/create-coupon.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "code": "DISCOUNT20",
    "discount_percentage": 20,
    "valid_from": "2025-01-01",
    "valid_to": "2025-12-31",
    "max_uses": 100
  }
  ```

### 49. Update Coupon
- **Endpoint:** `/update-coupon.php`
- **Method:** PUT

### 50. Delete Coupon
- **Endpoint:** `/delete-coupon.php`
- **Method:** DELETE

---

## 💬 Chat APIs

### 51. Get Chats
- **Endpoint:** `/chat.php?course_id={id}`
- **Method:** GET

### 52. Send Message
- **Endpoint:** `/chat.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "course_id": 1,
    "message": "Hello!"
  }
  ```

### 53. Get Teacher Course Chats
- **Endpoint:** `/get-teacher-course-chats.php?course_id={id}`
- **Method:** GET

---

## 🔔 Notification APIs

### 54. Get Notifications
- **Endpoint:** `/get-notifications.php`
- **Method:** GET

### 55. Create Notification
- **Endpoint:** `/create-notification.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "user_id": 1,
    "title": "New Message",
    "message": "You have a new message",
    "type": "info"
  }
  ```

### 56. Mark Notification Read
- **Endpoint:** `/mark-notification-read.php`
- **Method:** PUT
- **Body:** `{ "id": 1 }`

### 57. Mark All Notifications Read
- **Endpoint:** `/mark-all-notifications-read.php`
- **Method:** PUT

---

## ⭐ Review APIs

### 58. Get Reviews
- **Endpoint:** `/reviews.php?course_id={id}`
- **Method:** GET

### 59. Create Review
- **Endpoint:** `/reviews.php`
- **Method:** POST
- **Body:**
  ```json
  {
    "course_id": 1,
    "rating": 5,
    "comment": "Excellent course!"
  }
  ```

---

## 📈 Statistics & Dashboard APIs

### 60. Get Stats (Admin)
- **Endpoint:** `/get-stats.php`
- **Method:** GET
- **Test:** `curl http://learningweb.test/api/get-stats.php`

### 61. Get Teacher Dashboard Stats
- **Endpoint:** `/get-teacher-dashboard-stats.php`
- **Method:** GET

### 62. Get Student Dashboard Stats
- **Endpoint:** `/get-student-dashboard-stats.php`
- **Method:** GET

### 63. Get Grades
- **Endpoint:** `/grades.php?course_id={id}`
- **Method:** GET

---

## 📁 File Upload APIs

### 64. Upload File
- **Endpoint:** `/upload-file.php`
- **Method:** POST
- **Content-Type:** multipart/form-data
- **Body:** Form data with `file` field

---

## 🧪 Quick Test Commands

```powershell
# Test API connection
curl http://learningweb.test/api/get-stats.php

# Get all users
curl http://learningweb.test/api/users.php

# Get all courses
curl http://learningweb.test/api/get-courses.php

# Login
curl -X POST http://learningweb.test/api/auth/login.php -H "Content-Type: application/json" -d '{\"username\":\"admin\",\"password\":\"admin123\"}'
```

---

## 📝 Default Users

| Username | Password | Role | Email |
|----------|----------|------|-------|
| admin | admin123 | admin | admin@example.com |
| teacher1 | teacher123 | teacher | teacher@example.com |
| student1 | student123 | student | student@example.com |

---

## 🔧 Common Response Format

**Success:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error description"
}
```

---

## 📌 Notes

- Tất cả API trả về JSON
- Một số API yêu cầu authentication (session-based)
- File uploads sử dụng multipart/form-data
- Dates sử dụng format: `YYYY-MM-DD`
- DateTime sử dụng format: `YYYY-MM-DD HH:MM:SS`
