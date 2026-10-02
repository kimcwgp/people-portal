<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Title bar, matching Attendance -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Shifts</h1>
        <button
          @click="openCreateModal"
          class="flex flex-shrink-0 items-center gap-2 rounded-lg bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Shift
        </button>
      </div>

      <!-- Shift mix across the whole roster. These counts are totals, so the
           filters below deliberately do not narrow them. -->
      <div class="mb-3 sm:mb-4 rounded-xl bg-surface p-4 shadow-sm sm:rounded-2xl sm:px-6">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-text-muted">Shift Summary</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div class="rounded-lg border border-border px-3 py-2.5">
            <p class="text-xs text-text-muted">Total Shifts</p>
            <p class="text-base sm:text-lg font-bold text-text">{{ totalShifts }}</p>
          </div>
          <div class="rounded-lg border border-border px-3 py-2.5">
            <p class="text-xs text-text-muted">Day Shifts</p>
            <p class="text-base sm:text-lg font-bold text-text">{{ dayShiftsCount }}</p>
          </div>
          <div class="rounded-lg border border-border px-3 py-2.5">
            <p class="text-xs text-text-muted">Night Shifts</p>
            <p class="text-base sm:text-lg font-bold text-text">{{ nightShiftsCount }}</p>
          </div>
        </div>
      </div>

      <!-- Filters + results live in one card, as in Attendance -->
      <div class="bg-surface rounded-lg shadow-sm border border-border overflow-hidden">
        <!-- Notched-outline fields: the label sits on the border line.
             shrink-0 keeps the set widths so they wrap instead of collapsing. -->
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="relative w-full shrink-0 sm:w-64">
            <label for="f-shift-search" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Search</label>
            <input
              id="f-shift-search"
              v-model="filters.search"
              @input="debouncedFetchShifts"
              type="text"
              placeholder="Shift type or time"
              class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text placeholder-text-subtle focus:border-accent focus:outline-none"
            />
          </div>

          <div class="relative w-full shrink-0 sm:w-48">
            <label for="f-shift-type" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Shift Type</label>
            <select id="f-shift-type" v-model="filters.shift_type" @change="fetchShifts"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option value="">All Types</option>
              <option value="day">Day Shift</option>
              <option value="night">Night Shift</option>
            </select>
          </div>

          <div class="relative w-full shrink-0 sm:w-40">
            <label for="f-shift-per-page" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Show</label>
            <select id="f-shift-per-page" v-model="filters.per_page" @change="fetchShifts"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option :value="5">5 per page</option>
              <option :value="10">10 per page</option>
              <option :value="25">25 per page</option>
              <option :value="50">50 per page</option>
            </select>
          </div>

          <button @click="clearFilters"
                  class="shrink-0 rounded-lg border border-border bg-surface-sunken px-5 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas">
            Clear
          </button>
        </div>

        <!-- Results summary -->
        <div v-if="!loading && shifts.data?.length > 0" class="border-b border-border px-4 py-2">
          <p class="text-xs text-text-muted">
            Showing {{ shifts.from }} to {{ shifts.to }} of {{ shifts.total }} shifts
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="!shifts.data?.length" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No shifts found</h3>
          <p class="mt-1 text-text-muted">
            {{ filters.search || filters.shift_type ? 'Try adjusting your filters' : 'Create your first shift to get started.' }}
          </p>
        </div>

        <div v-else>
          <!-- Desktop Table -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full min-w-[800px]">
              <thead class="bg-surface-sunken border-b border-border">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Shift Type</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Start Time</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">End Time</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Duration</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Users</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold text-text-muted uppercase tracking-wider">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="shift in shifts.data" :key="shift.id" class="hover:bg-surface-sunken transition-colors">
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1 text-xs font-medium capitalize text-status-text"
                          :class="shift.shift_type === 'day' ? 'bg-status-yellow' : 'bg-status-indigo'">
                      {{ shift.shift_type }} shift
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ shift.start_time }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ shift.end_time }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ calculateDuration(shift) }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center whitespace-nowrap rounded-md bg-status-gray px-2 py-1 text-xs font-medium text-status-text">
                      {{ shift.users_count || 0 }} user{{ (shift.users_count || 0) === 1 ? '' : 's' }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-2">
                      <button @click="editShift(shift)" class="p-1.5 text-accent-solid hover:bg-accent-subtle rounded transition-colors" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </button>
                      <button
                        @click="confirmDelete(shift)"
                        class="p-1.5 text-danger hover:bg-status-red rounded transition-colors"
                        :title="shift.users_count > 0 ? 'Shift has users assigned' : 'Delete'"
                        :disabled="shift.users_count > 0"
                        :class="shift.users_count > 0 ? 'opacity-50 cursor-not-allowed' : ''"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Cards -->
          <div class="lg:hidden divide-y divide-border">
            <div v-for="shift in shifts.data" :key="shift.id" class="p-4 hover:bg-surface-sunken transition-colors">
              <div class="mb-3 flex items-start justify-between gap-3">
                <span class="inline-flex items-center whitespace-nowrap rounded-md px-2 py-1 text-xs font-medium capitalize text-status-text"
                      :class="shift.shift_type === 'day' ? 'bg-status-yellow' : 'bg-status-indigo'">
                  {{ shift.shift_type }} shift
                </span>
                <span class="inline-flex flex-shrink-0 items-center whitespace-nowrap rounded-md bg-status-gray px-2 py-1 text-xs font-medium text-status-text">
                  {{ shift.users_count || 0 }} user{{ (shift.users_count || 0) === 1 ? '' : 's' }}
                </span>
              </div>

              <div class="mb-3 space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Start Time:</span>
                  <span class="font-medium text-text">{{ shift.start_time }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">End Time:</span>
                  <span class="font-medium text-text">{{ shift.end_time }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Duration:</span>
                  <span class="font-medium text-text">{{ calculateDuration(shift) }}</span>
                </div>
              </div>

              <div class="flex gap-2">
                <button @click="editShift(shift)" class="flex-1 rounded-lg bg-accent-solid px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-hover">Edit</button>
                <button
                  @click="confirmDelete(shift)"
                  :disabled="shift.users_count > 0"
                  class="flex-1 rounded-lg bg-danger px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-danger/90 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Delete
                </button>
              </div>
            </div>
          </div>

          <!-- Pagination sits inside the same card as the table -->
          <div class="border-t border-border px-4 py-3">
            <div class="flex items-center justify-between sm:hidden">
              <button
                @click="changePage(filters.page - 1)"
                :disabled="filters.page <= 1"
                class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Previous
              </button>
              <span class="text-sm text-text">Page {{ filters.page }} of {{ shifts.last_page }}</span>
              <button
                @click="changePage(filters.page + 1)"
                :disabled="filters.page >= shifts.last_page"
                class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Next
              </button>
            </div>

            <div class="hidden sm:flex sm:flex-col sm:space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
              <div class="flex items-center text-sm text-text">
                <span>Showing {{ shifts.from }} to {{ shifts.to }} of {{ shifts.total }} results</span>
              </div>

              <div class="flex items-center space-x-1">
                <button
                  @click="changePage(filters.page - 1)"
                  :disabled="filters.page <= 1"
                  class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  Previous
                </button>
                <button
                  @click="changePage(filters.page + 1)"
                  :disabled="filters.page >= shifts.last_page"
                  class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  Next
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div v-if="showModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
        <div class="relative bg-surface rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden">
          <div class="p-6 border-b border-border">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-text">{{ editingShift ? 'Edit Shift' : 'Create New Shift' }}</h3>
              <button @click="closeModal" class="p-2 rounded-lg hover:bg-surface-sunken">
                <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <form id="shift-form" @submit.prevent="saveShift" class="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-220px)]">
            <div>
              <label class="block text-sm font-medium text-text mb-2">Shift Type *</label>
              <select v-model="shiftForm.shift_type" required
                      class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200">
                <option value="">Select shift type</option>
                <option value="day">Day Shift</option>
                <option value="night">Night Shift</option>
              </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-text mb-2">Start Time *</label>
                <input v-model="shiftForm.start_time" type="time" required
                       class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200" />
              </div>
              <div>
                <label class="block text-sm font-medium text-text mb-2">End Time *</label>
                <input v-model="shiftForm.end_time" type="time" required
                       class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200" />
              </div>
            </div>
          </form>

          <div class="flex gap-3 border-t border-border p-6">
            <button type="button" @click="closeModal"
                    class="flex-1 rounded-xl border border-border bg-surface-sunken px-4 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas">
              Cancel
            </button>
            <button type="submit" form="shift-form" :disabled="submitting"
                    class="flex-1 rounded-xl bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover disabled:opacity-50">
              {{ submitting ? 'Saving...' : (editingShift ? 'Update' : 'Create') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Delete confirmation, replacing the browser's own confirm() dialog -->
      <div v-if="shiftToDelete" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="shiftToDelete = null"></div>
        <div class="relative bg-surface rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-status-red rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-text mb-2">Delete Shift</h3>
            <p class="text-text-muted mb-6">
              Delete the {{ shiftToDelete.shift_type }} shift ({{ shiftToDelete.start_time }} &ndash; {{ shiftToDelete.end_time }})? This cannot be undone.
            </p>
            <div class="flex gap-3">
              <button @click="shiftToDelete = null" class="flex-1 px-4 py-2 text-text bg-surface-sunken border border-border hover:bg-canvas rounded-xl">Cancel</button>
              <button @click="deleteShift" class="flex-1 px-4 py-2 text-white bg-danger hover:bg-danger/90 rounded-xl">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useNotification } from '@/composables/useNotification'
import { useDebounce } from '@/composables/useDebounce'

export default {
  name: 'Shifts',
  setup() {
    const { showNotification } = useNotification()
    const loading = ref(false)
    const submitting = ref(false)
    const shifts = ref({ data: [], current_page: 1, last_page: 1, total: 0, from: 0, to: 0 })
    const stats = ref({ total: 0, day_shifts: 0, night_shifts: 0 })
    const showModal = ref(false)
    const editingShift = ref(null)
    const shiftToDelete = ref(null)

    const filters = ref({
      search: '',
      shift_type: '',
      per_page: 10,
      page: 1,
      sort_by: 'shift_type',
      sort_direction: 'asc'
    })

    const shiftForm = ref({
      shift_type: '',
      start_time: '',
      end_time: ''
    })

    const dayShiftsCount = computed(() => stats.value.day_shifts)
    const nightShiftsCount = computed(() => stats.value.night_shifts)
    const totalShifts = computed(() => stats.value.total)

    const fetchShifts = async () => {
      loading.value = true
      try {
        const params = {
          search: filters.value.search || undefined,
          shift_type: filters.value.shift_type || undefined,
          per_page: filters.value.per_page,
          page: filters.value.page,
          sort_by: filters.value.sort_by,
          sort_direction: filters.value.sort_direction
        }

        const response = await axios.get('/shifts', { params })
        shifts.value = response.data.shifts
        stats.value = response.data.stats || { total: 0, day_shifts: 0, night_shifts: 0 }
      } catch (error) {
        console.error('Error fetching shifts:', error)
        showNotification('Error fetching shifts', 'error')
      } finally {
        loading.value = false
      }
    }

    const debouncedFetchShifts = useDebounce(fetchShifts, 500)

    const changePage = (page) => {
      if (page >= 1 && page <= shifts.value.last_page) {
        filters.value.page = page
        fetchShifts()
      }
    }

    const clearFilters = () => {
      filters.value = {
        search: '',
        shift_type: '',
        per_page: 10,
        page: 1,
        sort_by: 'shift_type',
        sort_direction: 'asc'
      }
      fetchShifts()
    }

    const openCreateModal = () => {
      editingShift.value = null
      shiftForm.value = {
        shift_type: '',
        start_time: '',
        end_time: ''
      }
      showModal.value = true
    }

    const editShift = (shift) => {
      editingShift.value = shift
      
      // Convert 12-hour format (8:00 AM) to 24-hour format (08:00) for time inputs
      const convertTo24Hour = (time12h) => {
        if (!time12h) return ''
        
        const [time, period] = time12h.split(' ')
        let [hours, minutes] = time.split(':')
        hours = parseInt(hours)
        
        if (period === 'PM' && hours !== 12) {
          hours += 12
        } else if (period === 'AM' && hours === 12) {
          hours = 0
        }
        
        return `${hours.toString().padStart(2, '0')}:${minutes}`
      }
      
      shiftForm.value = {
        shift_type: shift.shift_type,
        start_time: convertTo24Hour(shift.start_time),
        end_time: convertTo24Hour(shift.end_time)
      }
      showModal.value = true
    }

    const saveShift = async () => {
      submitting.value = true
      try {
        if (editingShift.value) {
          await axios.put(`/shifts/${editingShift.value.id}`, shiftForm.value)
          showNotification('Shift updated successfully', 'success')
        } else {
          await axios.post('/shifts', shiftForm.value)
          showNotification('Shift created successfully', 'success')
        }
        closeModal()
        fetchShifts()
      } catch (error) {
        console.error('Error saving shift:', error)
        showNotification(error.response?.data?.message || 'Error saving shift', 'error')
      } finally {
        submitting.value = false
      }
    }

    const confirmDelete = (shift) => {
      if (shift.users_count > 0) {
        showNotification('Cannot delete shift that has users assigned', 'error')
        return
      }
      shiftToDelete.value = shift
    }

    const deleteShift = async () => {
      const shift = shiftToDelete.value
      if (!shift) return
      shiftToDelete.value = null

      try {
        await axios.delete(`/shifts/${shift.id}`)
        showNotification('Shift deleted successfully', 'success')
        fetchShifts()
      } catch (error) {
        console.error('Error deleting shift:', error)
        showNotification(error.response?.data?.message || 'Error deleting shift', 'error')
      }
    }

    const closeModal = () => {
      showModal.value = false
      editingShift.value = null
      shiftForm.value = {
        shift_type: '',
        start_time: '',
        end_time: ''
      }
    }

    const calculateDuration = (shift) => {
      if (!shift.start_time || !shift.end_time) return 'N/A'
      
      const start = new Date(`2000-01-01 ${shift.start_time}`)
      const end = new Date(`2000-01-01 ${shift.end_time}`)
      
      let diff = (end - start) / 1000 / 60 / 60
      if (diff < 0) diff += 24 // Handle overnight shifts
      
      const hours = Math.floor(diff)
      const minutes = Math.round((diff - hours) * 60)
      
      return `${hours}h ${minutes}m`
    }

    onMounted(() => {
      fetchShifts()
    })

    return {
      loading,
      submitting,
      shifts,
      stats,
      filters,
      showModal,
      editingShift,
      shiftForm,
      dayShiftsCount,
      nightShiftsCount,
      totalShifts,
      fetchShifts,
      debouncedFetchShifts,
      changePage,
      clearFilters,
      openCreateModal,
      editShift,
      saveShift,
      shiftToDelete,
      confirmDelete,
      deleteShift,
      closeModal,
      calculateDuration
    }
  }
}
</script>