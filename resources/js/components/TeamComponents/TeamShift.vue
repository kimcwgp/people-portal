<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">My Team's Shift Changes</h1>

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
        <div class="flex flex-wrap items-center gap-4 lg:gap-6">

          <!-- Status Filter Buttons -->
          <div class="flex flex-wrap gap-2 min-w-0">
            <button
              v-for="status in statusFilters"
              :key="status.value"
              @click="currentStatus = status.value"
              :class="[
                'px-4 py-2.5 text-sm font-medium rounded-lg transition-colors whitespace-nowrap',
                currentStatus === status.value
                  ? 'bg-purple-600 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
              ]"
            >
              {{ status.label }}
              <span v-if="status.count > 0" :class="[
                'ml-2 px-2 py-0.5 text-xs rounded-full',
                currentStatus === status.value
                  ? 'bg-purple-100 text-purple-800'
                  : 'bg-purple-100 text-purple-800'
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
              class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 w-40 lg:w-48"
            >
          </div>

          <!-- Date Range Section -->
          <div class="flex items-center gap-3 text-sm">
            <span class="text-gray-700 font-medium whitespace-nowrap hidden sm:inline">Date:</span>
            <input
              type="date"
              v-model="filters.startDate"
              class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 w-40"
            >
            <span class="text-gray-400 text-sm">to</span>
            <input
              type="date"
              v-model="filters.endDate"
              class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 w-40"
            >
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-3 ml-auto">
            <button
              @click="applyFilters"
              class="bg-purple-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors whitespace-nowrap"
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
      <div v-if="!loading && shiftRequests.length > 0" class="mb-4">
        <p class="text-sm text-gray-600">
          Showing {{ pagination?.from || 0 }} to {{ pagination?.to || 0 }} of {{ pagination?.total || 0 }} shift change requests
        </p>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
      </div>

      <div v-else-if="shiftRequests.length === 0" class="rounded-lg bg-white p-8 shadow-sm text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
        </svg>
        <h3 class="mt-3 text-lg font-semibold text-gray-900">No shift change requests found</h3>
        <p class="mt-1 text-gray-600">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'Your team hasn\'t submitted any shift change requests yet.' }}
        </p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="request in shiftRequests"
          :key="request.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200"
        >
          <div class="p-4">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 space-y-3 sm:space-y-0">
              <div class="flex items-center space-x-3 flex-1">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                  <span class="text-sm font-bold text-white">
                    {{ request.user?.name?.charAt(0) || 'U' }}
                  </span>
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 truncate">{{ request.user?.name }}</h3>
                  <p class="text-sm text-gray-600 truncate">{{ request.user?.email }}</p>
                </div>
              </div>

              <div class="flex items-center justify-between sm:justify-end sm:space-x-3 flex-shrink-0">
                <div class="flex items-center space-x-3">
                  <span :class="getStatusClass(request.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ request.status }}
                  </span>
                  <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">{{ request.relative_date }}</p>
                    <p class="text-xs text-gray-500">Submitted</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
              <!-- Current Shift Column -->
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">CURRENT SHIFT</p>
                <p class="text-sm font-semibold text-gray-900">{{ request.current_shift?.shift_label || request.current_shift?.shift_type || 'None' }}</p>
                <p v-if="request.current_shift" class="text-xs text-gray-600">
                  {{ formatTime(request.current_shift.start_time) }} - {{ formatTime(request.current_shift.end_time) }}
                </p>
              </div>

              <!-- Requested Shift Column -->
              <div class="bg-purple-50 rounded-lg p-3">
                <p class="text-xs font-medium text-purple-700 mb-1">REQUESTED SHIFT</p>
                <p class="text-sm font-semibold text-purple-900">{{ request.requested_shift?.shift_label || request.requested_shift?.shift_type || 'Unknown' }}</p>
                <p v-if="request.requested_shift" class="text-xs text-purple-600">
                  {{ formatTime(request.requested_shift.start_time) }} - {{ formatTime(request.requested_shift.end_time) }}
                </p>
              </div>

              <!-- Effective Date Column -->
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">EFFECTIVE DATE</p>
                <p class="text-sm text-gray-900">{{ request.formatted_effective_date }}</p>
              </div>

              <!-- Reason Column -->
              <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs font-medium text-gray-500 mb-1">REASON</p>
                <p class="text-sm text-gray-900 line-clamp-2">{{ request.reason || 'No reason provided' }}</p>
              </div>
            </div>

            <!-- Rejection Note -->
            <div v-if="request.approver_notes && request.status === 'rejected'" class="bg-red-50 border border-red-200 rounded-lg p-3 mt-3">
              <div class="flex items-start space-x-2">
                <svg class="w-4 h-4 text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                  <p class="text-xs font-medium text-red-700 mb-1">REJECTION REASON</p>
                  <p class="text-xs text-red-600">{{ request.approver_notes }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-6 flex justify-center">
        <nav class="flex items-center space-x-2">
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-1 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <template v-for="page in visiblePages" :key="page">
            <button
              v-if="page !== '...'"
              @click="goToPage(page)"
              :class="[
                'px-3 py-1 rounded-lg text-sm font-medium',
                page === pagination.current_page
                  ? 'bg-purple-600 text-white'
                  : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            <span v-else class="px-2 text-gray-500">...</span>
          </template>
          <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </nav>
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
      filters: {
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
        { label: 'All', value: 'all', count: this.statusCounts.all },
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
      this.appliedFilters = { ...this.filters };
      this.loadShiftRequests(1);
    },
    clearFilters() {
      this.filters = {
        userName: '',
        startDate: '',
        endDate: ''
      };
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
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800'
      };
      return classes[status?.toLowerCase()] || 'bg-gray-100 text-gray-800';
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
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
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
  watch: {
    currentStatus() {
      this.loadShiftRequests(1);
    }
  }
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
