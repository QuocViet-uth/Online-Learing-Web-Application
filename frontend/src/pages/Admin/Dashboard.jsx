import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiUsers, FiBook, FiDollarSign, FiTrendingUp, FiArrowRight, FiImage } from 'react-icons/fi'
import { statsAPI } from '../../services/api'
import toast from 'react-hot-toast'

const AdminDashboard = () => {
  const [stats, setStats] = useState({
    totalUsers: 0,
    totalCourses: 0,
    totalRevenue: 0,
    growth: 0,
    totalTeachers: 0,
    totalStudents: 0,
    totalEnrollments: 0,
  })
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadStats()
  }, [])

  const loadStats = async () => {
    try {
      setLoading(true)
      // Load real data from stats API
      const response = await statsAPI.getAll()
      
      if (response && response.success && response.data) {
        const data = response.data
        setStats({
          totalUsers: data.total_users || 0,
          totalCourses: data.total_courses || 0,
          totalRevenue: data.total_revenue || 0,
          growth: data.growth || 0,
          totalTeachers: data.total_teachers || 0,
          totalStudents: data.total_students || 0,
          totalEnrollments: data.total_enrollments || 0,
        })
      } else {
        // Fallback to default values if API fails
        setStats({
          totalUsers: 0,
          totalCourses: 0,
          totalRevenue: 0,
          growth: 0,
          totalTeachers: 0,
          totalStudents: 0,
          totalEnrollments: 0,
        })
      }
    } catch (error) {
      console.error('Error loading stats:', error)
      toast.error('Không thể tải thống kê')
      // Set default values on error
      setStats({
        totalUsers: 0,
        totalCourses: 0,
        totalRevenue: 0,
        growth: 0,
        totalTeachers: 0,
        totalStudents: 0,
        totalEnrollments: 0,
      })
    } finally {
      setLoading(false)
    }
  }

  const statCards = [
    {
      title: 'Tổng người dùng',
      value: stats.totalUsers.toLocaleString('vi-VN'),
      icon: FiUsers,
      color: 'primary',
      link: '/admin/users',
    },
    {
      title: 'Tổng khóa học',
      value: stats.totalCourses.toLocaleString('vi-VN'),
      icon: FiBook,
      color: 'secondary',
      link: '/admin/courses',
    },
    {
      title: 'Doanh thu',
      value: new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
      }).format(stats.totalRevenue),
      icon: FiDollarSign,
      color: 'green',
      link: '/admin/payments',
    },
    {
      title: 'Tăng trưởng',
      value: `${stats.growth >= 0 ? '+' : ''}${stats.growth.toFixed(1)}%`,
      icon: FiTrendingUp,
      color: stats.growth >= 0 ? 'green' : 'red',
      link: '#',
    },
  ]

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-6 sm:py-8 px-3 sm:px-4">
        <div className="mb-6 sm:mb-8">
          <h1 className="text-2xl sm:text-3xl font-bold mb-1 sm:mb-2">Dashboard Quản trị</h1>
          <p className="text-sm sm:text-base text-gray-600">Tổng quan hệ thống</p>
        </div>

        {/* Stats Grid */}
        {loading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="card animate-pulse">
                <div className="h-20 bg-gray-200 rounded"></div>
              </div>
            ))}
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {statCards.map((stat, index) => (
              <motion.div
                key={stat.title}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: index * 0.1 }}
              >
                <Link to={stat.link} className="card card-hover block">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-gray-600 text-sm mb-1">{stat.title}</p>
                      <p className="text-2xl font-bold">{stat.value}</p>
                    </div>
                    <div className={`w-12 h-12 rounded-lg bg-${stat.color}-100 flex items-center justify-center`}>
                      <stat.icon className={`w-6 h-6 text-${stat.color}-600`} />
                    </div>
                  </div>
                </Link>
              </motion.div>
            ))}
          </div>
        )}

        {/* Quick Actions */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="card"
          >
            <h2 className="text-xl font-semibold mb-4">Thao tác nhanh</h2>
            <div className="space-y-2">
              <Link
                to="/admin/users"
                className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <span>Quản lý người dùng</span>
                <FiArrowRight className="text-gray-400" />
              </Link>
              <Link
                to="/admin/courses"
                className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <span>Quản lý khóa học</span>
                <FiArrowRight className="text-gray-400" />
              </Link>
              <Link
                to="/admin/payments"
                className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <span>Quản lý thanh toán</span>
                <FiArrowRight className="text-gray-400" />
              </Link>
              <Link
                to="/admin/coupons"
                className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <span>Quản lý mã giảm giá</span>
                <FiArrowRight className="text-gray-400" />
              </Link>
              <Link
                to="/admin/payment-qr-codes"
                className="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <span className="flex items-center">
                  <FiImage className="mr-2" />
                  Quản lý mã QR thanh toán
                </span>
                <FiArrowRight className="text-gray-400" />
              </Link>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="card"
          >
            <h2 className="text-xl font-semibold mb-4">Thông tin bổ sung</h2>
            <div className="space-y-3">
              {loading ? (
                <div className="space-y-2">
                  <div className="h-4 bg-gray-200 rounded animate-pulse"></div>
                  <div className="h-4 bg-gray-200 rounded animate-pulse"></div>
                </div>
              ) : (
                <>
                  <div className="flex justify-between items-center py-2 border-b border-gray-100">
                    <span className="text-gray-600">Tổng giảng viên:</span>
                    <span className="font-semibold">{stats.totalTeachers || 0}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b border-gray-100">
                    <span className="text-gray-600">Tổng học viên:</span>
                    <span className="font-semibold">{stats.totalStudents || 0}</span>
                  </div>
                  <div className="flex justify-between items-center py-2 border-b border-gray-100">
                    <span className="text-gray-600">Đăng ký khóa học:</span>
                    <span className="font-semibold">{stats.totalEnrollments || 0}</span>
                  </div>
                </>
              )}
            </div>
          </motion.div>
        </div>
      </div>
    </div>
  )
}

export default AdminDashboard

