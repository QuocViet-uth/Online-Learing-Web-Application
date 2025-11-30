import { Link } from 'react-router-dom'
import { FiFacebook, FiTwitter, FiInstagram, FiYoutube, FiMail, FiPhone, FiClock } from 'react-icons/fi'

const Footer = () => {
  return (
    <footer className="bg-gray-900 text-gray-300 mt-auto">
      <div className="container-custom py-6 sm:py-8 md:py-12 px-3 sm:px-4">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
          {/* About */}
          <div className="col-span-2 lg:col-span-1">
            <h3 className="text-white text-lg font-bold mb-3 md:mb-4">Online Learning</h3>
            <p className="text-xs md:text-sm mb-4 text-gray-400 leading-relaxed">
              Nền tảng học trực tuyến hàng đầu, mang đến trải nghiệm học tập tốt nhất cho mọi người.
            </p>
            <div className="flex space-x-3">
              <a href="#" className="hover:text-primary-400 transition-colors" aria-label="Facebook">
                <FiFacebook className="w-5 h-5" />
              </a>
              <a href="#" className="hover:text-primary-400 transition-colors" aria-label="Twitter">
                <FiTwitter className="w-5 h-5" />
              </a>
              <a href="#" className="hover:text-primary-400 transition-colors" aria-label="Instagram">
                <FiInstagram className="w-5 h-5" />
              </a>
              <a href="#" className="hover:text-primary-400 transition-colors" aria-label="YouTube">
                <FiYoutube className="w-5 h-5" />
              </a>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h4 className="text-white font-semibold mb-3 md:mb-4 text-sm md:text-base">Liên kết nhanh</h4>
            <ul className="space-y-2 text-xs md:text-sm">
              <li>
                <Link to="/courses" className="hover:text-primary-400 transition-colors block">
                  Khóa học
                </Link>
              </li>
              <li>
                <Link to="/" className="hover:text-primary-400 transition-colors block">
                  Trang chủ
                </Link>
              </li>
              <li>
                <Link to="/register" className="hover:text-primary-400 transition-colors block">
                  Đăng ký
                </Link>
              </li>
              <li>
                <Link to="/login" className="hover:text-primary-400 transition-colors block">
                  Đăng nhập
                </Link>
              </li>
            </ul>
          </div>

          {/* Support */}
          <div>
            <h4 className="text-white font-semibold mb-3 md:mb-4 text-sm md:text-base">Hỗ trợ</h4>
            <ul className="space-y-2 text-xs md:text-sm">
              <li>
                <a href="mailto:support@onlinelearning.com" className="hover:text-primary-400 transition-colors block">
                  Trung tâm trợ giúp
                </a>
              </li>
              <li>
                <a href="#" className="hover:text-primary-400 transition-colors block">
                  Điều khoản sử dụng
                </a>
              </li>
              <li>
                <a href="#" className="hover:text-primary-400 transition-colors block">
                  Chính sách bảo mật
                </a>
              </li>
              <li>
                <a href="#" className="hover:text-primary-400 transition-colors block">
                  Chính sách hoàn tiền
                </a>
              </li>
            </ul>
          </div>

          {/* Contact */}
          <div className="col-span-2 lg:col-span-1">
            <h4 className="text-white font-semibold mb-3 md:mb-4 text-sm md:text-base">Liên hệ</h4>
            <ul className="space-y-2 text-xs md:text-sm">
              <li className="flex items-start space-x-2">
                <FiMail className="w-4 h-4 mt-0.5 flex-shrink-0" />
                <a href="mailto:support@onlinelearning.com" className="hover:text-primary-400 transition-colors break-all">
                  support@onlinelearning.com
                </a>
              </li>
              <li className="flex items-start space-x-2">
                <FiPhone className="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>Hotline: <a href="tel:19001234" className="hover:text-primary-400 transition-colors">1900 1234</a></span>
              </li>
              <li className="flex items-start space-x-2 mt-3 md:mt-4">
                <FiClock className="w-4 h-4 mt-0.5 flex-shrink-0" />
                <p className="text-xs text-gray-500">
                  Thứ 2 - Chủ nhật: 8:00 - 22:00
                </p>
              </li>
            </ul>
          </div>
        </div>

        <div className="border-t border-gray-800 mt-6 md:mt-8 pt-6 md:pt-8 text-center text-xs md:text-sm">
          <p>&copy; {new Date().getFullYear()} Online Learning. Tất cả quyền được bảo lưu.</p>
        </div>
      </div>
    </footer>
  )
}

export default Footer

