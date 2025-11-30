import { Outlet, useLocation } from 'react-router-dom'
import Header from './Header'
import Footer from './Footer'

const Layout = () => {
  const location = useLocation()
  
  // Hide footer on certain pages if needed
  const hideFooter = false
  
  return (
    <div className="min-h-screen flex flex-col bg-gray-50">
      <Header />
      <main className="flex-grow pt-16 md:pt-20">
        <Outlet />
      </main>
      {!hideFooter && <Footer />}
    </div>
  )
}

export default Layout

