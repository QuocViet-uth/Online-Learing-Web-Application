import { useState, useEffect } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiSearch, FiFilter, FiStar, FiUsers, FiClock, FiX, FiPlay } from 'react-icons/fi'
import { coursesAPI } from '../services/api'
import toast from 'react-hot-toast'

const Courses = () => {
  const [searchParams, setSearchParams] = useSearchParams()
  const [courses, setCourses] = useState([])
  const [loading, setLoading] = useState(true)
  const [searchQuery, setSearchQuery] = useState(searchParams.get('search') || '')
  const [filters, setFilters] = useState({
    status: searchParams.get('status') || '',
    teacher_id: searchParams.get('teacher_id') || '',
  })
  const [showFilters, setShowFilters] = useState(false)

  useEffect(() => {
    loadCourses()
  }, [filters, searchQuery])

  const loadCourses = async () => {
    try {
      setLoading(true)
      const params = { ...filters }
      if (searchQuery) {
        // Note: Backend doesn't have search endpoint yet
        // We'll filter client-side for now
      }
      const response = await coursesAPI.getAll(params)
      if (response.success && response.data) {
        let filteredCourses = response.data
        if (searchQuery) {
          filteredCourses = filteredCourses.filter(
            (course) =>
              course.course_name.toLowerCase().includes(searchQuery.toLowerCase()) ||
              course.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
              course.description?.toLowerCase().includes(searchQuery.toLowerCase())
          )
        }
        setCourses(filteredCourses)
      }
    } catch (error) {
      console.error('Error loading courses:', error)
      console.error('Error details:', error.response || error.message)
      const errorMessage = error.response?.data?.message || error.message || 'Không thể tải danh sách khóa học'
      toast.error(errorMessage)
      setCourses([])
    } finally {
      setLoading(false)
    }
  }

  const handleFilterChange = (key, value) => {
    setFilters({ ...filters, [key]: value })
    const newParams = new URLSearchParams(searchParams)
    if (value) {
      newParams.set(key, value)
    } else {
      newParams.delete(key)
    }
    setSearchParams(newParams)
  }

  const clearFilters = () => {
    setFilters({ status: '', teacher_id: '' })
    setSearchQuery('')
    setSearchParams({})
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
    }).format(price)
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        {/* Header */}
        <div className="mb-6 sm:mb-8">
          <h1 className="text-2xl sm:text-3xl md:text-4xl font-bold mb-1 sm:mb-2">Tất cả khóa học</h1>
          <p className="text-sm sm:text-base text-gray-600">Khám phá {courses.length} khóa học có sẵn</p>
        </div>

        {/* Search and Filter Bar */}
        <div className="bg-white rounded-xl shadow-sm p-3 sm:p-4 md:p-6 mb-6 sm:mb-8">
          <div className="flex flex-col md:flex-row gap-3 sm:gap-4">
            {/* Search */}
            <div className="flex-1 relative">
              <FiSearch className="absolute left-2 sm:left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4 sm:w-5 sm:h-5" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Tìm kiếm khóa học..."
                className="w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>

            {/* Filter Button (Mobile) */}
            <button
              onClick={() => setShowFilters(!showFilters)}
              className="md:hidden btn btn-outline flex items-center justify-center"
            >
              <FiFilter className="mr-2" />
              Bộ lọc
            </button>

            {/* Filters (Desktop) */}
            <div className="hidden md:flex gap-4">
              <select
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                className="input"
              >
                <option value="">Tất cả trạng thái</option>
                <option value="active">Đang hoạt động</option>
                <option value="upcoming">Sắp khai giảng</option>
                <option value="closed">Đã kết thúc</option>
              </select>
            </div>
          </div>

          {/* Mobile Filters */}
          {showFilters && (
            <motion.div
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: 'auto' }}
              exit={{ opacity: 0, height: 0 }}
              className="mt-4 pt-4 border-t border-gray-200 md:hidden"
            >
              <select
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
                className="input mb-4"
              >
                <option value="">Tất cả trạng thái</option>
                <option value="active">Đang hoạt động</option>
                <option value="upcoming">Sắp khai giảng</option>
                <option value="closed">Đã kết thúc</option>
              </select>
            </motion.div>
          )}

          {/* Active Filters */}
          {(filters.status || searchQuery) && (
            <div className="flex flex-wrap gap-2 mt-4">
              {searchQuery && (
                <span className="badge badge-primary flex items-center">
                  Tìm kiếm: {searchQuery}
                  <button
                    onClick={() => setSearchQuery('')}
                    className="ml-2 hover:text-primary-900"
                  >
                    <FiX className="w-4 h-4" />
                  </button>
                </span>
              )}
              {filters.status && (
                <span className="badge badge-primary flex items-center">
                  Trạng thái: {filters.status}
                  <button
                    onClick={() => handleFilterChange('status', '')}
                    className="ml-2 hover:text-primary-900"
                  >
                    <FiX className="w-4 h-4" />
                  </button>
                </span>
              )}
              <button
                onClick={clearFilters}
                className="text-sm text-primary-600 hover:text-primary-700 font-medium"
              >
                Xóa tất cả
              </button>
            </div>
          )}
        </div>

        {/* Courses Grid */}
        {loading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="card animate-pulse">
                <div className="h-48 bg-gray-200 rounded-lg mb-4"></div>
                <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                <div className="h-4 bg-gray-200 rounded w-1/2"></div>
              </div>
            ))}
          </div>
        ) : courses.length === 0 ? (
          <div className="text-center py-12">
            <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <FiSearch className="w-12 h-12 text-gray-400" />
            </div>
            <p className="text-gray-600 text-lg mb-2">Không tìm thấy khóa học nào</p>
            <p className="text-gray-500 text-sm mb-6">Thử điều chỉnh bộ lọc hoặc tìm kiếm với từ khóa khác</p>
            <button
              onClick={clearFilters}
              className="btn btn-primary inline-flex items-center"
            >
              <FiX className="mr-2" />
              Xóa bộ lọc
            </button>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {courses.map((course, index) => (
              <motion.div
                key={course.id}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5, delay: index * 0.05 }}
                whileHover={{ y: -8, transition: { duration: 0.3 } }}
                className="card card-hover"
              >
                <Link to={`/courses/${course.id}`}>
                  <div className="relative h-48 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-lg mb-4 overflow-hidden group">
                    {course.thumbnail ? (
                      <motion.img
                        src={course.thumbnail}
                        alt={course.course_name}
                        className="w-full h-full object-cover"
                        whileHover={{ scale: 1.1 }}
                        transition={{ duration: 0.4, ease: "easeOut" }}
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-white text-4xl font-bold">
                        {course.course_name.charAt(0)}
                      </div>
                    )}
                    <motion.div 
                      className="absolute top-2 right-2"
                      initial={{ opacity: 0, scale: 0.8 }}
                      animate={{ opacity: 1, scale: 1 }}
                      transition={{ delay: index * 0.05 + 0.3 }}
                    >
                      <span className={`badge ${
                        course.status === 'active' ? 'badge-success' :
                        course.status === 'upcoming' ? 'badge-warning' :
                        'badge-danger'
                      }`}>
                        {course.status === 'active' ? 'Đang mở' :
                         course.status === 'upcoming' ? 'Sắp khai giảng' :
                         'Đã kết thúc'}
                      </span>
                    </motion.div>
                    <motion.div 
                      className="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                      initial={{ opacity: 0 }}
                      whileHover={{ opacity: 1 }}
                    >
                      <motion.div 
                        className="w-16 h-16 bg-white/90 rounded-full flex items-center justify-center"
                        initial={{ scale: 0.8 }}
                        whileHover={{ scale: 1.1 }}
                        transition={{ duration: 0.3 }}
                      >
                        <FiPlay className="w-8 h-8 text-primary-600 ml-1" />
                      </motion.div>
                    </motion.div>
                  </div>
                  <h3 className="text-xl font-semibold mb-2 line-clamp-2">{course.title}</h3>
                  <p className="text-gray-600 text-sm mb-4 line-clamp-2">{course.description}</p>
                  <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center space-x-4 text-sm text-gray-600">
                      <span className="flex items-center">
                        <FiUsers className="mr-1" /> {course.teacher_full_name || course.teacher_name || 'N/A'}
                      </span>
                      <span className="flex items-center">
                        <FiStar className="mr-1 text-yellow-400" />
                        {course.reviews?.average_rating ? (
                          <>
                            {course.reviews.average_rating.toFixed(1)}
                            {course.reviews.total_reviews > 0 && (
                              <span className="ml-1 text-gray-500">
                                ({course.reviews.total_reviews})
                              </span>
                            )}
                          </>
                        ) : (
                          'Chưa có đánh giá'
                        )}
                      </span>
                    </div>
                  </div>
                  <div className="flex items-center justify-between pt-4 border-t border-gray-200">
                    <span className="text-2xl font-bold text-primary-600">
                      {formatPrice(course.price)}
                    </span>
                    <span className="text-sm text-gray-500">
                      {course.start_date && new Date(course.start_date).toLocaleDateString('vi-VN')}
                    </span>
                  </div>
                </Link>
              </motion.div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default Courses

