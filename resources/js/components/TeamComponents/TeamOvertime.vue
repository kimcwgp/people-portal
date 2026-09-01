<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">My Team's Overtime</h1>
        
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
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <!-- Single responsive row with proper spacing -->
        <div class="flex flex-wrap items-center gap-4 lg:gap-6">
          
          <!-- Status Filter Buttons - Takes more space on larger screens -->
          <div class="flex flex-wrap gap-2 min-w-0">
            <button
              v-for="status in statusFilters"
              :key="status.value"
              @click="currentStatus = status.value"
              :class="[
                'px-4 py-2.5 text-sm font-medium rounded-lg transition-colors whitespace-nowrap',
                currentStatus === status.value
                  ? 'bg-orange-600 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
              ]"
            >
              {{ status.label }}
              <span v-if="status.count > 0" :class="[
                'ml-2 px-2 py-0.5 text-xs rounded-full',
                currentStatus === status.value 
                  ? 'bg-orange-100 text-orange-800' 
                  : 'bg-orange-100 text-orange-800'
              ]">
                {{ status.count }}
              </span>
            </button>
          </div>

          <!-- Name Filter -->
          <div class="flex items-center gap-3 min-w-0">
            <span class="text-gray-700 font-medium text-sm whitespace-nowrap hidden sm:inline">Name:</span>
            <input
              v-model="filters.userName"
              type="text"
              placeholder="Search by name..."
              class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 w-40 lg:w-48"
            >
          </div>

          <!-- Date Range Section -->
          <div class="flex items-center gap-3 text-sm">
            <span class="text-gray-700 font-medium whitespace-nowrap hidden sm:inline">Date:</span>
            <input
              type="date"
              v-model="filters.startDate"
              class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 w-40"
            >
            <span class="text-gray-400 text-sm">to</span>
            <input
              type="date"
              v-model="filters.endDate"
              class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 w-40"
            >
          </div>

          <!-- Action Buttons - Grouped together -->
          <div class="flex gap-3 ml-auto">
            <button
              @click="applyFilters"
              class="bg-orange-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-orange-700 transition-colors whitespace-nowrap"
            >
              Filter
            </button>
            <button
              @click="clearFilters"
              class="bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors whitespace-nowrap"
            >
              Clear
            </button>
          </div>
        </div>
      </div>

      <!-- Results Info -->
      <div v-if="!loading && overtime.length > 0" class="mb-4">
        <p class="text-sm text-gray-600">
          Showing {{ pagination?.from || 0 }} to {{ pagination?.to || 0 }} of {{ pagination?.total || 0 }} overtime records
        </p>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
      </div>

      <div v-else-if="overtime.length === 0" class="rounded-lg bg-white p-8 shadow-sm text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="mt-3 text-lg font-semibold text-gray-900">No overtime records found</h3>
        <p class="mt-1 text-gray-600">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'Your team hasn\'t submitted any overtime requests yet.' }}
        </p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="ot in overtime"
          :key="ot.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200"
        >
          <div class="p-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 space-y-3 sm:space-y-0">
              <div class="flex items-center space-x-3 flex-1">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 truncate">{{ ot.user?.name }}</h3>
                </div>
              </div>
              
              <div class="flex items-center justify-between sm:justify-end sm:space-x-3 flex-shrink-0">
                <div class="sm:hidden">
                  <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700">
                    {{ ot.project?.name || 'No Project' }}
                  </span>
                </div>
                <div class="flex items-center space-x-3">
                  <span :class="getStatusClass(ot.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ ot.status }}
                  </span>
                  <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">{{ ot.relative_date }}</p>
                    <p class="text-xs text-gray-500">Submitted</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Project (Desktop) -->
            <div class="hidden sm:block mb-3">
              <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-700">
                {{ ot.project?.name || 'No Project' }}
              </span>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
              <!-- Date & Hours Column -->
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">DATE & HOURS</p>
                <p class="text-sm font-semibold text-gray-900 mb-1">{{ ot.formatted_ot_date }}</p>
                <p class="text-xs text-gray-600">{{ ot.formatted_ot_hours }}</p>
              </div>

              <!-- Time Column -->
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">TIME PERIOD</p>
                <p class="text-sm text-gray-900">{{ ot.time_in }} - {{ ot.time_out }}</p>
              </div>

              <!-- Notes Column -->
              <div class="sm:col-span-2 xl:col-span-2 bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">NOTES</p>
                <p v-if="ot.rejection_note || ot.cancellation_reason" class="text-sm text-red-600 line-clamp-2">
                  <span class="font-semibold">{{ ot.status === 'CANCELLED' ? 'Cancelled: ' : 'Rejected: ' }}</span>{{ ot.rejection_note || ot.cancellation_reason }}
                </p>
                <p v-else-if="ot.notes" class="text-sm text-gray-600 line-clamp-2">{{ ot.notes }}</p>
                <span v-else class="text-sm text-gray-400">-</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.total > pagination.per_page" class="mt-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3">
          <!-- Mobile Pagination -->
          <div class="flex items-center justify-between sm:hidden">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Previous
            </button>
            
            <span class="text-sm text-gray-700">
              Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>
            
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Next
            </button>
          </div>

          <!-- Desktop Pagination -->
          <div class="hidden sm:flex sm:flex-col sm:space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex items-center text-sm text-gray-700">
              <span>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results</span>
            </div>
            
            <div class="flex items-center space-x-1">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Previous
              </button>
              
              <div class="flex items-center space-x-1">
                <button
                  v-for="page in visiblePages"
                  :key="page"
                  @click="changePage(page)"
                  :disabled="page === '...'"
                  :class="[
                    'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                    page === pagination.current_page
                      ? 'bg-orange-600 text-white'
                      : page === '...'
                        ? 'text-gray-400 cursor-default'
                        : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page"
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
  name: 'TeamOvertime',
  data() {
    return {
      overtime: [],
      stats: {},
      loading: true,
      currentStatus: '',
      currentPage: 1,
      pagination: null,
      filters: {
        userName: '',
        startDate: '',
        endDate: ''
      },
      statusFilters: [
        { label: 'All', value: '', count: 0 },
        { label: 'Pending', value: 'PENDING', count: 0 },
        { label: 'Approved', value: 'APPROVED', count: 0 },
        { label: 'Rejected', value: 'REJECTED', count: 0 },
        { label: 'Cancelled', value: 'CANCELLED', count: 0 }
      ]
    }
  },

  computed: {
    visiblePages() {
      if (!this.pagination) return []
      
      const current = this.pagination.current_page
      const last = this.pagination.last_page
      const delta = 2
      const range = []
      
      for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i)
      }
      
      if (current - delta > 2) {
        range.unshift('...')
      }
      if (current + delta < last - 1) {
        range.push('...')
      }
      
      range.unshift(1)
      if (last !== 1) {
        range.push(last)
      }
      
      return range.filter((item, index, arr) => arr.indexOf(item) === index)
    },

    hasActiveFilters() {
      return this.currentStatus || this.filters.userName || this.filters.startDate || this.filters.endDate
    }
  },

  async mounted() {
    document.title = 'Team Overtime Overview'
    await this.loadOvertime()
  },

  watch: {
    currentStatus() {
      this.currentPage = 1
      this.loadOvertime()
    }
  },

  methods: {
    async loadOvertime() {
      try {
        this.loading = true
        const params = { page: this.currentPage }
        
        if (this.currentStatus) params.status = this.currentStatus
        if (this.filters.userName) params.user_name = this.filters.userName
        if (this.filters.startDate) params.start_date = this.filters.startDate
        if (this.filters.endDate) params.end_date = this.filters.endDate

        // Stats params (without page and status)
        const statsParams = {}
        if (this.filters.userName) statsParams.user_name = this.filters.userName
        if (this.filters.startDate) statsParams.start_date = this.filters.startDate
        if (this.filters.endDate) statsParams.end_date = this.filters.endDate

        const [overtimeRes, statsRes] = await Promise.all([
          axios.get('/user/my-team-overtime/overtime', { params }),
          axios.get('/user/my-team-overtime/stats', { params: statsParams })
        ])
        
        this.overtime = overtimeRes.data.data || []
        this.pagination = {
          current_page: overtimeRes.data.current_page,
          last_page: overtimeRes.data.last_page,
          per_page: overtimeRes.data.per_page,
          total: overtimeRes.data.total,
          from: overtimeRes.data.from,
          to: overtimeRes.data.to
        }
        
        this.stats = statsRes.data.data || {}
        this.updateStatusCounts()
      } catch (error) {
        this.showToast('Failed to load team overtime', 'error')
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
        
        const response = await axios.get('/user/my-team-overtime/export', { 
          params,
          responseType: 'blob'
        });
        
        const blob = new Blob([response.data], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.href = url;
        
        const contentDisposition = response.headers['content-disposition'];
        let filename = 'team_overtime_export.csv';
        
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
      this.loadOvertime()
    },

    clearFilters() {
      this.filters = {
        userName: '',
        startDate: '',
        endDate: ''
      }
      this.currentStatus = ''
      this.currentPage = 1
      this.loadOvertime()
    },

    async changePage(page) {
      if (page === '...' || page < 1 || (this.pagination && page > this.pagination.last_page)) {
        return
      }
      
      this.currentPage = page
      await this.loadOvertime()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },

    updateStatusCounts() {
      this.statusFilters.forEach(filter => {
        if (filter.value === '') {
          filter.count = this.stats.total || 0;
        } else if (this.stats[filter.value.toLowerCase()] !== undefined) {
          filter.count = this.stats[filter.value.toLowerCase()];
        } else {
          filter.count = 0;
        }
      });
    },

    getStatusClass(status) {
      switch (status) {
        case 'PENDING':
          return 'bg-yellow-100 text-yellow-800'
        case 'APPROVED':
          return 'bg-green-100 text-green-800'
        case 'REJECTED':
          return 'bg-red-100 text-red-800'
        case 'CANCELLED':
          return 'bg-gray-100 text-gray-800'
        default:
          return 'bg-gray-100 text-gray-800'
      }
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
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>