<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">My Team's Attendance</h1>
        
        <!-- Export Button -->
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
      
      <!-- Enhanced Filters Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <div>
            <input
              v-model="filters.userName"
              type="text"
              placeholder="Search by name..."
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
            >
          </div>

          <div>
            <input
              v-model="filters.startDate"
              type="date"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
            >
          </div>
          
          <div>
            <input
              v-model="filters.endDate"
              type="date"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
            >
          </div>

          <!-- Per Page Selector -->
          <div>
            <select 
              v-model="selectedPerPage" 
              @change="changePerPage"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
            >
              <option v-for="option in perPageOptions" :key="option" :value="option">
                {{ option }} per page
              </option>
            </select>
          </div>

          <div class="flex space-x-2">
            <button
              @click="applyFilters"
              class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors flex-1 sm:flex-none"
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

      <!-- Results Summary -->
      <div v-if="!loading && attendances.length > 0" class="mb-4">
        <p class="text-sm text-gray-600">
          Showing {{ pagination?.from || 0 }} to {{ pagination?.to || 0 }} of {{ pagination?.total || 0 }} attendance records
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="attendances.length === 0" class="rounded-lg bg-white p-8 shadow-sm text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="mt-3 text-lg font-semibold text-gray-900">No attendance records found</h3>
        <p class="mt-1 text-gray-600">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'Your team hasn\'t logged any attendance yet.' }}
        </p>
      </div>

      <!-- Attendance Records -->
      <div v-else class="space-y-3">
        <div
          v-for="attendance in attendances"
          :key="attendance.id || attendance.attendance_date"
          class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200"
        >
          <div class="p-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 space-y-3 sm:space-y-0">
              <div class="flex items-center space-x-3 flex-1">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 truncate">{{ attendance.user?.name }}</h3>
                </div>
              </div>
              
              <div class="flex items-center justify-between sm:justify-end sm:space-x-3 flex-shrink-0">
                <div class="flex items-center space-x-3">
                  <span :class="getStatusClass(attendance)" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ getStatusLabel(attendance) }}
                  </span>
                  <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">{{ formatDate(attendance.attendance_date) }}</p>
                    <p class="text-xs text-gray-500">Attendance Date</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Updated Grid with 5 columns -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3">
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">TIME IN/OUT</p>
                <!-- Multiple sessions display -->
                <div v-if="attendance.sessions && attendance.sessions.length > 1" class="space-y-1">
                  <div v-for="(session, index) in attendance.sessions" :key="index" class="text-sm">
                    <span class="text-gray-900">{{ getTimeDisplay(session.time_in) }}</span>
                    <span class="text-gray-500 mx-1">/</span>
                    <span class="text-gray-900">{{ getTimeDisplay(session.time_out) }}</span>
                  </div>
                </div>
                <!-- Single session display -->
                <p v-else class="text-sm font-semibold text-gray-900">
                  {{ getTimeDisplay(attendance.time_in) }} / {{ getTimeDisplay(attendance.time_out) }}
                </p>
              </div>

              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">WORKING HOURS</p>
                <!-- Multiple sessions working hours -->
                <div v-if="attendance.sessions && attendance.sessions.length > 1" class="space-y-1">
                  <p v-for="(session, index) in attendance.sessions" :key="index" class="text-sm text-gray-900">
                    {{ session.working_hours || '--:--' }}
                  </p>
                </div>
                <!-- Single session -->
                <p v-else class="text-sm text-gray-900">{{ attendance.working_hours || '--:--' }}</p>
              </div>

              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">BREAK HOURS</p>
                <!-- Multiple sessions break hours -->
                <div v-if="attendance.sessions && attendance.sessions.length > 1" class="space-y-1">
                  <p v-for="(session, index) in attendance.sessions" :key="index" class="text-sm" :class="getBreakHoursClass(session.total_break_hours)">
                    {{ getBreakTimesDisplay(session) }}
                  </p>
                </div>
                <!-- Single session -->
                <p v-else class="text-sm text-gray-900" :class="getBreakHoursClass(attendance.total_break_hours)">
                  {{ getBreakTimesDisplay(attendance) }}
                </p>
              </div>

              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">LUNCH BREAK</p>
                <!-- Multiple sessions lunch break -->
                <div v-if="attendance.sessions && attendance.sessions.length > 1" class="space-y-1">
                  <p v-for="(session, index) in attendance.sessions" :key="index" class="text-sm text-gray-900">
                    {{ getLunchTimesDisplay(session) }}
                  </p>
                </div>
                <!-- Single session -->
                <p v-else class="text-sm text-gray-900 font-semibold">{{ getLunchTimesDisplay(attendance) }}</p>
              </div>

              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">NOTES & STATUS</p>
                <p class="text-sm text-gray-900 line-clamp-2">{{ attendance.notes || 'No notes' }}</p>
                
                <div v-if="attendance.leave_info?.has_attachment" class="mt-2">
                  <button 
                    @click="viewAttachment(attendance.leave_info.attachment_url)" 
                    class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700"
                  >
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
      <div v-if="pagination && pagination.total > pagination.per_page" class="mt-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-3">
          <div class="flex items-center justify-between sm:hidden">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Previous
            </button>
            
            <span class="text-sm text-gray-700 font-medium">
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
                  v-for="page in getPageNumbers()"
                  :key="page"
                  @click="changePage(page)"
                  :disabled="page === '...'"
                  :class="[
                    'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                    page === pagination.current_page
                      ? 'bg-emerald-600 text-white'
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
  name: 'TeamAttendance',
  data() {
    return {
      attendances: [],
      loading: true,
      currentPage: 1,
      pagination: null,
      filters: {
        userName: '',
        startDate: '',
        endDate: ''
      },
      selectedPerPage: 10, // Default from backend trait
      perPageOptions: [10, 25, 50, 100]
    }
  },

  computed: {
    hasActiveFilters() {
      return this.filters.userName || this.filters.startDate || this.filters.endDate
    }
  },

  async mounted() {
    document.title = 'Team Attendance Overview'
    await this.loadAttendances()
  },

  methods: {
    getStatusClass(attendance) {
      if (attendance.is_on_leave && !attendance.is_partial_leave) {
        return 'bg-purple-100 text-purple-800';
      }
      if (attendance.is_partial_leave) {
        return 'bg-indigo-100 text-indigo-800';
      }
      if (attendance.is_weekend) {
        return 'bg-gray-100 text-gray-800';
      }
      if (attendance.is_awol) {
        return 'bg-red-100 text-red-800';
      }
      
      switch (attendance.status) {
        case 'complete':
          return 'bg-green-100 text-green-800';
        case 'partial':
          return 'bg-yellow-100 text-yellow-800';
        case 'active':
          return 'bg-blue-100 text-blue-800';
        case 'undertime':
          return 'bg-orange-100 text-orange-800';
        case 'absent':
          return 'bg-red-100 text-red-800';
        default:
          return 'bg-gray-100 text-gray-800';
      }
    },

    getStatusLabel(attendance) {
      if (attendance.is_on_leave && !attendance.is_partial_leave) {
        return 'On Leave';
      }
      if (attendance.is_partial_leave) {
        return 'Partial Leave';
      }
      if (attendance.is_weekend) {
        return 'Weekend';
      }
      if (attendance.is_awol) {
        return 'AWOL';
      }
      
      switch (attendance.status) {
        case 'complete':
          return 'Complete';
        case 'partial':
          return 'Partial';
        case 'undertime':
          return 'Undertime';
        case 'active':
          return 'Active';
        case 'absent':
          return 'Absent';
        default:
          return 'Present';
      }
    },

    getTimeDisplay(timeString) {
      if (!timeString || timeString === 'On Leave' || timeString === 'Early Leave') {
        return timeString || '--:--';
      }
      return timeString || '--:--';
    },

    getBreakHoursClass(breakHours) {
      if (!breakHours || breakHours === '--:--') {
        return 'text-gray-400';
      }
      
      const hoursMatch = breakHours.match(/(\d+)h/);
      const minutesMatch = breakHours.match(/(\d+)m/);
      
      const hours = hoursMatch ? parseInt(hoursMatch[1]) : 0;
      const minutes = minutesMatch ? parseInt(minutesMatch[1]) : 0;
      const totalMinutes = (hours * 60) + minutes;
      
      if (totalMinutes > 90) {
        return 'text-orange-600 font-medium';
      } else if (totalMinutes > 60) {
        return 'text-yellow-600 font-medium';
      } else if (totalMinutes > 0) {
        return 'text-green-600';
      }
      
      return 'text-gray-400';
    },

    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    formatDay(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', { weekday: 'short' });
    },

    viewAttachment(url) {
      if (url) {
        window.open(url, '_blank');
      }
    },

    showToast(message, type = 'info') {
      const toast = document.createElement('div');
      toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error'   ? 'bg-red-500'   :
        type === 'warning' ? 'bg-yellow-500': 'bg-blue-500'
      }`;
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(() => toast.style.opacity = '1', 100);
      setTimeout(() => { 
        toast.style.opacity = '0'; 
        setTimeout(() => document.body.removeChild(toast), 300); 
      }, 3000);
    },

    async loadAttendances() {
      try {
        this.loading = true
        const params = { page: this.currentPage }
        
        // Only send per_page if different from default
        if (this.selectedPerPage !== 10) {
          params.per_page = this.selectedPerPage;
        }
        
        if (this.filters.userName) params.user_name = this.filters.userName
        if (this.filters.startDate) params.start_date = this.filters.startDate
        if (this.filters.endDate) params.end_date = this.filters.endDate

        const { data } = await axios.get('/user/my-team-attendance/attendance', { params })
        this.attendances = data.data || []
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
          from: data.from,
          to: data.to
        }
      } catch (error) {
        this.showToast('Failed to load team attendance', 'error')
      } finally {
        this.loading = false
      }
    },

    applyFilters() {
      this.currentPage = 1
      this.loadAttendances()
    },

    clearFilters() {
      this.filters = {
        userName: '',
        startDate: '',
        endDate: ''
      }
      this.selectedPerPage = 10 // Reset to default
      this.currentPage = 1
      this.loadAttendances()
    },

    changePerPage() {
      this.currentPage = 1 // Reset to first page when changing per_page
      this.loadAttendances()
    },

    async changePage(page) {
      if (page === '...' || page < 1 || (this.pagination && page > this.pagination.last_page)) {
        return
      }
      
      this.currentPage = page
      await this.loadAttendances()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },

    async exportData() {
      try {
        this.loading = true;
        
        const params = {};
        
        // Add current filters to export
        if (this.filters.userName) params.user_name = this.filters.userName;
        if (this.filters.startDate) params.start_date = this.filters.startDate;
        if (this.filters.endDate) params.end_date = this.filters.endDate;
        
        // Make API request to get the CSV data
        const response = await axios.get('/user/my-team-attendance/export', { 
          params,
          responseType: 'blob' // Important for file downloads
        });
        
        // Create blob and download
        const blob = new Blob([response.data], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        
        // Create download link
        const link = document.createElement('a');
        link.href = url;
        
        // Get filename from response headers or use default
        const contentDisposition = response.headers['content-disposition'];
        let filename = 'team_attendance_export.csv';
        
        if (contentDisposition) {
          const filenameMatch = contentDisposition.match(/filename="(.+)"/);
          if (filenameMatch) {
            filename = filenameMatch[1];
          }
        }
        
        link.download = filename;
        link.style.display = 'none';
        
        // Trigger download
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Clean up
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

    getPageNumbers() {
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      const pages = [];
      
      if (last <= 7) {
        for (let i = 1; i <= last; i++) {
          pages.push(i);
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(last);
        } else if (current >= last - 3) {
          pages.push(1);
          pages.push('...');
          for (let i = last - 4; i <= last; i++) {
            pages.push(i);
          }
        } else {
          pages.push(1);
          pages.push('...');
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(last);
        }
      }
      
      return pages;
    },

    getLunchTimesDisplay(attendance) {
      if (attendance.lunch_start && attendance.lunch_end) {
        return `${attendance.lunch_start} - ${attendance.lunch_end}`;
      }
      return '--:--';
    },

    getBreakTimesDisplay(attendance) {
      if (!attendance.breaks || attendance.breaks.length === 0) {
        return '--:--';
      }
      
      return attendance.breaks.map(brk => {
        const end = brk.end || 'ongoing';
        return `${brk.start} - ${end}`;
      }).join(', ');
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