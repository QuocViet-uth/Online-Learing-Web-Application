import { useState, useEffect, useRef } from 'react'
import { FiX, FiClock, FiCheckCircle } from 'react-icons/fi'
import { assignmentsAPI } from '../../services/api'
import toast from 'react-hot-toast'
import { motion } from 'framer-motion'

const QuizTakeModal = ({ isOpen, onClose, assignment, studentId, onSuccess }) => {
  const [loading, setLoading] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [questions, setQuestions] = useState([])
  const [answers, setAnswers] = useState({}) // { questionId: answerId }
  const [timeRemaining, setTimeRemaining] = useState(null) // seconds
  const [startTime, setStartTime] = useState(null)
  const timerRef = useRef(null)

  useEffect(() => {
    if (isOpen && assignment && assignment.id) {
      loadQuizQuestions()
      if (assignment.time_limit && assignment.time_limit > 0) {
        const timeLimitSeconds = assignment.time_limit * 60
        setTimeRemaining(timeLimitSeconds)
        setStartTime(Date.now())
      }
    }
    
    return () => {
      if (timerRef.current) {
        clearInterval(timerRef.current)
      }
    }
  }, [isOpen, assignment])

  useEffect(() => {
    if (timeRemaining !== null && timeRemaining > 0) {
      timerRef.current = setInterval(() => {
        setTimeRemaining(prev => {
          if (prev <= 1) {
            handleAutoSubmit()
            return 0
          }
          return prev - 1
        })
      }, 1000)
      
      return () => {
        if (timerRef.current) {
          clearInterval(timerRef.current)
        }
      }
    }
  }, [timeRemaining])

  const loadQuizQuestions = async () => {
    try {
      setLoading(true)
      const response = await assignmentsAPI.getQuizQuestions(assignment.id)
      if (response && response.success && response.data) {
        setQuestions(response.data)
        // Khởi tạo answers object
        const initialAnswers = {}
        response.data.forEach(q => {
          initialAnswers[q.id] = null
        })
        setAnswers(initialAnswers)
      } else {
        toast.error('Không thể tải câu hỏi quiz')
      }
    } catch (error) {
      console.error('Error loading quiz questions:', error)
      toast.error('Không thể tải câu hỏi quiz')
    } finally {
      setLoading(false)
    }
  }

  const handleAnswerChange = (questionId, answerId) => {
    setAnswers(prev => ({
      ...prev,
      [questionId]: answerId
    }))
  }

  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
  }

  const handleAutoSubmit = async () => {
    if (timerRef.current) {
      clearInterval(timerRef.current)
    }
    toast.info('Hết thời gian! Tự động nộp bài...')
    await handleSubmit(true)
  }

  const handleSubmit = async (isAutoSubmit = false) => {
    // Kiểm tra đã trả lời hết chưa
    const unansweredCount = questions.filter(q => !answers[q.id]).length
    if (!isAutoSubmit && unansweredCount > 0) {
      const confirm = window.confirm(
        `Bạn còn ${unansweredCount} câu hỏi chưa trả lời. Bạn có chắc chắn muốn nộp bài?`
      )
      if (!confirm) return
    }

    try {
      setSubmitting(true)
      
      // Tính thời gian làm bài
      const timeSpent = startTime ? Math.floor((Date.now() - startTime) / 1000) : null
      
      const response = await assignmentsAPI.submitQuiz(assignment.id, {
        student_id: studentId,
        answers: answers,
        time_spent: timeSpent
      })

      if (response && response.success) {
        toast.success('Nộp bài quiz thành công!')
        if (response.data && response.data.score !== null) {
          toast.success(`Điểm số của bạn: ${response.data.score}/${assignment.max_score}`)
        }
        onSuccess && onSuccess(response.data)
        onClose()
      } else {
        toast.error(response?.message || 'Không thể nộp bài quiz')
      }
    } catch (error) {
      console.error('Error submitting quiz:', error)
      toast.error('Có lỗi xảy ra khi nộp bài quiz')
    } finally {
      setSubmitting(false)
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-2 sm:p-4">
      <motion.div
        initial={{ opacity: 0, scale: 0.9 }}
        animate={{ opacity: 1, scale: 1 }}
        className="bg-white rounded-lg sm:rounded-xl shadow-xl w-full max-w-4xl max-h-[95vh] sm:max-h-[90vh] overflow-hidden flex flex-col"
      >
        {/* Header */}
        <div className="sticky top-0 bg-white border-b border-gray-200 px-3 sm:px-4 md:px-6 py-3 sm:py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 sm:gap-0">
          <div className="flex-1 min-w-0">
            <h2 className="text-lg sm:text-xl md:text-2xl font-bold line-clamp-2">{assignment.title}</h2>
            <p className="text-xs sm:text-sm text-gray-600 mt-1 line-clamp-1">{assignment.description}</p>
          </div>
          <div className="flex items-center gap-2 sm:gap-4 flex-shrink-0">
            {timeRemaining !== null && (
              <div className={`flex items-center space-x-1 sm:space-x-2 px-2 sm:px-3 md:px-4 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm ${
                timeRemaining < 300 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'
              }`}>
                <FiClock className="w-4 h-4 sm:w-5 sm:h-5" />
                <span className="font-bold sm:text-lg">{formatTime(timeRemaining)}</span>
              </div>
            )}
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 transition-colors p-1 sm:p-2"
              disabled={submitting}
              aria-label="Close"
            >
              <FiX className="w-5 h-5 sm:w-6 sm:h-6" />
            </button>
          </div>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-3 sm:p-4 md:p-6">
          {loading ? (
            <div className="text-center py-12">
              <div className="spinner mx-auto"></div>
              <p className="text-gray-600 mt-4">Đang tải câu hỏi...</p>
            </div>
          ) : questions.length === 0 ? (
            <div className="text-center py-12 text-gray-600">
              <p>Không có câu hỏi nào</p>
            </div>
          ) : (
            <div className="space-y-6">
              {questions.map((question, index) => (
                <motion.div
                  key={question.id}
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: index * 0.1 }}
                  className="border border-gray-200 rounded-lg p-6"
                >
                  <div className="flex items-start justify-between mb-4">
                    <h3 className="text-lg font-semibold">
                      Câu {index + 1}: {question.question_text}
                    </h3>
                    {answers[question.id] && (
                      <FiCheckCircle className="w-5 h-5 text-green-600 flex-shrink-0 ml-2" />
                    )}
                  </div>
                  
                  <div className="space-y-3">
                    {question.answers.map((answer) => (
                      <label
                        key={answer.id}
                        className={`flex items-start space-x-3 p-3 rounded-lg border-2 cursor-pointer transition-all ${
                          answers[question.id] === answer.id
                            ? 'border-primary-600 bg-primary-50'
                            : 'border-gray-200 hover:border-gray-300'
                        }`}
                      >
                        <input
                          type="radio"
                          name={`question-${question.id}`}
                          value={answer.id}
                          checked={answers[question.id] === answer.id}
                          onChange={() => handleAnswerChange(question.id, answer.id)}
                          className="mt-1 w-4 h-4 text-primary-600"
                        />
                        <span className="flex-1 text-gray-700">{answer.answer_text}</span>
                      </label>
                    ))}
                  </div>
                </motion.div>
              ))}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex items-center justify-between">
          <div className="text-sm text-gray-600">
            Đã trả lời: {Object.values(answers).filter(a => a !== null).length} / {questions.length} câu hỏi
          </div>
          <div className="flex space-x-3">
            <button
              onClick={onClose}
              className="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
              disabled={submitting}
            >
              Hủy
            </button>
            <button
              onClick={() => handleSubmit(false)}
              disabled={submitting || loading}
              className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
              {submitting ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                  Đang nộp...
                </>
              ) : (
                'Nộp bài'
              )}
            </button>
          </div>
        </div>
      </motion.div>
    </div>
  )
}

export default QuizTakeModal

