import { useState, useEffect } from 'react'
import { useParams, useSearchParams, useNavigate } from 'react-router-dom'
import { motion, AnimatePresence } from 'framer-motion'
import ReactPlayer from 'react-player'
import { 
  FiMenu, 
  FiX, 
  FiCheck, 
  FiPlay, 
  FiChevronLeft, 
  FiChevronRight,
  FiBook,
  FiCheckCircle
} from 'react-icons/fi'
import { lessonsAPI, progressAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import toast from 'react-hot-toast'

const CoursePlayer = () => {
  const { id } = useParams()
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { user, isAuthenticated } = useAuth()
  const [lessons, setLessons] = useState([])
  const [currentLesson, setCurrentLesson] = useState(null)
  const [loading, setLoading] = useState(true)
  const [sidebarOpen, setSidebarOpen] = useState(true)
  const [playing, setPlaying] = useState(false)
  const [played, setPlayed] = useState(0)
  const [duration, setDuration] = useState(0)
  const [lessonProgress, setLessonProgress] = useState({}) // { lessonId: { is_completed: true/false } }
  const [markingComplete, setMarkingComplete] = useState(false)

  useEffect(() => {
    if (!isAuthenticated) {
      navigate(`/courses/${id}`)
      return
    }
    loadLessons()
    if (user && user.id) {
      loadProgress()
    }
  }, [id, user])
  
  const loadProgress = async () => {
    if (!user || !user.id) return
    try {
      const response = await progressAPI.getByStudentAndCourse(user.id, parseInt(id))
      if (response && response.success && response.data) {
        setLessonProgress(response.data)
      }
    } catch (error) {
      console.error('Error loading progress:', error)
    }
  }
  
  const handleMarkComplete = async (lessonId, isCompleted) => {
    if (!user || !user.id) {
      toast.error('Vui lòng đăng nhập')
      return
    }
    
    try {
      setMarkingComplete(true)
      const response = await progressAPI.markComplete(
        user.id,
        parseInt(id),
        lessonId,
        isCompleted
      )
      
      if (response && response.success) {
        // Cập nhật local state
        setLessonProgress(prev => ({
          ...prev,
          [lessonId]: {
            is_completed: isCompleted,
            ...prev[lessonId]
          }
        }))
        toast.success(isCompleted ? 'Đã đánh dấu hoàn thành!' : 'Đã bỏ đánh dấu hoàn thành')
      } else {
        toast.error(response?.message || 'Không thể cập nhật trạng thái')
      }
    } catch (error) {
      console.error('Error marking lesson complete:', error)
      toast.error('Không thể cập nhật trạng thái')
    } finally {
      setMarkingComplete(false)
    }
  }

  useEffect(() => {
    const lessonId = searchParams.get('lesson')
    if (lessonId && lessons.length > 0) {
      const lesson = lessons.find(l => l.id === parseInt(lessonId))
      if (lesson) {
        setCurrentLesson(lesson)
      } else if (lessons.length > 0) {
        setCurrentLesson(lessons[0])
      }
    } else if (lessons.length > 0) {
      setCurrentLesson(lessons[0])
    }
  }, [lessons, searchParams])

  const loadLessons = async () => {
    try {
      setLoading(true)
      const response = await lessonsAPI.getByCourse(id)
      if (response.success && response.data) {
        setLessons(response.data)
      }
    } catch (error) {
      toast.error('Không thể tải danh sách bài học')
    } finally {
      setLoading(false)
    }
  }

  const handleLessonChange = (lesson) => {
    setCurrentLesson(lesson)
    setPlayed(0)
    setPlaying(true)
    navigate(`/courses/${id}/learn?lesson=${lesson.id}`, { replace: true })
  }

  const handleProgress = async (state) => {
    setPlayed(state.played)
    // Tự động đánh dấu hoàn thành khi xem 90% (chỉ nếu chưa đánh dấu)
    if (state.played > 0.9 && currentLesson && user && !lessonProgress[currentLesson.id]?.is_completed) {
      try {
        await progressAPI.markComplete(user.id, parseInt(id), currentLesson.id, true)
        setLessonProgress(prev => ({
          ...prev,
          [currentLesson.id]: {
            is_completed: true,
            ...prev[currentLesson.id]
          }
        }))
      } catch (error) {
        // Silent fail
      }
    }
  }

  const handleNext = () => {
    if (!currentLesson) return
    const currentIndex = lessons.findIndex(l => l.id === currentLesson.id)
    if (currentIndex < lessons.length - 1) {
      handleLessonChange(lessons[currentIndex + 1])
    }
  }

  const handlePrevious = () => {
    if (!currentLesson) return
    const currentIndex = lessons.findIndex(l => l.id === currentLesson.id)
    if (currentIndex > 0) {
      handleLessonChange(lessons[currentIndex - 1])
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="spinner"></div>
      </div>
    )
  }

  if (lessons.length === 0) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <p className="text-gray-600 text-lg mb-4">Khóa học chưa có bài học nào</p>
          <button
            onClick={() => navigate(`/courses/${id}`)}
            className="btn btn-primary"
          >
            Quay lại
          </button>
        </div>
      </div>
    )
  }

  const currentIndex = currentLesson
    ? lessons.findIndex(l => l.id === currentLesson.id)
    : -1

  return (
    <div className="min-h-screen bg-gray-900 text-white flex flex-col">
      {/* Top Bar */}
      <div className="bg-gray-800 border-b border-gray-700 px-2 sm:px-4 py-2 sm:py-3 flex items-center justify-between">
        <div className="flex items-center space-x-2 sm:space-x-4 flex-1 min-w-0">
          <button
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="p-2 hover:bg-gray-700 rounded-lg transition-colors flex-shrink-0"
            aria-label="Toggle sidebar"
          >
            {sidebarOpen ? <FiX className="w-5 h-5" /> : <FiMenu className="w-5 h-5" />}
          </button>
          <h1 className="text-sm sm:text-base md:text-lg font-semibold truncate">
            {currentLesson?.title || 'Chọn bài học'}
          </h1>
        </div>
        <button
          onClick={() => navigate(`/courses/${id}`)}
          className="text-gray-400 hover:text-white transition-colors p-2 flex-shrink-0"
          aria-label="Close"
        >
          <FiX className="w-5 h-5 sm:w-6 sm:h-6" />
        </button>
      </div>

      <div className="flex-1 flex overflow-hidden">
        {/* Sidebar - Lessons List */}
        <AnimatePresence>
          {sidebarOpen && (
            <motion.div
              initial={{ x: '-100%' }}
              animate={{ x: 0 }}
              exit={{ x: '-100%' }}
              transition={{ type: 'tween', duration: 0.3 }}
              className="w-full sm:w-80 bg-gray-800 border-r border-gray-700 overflow-y-auto custom-scrollbar"
            >
              <div className="p-3 sm:p-4">
                <h2 className="font-semibold mb-3 sm:mb-4 text-sm sm:text-base flex items-center">
                  <FiBook className="mr-2 w-4 h-4 sm:w-5 sm:h-5" />
                  Giáo trình
                </h2>
                <div className="space-y-1">
                  {lessons.map((lesson, index) => (
                    <button
                      key={lesson.id}
                      onClick={() => handleLessonChange(lesson)}
                      className={`w-full text-left p-3 rounded-lg transition-colors ${
                        currentLesson?.id === lesson.id
                          ? 'bg-primary-600 text-white'
                          : 'bg-gray-700 hover:bg-gray-600 text-gray-300'
                      }`}
                    >
                        <div className="flex items-center justify-between">
                          <div className="flex items-center space-x-3 flex-1 min-w-0">
                            <div className={`w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold ${
                              currentLesson?.id === lesson.id
                                ? 'bg-white text-primary-600'
                                : 'bg-gray-600 text-gray-300'
                            }`}>
                              {index + 1}
                            </div>
                            <div className="flex-1 min-w-0">
                              <p className="font-medium truncate">{lesson.title}</p>
                              <p className="text-xs opacity-75">
                                {lesson.duration || 0} phút
                              </p>
                            </div>
                          </div>
                          {lessonProgress[lesson.id]?.is_completed ? (
                            <FiCheckCircle className="w-5 h-5 text-green-500" />
                          ) : (
                            <FiCheck className={`w-5 h-5 ${
                              currentLesson?.id === lesson.id ? 'text-white' : 'text-gray-500'
                            }`} />
                          )}
                        </div>
                    </button>
                  ))}
                </div>
              </div>
            </motion.div>
          )}
        </AnimatePresence>

        {/* Main Content */}
        <div className="flex-1 flex flex-col overflow-hidden">
          {/* Video Player */}
          <div className="relative bg-black flex-1 flex items-center justify-center">
            {currentLesson?.video_url ? (
              <div className="w-full h-full">
                <ReactPlayer
                  url={currentLesson.video_url}
                  playing={playing}
                  controls
                  width="100%"
                  height="100%"
                  onProgress={handleProgress}
                  onDuration={setDuration}
                  onEnded={() => {
                    setPlaying(false)
                    toast.success('Hoàn thành bài học!')
                  }}
                  config={{
                    youtube: {
                      playerVars: {
                        modestbranding: 1,
                        rel: 0,
                      },
                    },
                  }}
                />
              </div>
            ) : (
              <div className="text-center p-8">
                <p className="text-gray-400 mb-4">Bài học này chưa có video</p>
                <p className="text-gray-500 text-sm">{currentLesson?.content}</p>
              </div>
            )}
          </div>

          {/* Lesson Content */}
          <div className="bg-gray-800 border-t border-gray-700 p-3 sm:p-4 md:p-6 max-h-48 sm:max-h-64 overflow-y-auto custom-scrollbar">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0 mb-3 sm:mb-4">
              <h3 className="text-base sm:text-lg md:text-xl font-semibold">{currentLesson?.title}</h3>
              {currentLesson && user && (
                <button
                  onClick={() => {
                    const isCompleted = lessonProgress[currentLesson.id]?.is_completed || false
                    handleMarkComplete(currentLesson.id, !isCompleted)
                  }}
                  disabled={markingComplete}
                  className={`flex items-center space-x-2 px-4 py-2 rounded-lg transition-colors ${
                    lessonProgress[currentLesson.id]?.is_completed
                      ? 'bg-green-600 hover:bg-green-700 text-white'
                      : 'bg-gray-700 hover:bg-gray-600 text-gray-300'
                  } disabled:opacity-50 disabled:cursor-not-allowed`}
                >
                  {markingComplete ? (
                    <>
                      <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                      <span>Đang xử lý...</span>
                    </>
                  ) : (
                    <>
                      <FiCheckCircle className="w-5 h-5" />
                      <span>
                        {lessonProgress[currentLesson.id]?.is_completed ? 'Đã hoàn thành' : 'Đánh dấu hoàn thành'}
                      </span>
                    </>
                  )}
                </button>
              )}
            </div>
            <div className="prose prose-invert max-w-none">
              <p className="text-gray-300 whitespace-pre-line">
                {currentLesson?.content || 'Chưa có nội dung'}
              </p>
            </div>
          </div>

          {/* Navigation */}
          <div className="bg-gray-800 border-t border-gray-700 px-6 py-4 flex items-center justify-between">
            <button
              onClick={handlePrevious}
              disabled={currentIndex <= 0}
              className="btn bg-gray-700 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
              <FiChevronLeft className="mr-2" />
              Bài trước
            </button>
            <div className="text-sm text-gray-400">
              Bài {currentIndex + 1} / {lessons.length}
            </div>
            <button
              onClick={handleNext}
              disabled={currentIndex >= lessons.length - 1}
              className="btn bg-gray-700 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
              Bài sau
              <FiChevronRight className="ml-2" />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}

export default CoursePlayer

