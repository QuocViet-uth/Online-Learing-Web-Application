import { useState, useEffect } from 'react'
import { FiStar, FiSend, FiX } from 'react-icons/fi'
import { motion } from 'framer-motion'
import toast from 'react-hot-toast'
import { reviewsAPI } from '../../services/api'

const ReviewForm = ({ courseId, studentId, existingReview, onSuccess, onCancel }) => {
  const [rating, setRating] = useState(existingReview?.rating || 0)
  const [hoverRating, setHoverRating] = useState(0)
  const [comment, setComment] = useState(existingReview?.comment || '')
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (existingReview) {
      setRating(existingReview.rating)
      setComment(existingReview.comment || '')
    }
  }, [existingReview])

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    if (rating === 0) {
      toast.error('Vui lòng chọn số sao đánh giá')
      return
    }
    
    if (!comment.trim()) {
      toast.error('Vui lòng nhập bình luận')
      return
    }
    
    setSubmitting(true)
    
    try {
      const reviewData = {
        course_id: courseId,
        student_id: studentId,
        rating: rating,
        comment: comment.trim()
      }
      
      const response = await reviewsAPI.create(reviewData)
      
      if (response && response.success) {
        toast.success(existingReview ? 'Cập nhật đánh giá thành công' : 'Đánh giá thành công')
        if (onSuccess) {
          onSuccess(response.data)
        }
      } else {
        toast.error(response?.message || 'Có lỗi xảy ra')
      }
    } catch (error) {
      console.error('Error submitting review:', error)
      toast.error(error?.response?.data?.message || 'Có lỗi xảy ra khi đánh giá')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="bg-white rounded-xl shadow-sm p-6 border border-gray-200"
    >
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-xl font-bold">
          {existingReview ? 'Chỉnh sửa đánh giá' : 'Viết đánh giá'}
        </h3>
        {onCancel && (
          <button
            onClick={onCancel}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <FiX className="w-5 h-5" />
          </button>
        )}
      </div>
      
      <form onSubmit={handleSubmit}>
        {/* Rating Stars */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Đánh giá của bạn *
          </label>
          <div className="flex items-center gap-2">
            {[1, 2, 3, 4, 5].map((star) => (
              <button
                key={star}
                type="button"
                onClick={() => setRating(star)}
                onMouseEnter={() => setHoverRating(star)}
                onMouseLeave={() => setHoverRating(0)}
                className="focus:outline-none transition-transform hover:scale-110"
              >
                <FiStar
                  className={`w-8 h-8 ${
                    star <= (hoverRating || rating)
                      ? 'text-yellow-400 fill-yellow-400'
                      : 'text-gray-300'
                  }`}
                />
              </button>
            ))}
            {rating > 0 && (
              <span className="ml-2 text-sm text-gray-600">
                {rating} {rating === 1 ? 'sao' : 'sao'}
              </span>
            )}
          </div>
        </div>
        
        {/* Comment */}
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Bình luận *
          </label>
          <textarea
            value={comment}
            onChange={(e) => setComment(e.target.value)}
            rows={4}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
            placeholder="Chia sẻ trải nghiệm của bạn về khóa học này..."
            required
          />
          <p className="text-xs text-gray-500 mt-1">
            {comment.length} / 1000 ký tự
          </p>
        </div>
        
        {/* Submit Button */}
        <div className="flex items-center justify-end gap-3">
          {onCancel && (
            <button
              type="button"
              onClick={onCancel}
              className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
            >
              Hủy
            </button>
          )}
          <button
            type="submit"
            disabled={submitting || rating === 0 || !comment.trim()}
            className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            {submitting ? (
              <>
                <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                Đang gửi...
              </>
            ) : (
              <>
                <FiSend className="w-4 h-4" />
                {existingReview ? 'Cập nhật' : 'Gửi đánh giá'}
              </>
            )}
          </button>
        </div>
      </form>
    </motion.div>
  )
}

export default ReviewForm

