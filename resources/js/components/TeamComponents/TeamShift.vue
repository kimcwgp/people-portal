<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Title bar, matching Leave Requests -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Team's Shift</h1>
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
            <label for="ts-status" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Status</label>
            <select id="ts-status" v-model="filters.status"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option v-for="status in statusFilters" :key="status.value" :value="status.value">
                {{ status.label }} ({{ status.count }})
              </option>
            </select>
          </div>

          <div class="relative w-full shrink-0 sm:w-56">
            <label for="ts-name" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Employee</label>
            <input id="ts-name" type="text" v-model="filters.userName" placeholder="Search by name"
                   class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text placeholder:text-text-subtle focus:border-accent focus:outline-none">
          </div>

          <div class="relative w-full shrink-0 sm:w-72">
            <label for="ts-date" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Date</label>
            <div id="ts-date" class="flex items-center gap-1.5 rounded-lg border border-border px-3 py-2.5 focus-within:border-accent">
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
        <div v-if="!loading && shiftRequests.length > 0" class="border-b border-border px-4 py-2">
          <p class="text-xs text-text-muted">
            Showing {{ pagination?.from || 0 }} to {{ pagination?.to || 0 }} of {{ pagination?.total || 0 }} shift change requests
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="shiftRequests.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No shift change requests found</h3>
          <p class="mt-1 text-text-muted">
            {{ hasActiveFilters ? 'Try adjusting your filters' : 'Your team hasn\'t submitted any shift change requests yet.' }}
          </p>
        </div>

        <!-- Shift Requests Table -->
        <div v-else>
          <!-- Desktop Table -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full min-w-[1100px]">
              <thead class="bg-surface-sunken border-b border-border">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Employee</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Current Shift</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Requested Shift</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Effective Date</th>
                  <th class="min-w-[190px] px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Reason</th>
                  <th class="min-w-[190px] px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Notes</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Submitted</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="request in shiftRequests" :key="request.id" class="hover:bg-surface-sunken transition-colors">
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm font-semibold text-text">{{ request.user?.name }}</p>
                    <p class="whitespace-nowrap text-xs text-text-muted">{{ request.user?.email }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ request.current_shift?.shift_label || request.current_shift?.shift_type || 'None' }}</p>
                    <p v-if="request.current_shift" class="whitespace-nowrap text-xs text-text-muted">
                      {{ formatTime(request.current_shift.start_time) }} - {{ formatTime(request.current_shift.end_time) }}
                    </p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm font-semibold text-text">{{ request.requested_shift?.shift_label || request.requested_shift?.shift_type || 'Unknown' }}</p>
                    <p v-if="request.requested_shift" class="whitespace-nowrap text-xs text-text-muted">
                      {{ formatTime(request.requested_shift.start_time) }} - {{ formatTime(request.requested_shift.end_time) }}
                    </p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ request.formatted_effective_date }}</p>
                  </td>
                  <td class="px-4 py-3 max-w-xs">
                    <p class="text-sm text-text line-clamp-2"
                       @mouseenter="showTip($event, request.reason)" @mouseleave="hideTip">
                      {{ request.reason || 'No reason provided' }}
                    </p>
                  </td>
                  <td class="px-4 py-3 max-w-xs">
                    <p v-if="request.approver_notes && request.status === 'rejected'" class="text-sm text-danger line-clamp-2"
                       @mouseenter="showTip($event, request.approver_notes)" @mouseleave="hideTip">
                      <span class="font-semibold">Rejected: </span>{{ request.approver_notes }}
                    </p>
                    <p v-else-if="request.approver_notes" class="text-sm text-text-muted line-clamp-2"
                       @mouseenter="showTip($event, request.approver_notes)" @mouseleave="hideTip">{{ request.approver_notes }}</p>
                    <span v-else class="text-sm text-text-muted">-</span>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ request.relative_date }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <span :class="getStatusClass(request.status)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap uppercase">
                      {{ request.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Cards -->
          <div class="lg:hidden divide-y divide-border">
            <div v-for="request in shiftRequests" :key="request.id" class="p-4 hover:bg-surface-sunken transition-colors">
              <div class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-text">{{ request.user?.name }}</p>
                  <p class="truncate text-xs text-text-muted">{{ request.user?.email }}</p>
                </div>
                <span :class="getStatusClass(request.status)" class="flex-shrink-0 px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap uppercase">
                  {{ request.status }}
                </span>
              </div>

              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Current:</span>
                  <span class="text-right font-medium text-text">{{ request.current_shift?.shift_label || request.current_shift?.shift_type || 'None' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Requested:</span>
                  <span class="text-right font-medium text-text">{{ request.requested_shift?.shift_label || request.requested_shift?.shift_type || 'Unknown' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Effective:</span>
                  <span class="font-medium text-text">{{ request.formatted_effective_date }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Submitted:</span>
                  <span class="font-medium text-text">{{ request.relative_date }}</span>
                </div>
                <div class="text-sm">
                  <span class="text-text-muted">Reason:</span>
                  <p class="mt-1 text-text">{{ request.reason || 'No reason provided' }}</p>
                </div>
                <div v-if="request.approver_notes" class="text-sm">
                  <span class="text-text-muted">Notes:</span>
                  <p :class="request.status === 'rejected' ? 'text-danger' : 'text-text-muted'" class="mt-1">
                    <span v-if="request.status === 'rejected'" class="font-semibold">Rejected: </span>{{ request.approver_notes }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination sits inside the same card as the table -->
        <div v-if="pagination && pagination.last_page > 1" class="border-t border-border px-4 py-3">
          <div class="flex items-center justify-center gap-1">
            <button
              @click="goToPage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50"
            >
              Previous
            </button>
            <template v-for="page in visiblePages" :key="page">
              <button
                v-if="page !== '...'"
                @click="goToPage(page)"
                :class="[
                  'rounded-md px-3 py-2 text-sm font-medium transition-colors',
                  page === pagination.current_page
                    ? 'bg-accent-solid text-white'
                    : 'border border-border bg-surface text-text hover:bg-surface-sunken'
                ]"
              >
                {{ page }}
              </button>
              <span v-else class="px-2 text-text-subtle">...</span>
            </template>
            <button
              @click="goToPage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="rounded-md border border-border bg-surface px-3 py-2 text-sm font-medium text-text-muted transition-colors hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50"
            >
              Next
            </button>
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
import axios from '@/axios';

export default {
  name: 'TeamShift',
  data() {
    return {
      loading: false,
      shiftRequests: [],
      pagination: null,
      currentStatus: 'all',
      tip: { show: false, text: '', x: 0, y: 0 },
      filters: {
        status: 'all',
        userName: '',
        startDate: '',
        endDate: ''
      },
      appliedFilters: {},
      statusCounts: {
        all: 0,
        pending: 0,
        approved: 0,
        rejected: 0
      }
    };
  },
  computed: {
    statusFilters() {
      return [
        { label: 'All Statuses', value: 'all', count: this.statusCounts.all },
        { label: 'Pending', value: 'pending', count: this.statusCounts.pending },
        { label: 'Approved', value: 'approved', count: this.statusCounts.approved },
        { label: 'Rejected', value: 'rejected', count: this.statusCounts.rejected }
      ];
    },
    hasActiveFilters() {
      return !!(this.appliedFilters.userName || this.appliedFilters.startDate || this.appliedFilters.endDate);
    },
    visiblePages() {
      if (!this.pagination) return [];
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      const pages = [];

      if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i);
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) pages.push(i);
          pages.push('...');
          pages.push(last);
        } else if (current >= last - 3) {
          pages.push(1);
          pages.push('...');
          for (let i = last - 4; i <= last; i++) pages.push(i);
        } else {
          pages.push(1);
          pages.push('...');
          for (let i = current - 1; i <= current + 1; i++) pages.push(i);
          pages.push('...');
          pages.push(last);
        }
      }
      return pages;
    }
  },
  mounted() {
    this.loadShiftRequests();
  },
  methods: {
    async loadShiftRequests(page = 1) {
      try {
        this.loading = true;
        const params = {
          page,
          status: this.currentStatus,
          ...this.appliedFilters
        };

        const { data } = await axios.get('/user/my-team-shift/requests', { params });

        this.shiftRequests = data.data.data || [];
        this.pagination = {
          current_page: data.data.current_page,
          last_page: data.data.last_page,
          from: data.data.from,
          to: data.data.to,
          total: data.data.total
        };
        this.statusCounts = data.data.status_counts || this.statusCounts;
      } catch (error) {
        this.showMessage(error.response?.data?.message || 'Failed to load shift change requests', 'error');
      } finally {
        this.loading = false;
      }
    },
    applyFilters() {
      this.currentStatus = this.filters.status;
      this.appliedFilters = { ...this.filters };
      this.loadShiftRequests(1);
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
    clearFilters() {
      this.filters = {
        status: 'all',
        userName: '',
        startDate: '',
        endDate: ''
      };
      this.currentStatus = 'all';
      this.appliedFilters = {};
      this.loadShiftRequests(1);
    },
    async exportData() {
      try {
        this.loading = true;
        const params = {
          status: this.currentStatus,
          ...this.appliedFilters,
          export: true
        };

        const response = await axios.get('/user/my-team-shift/export', {
          params,
          responseType: 'blob'
        });

        const blob = new Blob([response.data], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `team-shift-changes-${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
        window.URL.revokeObjectURL(url);

        this.showMessage('Export completed successfully', 'success');
      } catch (error) {
        this.showMessage('Failed to export data', 'error');
      } finally {
        this.loading = false;
      }
    },
    goToPage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.loadShiftRequests(page);
      }
    },
    getStatusClass(status) {
      const classes = {
        'pending': 'bg-status-yellow text-status-text',
        'approved': 'bg-status-green text-status-text',
        'rejected': 'bg-status-red text-status-text'
      };
      return classes[status?.toLowerCase()] || 'bg-status-gray text-status-text';
    },
    formatTime(time) {
      if (!time) return '';
      try {
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
      } catch (e) {
        return time;
      }
    },
    showMessage(message, type = 'info') {
      const toast = document.createElement('div');
      toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-success' :
        type === 'error' ? 'bg-danger' :
        type === 'warning' ? 'bg-warning' : 'bg-accent-solid'
      }`;
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(() => toast.style.opacity = '1', 100);
      setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => document.body.removeChild(toast), 300);
      }, 3000);
    }
  },
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
