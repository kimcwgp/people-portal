import { computed } from 'vue'

export function usePagination(pagination) {
  const getPageNumbers = computed(() => {
    const current = pagination.value.current_page
    const last = pagination.value.last_page
    const pages = []

    if (last <= 7) {
      for (let i = 1; i <= last; i++) {
        pages.push(i)
      }
    } else {
      if (current <= 4) {
        for (let i = 1; i <= 5; i++) {
          pages.push(i)
        }
        pages.push('...')
        pages.push(last)
      } else if (current >= last - 3) {
        pages.push(1)
        pages.push('...')
        for (let i = last - 4; i <= last; i++) {
          pages.push(i)
        }
      } else {
        pages.push(1)
        pages.push('...')
        for (let i = current - 1; i <= current + 1; i++) {
          pages.push(i)
        }
        pages.push('...')
        pages.push(last)
      }
    }

    return pages
  })

  return {
    getPageNumbers
  }
}
