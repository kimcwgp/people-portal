<template>
  <div class="min-h-screen bg-gray-50/50">
    <div class="p-3 sm:p-4 lg:p-6 xl:p-8 2xl:p-4 w-full mx-auto max-w-none 2xl:max-w-[95%]">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-6">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Shift Management</h1>
        </div>
        <button
          @click="openCreateModal"
          class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-all duration-200 shadow-sm hover:shadow-md flex items-center justify-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
          Add Shift
        </button>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-4 sm:mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">Total Shifts</p>
              <p class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ totalShifts }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">Day Shifts</p>
              <p class="text-xl sm:text-2xl font-bold text-amber-600 mt-1">{{ dayShiftsCount }}</p>
            </div>
            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">Night Shifts</p>
              <p class="text-xl sm:text-2xl font-bold text-indigo-600 mt-1">{{ nightShiftsCount }}</p>
            </div>
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Shifts Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Filters -->
        <div class="p-4 border-b border-gray-200">
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input
              v-model="filters.search"
              @input="debouncedFetchShifts"
              type="text"
              placeholder="Search shifts..."
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            
            <select
              v-model="filters.shift_type"
              @change="fetchShifts"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">All Types</option>
              <option value="day">Day Shift</option>
              <option value="night">Night Shift</option>
            </select>

            <select
              v-model="filters.per_page"
              @change="fetchShifts"
              class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option :value="5">5 per page</option>
              <option :value="10">10 per page</option>
              <option :value="25">25 per page</option>
              <option :value="50">50 per page</option>
            </select>

            <button
              @click="clearFilters"
              class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Clear Filters
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="text-left p-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Shift Type</th>
                <th class="text-left p-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Start Time</th>
                <th class="text-left p-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">End Time</th>
                <th class="text-left p-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Duration</th>
                <th class="text-left p-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Users</th>
                <th class="text-left p-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="loading" v-for="n in 3" :key="n">
                <td colspan="6" class="p-4">
                  <div class="animate-pulse flex space-x-4">
                    <div class="h-10 bg-gray-200 rounded w-full"></div>
                  </div>
                </td>
              </tr>
              <tr v-else-if="shifts.data?.length === 0">
                <td colspan="6" class="p-8 text-center text-gray-500">
                  <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  No shifts found. Create your first shift to get started.
                </td>
              </tr>
              <tr v-else v-for="shift in shifts.data" :key="shift.id" class="hover:bg-gray-50 transition-colors">
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <span v-if="shift.shift_type === 'day'" class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                      <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                      </svg>
                    </span>
                    <span v-else class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center flex-shrink-0">
                      <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                      </svg>
                    </span>
                    <span class="font-medium text-gray-900 capitalize">{{ shift.shift_type }}</span>
                  </div>
                </td>
                <td class="p-4">
                  <span class="text-sm text-gray-600">{{ shift.start_time }}</span>
                </td>
                <td class="p-4">
                  <span class="text-sm text-gray-600">{{ shift.end_time }}</span>
                </td>
                <td class="p-4">
                  <span class="text-sm text-gray-600">{{ calculateDuration(shift) }}</span>
                </td>
                <td class="p-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {{ shift.users_count || 0 }} users
                  </span>
                </td>
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <button
                      @click="editShift(shift)"
                      class="p-1.5 text-amber-600 hover:bg-amber-50 rounded transition-colors"
                      title="Edit Shift"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button
                      @click="deleteShift(shift)"
                      class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"
                      title="Delete Shift"
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

        <!-- Pagination -->
        <div v-if="shifts.data?.length > 0" class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Showing <span class="font-medium">{{ shifts.from }}</span> to <span class="font-medium">{{ shifts.to }}</span> of <span class="font-medium">{{ shifts.total }}</span> shifts
          </div>
          
          <div class="flex items-center gap-2">
            <button
              @click="changePage(filters.page - 1)"
              :disabled="filters.page === 1"
              class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Previous
            </button>
            
            <span class="px-3 py-1.5 text-sm">Page {{ filters.page }} of {{ shifts.last_page }}</span>
            
            <button
              @click="changePage(filters.page + 1)"
              :disabled="filters.page === shifts.last_page"
              class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ editingShift ? 'Edit Shift' : 'Create New Shift' }}
          </h3>
        </div>
        
        <form @submit.prevent="saveShift" class="p-6 space-y-4">
          <!-- Shift Type -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Shift Type <span class="text-red-500">*</span>
            </label>
            <select
              v-model="shiftForm.shift_type"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Select shift type</option>
              <option value="day">Day Shift</option>
              <option value="night">Night Shift</option>
            </select>
          </div>

          <!-- Start Time -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Start Time <span class="text-red-500">*</span>
            </label>
            <input
              v-model="shiftForm.start_time"
              type="time"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- End Time -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              End Time <span class="text-red-500">*</span>
            </label>
            <input
              v-model="shiftForm.end_time"
              type="time"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div class="flex gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              {{ submitting ? 'Saving...' : (editingShift ? 'Update' : 'Create') }}
            </button>
          </div>
        </form>
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

    const deleteShift = async (shift) => {
      if (shift.users_count > 0) {
        showNotification('Cannot delete shift that has users assigned', 'error')
        return
      }

      if (!confirm(`Are you sure you want to delete the ${shift.shift_type} shift?`)) {
        return
      }

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
      deleteShift,
      closeModal,
      calculateDuration
    }
  }
}
</script>