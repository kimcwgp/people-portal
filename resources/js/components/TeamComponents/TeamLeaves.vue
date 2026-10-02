<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Title bar, matching Leave Requests -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Team's Leaves</h1>
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

      <!-- Filters + results live in one card, as in Leave Requests -->
      <div class="bg-surface rounded-lg shadow-sm border border-border overflow-hidden">
        <!-- Notched-outline fields: the label sits on the border line.
             shrink-0 keeps the set widths so they wrap instead of collapsing. -->
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="relative w-full shrink-0 sm:w-52">
            <label for="tl-status" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Status</label>
            <select id="tl-status" v-model="filters.status"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option v-for="status in statusFilters" :key="status.value" :value="status.value">
                {{ status.label }} ({{ status.count }})
              </option>
            </select>
          </div>

          <div class="relative w-full shrink-0 sm:w-56">
            <label for="tl-name" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Employee</label>
            <input id="tl-name" type="text" v-model="filters.userName" placeholder="Search by name"
                   class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text placeholder:text-text-subtle focus:border-accent focus:outline-none">
          </div>

          <div class="relative w-full shrink-0 sm:w-72">
            <label for="tl-date" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Date</label>
            <div id="tl-date" class="flex items-center gap-1.5 rounded-lg border border-border px-3 py-2.5 focus-within:border-accent">
              <input type="date" v-model="filters.startDate"
                     class="w-full min-w-0 bg-transparent text-sm text-text focus:outline-none">
              <span class="text-text-subtle">&ndash;</span>
              <input type="date" v-model="filters.endDate"
                     class="w-full min-w-0 bg-transparent text-sm text-text focus:outline-none">
            </div>
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
        <div v-if="!loading && leaves.length > 0" class="border-b border-border px-4 py-2">
          <p class="text-xs text-text-muted">
            Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} leave requests
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="leaves.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No leave requests found</h3>
          <p class="mt-1 text-text-muted">
            {{ hasActiveFilters ? 'Try adjusting your filters' : 'Your team hasn\'t submitted any leave requests yet.' }}
          </p>
        </div>

        <!-- Leaves Table -->
        <div v-else>
          <!-- Desktop Table -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full min-w-[1200px]">
              <thead class="bg-surface-sunken border-b border-border">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Employee</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Leave Type</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Start Date</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">End Date</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Duration</th>
                  <th class="min-w-[190px] px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Reason</th>
                  <th class="min-w-[190px] px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Notes</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Attachment</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Submitted</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="leave in leaves" :key="leave.id" class="hover:bg-surface-sunken transition-colors">
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm font-semibold text-text">{{ leave.user?.name }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center whitespace-nowrap px-2 py-1 rounded-md text-xs font-medium bg-status-purple text-status-text">
                      {{ leave.leave_type?.name }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ formatDate(leave.start_date) }}</p>
                    <p v-if="leave.time_in" class="whitespace-nowrap text-xs text-text-muted">{{ formatTime(leave.time_in) }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ formatDate(leave.end_date) }}</p>
                    <p v-if="leave.time_out" class="whitespace-nowrap text-xs text-text-muted">{{ formatTime(leave.time_out) }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm font-medium text-text">
                      {{ leave.formatted_duration || (calculateDays(leave.start_date, leave.end_date) + ' day' + (calculateDays(leave.start_date, leave.end_date) > 1 ? 's' : '')) }}
                    </p>
                    <p class="whitespace-nowrap text-xs text-text-muted">{{ leave.duration || 'All Day' }}</p>
                  </td>
                  <td class="px-4 py-3 max-w-xs">
                    <p class="text-sm text-text line-clamp-2" @mouseenter="showTip($event, leave.reason)" @mouseleave="hideTip">{{ leave.reason || 'No reason provided' }}</p>
                  </td>
                  <td class="px-4 py-3 max-w-xs">
                    <p v-if="leave.rejection_note" class="text-sm text-danger line-clamp-2"
                       @mouseenter="showTip($event, leave.rejection_note)" @mouseleave="hideTip">
                      <span class="font-semibold">{{ leave.status === 'cancelled' ? 'Cancelled: ' : 'Rejected: ' }}</span>{{ leave.rejection_note }}
                    </p>
                    <p v-else-if="leave.notes" class="text-sm text-text-muted line-clamp-2"
                       @mouseenter="showTip($event, leave.notes)" @mouseleave="hideTip">{{ leave.notes }}</p>
                    <span v-else class="text-sm text-text-muted">-</span>
                  </td>
                  <td class="px-4 py-3">
                    <button
                      v-if="leave.attachment"
                      @click="viewAttachment(leave)"
                      class="flex items-center gap-1 whitespace-nowrap text-sm text-accent-solid hover:text-accent-hover"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                      </svg>
                      <span>View</span>
                    </button>
                    <span v-else class="text-sm text-text-muted">-</span>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ formatDate(leave.created_at) }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <span :class="getStatusClass(leave.status)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap">
                      {{ leave.status.toUpperCase() }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Cards -->
          <div class="lg:hidden divide-y divide-border">
            <div v-for="leave in leaves" :key="leave.id" class="p-4 hover:bg-surface-sunken transition-colors">
              <div class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-text">{{ leave.user?.name }}</p>
                  <span class="mt-1 inline-flex items-center rounded-md bg-status-purple px-2 py-0.5 text-xs font-medium text-status-text">
                    {{ leave.leave_type?.name }}
                  </span>
                </div>
                <span :class="getStatusClass(leave.status)" class="flex-shrink-0 px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap">
                  {{ leave.status.toUpperCase() }}
                </span>
              </div>

              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Dates:</span>
                  <span class="font-medium text-text">{{ formatDateRange(leave.start_date, leave.end_date) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Duration:</span>
                  <span class="font-medium text-text">
                    {{ leave.formatted_duration || (calculateDays(leave.start_date, leave.end_date) + ' days') }}
                    ({{ leave.duration || 'All Day' }})
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Submitted:</span>
                  <span class="font-medium text-text">{{ formatDate(leave.created_at) }}</span>
                </div>
                <div class="text-sm">
                  <span class="text-text-muted">Reason:</span>
                  <p class="mt-1 text-text line-clamp-2">{{ leave.reason || 'No reason provided' }}</p>
                </div>
                <div v-if="leave.rejection_note || leave.notes" class="text-sm">
                  <span class="text-text-muted">Notes:</span>
                  <p v-if="leave.rejection_note" class="mt-1 text-danger line-clamp-2">
                    <span class="font-semibold">{{ leave.status === 'cancelled' ? 'Cancelled: ' : 'Rejected: ' }}</span>{{ leave.rejection_note }}
                  </p>
                  <p v-else class="mt-1 text-text-muted line-clamp-2">{{ leave.notes }}</p>
                </div>
                <div v-if="leave.attachment">
                  <button @click="viewAttachment(leave)"
                          class="inline-flex items-center gap-1 text-xs text-accent-solid hover:text-accent-hover">
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

        <!-- Pagination sits inside the same card as the table -->
        <div v-if="leaves.length > 0" class="border-t border-border px-4 py-3">
          <div class="flex items-center justify-between sm:hidden">
            <button
              @click="changePage(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50"
            >
              Previous
            </button>
            <span class="text-sm text-text">Page {{ currentPage }} of {{ lastPage }}</span>
            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage >= lastPage"
              class="rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50"
            >
              Next
            </button>
          </div>

          <div class="hidden sm:flex sm:flex-col sm:space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex items-center text-sm text-text-muted">
              <span>Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} results</span>
            </div>
            <div class="flex items-center space-x-1">
              <button
                @click="changePage(currentPage - 1)"
                :disabled="currentPage <= 1"
                class="rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50"
              >
                Previous
              </button>
              <button
                @click="changePage(currentPage + 1)"
                :disabled="currentPage >= lastPage"
                class="rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- Attachment preview: images inline, PDFs in a frame, anything else
           falls back to a download link. -->
      <div v-if="showAttachmentModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="closeAttachmentModal"></div>
        <div class="relative bg-surface rounded-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
          <div class="p-4 border-b border-border flex items-center justify-between">
            <h3 class="text-lg font-semibold text-text truncate">{{ currentAttachmentName }}</h3>
            <div class="flex flex-shrink-0 items-center gap-2">
              <a :href="currentAttachmentUrl" target="_blank" rel="noopener"
                 class="px-3 py-1 text-sm bg-accent-solid text-white rounded-lg hover:bg-accent-hover">Download</a>
              <button @click="closeAttachmentModal" class="p-2 rounded-lg hover:bg-surface-sunken" aria-label="Close">
                <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="p-6 overflow-auto max-h-[calc(90vh-100px)]">
            <div v-if="isImageFile(currentAttachment)" class="text-center">
              <img :src="currentAttachmentUrl" :alt="currentAttachmentName" class="max-w-full max-h-[70vh] mx-auto rounded-lg shadow-lg">
            </div>
            <div v-else class="text-center">
              <iframe v-if="getFileExtension(currentAttachment) === 'pdf'" :src="currentAttachmentUrl"
                      class="w-full h-[70vh] rounded-lg border border-border"></iframe>
              <div v-else class="py-12">
                <div class="w-16 h-16 mx-auto mb-4 bg-surface-sunken rounded-full flex items-center justify-center">
                  <svg class="w-8 h-8 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <p class="text-text-muted mb-2">{{ currentAttachmentName }}</p>
                <p class="text-sm text-text-muted">This file type cannot be previewed. Use Download to open it.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Hover tooltip for clamped cells. Fixed positioning so the table's
           overflow-x-auto wrapper cannot clip it. -->
      <div
        v-if="tip.show"
        class="pointer-events-none fixed z-[60] max-w-sm rounded-lg bg-text px-3 py-2 text-xs leading-relaxed text-white shadow-lg"
        :style="{ left: tip.x + 'px', top: tip.y + 'px' }"
      >
        {{ tip.text }}
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
      tip: { show: false, text: '', x: 0, y: 0 },
      showAttachmentModal: false,
      currentAttachment: null,
      currentAttachmentUrl: '',
      imageExtensions: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
      currentStatus: '',
      currentPage: 1,
      lastPage: 1,
      totalItems: 0,
      perPage: 10,
      perPageOptions: [10, 15, 25, 50],
      filters: {
        status: '',
        userName: '',
        startDate: '',
        endDate: ''
      },
      statusFilters: [
        { label: 'All Statuses', value: '', count: 0 },
        { label: 'Pending', value: 'pending', count: 0 },
        { label: 'Approved', value: 'approved', count: 0 },
        { label: 'Rejected', value: 'rejected', count: 0 },
        { label: 'Cancelled', value: 'cancelled', count: 0 }
      ]
    }
  },

  computed: {
    currentAttachmentName() {
      return this.currentAttachment ? this.currentAttachment.split('/').pop() : 'Attachment'
    },

    hasActiveFilters() {
      return this.currentStatus || this.filters.userName || this.filters.startDate || this.filters.endDate
    }
  },

  async mounted() {
    document.title = "Team's Leaves"
    await this.loadLeaves()
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
      this.currentStatus = this.filters.status
      this.currentPage = 1
      this.loadLeaves()
    },

    clearFilters() {
      this.filters = {
        status: '',
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
          filter.count = this.stats.total ?? this.totalItems ?? 0
        } else if (this.stats[filter.value] !== undefined) {
          filter.count = this.stats[filter.value]
        } else {
          filter.count = 0
        }
      })
    },

    /** Preview rather than download; the modal offers Download as a fallback. */
    viewAttachment(leave) {
      this.currentAttachment = leave.attachment;
      this.currentAttachmentUrl = leave.attachment_url;
      this.showAttachmentModal = true;
    },

    closeAttachmentModal() {
      this.showAttachmentModal = false;
      this.currentAttachment = null;
      this.currentAttachmentUrl = '';
    },

    getFileExtension(p) {
      return p ? p.split('.').pop().toLowerCase() : '';
    },

    isImageFile(p) {
      return this.imageExtensions.includes(this.getFileExtension(p));
    },

    /** Only worth a tooltip when the cell is actually clamped. */
    showTip(event, text) {
      const el = event.currentTarget;
      if (!text || el.scrollHeight <= el.clientHeight + 1) return;
      const r = el.getBoundingClientRect();
      this.tip = {
        show: true,
        text,
        x: Math.min(r.left, window.innerWidth - 400),
        y: r.bottom + 8,
      };
    },

    hideTip() {
      this.tip.show = false;
    },

    getStatusClass(status) {
      switch (status) {
        case 'pending':
          return 'bg-status-yellow text-status-text'
        case 'approved':
          return 'bg-status-green text-status-text'
        case 'rejected':
          return 'bg-status-red text-status-text'
        case 'cancelled':
          return 'bg-status-gray text-status-text'
        default:
          return 'bg-status-gray text-status-text'
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
        type === 'success' ? 'bg-success' :
        type === 'error'   ? 'bg-danger'   :
        type === 'warning' ? 'bg-warning': 'bg-accent-solid'
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