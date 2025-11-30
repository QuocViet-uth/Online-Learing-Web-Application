import { useState } from 'react'
import { motion } from 'framer-motion'

/**
 * ResponsiveTable Component
 * Automatically converts table to card layout on mobile devices
 */
const ResponsiveTable = ({ 
  columns = [], 
  data = [], 
  renderRow = null,
  renderCard = null,
  emptyMessage = 'Không có dữ liệu',
  className = ''
}) => {
  const [selectedRow, setSelectedRow] = useState(null)

  // Default card renderer if not provided
  const defaultRenderCard = (item, index) => (
    <motion.div
      key={index}
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: index * 0.05 }}
      className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4"
    >
      {columns.map((col, colIndex) => (
        <div key={colIndex} className="mb-3 last:mb-0">
          <div className="text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">
            {col.header}
          </div>
          <div className="text-sm text-gray-900">
            {col.render ? col.render(item) : item[col.accessor]}
          </div>
        </div>
      ))}
    </motion.div>
  )

  return (
    <>
      {/* Desktop Table View */}
      <div className={`hidden md:block overflow-x-auto ${className}`}>
        <table className="w-full">
          <thead>
            <tr className="border-b border-gray-200">
              {columns.map((col, index) => (
                <th
                  key={index}
                  className={`text-left p-4 font-semibold text-sm ${
                    col.align === 'right' ? 'text-right' : 
                    col.align === 'center' ? 'text-center' : ''
                  }`}
                >
                  {col.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {data.length === 0 ? (
              <tr>
                <td colSpan={columns.length} className="text-center py-12 text-gray-500">
                  {emptyMessage}
                </td>
              </tr>
            ) : (
              data.map((item, index) => (
                renderRow ? (
                  renderRow(item, index)
                ) : (
                  <motion.tr
                    key={index}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: index * 0.05 }}
                    className="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                  >
                    {columns.map((col, colIndex) => (
                      <td
                        key={colIndex}
                        className={`p-4 ${
                          col.align === 'right' ? 'text-right' : 
                          col.align === 'center' ? 'text-center' : ''
                        }`}
                      >
                        {col.render ? col.render(item) : item[col.accessor]}
                      </td>
                    ))}
                  </motion.tr>
                )
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Mobile Card View */}
      <div className={`md:hidden space-y-4 ${className}`}>
        {data.length === 0 ? (
          <div className="text-center py-12 text-gray-500">
            {emptyMessage}
          </div>
        ) : (
          data.map((item, index) => (
            renderCard ? renderCard(item, index) : defaultRenderCard(item, index)
          ))
        )}
      </div>
    </>
  )
}

export default ResponsiveTable

