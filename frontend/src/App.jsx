import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuth } from './contexts/AuthContext'
import Layout from './components/Layout/Layout'
import DashboardLayout from './components/Layout/DashboardLayout'
import Home from './pages/Home'
import Login from './pages/Auth/Login'
import Register from './pages/Auth/Register'
import ForgotPassword from './pages/Auth/ForgotPassword'
import Courses from './pages/Courses'
import CourseDetail from './pages/CourseDetail'
import CoursePlayer from './pages/CoursePlayer'
import Checkout from './pages/Checkout'
import PaymentSuccess from './pages/PaymentSuccess'
import PaymentFailed from './pages/PaymentFailed'
import Dashboard from './pages/Dashboard'
import Profile from './pages/Profile'
import Chat from './pages/Chat'
import NotFound from './pages/NotFound'

// Admin routes
import AdminDashboard from './pages/Admin/Dashboard'
import AdminUsers from './pages/Admin/Users'
import AdminCourses from './pages/Admin/Courses'
import AdminCourseManage from './pages/Admin/CourseManage'
import AdminPayments from './pages/Admin/Payments'
import AdminCoupons from './pages/Admin/Coupons'
import AdminPaymentQRCodes from './pages/Admin/PaymentQRCodes'

// Teacher routes
import TeacherDashboard from './pages/Teacher/Dashboard'
import TeacherCourses from './pages/Teacher/Courses'
import TeacherCourseManage from './pages/Teacher/CourseManage'
import TeacherAssignments from './pages/Teacher/Assignments'
import AssignmentSubmissions from './pages/Teacher/AssignmentSubmissions'
import TeacherGrades from './pages/Teacher/Grades'

// Student routes
import StudentDashboard from './pages/Student/Dashboard'
import StudentCourses from './pages/Student/Courses'
import StudentAssignments from './pages/Student/Assignments'
import StudentGrades from './pages/Student/Grades'
import StudentProgress from './pages/Student/Progress'
import StudentCoursePerformance from './pages/Student/CoursePerformance'

// Protected Route Component
const ProtectedRoute = ({ children, allowedRoles = [] }) => {
  const { user, loading } = useAuth()

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="spinner"></div>
      </div>
    )
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
    return <Navigate to="/" replace />
  }

  return children
}

function App() {
  return (
    <Routes>
      {/* Public routes */}
      <Route path="/" element={<Layout />}>
        <Route index element={<Home />} />
        <Route path="courses" element={<Courses />} />
        <Route path="courses/:id" element={<CourseDetail />} />
        <Route path="courses/:id/learn" element={<CoursePlayer />} />
        <Route
          path="checkout/:id"
          element={
            <ProtectedRoute>
              <Checkout />
            </ProtectedRoute>
          }
        />
        <Route path="checkout/success" element={<PaymentSuccess />} />
        <Route path="checkout/failed" element={<PaymentFailed />} />
        <Route path="checkout/error" element={<PaymentFailed />} />
      </Route>

      {/* Auth routes */}
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route path="/forgot-password" element={<ForgotPassword />} />

      {/* Protected routes with Dashboard Layout */}
      <Route
        element={
          <ProtectedRoute>
            <DashboardLayout />
          </ProtectedRoute>
        }
      >
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/profile" element={<Profile />} />
        <Route path="/chat" element={<Chat />} />
      </Route>

      {/* Admin routes */}
      <Route
        element={
          <ProtectedRoute allowedRoles={['admin']}>
            <DashboardLayout />
          </ProtectedRoute>
        }
      >
        <Route path="/admin" element={<AdminDashboard />} />
        <Route path="/admin/users" element={<AdminUsers />} />
        <Route path="/admin/courses" element={<AdminCourses />} />
        <Route path="/admin/courses/:id/manage" element={<AdminCourseManage />} />
        <Route path="/admin/payments" element={<AdminPayments />} />
        <Route path="/admin/coupons" element={<AdminCoupons />} />
        <Route path="/admin/payment-qr-codes" element={<AdminPaymentQRCodes />} />
      </Route>

      {/* Teacher routes */}
      <Route
        element={
          <ProtectedRoute allowedRoles={['teacher']}>
            <DashboardLayout />
          </ProtectedRoute>
        }
      >
        <Route path="/teacher" element={<TeacherDashboard />} />
        <Route path="/teacher/courses" element={<TeacherCourses />} />
        <Route path="/teacher/courses/:id/manage" element={<TeacherCourseManage />} />
        <Route path="/teacher/assignments" element={<TeacherAssignments />} />
        <Route path="/teacher/assignments/:assignmentId/submissions" element={<AssignmentSubmissions />} />
        <Route path="/teacher/grades" element={<TeacherGrades />} />
      </Route>

      {/* Student routes */}
      <Route
        element={
          <ProtectedRoute allowedRoles={['student']}>
            <DashboardLayout />
          </ProtectedRoute>
        }
      >
        <Route path="/student" element={<StudentDashboard />} />
        <Route path="/student/courses" element={<StudentCourses />} />
        <Route path="/student/assignments" element={<StudentAssignments />} />
        <Route path="/student/grades" element={<StudentGrades />} />
        <Route path="/student/progress" element={<StudentProgress />} />
        <Route path="/student/courses/:id/performance" element={<StudentCoursePerformance />} />
      </Route>

      {/* 404 */}
      <Route path="*" element={<NotFound />} />
    </Routes>
  )
}

export default App

