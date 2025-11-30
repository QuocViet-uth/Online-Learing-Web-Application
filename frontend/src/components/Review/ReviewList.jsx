import { useState, useEffect } from 'react'
import { FiStar, FiUser, FiMoreVertical, FiEdit, FiTrash2 } from 'react-icons/fi'
import { motion } from 'framer-motion'
import { formatRelativeTime } from '../../utils/dateTime'
import { reviewsAPI } from '../../services/api'
import { useAuth } from '../../contexts/AuthContext'
import toast from 'react-hot-toast'
import ReviewForm from './ReviewForm'

const ReviewList = ({ courseId, onReviewUpdate }) => {
  const { user } = useAuth()
  const [reviews, setReviews] = useState([])
  const [stats, setStats] = useState(null)
  const [loading, setLoading] = useState(true)
  const [editingReview, setEditingReview] = useState(null)
  const [showMoreMenu, setShowMoreMenu] = useState(null)

  useEffect(() => {
    loadReviews()
    loadStats()
  }, [courseId])

  const loadReviews = async () => {
    try {
      setLoading(true)
      const response = await reviewsAPI.getByCourse(courseId)
      if (response && response.success) {
        setReviews(response.data || [])
      }
    } catch (error) {
      console.error('Error loading reviews:', error)
      toast.error('Không thể tải đánh giá')
    } finally {
      setLoading(false)
    }
  }

  const loadStats = async () => {
    try {
      const response = await reviewsAPI.getStats(courseId)
      if (response && response.success) {
        setStats(response.data)
      }
    } catch (error) {
      console.error('Error loading stats:', error)
    }
  }

  const handleDelete = async (reviewId) => {
    if (!window.confirm('Bạn có chắc chắn muốn xóa đánh giá này?')) {
      return
    }

    try {
      const response = await reviewsAPI.delete(reviewId, user.id)
      if (response && response.success) {
        toast.success('Xóa đánh giá thành công')
        loadReviews()
        loadStats()
        if (onReviewUpdate) {
          onReviewUpdate()
        }
      } else {
        toast.error(response?.message || 'Không thể xóa đánh giá')
      }
    } catch (error) {
      console.error('Error deleting review:', error)
      toast.error('Có lỗi xảy ra khi xóa đánh giá')
    }
    setShowMoreMenu(null)
  }

  const handleEdit = (review) => {
    setEditingReview(review)
    setShowMoreMenu(null)
  }

  const handleReviewSuccess = () => {
    setEditingReview(null)
    loadReviews()
    loadStats()
    if (onReviewUpdate) {
      onReviewUpdate()
    }
  }

  const renderStars = (rating) => {
    return (
      <div className="flex items-center gap-1">
        {[1, 2, 3, 4, 5].map((star) => (
          <FiStar
            key={star}
            className={`w-4 h-4 ${
              star <= rating
                ? 'text-yellow-400 fill-yellow-400'
                : 'text-gray-300'
            }`}
          />
        ))}
      </div>
    )
  }

  if (loading) {
    return (
      <div className="text-center py-12">
        <div className="inline-block w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full animate-spin" />
        <p className="mt-4 text-gray-600">Đang tải đánh giá...</p>
      </div>
    )
  }

  return (
    <div>
      {/* Stats */}
      {stats && (
        <div className="bg-white rounded-xl shadow-sm p-6 mb-6 border border-gray-200">
          <div className="flex items-center justify-between mb-4">
            <div>
              <div className="flex items-center gap-2 mb-2">
                <span className="text-4xl font-bold">{stats.average_rating.toFixed(1)}</span>
                <div className="flex items-center">
                  <FiStar className="w-6 h-6 text-yellow-400 fill-yellow-400" />
                </div>
              </div>
              <p className="text-gray-600">
                {stats.total_reviews} {stats.total_reviews === 1 ? 'đánh giá' : 'đánh giá'}
              </p>
            </div>
            
            {/* Rating Distribution */}
            <div className="flex-1 max-w-md ml-8">
              {[5, 4, 3, 2, 1].map((star) => (
                <div key={star} className="flex items-center gap-2 mb-2">
                  <span className="text-sm text-gray-600 w-8">{star} sao</span>
                  <div className="flex-1 bg-gray-200 rounded-full h-2">
                    <div
                      className="bg-yellow-400 h-2 rounded-full"
                      style={{
                        width: `${stats.total_reviews > 0 ? (stats.distribution[star] / stats.total_reviews) * 100 : 0}%`
                      }}
                    />
                  </div>
                  <span className="text-sm text-gray-600 w-12 text-right">
                    {stats.distribution[star] || 0}
                  </span>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Edit Form */}
      {editingReview && (
        <div className="mb-6">
          <ReviewForm
            courseId={courseId}
            studentId={user.id}
            existingReview={editingReview}
            onSuccess={handleReviewSuccess}
            onCancel={() => setEditingReview(null)}
          />
        </div>
      )}

      {/* Reviews List */}
      {reviews.length === 0 ? (
        <div className="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200">
          <FiStar className="w-12 h-12 text-gray-300 mx-auto mb-4" />
          <p className="text-gray-600">Chưa có đánh giá nào</p>
        </div>
      ) : (
        <div className="space-y-4">
          {reviews.map((review) => (
            <motion.div
              key={review.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              className="bg-white rounded-xl shadow-sm p-6 border border-gray-200"
            >
              <div className="flex items-start justify-between">
                <div className="flex items-start gap-4 flex-1">
                  {/* Avatar */}
                  <div className="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0">
                    {review.student_avatar ? (
                      <img
                        src={review.student_avatar}
                        alt={review.student_name}
                        className="w-full h-full rounded-full object-cover"
                      />
                    ) : (
                      <FiUser className="w-6 h-6 text-primary-600" />
                    )}
                  </div>
                  
                  {/* Content */}
                  <div className="flex-1">
                    <div className="flex items-center gap-3 mb-2">
                      <h4 className="font-semibold text-gray-900">
                        {review.student_name}
                      </h4>
                      <div className="flex items-center gap-1">
                        {renderStars(review.rating)}
                      </div>
                      <span className="text-sm text-gray-500">
                        {formatRelativeTime(review.created_at)}
                      </span>
                    </div>
                    
                    {review.comment && (
                      <p className="text-gray-700 whitespace-pre-wrap">
                        {review.comment}
                      </p>
                    )}
                    
                    {review.updated_at && review.updated_at !== review.created_at && (
                      <p className="text-xs text-gray-400 mt-2">
                        Đã chỉnh sửa {formatRelativeTime(review.updated_at)}
                      </p>
                    )}
                  </div>
                </div>
                
                {/* Actions Menu */}
                {user && user.id === review.student_id && (
                  <div className="relative">
                    <button
                      onClick={() => setShowMoreMenu(showMoreMenu === review.id ? null : review.id)}
                      className="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                      <FiMoreVertical className="w-5 h-5" />
                    </button>
                    
                    {showMoreMenu === review.id && (
                      <div className="absolute right-0 top-10 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-10 min-w-[150px]">
                        <button
                          onClick={() => handleEdit(review)}
                          className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
                        >
                          <FiEdit className="w-4 h-4" />
                          Chỉnh sửa
                        </button>
                        <button
                          onClick={() => handleDelete(review.id)}
                          className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                        >
                          <FiTrash2 className="w-4 h-4" />
                          Xóa
                        </button>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </motion.div>
          ))}
        </div>
      )}
    </div>
  )
}

export default ReviewList

