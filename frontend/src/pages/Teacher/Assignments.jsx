import { useState, useEffect } from 'react'
import { motion } from 'framer-motion'
import { FiFileText, FiClock, FiUsers } from 'react-icons/fi'
import { assignmentsAPI } from '../../services/api'
import toast from 'react-hot-toast'

const TeacherAssignments = () => {
  const [assignments, setAssignments] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadAssignments()
  }, [])

  const loadAssignments = async () => {
    try {
      setLoading(true)
      // Mock data
      setAssignments([])
    } catch (error) {
      toast.error('Không thể tải danh sách bài tập')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="pt-16 md:pt-20 min-h-screen bg-gray-50">
      <div className="container-custom py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Quản lý bài tập</h1>
          <p className="text-gray-600">Xem và chấm điểm bài tập của học viên</p>
        </div>

        <div className="card">
          {loading ? (
            <div className="text-center py-12">
              <div className="spinner mx-auto"></div>
            </div>
          ) : assignments.length === 0 ? (
            <div className="text-center py-12 text-gray-600">
              <FiFileText className="w-16 h-16 mx-auto mb-4 text-gray-300" />
              <p>Chưa có bài tập nào</p>
            </div>
          ) : (
            <div className="space-y-4">
              {assignments.map((assignment) => (
                <div key={assignment.id} className="p-4 border border-gray-200 rounded-lg">
                  <h3 className="font-semibold mb-2">{assignment.title}</h3>
                  <p className="text-sm text-gray-600">{assignment.description}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default TeacherAssignments

