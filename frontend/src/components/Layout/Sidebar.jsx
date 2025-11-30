import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'
import { motion } from 'framer-motion'
import {
  FiHome,
  FiBook,
  FiFileText,
  FiTrendingUp,
  FiUsers,
  FiDollarSign,
  FiTag,
  FiMaximize2,
  FiMessageCircle,
  FiSettings,
  FiBarChart2,
  FiCheckCircle
} from 'react-icons/fi'

const Sidebar = ({ onNavigate }) => {
  const { user } = useAuth()
  const location = useLocation()

  const isActive = (path) => {
    return location.pathname === path || location.pathname.startsWith(path + '/')
  }

  const handleNavigate = () => {
    if (onNavigate) {
      onNavigate()
    }
  }

  const studentMenu = [
    { path: '/student', label: 'Dashboard', icon: FiHome },
    { path: '/student/courses', label: 'Khóa học của tôi', icon: FiBook },
    { path: '/student/assignments', label: 'Bài tập', icon: FiFileText },
    { path: '/student/grades', label: 'Điểm số', icon: FiTrendingUp },
    { path: '/student/progress', label: 'Tiến độ học tập', icon: FiBarChart2 },
  ]

  const teacherMenu = [
    { path: '/teacher', label: 'Dashboard', icon: FiHome },
    { path: '/teacher/courses', label: 'Khóa học của tôi', icon: FiBook },
    { path: '/teacher/assignments', label: 'Bài tập', icon: FiFileText },
    { path: '/teacher/grades', label: 'Chấm điểm', icon: FiCheckCircle },
  ]

  const adminMenu = [
    { path: '/admin', label: 'Dashboard', icon: FiHome },
    { path: '/admin/users', label: 'Người dùng', icon: FiUsers },
    { path: '/admin/courses', label: 'Khóa học', icon: FiBook },
    { path: '/admin/payments', label: 'Thanh toán', icon: FiDollarSign },
    { path: '/admin/coupons', label: 'Mã giảm giá', icon: FiTag },
    { path: '/admin/payment-qr-codes', label: 'QR Code', icon: FiMaximize2 },
  ]

  const getMenu = () => {
    if (user?.role === 'student') return studentMenu
    if (user?.role === 'teacher') return teacherMenu
    if (user?.role === 'admin') return adminMenu
    return []
  }

  const menu = getMenu()

  if (!user || menu.length === 0) return null

  return (
    <>
      {/* Desktop Sidebar */}
      <aside className="hidden lg:block w-64 flex-shrink-0">
        <div className="sticky top-20 h-[calc(100vh-5rem)] overflow-y-auto custom-scrollbar">
          <nav className="p-4 space-y-1">
            {menu.map((item) => {
              const Icon = item.icon
              const active = isActive(item.path)
              
              return (
                <Link
                  key={item.path}
                  to={item.path}
                  onClick={handleNavigate}
                  className={`flex items-center space-x-3 px-4 py-3 rounded-lg transition-all ${
                    active
                      ? 'bg-primary-50 text-primary-700 font-medium border-l-4 border-primary-600'
                      : 'text-gray-700 hover:bg-gray-100 hover:text-primary-600'
                  }`}
                >
                  <Icon className={`w-5 h-5 ${active ? 'text-primary-600' : 'text-gray-500'}`} />
                  <span className="text-sm">{item.label}</span>
                </Link>
              )
            })}
            
            <div className="pt-4 mt-4 border-t border-gray-200">
              <Link
                to="/profile"
                onClick={handleNavigate}
                className={`flex items-center space-x-3 px-4 py-3 rounded-lg transition-all ${
                  isActive('/profile')
                    ? 'bg-primary-50 text-primary-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-primary-600'
                }`}
              >
                <FiSettings className={`w-5 h-5 ${isActive('/profile') ? 'text-primary-600' : 'text-gray-500'}`} />
                <span className="text-sm">Cài đặt</span>
              </Link>
              <Link
                to="/chat"
                onClick={handleNavigate}
                className={`flex items-center space-x-3 px-4 py-3 rounded-lg transition-all ${
                  isActive('/chat')
                    ? 'bg-primary-50 text-primary-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-primary-600'
                }`}
              >
                <FiMessageCircle className={`w-5 h-5 ${isActive('/chat') ? 'text-primary-600' : 'text-gray-500'}`} />
                <span className="text-sm">Chat</span>
              </Link>
            </div>
          </nav>
        </div>
      </aside>

      {/* Mobile Sidebar */}
      <aside className="lg:hidden w-full">
        <div className="h-full overflow-y-auto custom-scrollbar">
          <nav className="p-4 space-y-1">
            {menu.map((item) => {
              const Icon = item.icon
              const active = isActive(item.path)
              
              return (
                <Link
                  key={item.path}
                  to={item.path}
                  onClick={handleNavigate}
                  className={`flex items-center space-x-3 px-4 py-3 rounded-lg transition-all ${
                    active
                      ? 'bg-primary-50 text-primary-700 font-medium border-l-4 border-primary-600'
                      : 'text-gray-700 hover:bg-gray-100 hover:text-primary-600'
                  }`}
                >
                  <Icon className={`w-5 h-5 ${active ? 'text-primary-600' : 'text-gray-500'}`} />
                  <span className="text-sm">{item.label}</span>
                </Link>
              )
            })}
            
            <div className="pt-4 mt-4 border-t border-gray-200">
              <Link
                to="/profile"
                onClick={handleNavigate}
                className={`flex items-center space-x-3 px-4 py-3 rounded-lg transition-all ${
                  isActive('/profile')
                    ? 'bg-primary-50 text-primary-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-primary-600'
                }`}
              >
                <FiSettings className={`w-5 h-5 ${isActive('/profile') ? 'text-primary-600' : 'text-gray-500'}`} />
                <span className="text-sm">Cài đặt</span>
              </Link>
              <Link
                to="/chat"
                onClick={handleNavigate}
                className={`flex items-center space-x-3 px-4 py-3 rounded-lg transition-all ${
                  isActive('/chat')
                    ? 'bg-primary-50 text-primary-700 font-medium'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-primary-600'
                }`}
              >
                <FiMessageCircle className={`w-5 h-5 ${isActive('/chat') ? 'text-primary-600' : 'text-gray-500'}`} />
                <span className="text-sm">Chat</span>
              </Link>
            </div>
          </nav>
        </div>
      </aside>
    </>
  )
}

export default Sidebar

