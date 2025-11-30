import { useEffect, useState } from 'react'
import { useSearchParams, useNavigate, Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiCheckCircle, FiArrowRight, FiBook } from 'react-icons/fi'
import { coursesAPI } from '../services/api'
import toast from 'react-hot-toast'

const PaymentSuccess = () => {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const paymentId = searchParams.get('payment_id')
  const courseId = searchParams.get('course_id')
  const [course, setCourse] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (courseId) {
      loadCourse()
    } else {
      setLoading(false)
    }
  }, [courseId])

  const loadCourse = async () => {
    try {
      const response = await coursesAPI.getById(courseId)
      if (response.success && response.data) {
        setCourse(response.data)
      }
    } catch (error) {
      console.error('Load course error:', error)
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="pt-16 md:pt-20 min-h-screen flex items-center justify-center">
        <div className="spinner"></div>
      </div>
    )
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50 flex items-center justify-center">
      <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        className="bg-white rounded-xl shadow-xl max-w-md w-full p-8 text-center"
      >
        <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <FiCheckCircle className="w-12 h-12 text-green-600" />
        </div>
        
        <h1 className="text-2xl font-bold text-gray-900 mb-2">
          Thanh toán thành công!
        </h1>
        
        <p className="text-gray-600 mb-6">
          Bạn đã được đăng ký khóa học thành công.
        </p>

        {course && (
          <div className="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <h3 className="font-semibold mb-2">{course.title}</h3>
            <p className="text-sm text-gray-600">
              Bạn có thể bắt đầu học ngay bây giờ!
            </p>
          </div>
        )}

        <div className="space-y-3">
          {courseId && (
            <Link
              to={`/courses/${courseId}/learn`}
              className="w-full btn btn-primary flex items-center justify-center"
            >
              <FiBook className="mr-2" />
              Bắt đầu học ngay
            </Link>
          )}
          <Link
            to="/courses"
            className="w-full btn btn-outline flex items-center justify-center"
          >
            Xem thêm khóa học
            <FiArrowRight className="ml-2" />
          </Link>
        </div>
      </motion.div>
    </div>
  )
}

export default PaymentSuccess

