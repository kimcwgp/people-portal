<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Title bar, matching Attendance -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Team's Attendance</h1>
        <button
          @click="exportData"
          :disabled="loading"
          class="flex flex-shrink-0 items-center gap-2 rounded-lg bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover disabled:opacity-50"
        >
          <svg v-if="!loading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="hidden sm:inline">{{ loading ? 'Exporting...' : 'Export CSV' }}</span>
        </button>
      </div>

      <!-- Filters + results live in one card, as in Attendance -->
      <div class="bg-surface rounded-lg shadow-sm border border-border overflow-hidden">
        <!-- Notched-outline fields: the label sits on the border line.
             shrink-0 keeps the set widths so they wrap instead of collapsing. -->
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="relative w-full shrink-0 sm:w-56">
            <label for="ta-name" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Employee</label>
            <input id="ta-name" type="text" v-model="filters.userName" placeholder="Search by name"
                   class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text placeholder:text-text-subtle focus:border-accent focus:outline-none">
          </div>

          <div class="relative w-full shrink-0 sm:w-72">
            <label for="ta-date" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Date</label>
            <div id="ta-date" class="flex items-center gap-1.5 rounded-lg border border-border px-3 py-2.5 focus-within:border-accent">
              <input type="date" v-model="filters.startDate"
                     class="w-full min-w-0 bg-transparent text-sm text-text focus:outline-none">
              <span class="text-text-subtle">&ndash;</span>
              <input type="date" v-model="filters.endDate"
                     class="w-full min-w-0 bg-transparent text-sm text-text focus:outline-none">
            </div>
          </div>

          <div class="relative w-full shrink-0 sm:w-28">
            <label for="ta-page" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Page</label>
            <select id="ta-page" v-model="selectedPerPage" @change="changePerPage"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
            </select>
          </div>

          <div class="flex shrink-0 gap-2">
            <button @click="applyFilters"
                    class="flex-1 rounded-lg bg-accent-solid px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover sm:flex-none">
              Filter
            </button>
            <button @click="clearFilters"
                    class="flex-1 rounded-lg border border-border bg-surface-sunken px-5 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas sm:flex-none">
              Clear
            </button>
          </div>
        </div>

        <!-- Results summary -->
        <div v-if="!loading && attendances.length > 0" class="border-b border-border px-4 py-2">
          <p class="text-xs text-text-muted">
            Showing {{ pagination?.from || 0 }} to {{ pagination?.to || 0 }} of {{ pagination?.total || 0 }} records
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="attendances.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No attendance records found</h3>
          <p class="mt-1 text-text-muted">
            {{ hasActiveFilters ? 'Try adjusting your filters or date range' : 'Your team hasn\'t logged any attendance yet.' }}
          </p>
        </div>

        <!-- Attendance Table -->
        <div v-else>
          <!-- Desktop Table -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full min-w-[900px]">
              <thead class="bg-surface-sunken border-b border-border">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Employee</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Time In / Time Out</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Date</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Working Hours</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr
                  v-for="attendance in attendances"
                  :key="attendance.id || `${attendance.user?.id}-${attendance.attendance_date}`"
                  class="hover:bg-surface-sunken transition-colors"
                >
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm font-semibold text-text">{{ attendance.user?.name || '&mdash;' }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <!-- Multiple sessions display -->
                    <div v-if="attendance.sessions && attendance.sessions.length > 1" class="space-y-1">
                      <div v-for="(session, index) in attendance.sessions" :key="index" class="text-sm">
                        <span class="text-text">{{ getTimeDisplay(session.time_in) }}</span>
                        <span class="text-text-muted mx-1">/</span>
                        <span class="text-text">{{ getTimeDisplay(session.time_out) }}</span>
                      </div>
                    </div>
                    <!-- Single session display -->
                    <div v-else>
                      <p class="text-sm text-text">{{ getTimeDisplay(attendance.time_in) }}</p>
                      <p class="text-sm text-text">{{ getTimeDisplay(attendance.time_out) }}</p>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm font-semibold text-text">{{ formatDay(attendance.attendance_date) }}</p>
                    <p class="whitespace-nowrap text-xs text-text-muted">{{ formatDate(attendance.attendance_date) }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <!-- Multiple sessions working hours -->
                    <div v-if="attendance.sessions && attendance.sessions.length > 1" class="space-y-1">
                      <p v-for="(session, index) in attendance.sessions" :key="index" class="text-sm font-semibold text-text">
                        {{ session.working_hours || '--:--' }}
                      </p>
                    </div>
                    <!-- Single session -->
                    <p v-else class="text-sm font-semibold text-text">{{ attendance.working_hours || '--:--' }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <span
                      v-if="getStatusLabel(attendance)"
                      :class="getStatusClass(attendance)"
                      class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                    >
                      {{ getStatusLabel(attendance) }}
                    </span>
                    <span v-else class="text-sm text-text-subtle">-</span>
                    <button
                      v-if="attendance.leave_info?.has_attachment"
                      @click="viewAttachment(attendance.leave_info.attachment_url)"
                      class="mt-1 flex items-center gap-1 text-xs text-accent-solid hover:text-accent-hover"
                    >
                      <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                      </svg>
                      Certificate
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Cards -->
          <div class="lg:hidden divide-y divide-border">
            <div
              v-for="attendance in attendances"
              :key="attendance.id || `${attendance.user?.id}-${attendance.attendance_date}`"
              class="p-4 hover:bg-surface-sunken transition-colors"
            >
              <div class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-text">{{ attendance.user?.name || '&mdash;' }}</p>
                  <p class="text-xs text-text-muted">{{ formatDay(attendance.attendance_date) }}, {{ formatDate(attendance.attendance_date) }}</p>
                </div>
                <span
                  v-if="getStatusLabel(attendance)"
                  :class="getStatusClass(attendance)"
                  class="flex-shrink-0 px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                >
                  {{ getStatusLabel(attendance) }}
                </span>
              </div>

              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Time In/Out:</span>
                  <div v-if="attendance.sessions && attendance.sessions.length > 1" class="space-y-1 text-right">
                    <div v-for="(session, index) in attendance.sessions" :key="index" class="font-medium text-text">
                      {{ getTimeDisplay(session.time_in) }} / {{ getTimeDisplay(session.time_out) }}
                    </div>
                  </div>
                  <span v-else class="font-medium text-text">
                    {{ getTimeDisplay(attendance.time_in) }} / {{ getTimeDisplay(attendance.time_out) }}
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Working Hours:</span>
                  <div v-if="attendance.sessions && attendance.sessions.length > 1" class="space-y-1 text-right">
                    <div v-for="(session, index) in attendance.sessions" :key="index" class="font-medium text-text">
                      {{ session.working_hours || '--:--' }}
                    </div>
                  </div>
                  <span v-else class="font-medium text-text">{{ attendance.working_hours || '--:--' }}</span>
                </div>
                <div v-if="attendance.leave_info?.has_attachment">
                  <button
                    @click="viewAttachment(attendance.leave_info.attachment_url)"
                    class="inline-flex items-center gap-1 text-xs text-accent-solid hover:text-accent-hover"
                  >
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    View Medical Certificate
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination sits inside the same card as the table -->
        <div v-if="pagination && pagination.total > pagination.per_page" class="border-t border-border px-4 py-3">
          <div class="flex items-center justify-between sm:hidden">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50"
            >
              Previous
            </button>
            <span class="text-sm font-medium text-text">
              Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
              class="rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50"
            >
              Next
            </button>
          </div>

          <div class="hidden sm:flex sm:flex-col sm:space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex items-center text-sm text-text-muted">
              <span>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results</span>
            </div>

            <div class="flex items-center space-x-1">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                class="rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50"
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
                    'rounded-md px-3 py-2 text-sm font-medium transition-colors',
                    page === pagination.current_page
                      ? 'bg-accent-solid text-white'
                      : page === '...'
                        ? 'cursor-default text-text-subtle'
                        : 'border border-border bg-surface text-text hover:bg-surface-sunken'
                  ]"
                >
                  {{ page }}
                </button>
              </div>

              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page"
                class="rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50"
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
    document.title = "Team's Attendance"
    await this.loadAttendances()
  },

  methods: {
    getStatusClass(attendance) {
      if (attendance.is_on_leave && !attendance.is_partial_leave) {
        return 'bg-status-purple text-status-text';
      }
      if (attendance.is_partial_leave) {
        return 'bg-status-indigo text-status-text';
      }
      if (attendance.is_weekend) {
        return 'bg-surface-sunken text-text';
      }
      if (attendance.is_awol) {
        return 'bg-status-red text-status-text';
      }
      
      switch (attendance.status) {
        case 'complete':
          return 'bg-status-green text-status-text';
        case 'partial':
          return 'bg-status-yellow text-status-text';
        case 'active':
          return 'bg-status-blue text-status-text';
        case 'undertime':
          return 'bg-status-orange text-status-text';
        case 'absent':
          return 'bg-status-red text-status-text';
        default:
          return 'bg-surface-sunken text-text';
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
        type === 'success' ? 'bg-success' :
        type === 'error'   ? 'bg-danger'   :
        type === 'warning' ? 'bg-warning': 'bg-accent-solid'
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