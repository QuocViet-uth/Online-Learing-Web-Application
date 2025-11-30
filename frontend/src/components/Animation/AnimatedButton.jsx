import { motion } from 'framer-motion'

const AnimatedButton = ({ 
  children, 
  className = '', 
  whileHover = { scale: 1.05 },
  whileTap = { scale: 0.95 },
  ...props 
}) => {
  return (
    <motion.button
      className={className}
      whileHover={whileHover}
      whileTap={whileTap}
      transition={{ duration: 0.2 }}
      {...props}
    >
      {children}
    </motion.button>
  )
}

export default AnimatedButton

