import { useSearchParams, Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { FiXCircle, FiArrowLeft, FiRefreshCw } from 'react-icons/fi'

const PaymentFailed = () => {
  const [searchParams] = useSearchParams()
  const paymentId = searchParams.get('payment_id')

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50 flex items-center justify-center">
      <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        className="bg-white rounded-xl shadow-xl max-w-md w-full p-8 text-center"
      >
        <div className="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <FiXCircle className="w-12 h-12 text-red-600" />
        </div>
        
        <h1 className="text-2xl font-bold text-gray-900 mb-2">
          Thanh toán thất bại
        </h1>
        
        <p className="text-gray-600 mb-6">
          Có vấn đề xảy ra trong quá trình thanh toán. Vui lòng thử lại.
        </p>

        <div className="space-y-3">
          <button
            onClick={() => window.history.back()}
            className="w-full btn btn-primary flex items-center justify-center"
          >
            <FiRefreshCw className="mr-2" />
            Thử lại
          </button>
          <Link
            to="/courses"
            className="w-full btn btn-outline flex items-center justify-center"
          >
            <FiArrowLeft className="mr-2" />
            Quay lại danh sách khóa học
          </Link>
        </div>
      </motion.div>
    </div>
  )
}

export default PaymentFailed

