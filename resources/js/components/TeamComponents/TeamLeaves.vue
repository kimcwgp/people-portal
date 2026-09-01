<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">My Team's Leaves</h1>
        
        <button
          @click="exportData"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="!loading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <svg v-else class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ loading ? 'Exporting...' : 'Export CSV' }}
        </button>
      </div>
      
      <!-- Filters Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
          <!-- Status Filter -->
          <div class="lg:col-span-2">
            <div class="flex space-x-1">
              <button
                v-for="status in statusFilters"
                :key="status.value"
                @click="currentStatus = status.value"
                :class="[
                  'px-3 py-2 text-xs font-medium rounded-lg transition-colors flex-1 sm:flex-none',
                  currentStatus === status.value
                    ? 'bg-blue-600 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-100 border'
                ]"
              >
                {{ status.label }}
                <span v-if="status.count > 0" class="ml-1 px-1.5 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full">
                  {{ status.count }}
                </span>
              </button>
            </div>
          </div>

          <!-- Name Filter -->
          <div>
            <input
              v-model="filters.userName"
              type="text"
              placeholder="Search by name..."
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>

          <!-- Date Range Filters -->
          <div>
            <input
              v-model="filters.startDate"
              type="date"
              placeholder="Start Date"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>
          
          <div>
            <input
              v-model="filters.endDate"
              type="date"
              placeholder="End Date"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>

          <!-- Filter Actions -->
          <div class="flex space-x-2">
            <button
              @click="applyFilters"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex-1 sm:flex-none"
            >
              Filter
            </button>
            <button
              @click="clearFilters"
              class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex-1 sm:flex-none"
            >
              Clear
            </button>
          </div>
        </div>
      </div>

      <!-- Results Info -->
      <div v-if="!loading && leaves.length > 0" class="mb-4">
        <p class="text-sm text-gray-600">
          Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} leave requests
        </p>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <div v-else-if="leaves.length === 0" class="rounded-lg bg-white p-8 shadow-sm text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <h3 class="mt-3 text-lg font-semibold text-gray-900">No leave requests found</h3>
        <p class="mt-1 text-gray-600">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'Your team hasn\'t submitted any leave requests yet.' }}
        </p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="leave in leaves"
          :key="leave.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200"
        >
          <div class="p-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 space-y-3 sm:space-y-0">
              <div class="flex items-center space-x-3 flex-1">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 truncate">{{ leave.user?.name }}</h3>
                </div>
              </div>
              
              <div class="flex items-center justify-between sm:justify-end sm:space-x-3 flex-shrink-0">
                <div class="sm:hidden">
                  <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700">
                    {{ leave.leave_type?.name }}
                  </span>
                </div>
                <div class="flex items-center space-x-3">
                  <span :class="getStatusClass(leave.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ leave.status.toUpperCase() }}
                  </span>
                  <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">{{ formatDate(leave.created_at) }}</p>
                    <p class="text-xs text-gray-500">Submitted</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Leave Type (Desktop) -->
            <div class="hidden sm:block mb-3">
              <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700">
                {{ leave.leave_type?.name }}
              </span>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3">
              <!-- Dates Column -->
              <div class="bg-gray-50 rounded-lg p-3 xl:col-span-2">
                <p class="text-xs font-medium text-gray-500 mb-1">DATES & DURATION</p>
                <p class="text-sm font-semibold text-gray-900 mb-1">{{ formatDateRange(leave.start_date, leave.end_date) }}</p>
                <div class="flex justify-between text-xs text-gray-600">
                  <span>{{ leave.duration || 'All Day' }}</span>
                  <span>{{ calculateDays(leave.start_date, leave.end_date) }} day{{ calculateDays(leave.start_date, leave.end_date) > 1 ? 's' : '' }}</span>
                </div>
                <div v-if="leave.time_in && leave.time_out" class="mt-1 pt-1 border-t border-gray-200">
                  <p class="text-xs text-gray-600">{{ formatTime(leave.time_in) }} - {{ formatTime(leave.time_out) }}</p>
                </div>
              </div>

              <!-- Reason Column -->
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">REASON</p>
                <p class="text-sm text-gray-900 line-clamp-2">{{ leave.reason || 'No reason provided' }}</p>
              </div>

              <!-- Notes Column -->
              <div class="bg-gray-50 rounded-lg p-3 sm:col-span-2 xl:col-span-2">
                <p class="text-xs font-medium text-gray-500 mb-1">NOTES</p>
                <p v-if="leave.rejection_note" class="text-sm text-red-600 line-clamp-2">
                  <span class="font-semibold">{{ leave.status === 'cancelled' ? 'Cancelled: ' : 'Rejected: ' }}</span>{{ leave.rejection_note }}
                </p>
                <p v-else-if="leave.notes" class="text-sm text-gray-600 line-clamp-2">{{ leave.notes }}</p>
                <span v-else class="text-sm text-gray-400">-</span>

                <div v-if="leave.attachment" class="mt-2 pt-2 border-t border-gray-200">
                  <button @click="viewAttachment(leave.attachment_url)" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    View Medical Certificate
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="leaves.length > 0" class="mt-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3">
          <!-- Mobile Pagination -->
          <div class="flex items-center justify-between sm:hidden">
            <button
              @click="changePage(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Previous
            </button>
            
            <span class="text-sm text-gray-700">
              Page {{ currentPage }} of {{ lastPage }}
            </span>
            
            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage >= lastPage"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Next
            </button>
          </div>

          <!-- Desktop Pagination -->
          <div class="hidden sm:flex sm:flex-col sm:space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex items-center text-sm text-gray-700">
              <span>Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} results</span>
            </div>
            
            <div class="flex items-center space-x-1">
              <button
                @click="changePage(currentPage - 1)"
                :disabled="currentPage <= 1"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Previous
              </button>
              
              <button
                @click="changePage(currentPage + 1)"
                :disabled="currentPage >= lastPage"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios'

