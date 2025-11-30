import { Link } from 'react-router-dom'
import { FiHome, FiAlertCircle, FiSearch } from 'react-icons/fi'
import { motion } from 'framer-motion'

const NotFound = () => {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
      <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        className="text-center max-w-md"
      >
        <motion.div
          initial={{ opacity: 0, y: -20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="mb-6"
        >
          <div className="w-32 h-32 bg-gradient-to-br from-primary-100 to-secondary-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <FiAlertCircle className="w-16 h-16 text-primary-600" />
          </div>
        </motion.div>
        <h1 className="text-9xl font-bold text-primary-600 mb-4">404</h1>
        <h2 className="text-3xl font-bold mb-4">Trang không tìm thấy</h2>
        <p className="text-gray-600 mb-8">
          Xin lỗi, trang bạn đang tìm kiếm không tồn tại. Vui lòng kiểm tra lại đường dẫn.
        </p>
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <Link to="/" className="btn btn-primary inline-flex items-center justify-center">
            <FiHome className="mr-2" />
            Về trang chủ
          </Link>
          <Link to="/courses" className="btn btn-outline inline-flex items-center justify-center">
            <FiSearch className="mr-2" />
            Tìm khóa học
          </Link>
        </div>
      </motion.div>
    </div>
  )
}

export default NotFound

