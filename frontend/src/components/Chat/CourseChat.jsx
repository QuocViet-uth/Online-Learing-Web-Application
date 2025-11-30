import { useState, useEffect, useRef } from 'react'
import { useAuth } from '../../contexts/AuthContext'
import { FiSend, FiMessageCircle, FiX } from 'react-icons/fi'
import { motion, AnimatePresence } from 'framer-motion'
import toast from 'react-hot-toast'
import { chatAPI } from '../../services/api'
import { formatDateTime } from '../../utils/dateTime'

const CourseChat = ({ courseId, courseName, isOpen, onClose }) => {
  const { user } = useAuth()
  const [messages, setMessages] = useState([])
  const [newMessage, setNewMessage] = useState('')
  const [loading, setLoading] = useState(false)
  const [sending, setSending] = useState(false)
  const messagesEndRef = useRef(null)
  const messagesContainerRef = useRef(null)
  const shouldAutoScrollRef = useRef(true)

  useEffect(() => {
    if (isOpen && courseId && user) {
      shouldAutoScrollRef.current = true
      loadMessages()
      // Auto refresh mỗi 3 giây
      const interval = setInterval(() => {
        loadMessages()
      }, 3000)
      return () => clearInterval(interval)
    }
  }, [isOpen, courseId, user])

  // Chỉ scroll khi cần thiết (user ở gần cuối hoặc gửi tin nhắn mới)
  useEffect(() => {
    if (shouldAutoScrollRef.current && messagesContainerRef.current) {
      const container = messagesContainerRef.current
      const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100
      
      if (isNearBottom || messages.length === 0) {
        scrollToBottom()
      }
    }
  }, [messages])

  // Theo dõi scroll của user
  useEffect(() => {
    const container = messagesContainerRef.current
    if (!container) return

    const handleScroll = () => {
      const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100
      shouldAutoScrollRef.current = isNearBottom
    }

    container.addEventListener('scroll', handleScroll)
    return () => container.removeEventListener('scroll', handleScroll)
  }, [isOpen])

  const scrollToBottom = () => {
    if (messagesContainerRef.current) {
      messagesContainerRef.current.scrollTop = messagesContainerRef.current.scrollHeight
    }
  }

  const loadMessages = async () => {
    if (!courseId || !user) return
    
    try {
      const response = await chatAPI.getMessagesByCourse(courseId, user.id)
      if (response && response.success && response.data) {
        const messagesData = Array.isArray(response.data) ? response.data : []
        const messagesWithTime = messagesData.map(msg => ({
          ...msg,
          sent_at: msg.sent_at || msg.created_at || new Date().toISOString()
        }))
        setMessages(messagesWithTime)
      } else {
        setMessages([])
      }
    } catch (error) {
      console.error('CourseChat - Error loading messages:', error)
      // Silent fail for auto-refresh
    }
  }

  const handleSend = async (e) => {
    e.preventDefault()
    if (!newMessage.trim() || !user || !courseId) return

    setSending(true)
    try {
      const result = await chatAPI.sendMessage({
        sender_id: user.id,
        receiver_id: null, // Group chat trong course - để null để chat trong course
        course_id: parseInt(courseId),
        content: newMessage.trim(),
      })
      
      if (result.success) {
        setNewMessage('')
        shouldAutoScrollRef.current = true // Đảm bảo scroll khi gửi tin nhắn
        // Reload messages
        await loadMessages()
      } else {
        toast.error(result.message || 'Không thể gửi tin nhắn')
      }
    } catch (error) {
      console.error('Error sending message:', error)
      toast.error('Có lỗi xảy ra khi gửi tin nhắn')
    } finally {
      setSending(false)
    }
  }

  const formatTime = (dateString) => {
    return formatDateTime(dateString, {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  if (!isOpen) return null

  return (
    <AnimatePresence>
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        exit={{ opacity: 0, y: 20 }}
        className="fixed bottom-4 right-4 w-96 bg-white rounded-xl shadow-2xl flex flex-col z-50 border border-gray-200"
        style={{ maxHeight: '600px', height: '600px' }}
      >
        {/* Header */}
        <div className="bg-gradient-to-r from-primary-600 to-secondary-600 text-white p-4 rounded-t-xl flex items-center justify-between">
          <div className="flex items-center">
            <FiMessageCircle className="mr-2" />
            <div>
              <h3 className="font-semibold">{courseName || 'Chat'}</h3>
              <p className="text-xs text-white/80">Khóa học</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="text-white hover:bg-white/20 rounded-lg p-1 transition-colors"
          >
            <FiX className="w-5 h-5" />
          </button>
        </div>

        {/* Messages */}
        <div
          ref={messagesContainerRef}
          className="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50 custom-scrollbar"
          style={{ scrollBehavior: 'smooth' }}
        >
          {messages.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <FiMessageCircle className="w-12 h-12 mx-auto mb-2 text-gray-300" />
              <p className="text-sm">Chưa có tin nhắn nào</p>
              <p className="text-xs mt-1">Bắt đầu cuộc trò chuyện!</p>
            </div>
          ) : (
            messages.map((message) => {
              const isOwnMessage = message.sender_id === user?.id
              const isTeacher = message.sender_role === 'teacher'
              
              return (
                <motion.div
                  key={message.id}
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  className={`flex ${isOwnMessage ? 'justify-end' : 'justify-start'}`}
                >
                  <div className={`max-w-[75%] ${isOwnMessage ? 'order-2' : 'order-1'}`}>
                    {!isOwnMessage && (
                      <div className="flex items-center mb-1">
                        <span className={`text-xs font-medium font-sans ${
                          isTeacher ? 'text-primary-600' : 'text-gray-600'
                        }`}>
                          {message.sender_name}
                          {isTeacher && ' (Giảng viên)'}
                        </span>
                      </div>
                    )}
                    <div
                      className={`px-4 py-2 rounded-lg shadow-sm ${
                        isOwnMessage
                          ? 'bg-primary-600 text-white rounded-br-none'
                          : isTeacher
                          ? 'bg-primary-100 text-gray-900 rounded-bl-none border border-primary-200'
                          : 'bg-white text-gray-900 rounded-bl-none border border-gray-200'
                      }`}
                    >
                      <p className="whitespace-pre-wrap text-sm break-words font-sans">{message.content}</p>
                      <p className={`text-xs mt-1 font-sans ${
                        isOwnMessage ? 'text-white/70' : 'text-gray-500'
                      }`}>
                        {message.sent_at ? formatTime(message.sent_at) : (message.created_at ? formatTime(message.created_at) : '')}
                      </p>
                    </div>
                  </div>
                </motion.div>
              )
            })
          )}
          <div ref={messagesEndRef} />
        </div>

        {/* Input */}
        <form onSubmit={handleSend} className="p-4 border-t border-gray-200 bg-white rounded-b-xl shadow-sm">
          <div className="flex space-x-2">
            <input
              type="text"
              value={newMessage}
              onChange={(e) => setNewMessage(e.target.value)}
              placeholder="Nhập tin nhắn..."
              className="flex-1 input text-sm font-sans focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              disabled={sending}
              autoComplete="off"
            />
            <button
              type="submit"
              disabled={sending || !newMessage.trim() || !user}
              className="btn btn-primary px-4 py-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all hover:shadow-md"
            >
              <FiSend className="w-4 h-4" />
            </button>
          </div>
        </form>
      </motion.div>
    </AnimatePresence>
  )
}

export default CourseChat

