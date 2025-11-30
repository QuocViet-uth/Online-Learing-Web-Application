import { useState } from 'react'
import { FiX, FiUpload, FiPlus, FiTrash2 } from 'react-icons/fi'
import { assignmentsAPI } from '../../services/api'
import toast from 'react-hot-toast'

const CreateAssignmentModal = ({ isOpen, onClose, onSuccess, courseId }) => {
  const [loading, setLoading] = useState(false)
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    type: 'homework',
    time_limit: '',
    start_date: '',
    deadline: '',
    max_score: 100
  })
  const [quizQuestions, setQuizQuestions] = useState([])

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({
      ...prev,
      [name]: name === 'max_score' ? parseFloat(value) || 0 : value
    }))
    
    // Nếu đổi type, reset quiz questions
    if (name === 'type' && value === 'homework') {
      setQuizQuestions([])
    }
  }

  const addQuizQuestion = () => {
    setQuizQuestions(prev => [...prev, {
      question_text: '',
      answers: [
        { answer_text: '', is_correct: false },
        { answer_text: '', is_correct: false },
        { answer_text: '', is_correct: false },
        { answer_text: '', is_correct: false }
      ]
    }])
  }

  const removeQuizQuestion = (index) => {
    setQuizQuestions(prev => prev.filter((_, i) => i !== index))
  }

  const updateQuizQuestion = (index, field, value) => {
    setQuizQuestions(prev => prev.map((q, i) => 
      i === index ? { ...q, [field]: value } : q
    ))
  }

  const updateQuizAnswer = (questionIndex, answerIndex, field, value) => {
    setQuizQuestions(prev => prev.map((q, qIndex) => {
      if (qIndex === questionIndex) {
        const newAnswers = q.answers.map((a, aIndex) => {
          if (aIndex === answerIndex) {
            if (field === 'is_correct') {
              // Chỉ cho phép 1 đáp án đúng, bỏ chọn các đáp án khác
              return { ...a, is_correct: value }
            }
            return { ...a, [field]: value }
          }
          // Nếu chọn đáp án này là đúng, bỏ chọn các đáp án khác
          if (field === 'is_correct' && value && aIndex !== answerIndex) {
            return { ...a, is_correct: false }
          }
          return a
        })
        return { ...q, answers: newAnswers }
      }
      return q
    }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    
    if (!courseId) {
      toast.error('Không tìm thấy thông tin khóa học')
      return
    }

    // Validate
    if (!formData.title.trim()) {
      toast.error('Vui lòng nhập tiêu đề bài tập')
      return
    }

    if (!formData.deadline) {
      toast.error('Vui lòng chọn hạn nộp')
      return
    }

    // Validate start_date < deadline nếu có start_date
    if (formData.start_date && formData.deadline) {
      if (new Date(formData.start_date) >= new Date(formData.deadline)) {
        toast.error('Thời gian bắt đầu phải trước hạn nộp')
        return
      }
    }

    if (formData.max_score < 0) {
      toast.error('Điểm tối đa phải >= 0')
      return
    }

    // Validate quiz questions nếu là quiz
    if (formData.type === 'quiz') {
      if (quizQuestions.length === 0) {
        toast.error('Quiz phải có ít nhất 1 câu hỏi')
        return
      }
      
      for (let i = 0; i < quizQuestions.length; i++) {
        const q = quizQuestions[i]
        if (!q.question_text.trim()) {
          toast.error(`Câu hỏi ${i + 1} không được để trống`)
          return
        }
        if (q.answers.length !== 4) {
          toast.error(`Câu hỏi ${i + 1} phải có đúng 4 đáp án`)
          return
        }
        let hasCorrect = false
        for (let j = 0; j < q.answers.length; j++) {
          if (!q.answers[j].answer_text.trim()) {
            toast.error(`Đáp án ${j + 1} của câu hỏi ${i + 1} không được để trống`)
            return
          }
          if (q.answers[j].is_correct) {
            hasCorrect = true
          }
        }
        if (!hasCorrect) {
          toast.error(`Câu hỏi ${i + 1} phải có ít nhất 1 đáp án đúng`)
          return
        }
      }
    }

    try {
      setLoading(true)
      const submitData = {
        ...formData,
        course_id: courseId
      }
      
      // Xử lý time_limit - chuyển chuỗi rỗng thành null hoặc không gửi
      if (submitData.time_limit === '' || submitData.time_limit === null) {
        submitData.time_limit = null
      } else {
        submitData.time_limit = parseInt(submitData.time_limit)
      }
      
      // Đảm bảo type được gửi đúng
      if (!submitData.type || !['homework', 'quiz'].includes(submitData.type)) {
        submitData.type = 'homework'
      }
      
      // Thêm questions nếu là quiz
      if (submitData.type === 'quiz') {
        submitData.questions = quizQuestions
      }
      
      const response = await assignmentsAPI.create(submitData)
      // API interceptor trả về response.data, nên response đã là { success, message, data }
      if (response && response.success) {
        toast.success('Tạo bài tập thành công!')
        // Reset form
        setFormData({
          title: '',
          description: '',
          type: 'homework',
          time_limit: '',
          start_date: '',
          deadline: '',
          max_score: 100
        })
        setQuizQuestions([])
        // Gọi onSuccess với data từ response
        onSuccess && onSuccess(response.data || response)
        onClose()
      } else {
        toast.error(response?.message || 'Không thể tạo bài tập')
      }
    } catch (error) {
      console.error('Error creating assignment:', error)
      const errorMessage = error.response?.data?.message || error.message || 'Có lỗi xảy ra khi tạo bài tập'
      toast.error(errorMessage)
      
      // Hiển thị lỗi chi tiết nếu có
      if (error.response?.data?.errors) {
        error.response.data.errors.forEach(err => {
          toast.error(err)
        })
      }
    } finally {
      setLoading(false)
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-2 sm:p-4">
      <div className="bg-white rounded-lg sm:rounded-xl shadow-xl w-full max-w-2xl max-h-[95vh] sm:max-h-[90vh] overflow-y-auto flex flex-col">
        <div className="sticky top-0 bg-white border-b border-gray-200 px-3 sm:px-4 md:px-6 py-3 sm:py-4 flex items-center justify-between flex-shrink-0">
          <h2 className="text-lg sm:text-xl md:text-2xl font-bold">Tạo bài tập mới</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors p-1 sm:p-2"
            aria-label="Close"
          >
            <FiX className="w-5 h-5 sm:w-6 sm:h-6" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-3 sm:p-4 md:p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Tiêu đề bài tập <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              name="title"
              value={formData.title}
              onChange={handleChange}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="VD: Bài tập tuần 1 - Lập trình cơ bản"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Mô tả
            </label>
            <textarea
              name="description"
              value={formData.description}
              onChange={handleChange}
              rows="4"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              placeholder="Mô tả chi tiết về bài tập..."
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Loại bài tập
              </label>
              <select
                name="type"
                value={formData.type}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              >
                <option value="homework">Bài tập về nhà</option>
                <option value="quiz">Quiz</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Điểm tối đa
              </label>
              <input
                type="number"
                name="max_score"
                value={formData.max_score}
                onChange={handleChange}
                min="0"
                step="0.5"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="100"
              />
            </div>
          </div>

          {formData.type === 'quiz' && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Thời gian làm bài (phút)
              </label>
              <input
                type="number"
                name="time_limit"
                value={formData.time_limit}
                onChange={handleChange}
                min="1"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="Ví dụ: 30 (để trống = không giới hạn)"
              />
              <p className="text-xs text-gray-500 mt-1">
                Thời gian giới hạn để học viên làm bài quiz (tính bằng phút). Để trống nếu không giới hạn thời gian.
              </p>
            </div>
          )}

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Thời gian bắt đầu làm bài
              </label>
              <input
                type="datetime-local"
                name="start_date"
                value={formData.start_date}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Hạn nộp <span className="text-red-500">*</span>
              </label>
              <input
                type="datetime-local"
                name="deadline"
                value={formData.deadline}
                onChange={handleChange}
                required
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          </div>

          {/* Quiz Questions Section */}
          {formData.type === 'quiz' && (
            <div className="border-t border-gray-200 pt-4">
              <div className="flex items-center justify-between mb-4">
                <label className="block text-sm font-medium text-gray-700">
                  Câu hỏi Quiz
                </label>
                <button
                  type="button"
                  onClick={addQuizQuestion}
                  className="flex items-center px-3 py-1.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm"
                >
                  <FiPlus className="mr-1" />
                  Thêm câu hỏi
                </button>
              </div>
              
              {quizQuestions.length === 0 ? (
                <p className="text-sm text-gray-500 text-center py-4">
                  Chưa có câu hỏi nào. Nhấn "Thêm câu hỏi" để bắt đầu.
                </p>
              ) : (
                <div className="space-y-4">
                  {quizQuestions.map((question, qIndex) => (
                    <div key={qIndex} className="border border-gray-200 rounded-lg p-4">
                      <div className="flex items-start justify-between mb-3">
                        <h4 className="font-medium text-gray-700">Câu hỏi {qIndex + 1}</h4>
                        <button
                          type="button"
                          onClick={() => removeQuizQuestion(qIndex)}
                          className="text-red-600 hover:text-red-700"
                        >
                          <FiTrash2 />
                        </button>
                      </div>
                      
                      <div className="mb-4">
                        <label className="block text-sm font-medium text-gray-700 mb-1">
                          Nội dung câu hỏi <span className="text-red-500">*</span>
                        </label>
                        <textarea
                          value={question.question_text}
                          onChange={(e) => updateQuizQuestion(qIndex, 'question_text', e.target.value)}
                          rows="2"
                          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                          placeholder="Nhập câu hỏi..."
                        />
                      </div>
                      
                      <div className="space-y-2">
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Đáp án (chọn 1 đáp án đúng) <span className="text-red-500">*</span>
                        </label>
                        {question.answers.map((answer, aIndex) => (
                          <div key={aIndex} className="flex items-center space-x-2">
                            <input
                              type="radio"
                              name={`correct-${qIndex}`}
                              checked={answer.is_correct}
                              onChange={(e) => updateQuizAnswer(qIndex, aIndex, 'is_correct', e.target.checked)}
                              className="w-4 h-4 text-primary-600"
                            />
                            <input
                              type="text"
                              value={answer.answer_text}
                              onChange={(e) => updateQuizAnswer(qIndex, aIndex, 'answer_text', e.target.value)}
                              placeholder={`Đáp án ${aIndex + 1}`}
                              className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            />
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
              disabled={loading}
            >
              Hủy
            </button>
            <button
              type="submit"
              className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
              disabled={loading}
            >
              {loading ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                  Đang tạo...
                </>
              ) : (
                <>
                  <FiUpload className="mr-2" />
                  Tạo bài tập
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default CreateAssignmentModal

