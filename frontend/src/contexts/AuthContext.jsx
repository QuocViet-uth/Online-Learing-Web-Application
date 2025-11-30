import { createContext, useContext, useState, useEffect } from 'react'
import { authAPI } from '../services/api'

const AuthContext = createContext(null)

export const useAuth = () => {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return context
}

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)
  const [token, setToken] = useState(localStorage.getItem('token'))

  useEffect(() => {
    // Check if user is logged in on mount
    if (token) {
      // Try to get user info from token
      // For now, we'll just check localStorage
      const savedUser = localStorage.getItem('user')
      if (savedUser) {
        try {
          setUser(JSON.parse(savedUser))
        } catch (e) {
          console.error('Error parsing user data:', e)
          localStorage.removeItem('user')
          localStorage.removeItem('token')
        }
      }
    }
    setLoading(false)
  }, [])

  const login = async (username, password) => {
    try {
      const response = await authAPI.login(username, password)
      
      if (response && response.success === true && response.data) {
        const { user, token } = response.data
        
        if (!user || !token) {
          console.error('Missing user or token in response')
          return { success: false, message: 'Dữ liệu đăng nhập không hợp lệ' }
        }
        
        setUser(user)
        setToken(token)
        localStorage.setItem('token', token)
        localStorage.setItem('user', JSON.stringify(user))
        return { success: true }
      }
      
      const errorMessage = response?.message || 'Đăng nhập thất bại'
      return { success: false, message: errorMessage }
    } catch (error) {
      console.error('Login error:', error)
      return { 
        success: false, 
        message: error.message || error.response?.data?.message || 'Có lỗi xảy ra khi đăng nhập' 
      }
    }
  }

  const register = async (userData) => {
    try {
      const response = await authAPI.register(userData)
      if (response.success) {
        return { success: true, message: 'Đăng ký thành công' }
      }
      return { success: false, message: response.message || 'Đăng ký thất bại' }
    } catch (error) {
      return { 
        success: false, 
        message: error.response?.data?.message || 'Có lỗi xảy ra khi đăng ký' 
      }
    }
  }

  const logout = () => {
    setUser(null)
    setToken(null)
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  const updateUser = (userData) => {
    setUser(userData)
    localStorage.setItem('user', JSON.stringify(userData))
  }

  const value = {
    user,
    token,
    loading,
    login,
    register,
    logout,
    updateUser,
    isAuthenticated: !!user,
    isAdmin: user?.role === 'admin',
    isTeacher: user?.role === 'teacher',
    isStudent: user?.role === 'student',
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

