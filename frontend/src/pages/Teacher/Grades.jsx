import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { FiFileText, FiCheckCircle } from 'react-icons/fi'
import { submissionsAPI } from '../../services/api'
import toast from 'react-hot-toast'

const TeacherGrades = () => {
  const [submissions, setSubmissions] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadSubmissions()
  }, [])

  const loadSubmissions = async () => {
    try {
      setLoading(true)
      // Mock data
      setSubmissions([])
    } catch (error) {
      toast.error('Không thể tải danh sách bài nộp')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Chấm điểm</h1>
          <p className="text-gray-600">Xem và chấm điểm bài nộp của học viên</p>
        </div>

        <div className="card">
          {loading ? (
            <div className="text-center py-12">
              <div className="spinner mx-auto"></div>
            </div>
          ) : submissions.length === 0 ? (
            <div className="text-center py-12 text-gray-600">
              <FiCheckCircle className="w-16 h-16 mx-auto mb-4 text-gray-300" />
              <p>Chưa có bài nộp nào cần chấm</p>
            </div>
          ) : (
            <div className="space-y-4">
              {submissions.map((submission) => (
                <div key={submission.id} className="p-4 border border-gray-200 rounded-lg">
                  <h3 className="font-semibold mb-2">{submission.assignment_title}</h3>
                  <p className="text-sm text-gray-600">Học viên: {submission.student_name}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default TeacherGrades

