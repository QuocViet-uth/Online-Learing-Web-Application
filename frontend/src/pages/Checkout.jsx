import { useState, useEffect } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { 
  FiArrowLeft, 
  FiCreditCard, 
  FiSmartphone, 
  FiShield,
  FiCheck,
  FiLock,
  FiAlertCircle
} from 'react-icons/fi'
import { coursesAPI, paymentsAPI, couponsAPI, paymentQRCodesAPI, enrollmentsAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import toast from 'react-hot-toast'

const Checkout = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user, isAuthenticated } = useAuth()
  const [course, setCourse] = useState(null)
  const [loading, setLoading] = useState(true)
  const [processing, setProcessing] = useState(false)
  const [selectedGateway, setSelectedGateway] = useState('')
  const [couponCode, setCouponCode] = useState('')
  const [appliedCoupon, setAppliedCoupon] = useState(null)
  const [couponError, setCouponError] = useState('')
  const [applyingCoupon, setApplyingCoupon] = useState(false)
  const [qrCodes, setQRCodes] = useState({}) // { gateway: qrCodeData }
  const [paymentId, setPaymentId] = useState(null) // Lưu payment ID sau khi tạo

  useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login', { state: { from: `/checkout/${id}` } })
      return
    }

    if (!user || !user.id) {
      toast.error('Vui lòng đăng nhập lại')
      navigate('/login')
      return
    }

    loadCourse()
    loadQRCodes()
  }, [id, isAuthenticated, user])

  const loadCourse = async () => {
    try {
      setLoading(true)
      const response = await coursesAPI.getById(id)
      if (response.success && response.data) {
        const courseData = response.data
        setCourse(courseData)
        
        // Nếu khóa học miễn phí (price = 0), tự động enroll và redirect
        const coursePrice = parseFloat(courseData.price) || 0
        if (coursePrice === 0) {
          toast.success('Khóa học miễn phí! Đang đăng ký...')
          try {
            const enrollResult = await enrollmentsAPI.enroll(id, user.id)
            if (enrollResult.success) {
              toast.success('Đăng ký khóa học miễn phí thành công!')
              navigate(`/courses/${id}/learn`)
            } else {
              // Nếu đã enroll rồi, vẫn redirect đến trang học
              if (enrollResult.message?.includes('đã đăng ký')) {
                navigate(`/courses/${id}/learn`)
              } else {
                toast.error(enrollResult.message || 'Đăng ký thất bại')
                navigate(`/courses/${id}`)
              }
            }
          } catch (enrollError) {
            console.error('Auto enroll error:', enrollError)
            // Nếu lỗi do đã enroll, vẫn redirect
            if (enrollError.response?.data?.message?.includes('đã đăng ký')) {
              navigate(`/courses/${id}/learn`)
            } else {
              toast.error('Có lỗi khi đăng ký khóa học miễn phí')
              navigate(`/courses/${id}`)
            }
          }
          return
        }
      } else {
        toast.error('Không tìm thấy khóa học')
        navigate('/courses')
      }
    } catch (error) {
      console.error('Load course error:', error)
      toast.error('Không thể tải thông tin khóa học')
      navigate('/courses')
    } finally {
      setLoading(false)
    }
  }

  const loadQRCodes = async () => {
    try {
      const gateways = ['momo', 'vnpay', 'bank_transfer']
      const qrCodesData = {}
      
      for (const gateway of gateways) {
        try {
          const response = await paymentQRCodesAPI.getByGateway(gateway)
          if (response.success && response.data) {
            qrCodesData[gateway] = response.data
          }
        } catch (error) {
          console.error(`Error loading QR code for ${gateway}:`, error)
        }
      }
      
      setQRCodes(qrCodesData)
    } catch (error) {
      console.error('Load QR codes error:', error)
    }
  }

  const handleApplyCoupon = async () => {
    if (!couponCode.trim()) {
      setCouponError('Vui lòng nhập mã giảm giá')
      return
    }

    setApplyingCoupon(true)
    setCouponError('')
    try {
      const response = await couponsAPI.getByCode(couponCode.trim())
      if (response.success && response.data) {
        const coupon = response.data
        // Kiểm tra coupon có hợp lệ không
        const now = new Date()
        const validFrom = new Date(coupon.valid_from)
        const validUntil = new Date(coupon.valid_until)

        if (coupon.status !== 'active') {
          setCouponError('Mã giảm giá không còn hiệu lực')
          return
        }

        if (now < validFrom || now > validUntil) {
          setCouponError('Mã giảm giá đã hết hạn')
          return
        }

        if (coupon.max_uses && coupon.used_count >= coupon.max_uses) {
          setCouponError('Mã giảm giá đã hết lượt sử dụng')
          return
        }

        setAppliedCoupon(coupon)
        toast.success('Áp dụng mã giảm giá thành công!')
      } else {
        setCouponError('Mã giảm giá không hợp lệ')
      }
    } catch (error) {
      console.error('Apply coupon error:', error)
      setCouponError('Không thể áp dụng mã giảm giá')
    } finally {
      setApplyingCoupon(false)
    }
  }

  const removeCoupon = () => {
    setAppliedCoupon(null)
    setCouponCode('')
    setCouponError('')
  }

  const calculateTotal = () => {
    if (!course) return 0
    let total = course.price

    if (appliedCoupon) {
      const discount = (total * appliedCoupon.discount_percent) / 100
      total = total - discount
    }

    return Math.max(0, total)
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
    }).format(price)
  }

  const handlePayment = async () => {
    if (!selectedGateway) {
      toast.error('Vui lòng chọn phương thức thanh toán')
      return
    }

    if (!user || !user.id) {
      toast.error('Vui lòng đăng nhập lại')
      navigate('/login')
      return
    }

    setProcessing(true)
    try {
      // Tạo payment
      const paymentData = {
        course_id: parseInt(id),
        payment_gateway: selectedGateway,
        student_id: user.id
      }

      // Nếu có coupon, có thể thêm vào payment data
      if (appliedCoupon) {
        paymentData.coupon_id = appliedCoupon.id
      }

      const paymentResult = await paymentsAPI.create(paymentData)

      if (paymentResult.success) {
        // Lưu payment ID để xác nhận sau
        if (paymentResult.data && paymentResult.data.id) {
          setPaymentId(paymentResult.data.id)
        }
        
        // Hiển thị warning nếu có
        if (paymentResult.warning) {
          toast(paymentResult.warning, { 
            icon: '⚠️',
            duration: 5000 
          })
        }
        
        // Kiểm tra loại thanh toán
        if (paymentResult.payment_type === 'redirect' && paymentResult.payment_url) {
          // Redirect đến payment gateway (VNPay hoặc MoMo)
          toast.success('Đang chuyển hướng đến trang thanh toán...')
          window.location.href = paymentResult.payment_url
        } else {
          // Hiển thị QR code (khi chưa config gateway hoặc bank_transfer)
          setProcessing(false)
          
          // Kiểm tra xem có QR code cho gateway này không
          if (qrCodes[selectedGateway] && qrCodes[selectedGateway].qr_code_image) {
            toast.success('Vui lòng quét mã QR để thanh toán')
            // Scroll đến QR code section
            setTimeout(() => {
              const qrSection = document.querySelector('.qr-code-section')
              if (qrSection) {
                qrSection.scrollIntoView({ behavior: 'smooth', block: 'center' })
              }
            }, 100)
          } else {
            // Nếu không có QR code, thông báo cho user
            if (selectedGateway === 'vnpay' || selectedGateway === 'momo') {
              toast.error('Payment gateway chưa được cấu hình và không có QR code. Vui lòng chọn phương thức khác hoặc liên hệ admin.')
            } else {
              toast.success('Tạo thanh toán thành công! Vui lòng quét mã QR để thanh toán.')
            }
          }
        }
      } else {
        toast.error(paymentResult.message || 'Tạo thanh toán thất bại')
        setProcessing(false)
      }
    } catch (error) {
      console.error('Payment error:', error)
      toast.error(error.message || 'Có lỗi xảy ra khi thanh toán')
    } finally {
      setProcessing(false)
    }
  }

  if (loading) {
    return (
      <div className="pt-16 md:pt-20 min-h-screen flex items-center justify-center">
        <div className="spinner"></div>
      </div>
    )
  }

  if (!course) {
    return (
      <div className="pt-16 md:pt-20 min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center max-w-md">
          <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <FiAlertCircle className="w-12 h-12 text-gray-400" />
          </div>
          <p className="text-gray-600 text-lg mb-2 font-medium">Không tìm thấy khóa học</p>
          <p className="text-gray-500 text-sm mb-6">Khóa học này có thể đã bị xóa hoặc không tồn tại</p>
          <Link to="/courses" className="btn btn-primary inline-flex items-center">
            <FiArrowLeft className="mr-2" />
            Quay lại danh sách
          </Link>
        </div>
      </div>
    )
  }

  const originalPrice = course.price
  const discount = appliedCoupon ? (originalPrice * appliedCoupon.discount_percent) / 100 : 0
  const finalPrice = calculateTotal()

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-6 sm:py-8 px-3 sm:px-4">
        {/* Header */}
        <div className="mb-4 sm:mb-6">
          <Link
            to={`/courses/${id}`}
            className="inline-flex items-center text-sm sm:text-base text-gray-600 hover:text-primary-600 mb-3 sm:mb-4"
          >
            <FiArrowLeft className="mr-2" />
            Quay lại khóa học
          </Link>
          <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">Thanh toán</h1>
          <p className="text-sm sm:text-base text-gray-600 mt-1 sm:mt-2">Hoàn tất đơn hàng của bạn</p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-6">
            {/* Course Info */}
            <div className="bg-white rounded-xl shadow-sm p-4 sm:p-6">
              <h2 className="text-lg sm:text-xl font-bold mb-3 sm:mb-4">Thông tin khóa học</h2>
              <div className="flex flex-col sm:flex-row gap-3 sm:gap-4">
                {course.thumbnail && (
                  <img
                    src={course.thumbnail}
                    alt={course.title}
                    className="w-full sm:w-24 h-32 sm:h-24 object-cover rounded-lg"
                  />
                )}
                <div className="flex-1 min-w-0">
                  <h3 className="font-semibold text-base sm:text-lg mb-2 line-clamp-2">{course.title}</h3>
                  <p className="text-gray-600 text-xs sm:text-sm mb-2">
                    Giảng viên: {course.teacher_name || 'N/A'}
                  </p>
                  <div className="flex flex-wrap items-center gap-2 sm:gap-4 text-xs sm:text-sm text-gray-500">
                    <span>{course.lessons?.total || 0} bài học</span>
                    <span className="hidden sm:inline">•</span>
                    <span>{course.lessons?.total_duration || 0} phút</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Coupon Code */}
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-xl font-bold mb-4">Mã giảm giá</h2>
              {!appliedCoupon ? (
                <div className="flex gap-3">
                  <input
                    type="text"
                    value={couponCode}
                    onChange={(e) => {
                      setCouponCode(e.target.value)
                      setCouponError('')
                    }}
                    placeholder="Nhập mã giảm giá"
                    className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                  />
                  <button
                    onClick={handleApplyCoupon}
                    disabled={applyingCoupon}
                    className="btn btn-outline"
                  >
                    {applyingCoupon ? 'Đang xử lý...' : 'Áp dụng'}
                  </button>
                </div>
              ) : (
                <div className="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                  <div>
                    <div className="font-semibold text-green-800">
                      Mã: {appliedCoupon.code}
                    </div>
                    <div className="text-sm text-green-600">
                      Giảm {appliedCoupon.discount_percent}%
                    </div>
                  </div>
                  <button
                    onClick={removeCoupon}
                    className="text-red-600 hover:text-red-700 text-sm font-medium"
                  >
                    Xóa
                  </button>
                </div>
              )}
              {couponError && (
                <div className="mt-2 flex items-center text-red-600 text-sm">
                  <FiAlertCircle className="mr-2" />
                  {couponError}
                </div>
              )}
            </div>

            {/* Payment Methods */}
            <div className="bg-white rounded-xl shadow-sm p-6">
              <h2 className="text-xl font-bold mb-4">Phương thức thanh toán</h2>
              <div className="space-y-3">
                {/* MoMo */}
                <label className="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary-500 transition-all hover:shadow-md">
                  <input
                    type="radio"
                    name="payment_gateway"
                    value="momo"
                    checked={selectedGateway === 'momo'}
                    onChange={(e) => setSelectedGateway(e.target.value)}
                    className="mt-1 mr-4"
                  />
                  <div className="flex-1">
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center">
                        <FiSmartphone className="w-5 h-5 text-pink-600 mr-2" />
                        <span className="font-semibold">MoMo</span>
                      </div>
                      <div className="flex items-center gap-2">
                        {qrCodes['momo'] && (
                          <span className="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">
                            Có QR Code
                          </span>
                        )}
                        <img
                          src="https://developers.momo.vn/v3/images/logo.png"
                          alt="MoMo"
                          className="h-6"
                          onError={(e) => {
                            e.target.style.display = 'none'
                          }}
                        />
                      </div>
                    </div>
                    <p className="text-sm text-gray-600">
                      Thanh toán qua ví điện tử MoMo
                      {qrCodes['momo'] && (
                        <span className="text-green-600 ml-2">• Hoặc quét QR code</span>
                      )}
                    </p>
                  </div>
                </label>

                {/* VNPay */}
                <label className="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary-500 transition-all hover:shadow-md">
                  <input
                    type="radio"
                    name="payment_gateway"
                    value="vnpay"
                    checked={selectedGateway === 'vnpay'}
                    onChange={(e) => setSelectedGateway(e.target.value)}
                    className="mt-1 mr-4"
                  />
                  <div className="flex-1">
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center">
                        <FiCreditCard className="w-5 h-5 text-blue-600 mr-2" />
                        <span className="font-semibold">VNPay</span>
                      </div>
                      <div className="flex items-center gap-2">
                        {qrCodes['vnpay'] && (
                          <span className="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">
                            Có QR Code
                          </span>
                        )}
                        <span className="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">
                          Phổ biến
                        </span>
                      </div>
                    </div>
                    <p className="text-sm text-gray-600">
                      Thanh toán qua thẻ ngân hàng, ví điện tử
                      {qrCodes['vnpay'] && (
                        <span className="text-green-600 ml-2">• Hoặc quét QR code</span>
                      )}
                    </p>
                  </div>
                </label>

                {/* Bank Transfer */}
                <label className="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary-500 transition-all hover:shadow-md">
                  <input
                    type="radio"
                    name="payment_gateway"
                    value="bank_transfer"
                    checked={selectedGateway === 'bank_transfer'}
                    onChange={(e) => setSelectedGateway(e.target.value)}
                    className="mt-1 mr-4"
                  />
                  <div className="flex-1">
                    <div className="flex items-center mb-2">
                      <FiCreditCard className="w-5 h-5 text-green-600 mr-2" />
                      <span className="font-semibold">Chuyển khoản ngân hàng</span>
                    </div>
                    <p className="text-sm text-gray-600">
                      Chuyển khoản trực tiếp vào tài khoản ngân hàng
                    </p>
                  </div>
                </label>
              </div>

              {/* Warning khi gateway chưa config */}
              {selectedGateway && (selectedGateway === 'vnpay' || selectedGateway === 'momo') && 
               !qrCodes[selectedGateway] && (
                <motion.div
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="mt-6 p-4 bg-yellow-50 border-2 border-yellow-200 rounded-lg"
                >
                  <div className="flex items-start">
                    <FiAlertCircle className="w-5 h-5 text-yellow-600 mr-3 mt-0.5 flex-shrink-0" />
                    <div className="text-sm text-yellow-800">
                      <p className="font-semibold mb-1">
                        {selectedGateway === 'vnpay' ? 'VNPay' : 'MoMo'} chưa được cấu hình
                      </p>
                      <p>
                        Hệ thống sẽ tự động sử dụng QR code để thanh toán. 
                        Vui lòng quét mã QR bên dưới (nếu có) hoặc chọn phương thức chuyển khoản ngân hàng.
                      </p>
                    </div>
                  </div>
                </motion.div>
              )}

              {/* QR Code Display */}
              {selectedGateway && qrCodes[selectedGateway] && (
                <motion.div
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="mt-6 p-6 bg-gray-50 rounded-lg border-2 border-primary-200 qr-code-section"
                >
                  <h3 className="text-lg font-semibold mb-4 text-center">
                    Quét mã QR để thanh toán
                  </h3>
                  <div className="flex flex-col items-center">
                    {qrCodes[selectedGateway].qr_code_image && (
                      <div className="mb-4">
                        <img
                          src={(() => {
                            const baseUrl = qrCodes[selectedGateway].qr_code_image;
                            // Nếu là VietQR, thêm số tiền và nội dung
                            if (baseUrl.includes('vietqr.io') && selectedGateway === 'bank_transfer') {
                              const amount = Math.round(finalPrice);
                              const content = encodeURIComponent(`Thanh toan khoa hoc ${course?.course_name || ''}`);
                              return baseUrl.replace(/\?.*$/, '') + `?amount=${amount}&addInfo=${content}&accountName=${encodeURIComponent(qrCodes[selectedGateway].account_name || '')}`;
                            }
                            return baseUrl;
                          })()}
                          alt={`QR Code ${selectedGateway}`}
                          className="w-64 h-64 object-contain bg-white p-4 rounded-lg border-2 border-gray-200"
                          onError={(e) => {
                            e.target.src = 'https://via.placeholder.com/256?text=QR+Code+Not+Found'
                          }}
                        />
                      </div>
                    )}
                    
                    <div className="w-full space-y-2 text-sm text-center">
                      {qrCodes[selectedGateway].phone_number && (
                        <div className="bg-white p-3 rounded-lg">
                          <span className="font-semibold">Số điện thoại:</span>{' '}
                          <span className="text-primary-600 font-mono">
                            {qrCodes[selectedGateway].phone_number}
                          </span>
                        </div>
                      )}
                      
                      {qrCodes[selectedGateway].account_number && (
                        <div className="bg-white p-3 rounded-lg">
                          <div>
                            <span className="font-semibold">Số tài khoản:</span>{' '}
                            <span className="text-primary-600 font-mono">
                              {qrCodes[selectedGateway].account_number}
                            </span>
                          </div>
                          {qrCodes[selectedGateway].account_name && (
                            <div className="mt-1">
                              <span className="font-semibold">Chủ tài khoản:</span>{' '}
                              <span className="text-gray-700">
                                {qrCodes[selectedGateway].account_name}
                              </span>
                            </div>
                          )}
                          {qrCodes[selectedGateway].bank_name && (
                            <div className="mt-1">
                              <span className="font-semibold">Ngân hàng:</span>{' '}
                              <span className="text-gray-700">
                                {qrCodes[selectedGateway].bank_name}
                              </span>
                            </div>
                          )}
                        </div>
                      )}
                      
                      {qrCodes[selectedGateway].description && (
                        <div className="bg-blue-50 p-3 rounded-lg text-blue-800 text-xs">
                          {qrCodes[selectedGateway].description}
                        </div>
                      )}
                      
                      {/* Hiển thị số tiền cần thanh toán */}
                      <div className="bg-white p-4 rounded-lg border-2 border-primary-300 mt-3">
                        <div className="text-center">
                          <span className="text-sm text-gray-600">Số tiền cần thanh toán:</span>
                          <div className="text-2xl font-bold text-primary-600 mt-1">
                            {formatPrice(finalPrice)}
                          </div>
                        </div>
                      </div>
                      
                      {/* Nút xác nhận đã thanh toán */}
                      <button
                        onClick={async () => {
                          // Kiểm tra paymentId
                          if (!paymentId) {
                            toast.error('Không tìm thấy thông tin thanh toán. Vui lòng thử lại.');
                            return;
                          }
                          
                          if (window.confirm('Bạn đã hoàn tất thanh toán chưa?')) {
                            try {
                              const confirmResponse = await paymentsAPI.confirm(paymentId);
                              if (confirmResponse.success) {
                                toast.success('Xác nhận thanh toán thành công! Đang chuyển đến khóa học...');
                                setTimeout(() => {
                                  navigate(`/courses/${id}`);
                                }, 1500);
                              } else {
                                toast.error(confirmResponse.message || 'Xác nhận thanh toán thất bại');
                              }
                            } catch (error) {
                              console.error('Confirm payment error:', error);
                              toast.error(error.response?.data?.message || error.message || 'Có lỗi xảy ra khi xác nhận thanh toán');
                            }
                          }
                        }}
                        disabled={!paymentId}
                        className={`w-full mt-4 ${!paymentId ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'} text-white font-semibold py-3 px-6 rounded-lg transition-colors`}
                      >
                        ✓ Tôi đã thanh toán
                      </button>
                      
                      <p className="text-xs text-gray-500 text-center mt-2">
                        Vui lòng chỉ nhấn nút này sau khi đã chuyển khoản thành công
                      </p>
                    </div>
                  </div>
                </motion.div>
              )}
            </div>

            {/* Security Notice */}
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start">
              <FiShield className="w-5 h-5 text-blue-600 mr-3 mt-0.5 flex-shrink-0" />
              <div className="text-sm text-blue-800">
                <p className="font-semibold mb-1">Thanh toán an toàn</p>
                <p className="text-blue-700">
                  Thông tin thanh toán của bạn được mã hóa và bảo mật. Chúng tôi không lưu trữ thông tin thẻ của bạn.
                </p>
              </div>
            </div>
          </div>

          {/* Sidebar - Order Summary */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-xl shadow-sm p-6 sticky top-24">
              <h2 className="text-xl font-bold mb-6">Tóm tắt đơn hàng</h2>

              <div className="space-y-4 mb-6">
                <div className="flex justify-between text-gray-600">
                  <span>Giá gốc:</span>
                  <span>{formatPrice(originalPrice)}</span>
                </div>

                {appliedCoupon && (
                  <div className="flex justify-between text-green-600">
                    <span>Giảm giá ({appliedCoupon.discount_percent}%):</span>
                    <span>-{formatPrice(discount)}</span>
                  </div>
                )}

                <div className="border-t border-gray-200 pt-4">
                  <div className="flex justify-between items-center">
                    <span className="font-semibold text-lg">Tổng cộng:</span>
                    <span className="font-bold text-2xl text-primary-600">
                      {formatPrice(finalPrice)}
                    </span>
                  </div>
                </div>
              </div>

              <button
                onClick={handlePayment}
                disabled={processing || !selectedGateway}
                className="w-full btn btn-primary py-4 text-lg font-semibold mb-4 flex items-center justify-center"
              >
                {processing ? (
                  <span className="flex items-center">
                    <div className="spinner mr-2"></div>
                    Đang xử lý...
                  </span>
                ) : (
                  <>
                    <FiLock className="mr-2" />
                    Thanh toán ngay
                  </>
                )}
              </button>

              <div className="space-y-2 text-sm text-gray-600">
                <div className="flex items-start">
                  <FiCheck className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                  <span>Truy cập trọn đời</span>
                </div>
                <div className="flex items-start">
                  <FiCheck className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                  <span>Chứng chỉ hoàn thành</span>
                </div>
                <div className="flex items-start">
                  <FiCheck className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                  <span>Hỗ trợ 24/7</span>
                </div>
                <div className="flex items-start">
                  <FiCheck className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                  <span>Hoàn tiền trong 30 ngày</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Checkout

