export function useNotification() {
  const showNotification = (message, type = 'info') => {
    const toast = document.createElement('div')
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-[100] transition-all duration-300 ${
      type === 'success' ? 'bg-green-500' :
      type === 'error'   ? 'bg-red-500'   :
      type === 'warning' ? 'bg-yellow-500': 'bg-blue-500'
    }`
    toast.textContent = message
    toast.style.opacity = '0'

    document.body.appendChild(toast)

    setTimeout(() => {
      toast.style.opacity = '1'
    }, 100)

    setTimeout(() => {
      toast.style.opacity = '0'
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast)
        }
      }, 300)
    }, 3000)
  }

  return {
    showNotification
  }
}
