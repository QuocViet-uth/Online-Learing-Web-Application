import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { FiPlus, FiEdit, FiTrash2, FiImage, FiUpload, FiX, FiAlertCircle } from 'react-icons/fi'
import { paymentQRCodesAPI } from '../../services/api'
import toast from 'react-hot-toast'

const AdminPaymentQRCodes = () => {
  const [qrCodes, setQRCodes] = useState([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [editingQRCode, setEditingQRCode] = useState(null)
  const [deletingId, setDeletingId] = useState(null)
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(null)
  const [formData, setFormData] = useState({
    payment_gateway: '',
    qr_code_image: '',
    account_number: '',
    account_name: '',
    bank_name: '',
    phone_number: '',
    description: '',
    status: 'active'
  })

  useEffect(() => {
    loadQRCodes()
  }, [])

  const loadQRCodes = async () => {
    try {
      setLoading(true)
      const response = await paymentQRCodesAPI.getAll()
      
      if (response.success) {
        setQRCodes(response.data || [])
      } else {
        toast.error(response.message || 'Không thể tải danh sách QR codes')
        setQRCodes([])
      }
    } catch (error) {
      console.error('Load QR codes error:', error)
      toast.error(error.message || 'Không thể tải danh sách QR codes')
      setQRCodes([])
    } finally {
      setLoading(false)
    }
  }

  const handleOpenModal = (qrCode = null) => {
    if (qrCode) {
      setEditingQRCode(qrCode)
      setFormData({
        payment_gateway: qrCode.payment_gateway || '',
        qr_code_image: qrCode.qr_code_image || '',
        account_number: qrCode.account_number || '',
        account_name: qrCode.account_name || '',
        bank_name: qrCode.bank_name || '',
        phone_number: qrCode.phone_number || '',
        description: qrCode.description || '',
        status: qrCode.status || 'active'
      })
    } else {
      setEditingQRCode(null)
      setFormData({
        payment_gateway: '',
        qr_code_image: '',
        account_number: '',
        account_name: '',
        bank_name: '',
        phone_number: '',
        description: '',
        status: 'active'
      })
    }
    setShowModal(true)
  }

  const handleCloseModal = () => {
    setShowModal(false)
    setEditingQRCode(null)
    setFormData({
      payment_gateway: '',
      qr_code_image: '',
      account_number: '',
      account_name: '',
      bank_name: '',
      phone_number: '',
      description: '',
      status: 'active'
    })
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    try {
      let response
      if (editingQRCode) {
        response = await paymentQRCodesAPI.update(editingQRCode.id, formData)
      } else {
        response = await paymentQRCodesAPI.create(formData)
      }

      if (response.success) {
        toast.success(editingQRCode ? 'Cập nhật QR code thành công!' : 'Tạo QR code thành công!')
        handleCloseModal()
        loadQRCodes()
      } else {
        toast.error(response.message || 'Có lỗi xảy ra')
      }
    } catch (error) {
      console.error('Submit QR code error:', error)
      toast.error(error.message || 'Có lỗi xảy ra')
    }
  }

  const handleDelete = async (id) => {
    setDeletingId(id)
    try {
      const result = await paymentQRCodesAPI.delete(id)
      if (result.success) {
        toast.success('Xóa QR code thành công!')
        loadQRCodes()
      } else {
        toast.error(result.message || 'Xóa QR code thất bại')
      }
    } catch (error) {
      console.error('Delete QR code error:', error)
      toast.error(error.message || 'Có lỗi xảy ra khi xóa QR code')
    } finally {
      setDeletingId(null)
      setShowDeleteConfirm(null)
    }
  }

  const getGatewayLabel = (gateway) => {
    const labels = {
      momo: 'MoMo',
      vnpay: 'VNPay',
      bank_transfer: 'Chuyển khoản ngân hàng'
    }
    return labels[gateway] || gateway
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold mb-2">Quản lý mã QR thanh toán</h1>
              <p className="text-gray-600">Quản lý mã QR cho các phương thức thanh toán</p>
            </div>
            <button
              onClick={() => handleOpenModal()}
              className="btn btn-primary flex items-center"
            >
              <FiPlus className="mr-2" />
              Thêm QR code
            </button>
          </div>
        </div>

        {/* QR Codes List */}
        <div className="card">
          {loading ? (
            <div className="text-center py-12">
              <div className="spinner mx-auto"></div>
            </div>
          ) : qrCodes.length === 0 ? (
            <div className="text-center py-12 text-gray-600">
              <FiImage className="w-16 h-16 mx-auto mb-4 text-gray-300" />
              <p>Chưa có QR code nào</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {qrCodes.map((qrCode) => (
                <motion.div
                  key={qrCode.id}
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  className="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow"
                >
                  <div className="flex items-center justify-between mb-4">
                    <span className="badge badge-primary">
                      {getGatewayLabel(qrCode.payment_gateway)}
                    </span>
                    <span className={`badge ${
                      qrCode.status === 'active' ? 'badge-success' : 'badge-secondary'
                    }`}>
                      {qrCode.status === 'active' ? 'Hoạt động' : 'Tạm dừng'}
                    </span>
                  </div>

                  {qrCode.qr_code_image && (
                    <div className="mb-4 flex justify-center">
                      <img
                        src={qrCode.qr_code_image}
                        alt={`QR Code ${qrCode.payment_gateway}`}
                        className="w-48 h-48 object-contain border border-gray-200 rounded-lg"
                        onError={(e) => {
                          e.target.src = 'https://via.placeholder.com/200?text=QR+Code'
                        }}
                      />
                    </div>
                  )}

                  <div className="space-y-2 text-sm mb-4">
                    {qrCode.account_number && (
                      <div>
                        <span className="font-semibold">Số tài khoản:</span> {qrCode.account_number}
                      </div>
                    )}
                    {qrCode.account_name && (
                      <div>
                        <span className="font-semibold">Chủ tài khoản:</span> {qrCode.account_name}
                      </div>
                    )}
                    {qrCode.bank_name && (
                      <div>
                        <span className="font-semibold">Ngân hàng:</span> {qrCode.bank_name}
                      </div>
                    )}
                    {qrCode.phone_number && (
                      <div>
                        <span className="font-semibold">Số điện thoại:</span> {qrCode.phone_number}
                      </div>
                    )}
                    {qrCode.description && (
                      <div className="text-gray-600">
                        {qrCode.description}
                      </div>
                    )}
                  </div>

                  <div className="flex gap-2">
                    <button
                      onClick={() => handleOpenModal(qrCode)}
                      className="flex-1 btn btn-outline text-sm"
                    >
                      <FiEdit className="mr-2" />
                      Sửa
                    </button>
                    <button
                      onClick={() => setShowDeleteConfirm(qrCode.id)}
                      disabled={deletingId === qrCode.id}
                      className="flex-1 btn bg-red-600 hover:bg-red-700 text-white text-sm"
                    >
                      <FiTrash2 className="mr-2" />
                      Xóa
                    </button>
                  </div>
                </motion.div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Create/Edit Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
          >
            <div className="p-6">
              <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold">
                  {editingQRCode ? 'Chỉnh sửa QR code' : 'Thêm QR code mới'}
                </h2>
                <button
                  onClick={handleCloseModal}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <FiX className="w-6 h-6" />
                </button>
              </div>

              <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Phương thức thanh toán *
                  </label>
                  <select
                    value={formData.payment_gateway}
                    onChange={(e) => setFormData({ ...formData, payment_gateway: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                    required
                    disabled={!!editingQRCode}
                  >
                    <option value="">Chọn phương thức</option>
                    <option value="momo">MoMo</option>
                    <option value="vnpay">VNPay</option>
                    <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    URL ảnh QR code *
                  </label>
                  <input
                    type="text"
                    value={formData.qr_code_image}
                    onChange={(e) => setFormData({ ...formData, qr_code_image: e.target.value })}
                    placeholder="https://example.com/qr-code.png"
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                    required
                  />
                  <p className="text-xs text-gray-500 mt-1">
                    Nhập URL hoặc đường dẫn đến ảnh QR code
                  </p>
                </div>

                {formData.payment_gateway === 'bank_transfer' && (
                  <>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Số tài khoản
                      </label>
                      <input
                        type="text"
                        value={formData.account_number}
                        onChange={(e) => setFormData({ ...formData, account_number: e.target.value })}
                        placeholder="1234567890"
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Tên chủ tài khoản
                      </label>
                      <input
                        type="text"
                        value={formData.account_name}
                        onChange={(e) => setFormData({ ...formData, account_name: e.target.value })}
                        placeholder="NGUYEN VAN A"
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                      />
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Tên ngân hàng
                      </label>
                      <input
                        type="text"
                        value={formData.bank_name}
                        onChange={(e) => setFormData({ ...formData, bank_name: e.target.value })}
                        placeholder="Vietcombank"
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                      />
                    </div>
                  </>
                )}

                {formData.payment_gateway === 'momo' && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Số điện thoại
                    </label>
                    <input
                      type="text"
                      value={formData.phone_number}
                      onChange={(e) => setFormData({ ...formData, phone_number: e.target.value })}
                      placeholder="0901234567"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                  </div>
                )}

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Mô tả
                  </label>
                  <textarea
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    placeholder="Mô tả thêm về QR code..."
                    rows="3"
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Trạng thái
                  </label>
                  <select
                    value={formData.status}
                    onChange={(e) => setFormData({ ...formData, status: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                  >
                    <option value="active">Hoạt động</option>
                    <option value="inactive">Tạm dừng</option>
                  </select>
                </div>

                <div className="flex gap-3 pt-4">
                  <button
                    type="button"
                    onClick={handleCloseModal}
                    className="flex-1 btn btn-outline"
                  >
                    Hủy
                  </button>
                  <button
                    type="submit"
                    className="flex-1 btn btn-primary"
                  >
                    {editingQRCode ? 'Cập nhật' : 'Tạo mới'}
                  </button>
                </div>
              </form>
            </div>
          </motion.div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteConfirm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-white rounded-xl shadow-xl max-w-md w-full p-6"
          >
            <div className="flex items-center mb-4">
              <div className="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <FiAlertCircle className="w-6 h-6 text-red-600" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900">Xác nhận xóa</h3>
                <p className="text-gray-600 text-sm mt-1">Hành động này không thể hoàn tác</p>
              </div>
            </div>
            
            <p className="text-gray-700 mb-6">
              Bạn có chắc chắn muốn xóa QR code này?
            </p>
            
            <div className="flex gap-3">
              <button
                onClick={() => setShowDeleteConfirm(null)}
                disabled={deletingId === showDeleteConfirm}
                className="flex-1 btn btn-outline"
              >
                Hủy
              </button>
              <button
                onClick={() => handleDelete(showDeleteConfirm)}
                disabled={deletingId === showDeleteConfirm}
                className="flex-1 btn bg-red-600 hover:bg-red-700 text-white"
              >
                {deletingId === showDeleteConfirm ? (
                  <span className="flex items-center justify-center">
                    <div className="spinner mr-2"></div>
                    Đang xóa...
                  </span>
                ) : (
                  'Xóa'
                )}
              </button>
            </div>
          </motion.div>
        </div>
      )}
    </div>
  )
}

export default AdminPaymentQRCodes