export default {
  name: 'TeamLeaves',
  data() {
    return {
      leaves: [],
      stats: {},
      loading: true,
      currentStatus: '',
      currentPage: 1,
      lastPage: 1,
      totalItems: 0,
      perPage: 10,
      perPageOptions: [10, 15, 25, 50],
      filters: {
        userName: '',
        startDate: '',
        endDate: ''
      },
      statusFilters: [
        { label: 'All', value: '', count: 0 },
        { label: 'Pending', value: 'pending', count: 0 },
        { label: 'Approved', value: 'approved', count: 0 },
        { label: 'Rejected', value: 'rejected', count: 0 },
        { label: 'Cancelled', value: 'cancelled', count: 0 }
      ]
    }
  },

  computed: {
    hasActiveFilters() {
      return this.currentStatus || this.filters.userName || this.filters.startDate || this.filters.endDate
    }
  },

  async mounted() {
    document.title = 'Team Leaves Overview'
    await this.loadLeaves()
  },

  watch: {
    currentStatus() {
      this.currentPage = 1
      this.loadLeaves()
    }
  },

  methods: {
    async loadLeaves() {
      try {
        this.loading = true
        const params = { 
          page: this.currentPage,
          per_page: this.perPage
        }
        
        if (this.currentStatus) params.status = this.currentStatus
        if (this.filters.userName) params.user_name = this.filters.userName
        if (this.filters.startDate) params.start_date = this.filters.startDate
        if (this.filters.endDate) params.end_date = this.filters.endDate

        // Stats params (without page and status)
        const statsParams = {}
        if (this.filters.userName) statsParams.user_name = this.filters.userName
        if (this.filters.startDate) statsParams.start_date = this.filters.startDate
        if (this.filters.endDate) statsParams.end_date = this.filters.endDate

        const [leavesRes, statsRes] = await Promise.all([
          axios.get('/user/my-team-leaves/leaves', { params }),
          axios.get('/user/my-team-leaves/stats', { params: statsParams })
        ])
        
        this.leaves = leavesRes.data.data || []
        this.totalItems = leavesRes.data.meta.total
        this.currentPage = leavesRes.data.meta.current_page
        this.lastPage = leavesRes.data.meta.last_page
        
        this.stats = statsRes.data.data || {}
        this.updateStatusCounts()
      } catch (error) {
        this.showToast('Failed to load team leaves', 'error')
      } finally {
        this.loading = false
      }
    },

    async exportData() {
      try {
        this.loading = true;
        
        const params = {};
        
        if (this.currentStatus) params.status = this.currentStatus;
        if (this.filters.userName) params.user_name = this.filters.userName;
        if (this.filters.startDate) params.start_date = this.filters.startDate;
        if (this.filters.endDate) params.end_date = this.filters.endDate;
        
        const response = await axios.get('/user/my-team-leaves/export', { 
          params,
          responseType: 'blob'
        });
        
        const blob = new Blob([response.data], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.href = url;
        
        const contentDisposition = response.headers['content-disposition'];
        let filename = 'team_leaves_export.csv';
        
        if (contentDisposition) {
          const filenameMatch = contentDisposition.match(/filename="(.+)"/);
          if (filenameMatch) {
            filename = filenameMatch[1];
          }
        }
        
        link.download = filename;
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        window.URL.revokeObjectURL(url);

        this.showToast('Export completed successfully', 'success');

      } catch (error) {
        if (error.response?.status === 401) {
          this.showToast('Authentication required. Please log in again.', 'error');
        } else if (error.response?.status === 403) {
          this.showToast('You do not have permission to export this data.', 'error');
        } else {
          this.showToast('Failed to export data. Please try again.', 'error');
        }
      } finally {
        this.loading = false;
      }
    },

    applyFilters() {
      this.currentPage = 1
      this.loadLeaves()
    },

    clearFilters() {
      this.filters = {
        userName: '',
        startDate: '',
        endDate: ''
      }
      this.currentStatus = ''
      this.currentPage = 1
      this.loadLeaves()
    },

    changePerPage() {
      this.currentPage = 1
      this.loadLeaves()
    },

    async changePage(page) {
      if (page < 1 || page > this.lastPage) {
        return
      }
      
      this.currentPage = page
      await this.loadLeaves()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },

    updateStatusCounts() {
      this.statusFilters.forEach(filter => {
        if (filter.value === '') {
          filter.count = this.totalItems || 0
        } else if (this.stats[filter.value] !== undefined) {
          filter.count = this.stats[filter.value]
        } else {
          filter.count = 0
        }
      })
    },

    viewAttachment(url) {
      if (url) {
        window.open(url, '_blank');
      }
    },

    getStatusClass(status) {
      switch (status) {
        case 'pending':
          return 'bg-yellow-100 text-yellow-800'
        case 'approved':
          return 'bg-green-100 text-green-800'
        case 'rejected':
          return 'bg-red-100 text-red-800'
        case 'cancelled':
          return 'bg-gray-100 text-gray-800'
        default:
          return 'bg-gray-100 text-gray-800'
      }
    },

    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      })
    },

    formatDateRange(startDate, endDate) {
      if (!startDate || !endDate) return ''
      const start = this.formatDate(startDate)
      const end = this.formatDate(endDate)
      return startDate === endDate ? start : `${start} - ${end}`
    },

    formatTime(timeString) {
      if (!timeString) return ''
      const [hours, minutes] = timeString.split(':')
      const date = new Date()
      date.setHours(parseInt(hours), parseInt(minutes))
      return date.toLocaleString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit', 
        hour12: true 
      })
    },

    calculateDays(startDate, endDate) {
      if (!startDate || !endDate) return 0
      const start = new Date(startDate)
      const end = new Date(endDate)
      const diffTime = Math.abs(end - start)
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
    },

    showToast(message, type = 'info') {
      const toast = document.createElement('div')
      toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error'   ? 'bg-red-500'   :
        type === 'warning' ? 'bg-yellow-500': 'bg-blue-500'
      }`
      toast.textContent = message
      document.body.appendChild(toast)
      
      setTimeout(() => toast.style.opacity = '1', 100)
      setTimeout(() => {
        toast.style.opacity = '0'
        setTimeout(() => document.body.removeChild(toast), 300)
      }, 3000)
    }
  }
}
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>