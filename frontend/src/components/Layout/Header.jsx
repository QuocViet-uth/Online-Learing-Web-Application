import { useState, useEffect } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'
import { 
  FiSearch, 
  FiMenu, 
  FiX, 
  FiUser, 
  FiLogOut, 
  FiSettings,
  FiBookOpen
} from 'react-icons/fi'
import NotificationDropdown from '../Notification/NotificationDropdown'
import { motion, AnimatePresence } from 'framer-motion'
import toast from 'react-hot-toast'

const Header = () => {
  const [isScrolled, setIsScrolled] = useState(false)
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const [isSearchOpen, setIsSearchOpen] = useState(false)
  const [searchQuery, setSearchQuery] = useState('')
  const { user, logout, isAuthenticated } = useAuth()
  const navigate = useNavigate()

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20)
    }
    window.addEventListener('scroll', handleScroll)
    return () => window.removeEventListener('scroll', handleScroll)
  }, [])

  const handleLogout = () => {
    logout()
    toast.success('Đăng xuất thành công')
    navigate('/')
    setIsMobileMenuOpen(false)
  }

  const handleSearch = (e) => {
    e.preventDefault()
    if (searchQuery.trim()) {
      navigate(`/courses?search=${encodeURIComponent(searchQuery)}`)
      setIsSearchOpen(false)
      setSearchQuery('')
    }
  }

  const getDashboardLink = () => {
    if (!user) return '/login'
    if (user.role === 'admin') return '/admin'
    if (user.role === 'teacher') return '/teacher'
    return '/student'
  }

  return (
    <header
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
        isScrolled
          ? 'bg-white shadow-lg'
          : 'bg-white/95 backdrop-blur-sm shadow-sm'
      }`}
    >
      <div className="container-custom">
        <div className="flex items-center justify-between h-16 md:h-20">
          {/* Logo */}
          <Link
            to="/"
            className="flex items-center space-x-2 text-xl md:text-2xl font-bold gradient-text"
          >
            <div className="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-primary-500 to-secondary-500 rounded-lg flex items-center justify-center text-white">
              <FiBookOpen className="w-5 h-5 md:w-6 md:h-6" />
            </div>
            <span className="hidden sm:inline">Online Learning</span>
          </Link>

          {/* Desktop Search */}
          <div className="hidden lg:flex flex-1 max-w-2xl mx-8">
            <form onSubmit={handleSearch} className="w-full">
              <div className="relative">
                <input
                  type="text"
                  placeholder="Tìm kiếm khóa học..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
                <FiSearch className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              </div>
            </form>
          </div>

          {/* Desktop Navigation */}
          <nav className="hidden lg:flex items-center space-x-2">
            <Link
              to="/courses"
              className="px-3 py-2 text-sm text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-all font-medium"
            >
              Khóa học
            </Link>

            {isAuthenticated ? (
              <>
                {/* Role-specific navigation */}
                {user.role === 'student' && (
                  <>
                    <Link
                      to="/student/courses"
                      className="px-3 py-2 text-sm text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-all font-medium"
                    >
                      Khóa học của tôi
                    </Link>
                    <Link
                      to="/student/assignments"
                      className="px-3 py-2 text-sm text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-all font-medium"
                    >
                      Bài tập
                    </Link>
                  </>
                )}
                {user.role === 'teacher' && (
                  <>
                    <Link
                      to="/teacher/courses"
                      className="px-3 py-2 text-sm text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-all font-medium"
                    >
                      Khóa học của tôi
                    </Link>
                    <Link
                      to="/teacher/assignments"
                      className="px-3 py-2 text-sm text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-all font-medium"
                    >
                      Bài tập
                    </Link>
                  </>
                )}
                {user.role === 'admin' && (
                  <Link
                    to="/admin"
                    className="px-3 py-2 text-sm text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-all font-medium"
                  >
                    Quản trị
                  </Link>
                )}
                
                <Link
                  to="/chat"
                  className="px-3 py-2 text-sm text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-all font-medium"
                >
                  Chat
                </Link>
                
                <NotificationDropdown />
                
                <div className="relative group ml-2">
                  <button className="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <img
                      src={user.avatar || '/default-avatar.png'}
                      alt={user.full_name || 'User'}
                      className="w-8 h-8 rounded-full border-2 border-gray-200"
                      onError={(e) => {
                        e.target.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.full_name || 'User')
                      }}
                    />
                    <span className="font-medium text-sm hidden xl:inline">{user.full_name || 'User'}</span>
                  </button>
                  <div className="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl py-2 border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                    <Link
                      to={getDashboardLink()}
                      className="flex items-center space-x-3 px-4 py-2.5 hover:bg-gray-50 transition-colors"
                    >
                      <FiUser className="w-4 h-4 text-gray-600" />
                      <span className="text-sm">Dashboard</span>
                    </Link>
                    <Link
                      to="/profile"
                      className="flex items-center space-x-3 px-4 py-2.5 hover:bg-gray-50 transition-colors"
                    >
                      <FiSettings className="w-4 h-4 text-gray-600" />
                      <span className="text-sm">Hồ sơ</span>
                    </Link>
                    <div className="border-t border-gray-100 my-1"></div>
                    <button
                      onClick={handleLogout}
                      className="flex items-center space-x-3 px-4 py-2.5 hover:bg-red-50 text-red-600 w-full text-left transition-colors"
                    >
                      <FiLogOut className="w-4 h-4" />
                      <span className="text-sm">Đăng xuất</span>
                    </button>
                  </div>
                </div>
              </>
            ) : (
              <>
                <Link
                  to="/login"
                  className="px-4 py-2 text-sm text-gray-700 hover:text-primary-600 transition-colors font-medium"
                >
                  Đăng nhập
                </Link>
                <Link
                  to="/register"
                  className="btn btn-primary text-sm px-5"
                >
                  Đăng ký
                </Link>
              </>
            )}
          </nav>

          {/* Mobile Search & Menu Buttons */}
          <div className="flex items-center space-x-1 sm:space-x-2 md:hidden">
            <button
              onClick={() => setIsSearchOpen(!isSearchOpen)}
              className="p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
              aria-label="Toggle search"
            >
              <FiSearch className="w-5 h-5" />
            </button>
            {isAuthenticated && <NotificationDropdown />}
            <button
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
              aria-label="Toggle menu"
            >
              {isMobileMenuOpen ? (
                <FiX className="w-6 h-6" />
              ) : (
                <FiMenu className="w-6 h-6" />
              )}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Search */}
      <AnimatePresence>
        {isSearchOpen && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: 'auto', opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            className="lg:hidden border-t border-gray-200"
          >
            <form onSubmit={handleSearch} className="p-3 sm:p-4">
              <div className="relative">
                <input
                  type="text"
                  placeholder="Tìm kiếm khóa học..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="w-full pl-9 sm:pl-10 pr-3 sm:pr-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
                <FiSearch className="absolute left-2 sm:left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4 sm:w-5 sm:h-5" />
              </div>
            </form>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Mobile Menu */}
      <AnimatePresence>
        {isMobileMenuOpen && (
          <>
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-black/50 z-40 md:hidden"
              onClick={() => setIsMobileMenuOpen(false)}
            />
            <motion.div
              initial={{ x: '100%' }}
              animate={{ x: 0 }}
              exit={{ x: '100%' }}
              transition={{ type: 'tween', duration: 0.3 }}
              className="fixed top-0 right-0 bottom-0 w-80 max-w-[85vw] bg-white shadow-xl z-50 md:hidden overflow-y-auto custom-scrollbar"
            >
              <div className="p-4 sm:p-6">
                <div className="flex items-center justify-between mb-4 sm:mb-6">
                  <h2 className="text-lg sm:text-xl font-bold gradient-text">Menu</h2>
                  <button
                    onClick={() => setIsMobileMenuOpen(false)}
                    className="p-2 text-gray-700"
                  >
                    <FiX className="w-6 h-6" />
                  </button>
                </div>

                {/* Mobile Search Toggle - Removed since search is now in header */}

                <nav className="space-y-1">
                  <Link
                    to="/courses"
                    onClick={() => setIsMobileMenuOpen(false)}
                    className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                  >
                    Khóa học
                  </Link>

                  {isAuthenticated ? (
                    <>
                      {/* Role-specific navigation */}
                      {user.role === 'student' && (
                        <>
                          <Link
                            to="/student/courses"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Khóa học của tôi
                          </Link>
                          <Link
                            to="/student/assignments"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Bài tập
                          </Link>
                          <Link
                            to="/student/grades"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Điểm số
                          </Link>
                          <Link
                            to="/student/progress"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Tiến độ học tập
                          </Link>
                        </>
                      )}
                      {user.role === 'teacher' && (
                        <>
                          <Link
                            to="/teacher/courses"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Khóa học của tôi
                          </Link>
                          <Link
                            to="/teacher/assignments"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Bài tập
                          </Link>
                          <Link
                            to="/teacher/grades"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Chấm điểm
                          </Link>
                        </>
                      )}
                      {user.role === 'admin' && (
                        <>
                          <Link
                            to="/admin"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Dashboard
                          </Link>
                          <Link
                            to="/admin/users"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Quản lý người dùng
                          </Link>
                          <Link
                            to="/admin/courses"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Quản lý khóa học
                          </Link>
                          <Link
                            to="/admin/payments"
                            onClick={() => setIsMobileMenuOpen(false)}
                            className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                          >
                            Thanh toán
                          </Link>
                        </>
                      )}
                      
                      <Link
                        to="/chat"
                        onClick={() => setIsMobileMenuOpen(false)}
                        className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                      >
                        Chat
                      </Link>
                      
                      <Link
                        to={getDashboardLink()}
                        onClick={() => setIsMobileMenuOpen(false)}
                        className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                      >
                        Dashboard
                      </Link>
                      
                      <Link
                        to="/profile"
                        onClick={() => setIsMobileMenuOpen(false)}
                        className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                      >
                        Hồ sơ
                      </Link>
                      
                      <div className="border-t border-gray-200 pt-4 mt-4">
                        <div className="flex items-center space-x-3 px-4 py-3 bg-gray-50 rounded-lg mb-2">
                          <img
                            src={user.avatar || '/default-avatar.png'}
                            alt={user.full_name || 'User'}
                            className="w-10 h-10 rounded-full border-2 border-white"
                            onError={(e) => {
                              e.target.src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.full_name || 'User')
                            }}
                          />
                          <div className="flex-1">
                            <p className="font-medium text-sm">{user.full_name || 'User'}</p>
                            <p className="text-xs text-gray-500 capitalize">{user.role === 'student' ? 'Học viên' : user.role === 'teacher' ? 'Giảng viên' : 'Quản trị viên'}</p>
                          </div>
                        </div>
                        <button
                          onClick={handleLogout}
                          className="w-full text-left px-4 py-3 rounded-lg hover:bg-red-50 text-red-600 transition-colors font-medium flex items-center space-x-2"
                        >
                          <FiLogOut className="w-5 h-5" />
                          <span>Đăng xuất</span>
                        </button>
                      </div>
                    </>
                  ) : (
                    <>
                      <Link
                        to="/login"
                        onClick={() => setIsMobileMenuOpen(false)}
                        className="block px-4 py-3 rounded-lg hover:bg-gray-100 transition-colors font-medium"
                      >
                        Đăng nhập
                      </Link>
                      <Link
                        to="/register"
                        onClick={() => setIsMobileMenuOpen(false)}
                        className="block px-4 py-3 rounded-lg bg-primary-600 text-white text-center font-medium hover:bg-primary-700 transition-colors"
                      >
                        Đăng ký
                      </Link>
                    </>
                  )}
                </nav>
              </div>
            </motion.div>
          </>
        )}
      </AnimatePresence>
    </header>
  )
}

export default Header

