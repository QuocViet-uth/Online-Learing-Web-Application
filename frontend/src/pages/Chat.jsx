import { useState, useEffect, useRef } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { FiSend, FiMessageCircle, FiArrowLeft, FiBook, FiUsers } from 'react-icons/fi'
import { motion } from 'framer-motion'
import toast from 'react-hot-toast'
import { chatAPI } from '../services/api'
import { formatDateTime } from '../utils/dateTime'

const Chat = () => {
  const { user } = useAuth()
  const [conversations, setConversations] = useState([])
  const [selectedConversation, setSelectedConversation] = useState(null)
  const [messages, setMessages] = useState([])
  const [newMessage, setNewMessage] = useState('')
  const [loading, setLoading] = useState(false)
  const [sending, setSending] = useState(false)
  const messagesEndRef = useRef(null)
  const messagesContainerRef = useRef(null)
  const shouldAutoScrollRef = useRef(true)
  const isUserScrollingRef = useRef(false)
  
  const isTeacher = user?.role === 'teacher'

  useEffect(() => {
    if (user) {
      loadConversations()
      const interval = setInterval(loadConversations, 5000)
      return () => clearInterval(interval)
    }
  }, [user, isTeacher])

  useEffect(() => {
    if (selectedConversation && user) {
      shouldAutoScrollRef.current = true
      loadMessages()
      const interval = setInterval(loadMessages, 3000)
      return () => clearInterval(interval)
    }
  }, [selectedConversation, user])

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
      isUserScrollingRef.current = true
      
      // Reset flag sau khi scroll xong
      setTimeout(() => {
        isUserScrollingRef.current = false
      }, 100)
    }

    container.addEventListener('scroll', handleScroll)
    return () => container.removeEventListener('scroll', handleScroll)
  }, [selectedConversation])

  const scrollToBottom = () => {
    if (messagesContainerRef.current) {
      messagesContainerRef.current.scrollTop = messagesContainerRef.current.scrollHeight
    }
  }

  const loadConversations = async () => {
    if (!user) return
    
    try {
      if (isTeacher) {
        // Teacher: load conversations từ get-teacher-course-chats.php
        const response = await chatAPI.getTeacherCourseChats(user.id)
        if (response && response.success && response.data) {
          // Convert sang format tương thích với UI (mỗi course = 1 conversation)
          const formatted = response.data.map(course => ({
            course_id: course.id,
            course_name: course.course_name || course.title,
            course_title: course.title,
            other_user_id: null, // Group chat không có other_user_id
            other_user_name: course.title,
            other_user_avatar: course.thumbnail,
            other_user_role: null,
            last_message: course.last_message,
            last_message_time: course.last_message_time,
            unread_count: course.unread_count,
            total_students: course.total_students,
            total_messages: course.total_messages
          }))
          setConversations(formatted)
        } else {
          setConversations([])
        }
      } else {
        // Student: load conversations (courses có chat)
        const response = await chatAPI.getConversations(user.id)
        if (response.success && response.data) {
          // Filter chỉ lấy conversations có course_id (group chat)
          const courseConversations = response.data.filter(conv => conv.course_id)
          // Convert sang format tương thích với UI
          const formatted = courseConversations.map(conv => ({
            course_id: conv.course_id,
            course_name: conv.course_name,
            course_title: conv.course_name,
            other_user_id: null, // Group chat không có other_user_id
            other_user_name: conv.course_name,
            other_user_avatar: null,
            other_user_role: null,
            last_message: conv.last_message,
            last_message_time: conv.last_message_time,
            unread_count: conv.unread_count
          }))
          setConversations(formatted)
        } else {
          setConversations([])
        }
      }
    } catch (error) {
      console.error('Error loading conversations:', error)
      setConversations([])
    }
  }

  const loadMessages = async () => {
    if (!selectedConversation || !user) return
    
    try {
      if (selectedConversation.course_id) {
        // Cả teacher và student: load group chat trong course
        const response = await chatAPI.getMessagesByCourse(selectedConversation.course_id, user.id)
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
      } else {
        // Chat 1-1 (không có course_id)
        const response = await chatAPI.getConversation(
          user.id,
          selectedConversation.other_user_id,
          null
        )
        if (response.success && response.data) {
          const messagesData = Array.isArray(response.data) ? response.data : []
          const messagesWithTime = messagesData.map(msg => ({
            ...msg,
            sent_at: msg.sent_at || msg.created_at || new Date().toISOString()
          }))
          setMessages(messagesWithTime)
        } else {
          setMessages([])
        }
      }
    } catch (error) {
      console.error('Error loading messages:', error)
      setMessages([])
    }
  }

  const handleSelectConversation = (conversation) => {
    setSelectedConversation(conversation)
    setMessages([])
  }

  const handleSend = async (e) => {
    e.preventDefault()
    if (!newMessage.trim() || !user || !selectedConversation) return

    setSending(true)
    try {
      let result
      if (selectedConversation.course_id) {
        // Cả teacher và student: gửi tin nhắn trong group chat của course
        result = await chatAPI.sendMessage({
          sender_id: user.id,
          receiver_id: null, // Group chat
          course_id: selectedConversation.course_id,
          content: newMessage.trim(),
        })
      } else {
        // Chat 1-1 (không có course_id)
        result = await chatAPI.sendMessage({
          sender_id: user.id,
          receiver_id: selectedConversation.other_user_id,
          course_id: null,
          content: newMessage.trim(),
        })
      }
      
      if (result.success) {
        setNewMessage('')
        shouldAutoScrollRef.current = true // Đảm bảo scroll khi gửi tin nhắn
        await loadMessages()
        await loadConversations()
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
      minute: '2-digit',
      second: '2-digit'
    })
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50 flex flex-col">
      <div className="container-custom py-4 md:py-8 flex-1 flex flex-col max-w-6xl mx-auto px-4">
        <div className="card flex-1 flex flex-col overflow-hidden h-full">
          {/* Header */}
          <div className="border-b border-gray-200 pb-4 mb-4 px-6 pt-4">
            <h1 className="text-2xl font-bold flex items-center font-sans">
              <FiMessageCircle className="mr-2" />
              Chat
            </h1>
            <p className="text-gray-600 text-sm mt-1 font-sans">
              {isTeacher ? 'Xem và trả lời tin nhắn từ học viên theo từng khóa học' : 'Trò chuyện với giảng viên và học viên'}
            </p>
          </div>

          <div className="flex-1 flex overflow-hidden min-h-0">
            {/* Conversations List - Sidebar */}
            <div className={`w-full md:w-80 border-r border-gray-200 flex flex-col bg-white ${selectedConversation ? 'hidden md:flex' : 'flex'}`}>
              <div className="flex-1 overflow-y-auto custom-scrollbar p-4 min-h-0">
                {conversations.length === 0 ? (
                  <div className="text-center py-12 text-gray-500">
                    <FiMessageCircle className="w-16 h-16 mx-auto mb-4 text-gray-300" />
                    <p>Chưa có cuộc trò chuyện nào</p>
                    <p className="text-sm mt-2">Truy cập trang khóa học để bắt đầu chat!</p>
                  </div>
                ) : (
                  <div className="space-y-2">
                    {conversations.map((conversation) => (
                      <motion.div
                        key={`${conversation.other_user_id}-${conversation.course_id || 'general'}`}
                        initial={{ opacity: 0, y: 10 }}
                        animate={{ opacity: 1, y: 0 }}
                        onClick={() => handleSelectConversation(conversation)}
                        className={`p-4 border rounded-lg cursor-pointer transition-all duration-200 ${
                          isTeacher 
                            ? selectedConversation?.course_id === conversation.course_id
                            : selectedConversation?.other_user_id === conversation.other_user_id &&
                              selectedConversation?.course_id === conversation.course_id
                            ? 'bg-primary-50 border-primary-300 shadow-sm'
                            : 'border-gray-200 hover:bg-gray-50 hover:border-gray-300 hover:shadow-sm'
                        }`}
                      >
                        <div className="flex items-start justify-between">
                          <div className="flex-1">
                            <div className="flex items-center mb-1">
                              <h4 className="font-semibold text-gray-900 font-sans">
                                {isTeacher ? conversation.course_title || conversation.course_name : conversation.other_user_name}
                              </h4>
                              {!isTeacher && conversation.other_user_role === 'teacher' && (
                                <span className="ml-2 text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded font-sans">
                                  Giảng viên
                                </span>
                              )}
                              {conversation.unread_count > 0 && (
                                <span className="ml-2 bg-primary-600 text-white text-xs px-2 py-0.5 rounded-full font-sans">
                                  {conversation.unread_count}
                                </span>
                              )}
                            </div>
                            {isTeacher && conversation.total_students !== undefined && (
                              <p className="text-xs text-gray-500 mb-1 font-sans">
                                {conversation.total_students} học viên • {conversation.total_messages} tin nhắn
                              </p>
                            )}
                            {!isTeacher && conversation.course_name && (
                              <p className="text-xs text-gray-500 mb-1 font-sans">
                                Khóa học: {conversation.course_name}
                              </p>
                            )}
                            <p className="text-sm text-gray-600 line-clamp-1 font-sans">
                              {conversation.last_message || 'Chưa có tin nhắn'}
                            </p>
                            <p className="text-xs text-gray-400 mt-1 font-sans">
                              {conversation.last_message_time ? formatTime(conversation.last_message_time) : ''}
                            </p>
                          </div>
                        </div>
                      </motion.div>
                    ))}
                  </div>
                )}
              </div>
            </div>

            {/* Messages View */}
            {selectedConversation ? (
              <div className="flex-1 flex flex-col min-h-0 bg-white">
                {/* Chat Header */}
                <div className="border-b border-gray-200 p-4 bg-white flex items-center shadow-sm">
                  <button
                    onClick={() => setSelectedConversation(null)}
                    className="md:hidden mr-3 text-gray-600 hover:text-gray-900"
                  >
                    <FiArrowLeft className="w-5 h-5" />
                  </button>
                  <div>
                    <h3 className="font-semibold text-gray-900 font-sans">
                      {isTeacher ? selectedConversation.course_title || selectedConversation.course_name : selectedConversation.other_user_name}
                      {!isTeacher && selectedConversation.other_user_role === 'teacher' && (
                        <span className="ml-2 text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded font-sans">
                          Giảng viên
                        </span>
                      )}
                    </h3>
                    {isTeacher && selectedConversation.total_students !== undefined && (
                      <p className="text-xs text-gray-500 font-sans">
                        {selectedConversation.total_students} học viên • {selectedConversation.total_messages} tin nhắn
                      </p>
                    )}
                    {!isTeacher && selectedConversation.course_name && (
                      <p className="text-xs text-gray-500 font-sans">Khóa học: {selectedConversation.course_name}</p>
                    )}
                  </div>
                </div>

                {/* Messages */}
                <div 
                  ref={messagesContainerRef}
                  className="flex-1 overflow-y-auto custom-scrollbar p-4 bg-gray-50"
                  style={{ scrollBehavior: 'smooth' }}
                >
                  {messages.length === 0 ? (
                    <div className="text-center py-12 text-gray-500">
                      <FiMessageCircle className="w-12 h-12 mx-auto mb-2 text-gray-300" />
                      <p className="text-sm">Chưa có tin nhắn nào</p>
                      <p className="text-xs mt-1">Bắt đầu cuộc trò chuyện!</p>
                    </div>
                  ) : (
                    <div className="space-y-3">
                      {messages.map((message) => {
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
                      })}
                    </div>
                  )}
                  <div ref={messagesEndRef} />
                </div>

                {/* Input */}
                <form onSubmit={handleSend} className="p-4 border-t border-gray-200 bg-white shadow-sm">
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
              </div>
            ) : (
              <div className="flex-1 flex items-center justify-center bg-gray-50 hidden md:flex">
                <div className="text-center text-gray-500">
                  <FiMessageCircle className="w-16 h-16 mx-auto mb-4 text-gray-300" />
                  <p>Chọn một cuộc trò chuyện để bắt đầu</p>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

export default Chat

