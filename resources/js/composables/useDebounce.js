import { ref, onUnmounted } from 'vue'

export function useDebounce(callback, delay = 500) {
  const timeout = ref(null)

  const debounced = (...args) => {
    if (timeout.value) {
      clearTimeout(timeout.value)
    }
    timeout.value = setTimeout(() => {
      callback(...args)
    }, delay)
  }

  onUnmounted(() => {
    if (timeout.value) {
      clearTimeout(timeout.value)
    }
  })

  return debounced
}
