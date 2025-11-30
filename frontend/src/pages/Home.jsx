import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiPlay, FiUsers, FiClock, FiStar, FiArrowRight, FiSearch, FiBook, FiAward, FiCheckCircle, FiMessageCircle, FiTarget } from 'react-icons/fi'
import { coursesAPI } from '../services/api'
import toast from 'react-hot-toast'

const Home = () => {
  const [courses, setCourses] = useState([])
  const [latestCourses, setLatestCourses] = useState([])
  const [topRatedCourses, setTopRatedCourses] = useState([])
  const [loading, setLoading] = useState(true)
  const [searchQuery, setSearchQuery] = useState('')

  useEffect(() => {
    loadData()
  }, [])

  const loadData = async () => {
    try {
      setLoading(true)
      
      // Load popular courses
      const coursesResponse = await coursesAPI.getAll({ status: 'active' })
      if (coursesResponse.success && coursesResponse.data) {
        const allCourses = coursesResponse.data
        setCourses(allCourses.slice(0, 6)) // Show first 6 courses
        
        // Latest courses (sorted by created_at)
        const sortedByDate = [...allCourses].sort((a, b) => 
          new Date(b.created_at) - new Date(a.created_at)
        )
        setLatestCourses(sortedByDate.slice(0, 3))
        
        // Top rated courses
        const sortedByRating = [...allCourses]
          .filter(c => c.reviews?.average_rating > 0)
          .sort((a, b) => (b.reviews?.average_rating || 0) - (a.reviews?.average_rating || 0))
        setTopRatedCourses(sortedByRating.slice(0, 3))
      }
    } catch (error) {
      toast.error('Không thể tải dữ liệu')
    } finally {
      setLoading(false)
    }
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
    }).format(price)
  }

  return (
    <div>
      {/* Hero Section */}
      <section 
        className="relative text-white overflow-hidden bg-cover bg-center bg-no-repeat"
        style={{
          backgroundImage: `url('/hero-background.jpg')`,
          backgroundSize: 'cover',
          backgroundPosition: 'center',
          backgroundRepeat: 'no-repeat',
          minHeight: '600px',
          // Fallback gradient nếu hình ảnh không tải
          backgroundColor: '#4f46e5'
        }}
      >
        {/* Overlay để đảm bảo text dễ đọc */}
        <div className="absolute inset-0 bg-gradient-to-br from-primary-600/80 via-primary-700/80 to-secondary-600/80"></div>
        <div className="absolute inset-0 bg-black/30"></div>
        
        <div className="container-custom relative z-10 py-12 md:py-16 lg:py-20">
          <div className="max-w-4xl mx-auto text-center px-4">
            <motion.h1
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6 }}
              className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold mb-4 md:mb-6 leading-tight"
            >
              Học trực tuyến,{' '}
              <span className="text-yellow-300 block sm:inline">Thành công thực tế</span>
            </motion.h1>
            <motion.p
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.2 }}
              className="text-base sm:text-lg md:text-xl lg:text-2xl mb-6 md:mb-8 text-gray-100 px-2"
            >
              Khám phá hàng ngàn khóa học chất lượng cao từ các giảng viên hàng đầu
            </motion.p>
            
            {/* Search Bar */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.4 }}
              className="max-w-2xl mx-auto mb-6 md:mb-8"
            >
              <form
                onSubmit={(e) => {
                  e.preventDefault()
                  if (searchQuery.trim()) {
                    window.location.href = `/courses?search=${encodeURIComponent(searchQuery)}`
                  }
                }}
                className="flex flex-col sm:flex-row gap-2 sm:gap-3"
              >
                <div className="flex-1 relative">
                  <FiSearch className="absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4 sm:w-5 sm:h-5" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Tìm kiếm khóa học..."
                    className="w-full pl-10 sm:pl-12 pr-4 py-3 sm:py-4 rounded-lg text-gray-900 text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-yellow-300"
                  />
                </div>
                <button
                  type="submit"
                  className="px-6 sm:px-8 py-3 sm:py-4 bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-semibold rounded-lg transition-colors text-sm sm:text-base whitespace-nowrap"
                >
                  Tìm kiếm
                </button>
              </form>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.6 }}
              className="flex flex-col sm:flex-row justify-center gap-3 sm:gap-4"
            >
              <motion.div
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                <Link to="/courses" className="btn bg-white text-primary-600 hover:bg-gray-100 px-6 sm:px-8 py-3 sm:py-4 text-sm sm:text-base text-center">
                  Khám phá khóa học
                </Link>
              </motion.div>
              <motion.div
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                <Link to="/register" className="btn bg-yellow-400 text-gray-900 hover:bg-yellow-300 px-6 sm:px-8 py-3 sm:py-4 text-sm sm:text-base text-center">
                  Bắt đầu miễn phí
                </Link>
              </motion.div>
            </motion.div>
          </div>
        </div>
      </section>

          {/* Features Section */}
      <section className="py-10 sm:py-12 md:py-16 lg:py-20 bg-white">
        <div className="container-custom px-3 sm:px-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 md:gap-8">
            {[
              {
                icon: <FiPlay className="w-6 h-6 sm:w-8 sm:h-8" />,
                title: 'Học mọi lúc mọi nơi',
                description: 'Truy cập khóa học từ bất kỳ thiết bị nào, bất cứ lúc nào',
              },
              {
                icon: <FiUsers className="w-6 h-6 sm:w-8 sm:h-8" />,
                title: 'Giảng viên chuyên nghiệp',
                description: 'Học từ các chuyên gia hàng đầu trong lĩnh vực',
              },
              {
                icon: <FiClock className="w-6 h-6 sm:w-8 sm:h-8" />,
                title: 'Học theo tốc độ của bạn',
                description: 'Tự điều chỉnh tốc độ học tập phù hợp với bản thân',
              },
            ].map((feature, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
                className="text-center p-5 sm:p-6 rounded-xl hover:shadow-lg transition-shadow bg-gray-50 hover:bg-white"
              >
                <div className="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 bg-primary-100 text-primary-600 rounded-full mb-3 sm:mb-4">
                  {feature.icon}
                </div>
                <h3 className="text-lg sm:text-xl font-semibold mb-2">{feature.title}</h3>
                <p className="text-sm sm:text-base text-gray-600">{feature.description}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

          {/* Popular Courses Section */}
      <section className="py-10 sm:py-12 md:py-16 lg:py-20 bg-gray-50">
        <div className="container-custom px-3 sm:px-4">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 md:mb-8 gap-4">
            <div>
              <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">Khóa học phổ biến</h2>
              <p className="text-sm sm:text-base text-gray-600">Khám phá các khóa học được yêu thích nhất</p>
            </div>
            <Link
              to="/courses"
              className="flex items-center text-primary-600 hover:text-primary-700 font-medium text-sm sm:text-base"
            >
              Xem tất cả <FiArrowRight className="ml-2 w-4 h-4 sm:w-5 sm:h-5" />
            </Link>
          </div>

          {loading ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
              {[1, 2, 3].map((i) => (
                <div key={i} className="card animate-pulse">
                  <div className="h-40 sm:h-48 bg-gray-200 rounded-lg mb-4"></div>
                  <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                  <div className="h-4 bg-gray-200 rounded w-1/2"></div>
                </div>
              ))}
            </div>
          ) : courses.length > 0 ? (
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
              {courses.map((course, index) => (
                <motion.div
                  key={course.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                  whileHover={{ y: -8, transition: { duration: 0.3 } }}
                  className="card card-hover"
                >
                  <Link to={`/courses/${course.id}`}>
                    <div className="relative h-40 sm:h-48 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-lg mb-4 overflow-hidden group">
                      {course.thumbnail ? (
                        <motion.img
                          src={course.thumbnail}
                          alt={course.course_name}
                          className="w-full h-full object-cover"
                          whileHover={{ scale: 1.1 }}
                          transition={{ duration: 0.4, ease: "easeOut" }}
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center text-white text-3xl sm:text-4xl font-bold">
                          {course.course_name.charAt(0)}
                        </div>
                      )}
                      <motion.div 
                        className="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        initial={{ opacity: 0 }}
                        whileHover={{ opacity: 1 }}
                      >
                        <motion.div 
                          className="w-12 h-12 sm:w-16 sm:h-16 bg-white/90 rounded-full flex items-center justify-center"
                          initial={{ scale: 0.8 }}
                          whileHover={{ scale: 1.1 }}
                          transition={{ duration: 0.3 }}
                        >
                          <FiPlay className="w-6 h-6 sm:w-8 sm:h-8 text-primary-600 ml-0.5 sm:ml-1" />
                        </motion.div>
                      </motion.div>
                    </div>
                    <h3 className="text-lg sm:text-xl font-semibold mb-2 line-clamp-2">{course.title}</h3>
                    <p className="text-gray-600 text-xs sm:text-sm mb-4 line-clamp-2">{course.description}</p>
                    
                    {/* Teacher Info */}
                    {course.teacher_full_name || course.teacher_name ? (
                      <div className="flex items-center mb-3 sm:mb-4">
                        {course.teacher_avatar ? (
                          <img
                            src={course.teacher_avatar.startsWith('http') 
                              ? course.teacher_avatar 
                              : `${import.meta.env.VITE_API_URL || ''}/api/uploads/avatars/${course.teacher_avatar}`}
                            alt={course.teacher_full_name || course.teacher_name}
                            className="w-8 h-8 sm:w-10 sm:h-10 rounded-full object-cover mr-2 sm:mr-3 border-2 border-primary-200"
                            onError={(e) => {
                              e.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(course.teacher_full_name || course.teacher_name || 'Teacher')}&background=6366f1&color=fff&size=128`
                            }}
                          />
                        ) : (
                          <div className="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-primary-100 flex items-center justify-center mr-2 sm:mr-3 border-2 border-primary-200">
                            <span className="text-primary-600 font-semibold text-xs sm:text-sm">
                              {(course.teacher_full_name || course.teacher_name || 'T').charAt(0).toUpperCase()}
                            </span>
                          </div>
                        )}
                        <div className="flex-1 min-w-0">
                          <p className="text-sm sm:text-base font-medium text-gray-900 truncate">
                            {course.teacher_full_name || course.teacher_name || 'Giảng viên'}
                          </p>
                          {course.teacher_full_name && course.teacher_name && (
                            <p className="text-xs sm:text-sm text-gray-500 truncate">
                              @{course.teacher_name}
                            </p>
                          )}
                        </div>
                      </div>
                    ) : null}
                    
                    <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0 pt-2 border-t border-gray-100">
                      <div className="flex items-center space-x-3 sm:space-x-4 text-xs sm:text-sm text-gray-600">
                        <span className="flex items-center">
                          <FiStar className="mr-1 text-yellow-400 w-3 h-3 sm:w-4 sm:h-4" />
                          {course.reviews?.average_rating ? (
                            <>
                              <span className="font-medium">{course.reviews.average_rating.toFixed(1)}</span>
                              {course.reviews.total_reviews > 0 && (
                                <span className="ml-1 text-gray-500">
                                  ({course.reviews.total_reviews})
                                </span>
                              )}
                            </>
                          ) : (
                            <span className="text-gray-400">Chưa có đánh giá</span>
                          )}
                        </span>
                        <span className="flex items-center">
                          <FiUsers className="mr-1 w-3 h-3 sm:w-4 sm:h-4" /> 
                          <span>{course.enrollments_count || 0} học viên</span>
                        </span>
                      </div>
                      <span className="text-base sm:text-lg font-bold text-primary-600">
                        {formatPrice(course.price)}
                      </span>
                    </div>
                  </Link>
                </motion.div>
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <div className="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <FiBook className="w-10 h-10 text-gray-400" />
              </div>
              <p className="text-gray-600 text-lg">Chưa có khóa học nào</p>
              <p className="text-gray-500 text-sm mt-2">Các khóa học sẽ xuất hiện ở đây khi có sẵn</p>
            </div>
          )}
        </div>
      </section>

      {/* Latest Courses Section */}
      {latestCourses.length > 0 && (
        <section className="py-10 sm:py-12 md:py-16 lg:py-20 bg-white">
          <div className="container-custom px-3 sm:px-4">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 md:mb-8 gap-4">
              <div>
                <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">Khóa học mới nhất</h2>
                <p className="text-sm sm:text-base text-gray-600">Cập nhật những khóa học mới nhất từ các giảng viên</p>
              </div>
              <Link
                to="/courses"
                className="flex items-center text-primary-600 hover:text-primary-700 font-medium text-sm sm:text-base"
              >
                Xem tất cả <FiArrowRight className="ml-2 w-4 h-4 sm:w-5 sm:h-5" />
              </Link>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
              {latestCourses.map((course, index) => (
                <motion.div
                  key={course.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                  whileHover={{ y: -8, transition: { duration: 0.3 } }}
                  className="card card-hover"
                >
                  <Link to={`/courses/${course.id}`}>
                    <div className="relative h-32 sm:h-40 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-lg mb-4 overflow-hidden">
                      {course.thumbnail ? (
                        <img
                          src={course.thumbnail}
                          alt={course.course_name}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center text-white text-2xl font-bold">
                          {course.course_name.charAt(0)}
                        </div>
                      )}
                    </div>
                    <h3 className="text-base sm:text-lg font-semibold mb-2 line-clamp-2">{course.title}</h3>
                    {course.teacher_full_name || course.teacher_name ? (
                      <div className="flex items-center mb-3">
                        {course.teacher_avatar ? (
                          <img
                            src={course.teacher_avatar.startsWith('http') 
                              ? course.teacher_avatar 
                              : `${import.meta.env.VITE_API_URL || ''}/api/uploads/avatars/${course.teacher_avatar}`}
                            alt={course.teacher_full_name || course.teacher_name}
                            className="w-6 h-6 sm:w-8 sm:h-8 rounded-full object-cover mr-2"
                            onError={(e) => {
                              e.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(course.teacher_full_name || course.teacher_name || 'Teacher')}&background=6366f1&color=fff&size=128`
                            }}
                          />
                        ) : (
                          <div className="w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-primary-100 flex items-center justify-center mr-2">
                            <span className="text-primary-600 font-semibold text-xs">
                              {(course.teacher_full_name || course.teacher_name || 'T').charAt(0).toUpperCase()}
                            </span>
                          </div>
                        )}
                        <span className="text-xs sm:text-sm text-gray-600 truncate">
                          {course.teacher_full_name || course.teacher_name}
                        </span>
                      </div>
                    ) : null}
                    <div className="flex items-center justify-between pt-2 border-t border-gray-100">
                      <div className="flex items-center text-xs sm:text-sm text-gray-600">
                        {course.reviews?.average_rating ? (
                          <>
                            <FiStar className="text-yellow-400 mr-1" />
                            <span className="font-medium">{course.reviews.average_rating.toFixed(1)}</span>
                          </>
                        ) : (
                          <span className="text-gray-400">Chưa có đánh giá</span>
                        )}
                      </div>
                      <span className="text-sm sm:text-base font-bold text-primary-600">
                        {formatPrice(course.price)}
                      </span>
                    </div>
                  </Link>
                </motion.div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Top Rated Courses Section */}
      {topRatedCourses.length > 0 && (
        <section className="py-10 sm:py-12 md:py-16 lg:py-20 bg-gray-50">
          <div className="container-custom px-3 sm:px-4">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 md:mb-8 gap-4">
              <div>
                <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">Khóa học được đánh giá cao</h2>
                <p className="text-sm sm:text-base text-gray-600">Những khóa học được học viên yêu thích nhất</p>
              </div>
              <Link
                to="/courses"
                className="flex items-center text-primary-600 hover:text-primary-700 font-medium text-sm sm:text-base"
              >
                Xem tất cả <FiArrowRight className="ml-2 w-4 h-4 sm:w-5 sm:h-5" />
              </Link>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
              {topRatedCourses.map((course, index) => (
                <motion.div
                  key={course.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                  whileHover={{ y: -8, transition: { duration: 0.3 } }}
                  className="card card-hover relative"
                >
                  {index === 0 && (
                    <div className="absolute top-4 right-4 bg-yellow-400 text-yellow-900 px-2 py-1 rounded-full text-xs font-bold z-10 flex items-center">
                      <FiAward className="mr-1" /> #1
                    </div>
                  )}
                  <Link to={`/courses/${course.id}`}>
                    <div className="relative h-32 sm:h-40 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-lg mb-4 overflow-hidden">
                      {course.thumbnail ? (
                        <img
                          src={course.thumbnail}
                          alt={course.course_name}
                          className="w-full h-full object-cover"
                        />
                      ) : (
                        <div className="w-full h-full flex items-center justify-center text-white text-2xl font-bold">
                          {course.course_name.charAt(0)}
                        </div>
                      )}
                    </div>
                    <h3 className="text-base sm:text-lg font-semibold mb-2 line-clamp-2">{course.title}</h3>
                    {course.teacher_full_name || course.teacher_name ? (
                      <div className="flex items-center mb-3">
                        {course.teacher_avatar ? (
                          <img
                            src={course.teacher_avatar.startsWith('http') 
                              ? course.teacher_avatar 
                              : `${import.meta.env.VITE_API_URL || ''}/api/uploads/avatars/${course.teacher_avatar}`}
                            alt={course.teacher_full_name || course.teacher_name}
                            className="w-6 h-6 sm:w-8 sm:h-8 rounded-full object-cover mr-2"
                            onError={(e) => {
                              e.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(course.teacher_full_name || course.teacher_name || 'Teacher')}&background=6366f1&color=fff&size=128`
                            }}
                          />
                        ) : (
                          <div className="w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-primary-100 flex items-center justify-center mr-2">
                            <span className="text-primary-600 font-semibold text-xs">
                              {(course.teacher_full_name || course.teacher_name || 'T').charAt(0).toUpperCase()}
                            </span>
                          </div>
                        )}
                        <span className="text-xs sm:text-sm text-gray-600 truncate">
                          {course.teacher_full_name || course.teacher_name}
                        </span>
                      </div>
                    ) : null}
                    <div className="flex items-center justify-between pt-2 border-t border-gray-100">
                      <div className="flex items-center text-xs sm:text-sm">
                        <FiStar className="text-yellow-400 mr-1" />
                        <span className="font-bold text-gray-900">{course.reviews?.average_rating.toFixed(1)}</span>
                        <span className="text-gray-500 ml-1">({course.reviews?.total_reviews || 0})</span>
                      </div>
                      <span className="text-sm sm:text-base font-bold text-primary-600">
                        {formatPrice(course.price)}
                      </span>
                    </div>
                  </Link>
                </motion.div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* How It Works Section */}
      <section className="py-10 sm:py-12 md:py-16 lg:py-20 bg-white">
        <div className="container-custom px-3 sm:px-4">
          <div className="text-center mb-8 md:mb-12">
            <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 md:mb-4">Cách thức hoạt động</h2>
            <p className="text-sm sm:text-base md:text-lg text-gray-600 max-w-2xl mx-auto">
              Bắt đầu hành trình học tập của bạn chỉ với 3 bước đơn giản
            </p>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
            {[
              {
                step: '01',
                icon: <FiSearch className="w-8 h-8 sm:w-10 sm:h-10" />,
                title: 'Tìm khóa học',
                description: 'Khám phá hàng ngàn khóa học từ các giảng viên chuyên nghiệp phù hợp với nhu cầu của bạn'
              },
              {
                step: '02',
                icon: <FiCheckCircle className="w-8 h-8 sm:w-10 sm:h-10" />,
                title: 'Đăng ký học',
                description: 'Chọn khóa học yêu thích và đăng ký ngay để bắt đầu hành trình học tập của bạn'
              },
              {
                step: '03',
                icon: <FiTarget className="w-8 h-8 sm:w-10 sm:h-10" />,
                title: 'Học và phát triển',
                description: 'Học theo tốc độ của riêng bạn, làm bài tập và nhận chứng chỉ sau khi hoàn thành'
              }
            ].map((item, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
                className="relative text-center p-6 sm:p-8 rounded-xl bg-gradient-to-br from-gray-50 to-white hover:shadow-lg transition-shadow"
              >
                <div className="absolute top-4 left-4 text-6xl sm:text-7xl font-bold text-primary-100 opacity-50">
                  {item.step}
                </div>
                <div className="relative z-10">
                  <div className="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 bg-primary-600 text-white rounded-full mb-4 sm:mb-6">
                    {item.icon}
                  </div>
                  <h3 className="text-xl sm:text-2xl font-bold mb-3 sm:mb-4">{item.title}</h3>
                  <p className="text-sm sm:text-base text-gray-600 leading-relaxed">{item.description}</p>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-12 md:py-16 lg:py-20 bg-gradient-to-r from-primary-600 to-secondary-600 text-white">
        <div className="container-custom text-center px-4">
          <motion.h2
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            className="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 md:mb-4"
          >
            Sẵn sàng bắt đầu hành trình học tập của bạn?
          </motion.h2>
          <motion.p
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.2 }}
            className="text-base sm:text-lg md:text-xl mb-6 md:mb-8 text-gray-100"
          >
            Tham gia cùng hàng ngàn học viên đang học tập mỗi ngày
          </motion.p>
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ delay: 0.4 }}
          >
            <Link to="/register" className="btn bg-white text-primary-600 hover:bg-gray-100 px-6 sm:px-8 py-3 sm:py-4 text-sm sm:text-base md:text-lg inline-block">
              Đăng ký ngay miễn phí
            </Link>
          </motion.div>
        </div>
      </section>
    </div>
  )
}

export default Home

