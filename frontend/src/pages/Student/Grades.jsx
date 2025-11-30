import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { FiFileText, FiCheckCircle, FiTrendingUp } from 'react-icons/fi'
import { gradesAPI } from '../../services/api'
import { useAuth } from '../../contexts/AuthContext'
import toast from 'react-hot-toast'

const StudentGrades = () => {
  const { user } = useAuth()
  const [grades, setGrades] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadGrades()
  }, [])

  const loadGrades = async () => {
    try {
      setLoading(true)
      const response = await gradesAPI.getByStudent(user?.id)
      if (response.success) {
        setGrades(response)
      }
    } catch (error) {
      toast.error('Không thể tải điểm số')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Điểm số của tôi</h1>
          <p className="text-gray-600">Xem điểm số và thống kê học tập</p>
        </div>

        {loading ? (
          <div className="card text-center py-12">
            <div className="spinner mx-auto"></div>
          </div>
        ) : grades && grades.data && grades.data.length > 0 ? (
          <>
            {/* Statistics */}
            {grades.statistics && (
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="card">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-gray-600 text-sm mb-1">Tổng bài tập</p>
                      <p className="text-2xl font-bold">{grades.statistics.total_assignments}</p>
                    </div>
                    <FiFileText className="w-8 h-8 text-primary-600" />
                  </div>
                </div>
                <div className="card">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-gray-600 text-sm mb-1">Điểm trung bình</p>
                      <p className="text-2xl font-bold">{grades.statistics.average_score}</p>
                    </div>
                    <FiTrendingUp className="w-8 h-8 text-green-600" />
                  </div>
                </div>
                <div className="card">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-gray-600 text-sm mb-1">Tỷ lệ hoàn thành</p>
                      <p className="text-2xl font-bold">{grades.statistics.percentage}%</p>
                    </div>
                    <FiCheckCircle className="w-8 h-8 text-secondary-600" />
                  </div>
                </div>
              </div>
            )}

            {/* Grades List */}
            <div className="card">
              <h2 className="text-xl font-semibold mb-4">Chi tiết điểm số</h2>
              <div className="space-y-4">
                {grades.data.map((grade) => (
                  <div key={grade.submission_id} className="p-4 border border-gray-200 rounded-lg">
                    <div className="flex items-center justify-between mb-2">
                      <h3 className="font-semibold">{grade.assignment_title}</h3>
                      {grade.score !== null ? (
                        <span className="text-lg font-bold text-primary-600">
                          {grade.score} / {grade.max_score}
                        </span>
                      ) : (
                        <span className="badge badge-warning">Chưa chấm</span>
                      )}
                    </div>
                    <p className="text-sm text-gray-600 mb-2">Khóa học: {grade.course_name}</p>
                    {grade.feedback && (
                      <p className="text-sm text-gray-700 mt-2 p-2 bg-gray-50 rounded">
                        Nhận xét: {grade.feedback}
                      </p>
                    )}
                  </div>
                ))}
              </div>
            </div>
          </>
        ) : (
          <div className="card text-center py-12 text-gray-600">
            <FiFileText className="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <p>Chưa có điểm số nào</p>
          </div>
        )}
      </div>
    </div>
  )
}

export default StudentGrades

