<template>
  <div class="min-h-screen bg-gray-50 p-2 sm:p-4 lg:p-6">
    <div class="mx-auto max-w-7xl">
      <!-- Header -->
      <div class="mb-4 sm:mb-6 rounded-2xl bg-white p-4 sm:p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex-1">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">My Shift</h1>
          </div>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Current Shift Badge -->
            <div v-if="currentShift" class="flex items-center space-x-3 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-3 sm:p-4">
              <div class="flex-shrink-0">
                <svg class="h-8 w-8 sm:h-10 sm:w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <p class="text-xs text-gray-600">Current Shift</p>
                <p class="text-sm sm:text-base font-bold text-gray-900">{{ currentShift.shift_label }}</p>
              </div>
            </div>

            <!-- Request Shift Change Button -->
            <button
              @click="openRequestModal"
              class="flex items-center justify-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-xl transition-colors font-medium text-sm"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              <span>Request Change</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="mb-4 sm:mb-6 bg-white rounded-xl shadow-sm">
        <div class="border-b border-gray-200">
          <nav class="flex space-x-4 px-4" role="tablist">
            <button
              @click="activeTab = 'history'"
              :class="[
                'py-4 px-2 border-b-2 font-medium text-sm transition-colors',
                activeTab === 'history'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              Shift History
            </button>
            <button
              @click="activeTab = 'requests'"
              :class="[
                'py-4 px-2 border-b-2 font-medium text-sm transition-colors',
                activeTab === 'requests'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              My Requests
              <span v-if="pendingRequestsCount > 0" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                {{ pendingRequestsCount }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- History Tab -->
      <div v-if="activeTab === 'history'">
      <!-- Filters -->
      <div class="mb-4 sm:mb-6 rounded-xl bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
            <select
              v-model="filters.month"
              @change="loadShiftHistory"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
            >
              <option v-for="month in months" :key="month.value" :value="month.value">
                {{ month.label }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
            <select
              v-model="filters.year"
              @change="loadShiftHistory"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
            >
              <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>

          <div class="sm:col-span-2 flex items-end">
            <button
              @click="clearFilters"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="text-center">
          <svg class="motion-safe:animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-gray-600">Loading shift history...</p>
        </div>
      </div>

      <!-- Shift History -->
      <div v-else-if="shiftHistory.length > 0" class="space-y-4">
        <!-- Desktop Table View -->
        <div class="hidden lg:block bg-white rounded-xl shadow-sm overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Changed By</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="entry in shiftHistory" :key="entry.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ formatDate(entry.effective_date) }}</div>
                  <div v-if="entry.end_date" class="text-xs text-gray-500">to {{ formatDate(entry.end_date) }}</div>
                  <div v-else class="text-xs text-green-600 font-medium">Current</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getShiftBadgeClass(entry.shift?.shift_type)" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">
                    {{ entry.shift?.shift_type || 'N/A' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ entry.shift?.shift_label || 'Not Set' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ calculateDuration(entry) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  {{ entry.changed_by || 'System' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card View -->
        <div class="lg:hidden space-y-3">
          <div
            v-for="entry in shiftHistory"
            :key="entry.id"
            class="bg-white rounded-xl p-4 shadow-sm border border-gray-200"
          >
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1">
                <p class="text-sm font-semibold text-gray-900">{{ formatDate(entry.effective_date) }}</p>
                <p v-if="entry.end_date" class="text-xs text-gray-500">to {{ formatDate(entry.end_date) }}</p>
                <p v-else class="text-xs text-green-600 font-medium">Current</p>
              </div>
              <span :class="getShiftBadgeClass(entry.shift?.shift_type)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium">
                {{ entry.shift?.shift_type || 'N/A' }}
              </span>
            </div>

            <div class="space-y-2 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Schedule:</span>
                <span class="font-medium text-gray-900">{{ entry.shift?.shift_label || 'Not Set' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Duration:</span>
                <span class="font-medium text-gray-900">{{ calculateDuration(entry) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Changed By:</span>
                <span class="font-medium text-gray-900">{{ entry.changed_by || 'System' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-xl shadow-sm p-12 text-center">
        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Shift History</h3>
        <p class="text-gray-600">No shift changes found for the selected period.</p>
      </div>
      </div>

      <!-- Requests Tab -->
      <div v-if="activeTab === 'requests'">
        <!-- Loading State -->
        <div v-if="loadingRequests" class="flex items-center justify-center py-12">
          <div class="text-center">
            <svg class="motion-safe:animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-600">Loading requests...</p>
          </div>
        </div>

        <!-- Requests List -->
        <div v-else-if="shiftRequests.length > 0" class="space-y-4">
          <div
            v-for="request in shiftRequests"
            :key="request.id"
            class="bg-white rounded-xl p-4 sm:p-6 shadow-sm border border-gray-200"
          >
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <h3 class="font-semibold text-gray-900">Shift Change Request</h3>
                  <span :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    request.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                    request.status === 'approved' ? 'bg-green-100 text-green-800' :
                    'bg-red-100 text-red-800'
                  ]">
                    {{ request.status_label }}
                  </span>
                </div>
                <p class="text-sm text-gray-500">Requested on {{ request.created_at }}</p>
              </div>
            </div>

            <div class="space-y-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="text-xs text-gray-500">Current Shift</label>
                  <p class="text-sm font-medium text-gray-900">{{ request.current_shift?.shift_label || 'Not Set' }}</p>
                </div>
                <div>
                  <label class="text-xs text-gray-500">Requested Shift</label>
                  <p class="text-sm font-medium text-blue-600">{{ request.requested_shift.shift_label }}</p>
                </div>
              </div>

              <div>
                <label class="text-xs text-gray-500">Effective Date</label>
                <p class="text-sm font-medium text-gray-900">{{ request.effective_date }}</p>
              </div>

              <div>
                <label class="text-xs text-gray-500">Reason</label>
                <p class="text-sm text-gray-700">{{ request.reason }}</p>
              </div>

              <div v-if="request.approver_notes" class="bg-gray-50 rounded-lg p-3">
                <label class="text-xs text-gray-500">Supervisor Notes</label>
                <p class="text-sm text-gray-700 mt-1">{{ request.approver_notes }}</p>
                <p v-if="request.approver" class="text-xs text-gray-500 mt-1">- {{ request.approver }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-xl shadow-sm p-12 text-center">
          <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">No Requests Yet</h3>
          <p class="text-gray-600 mb-4">You haven't submitted any shift change requests.</p>
          <button
            @click="openRequestModal"
            class="inline-flex items-center space-x-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Request Shift Change</span>
          </button>
        </div>
      </div>

      <!-- Request Modal -->
      <div v-if="showRequestModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Request Shift Change</h2>
            <button @click="closeRequestModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitRequest">
            <div class="space-y-4">
              <!-- Notice -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                  <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                  </svg>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-blue-900">Important Notice</p>
                    <p class="text-sm text-blue-700 mt-1">You have {{ daysUntilMonthEnd }} day(s) left to submit your shift change request for next month.</p>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Shift</label>
                <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700">
                  {{ currentShift?.shift_label || 'Not Set' }}
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Requested Shift *</label>
                <select
                  v-model="requestForm.requested_shift_id"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="">Select a shift</option>
                  <option v-for="shift in availableShifts" :key="shift.id" :value="shift.id">
                    {{ shift.shift_label }} ({{ shift.start_time }} - {{ shift.end_time }})
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Effective Date</label>
                <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700">
                  {{ firstDayOfNextMonth }}
                </div>
                <p class="text-xs text-gray-500 mt-1">The shift change will be effective on the first day of next month</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
                <textarea
                  v-model="requestForm.reason"
                  rows="4"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                  placeholder="Please provide a reason for the shift change request"
                  required
                  maxlength="500"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">{{ requestForm.reason.length }}/500 characters</p>
              </div>
            </div>

            <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t">
              <button
                type="button"
                @click="closeRequestModal"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                :disabled="submitting"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                :disabled="submitting"
              >
                {{ submitting ? 'Submitting...' : 'Submit Request' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'MyShift',

  data() {
    const now = new Date();
    const currentYear = now.getFullYear();

    return {
      loading: true,
      loadingRequests: false,
      currentShift: null,
      shiftHistory: [],
      shiftRequests: [],
      availableShifts: [],
      activeTab: 'history',
      showRequestModal: false,
      submitting: false,
      requestForm: {
        requested_shift_id: '',
        reason: ''
      },
      filters: {
        month: now.getMonth() + 1,
        year: currentYear
      },
      months: [
        { value: 1, label: 'January' },
        { value: 2, label: 'February' },
        { value: 3, label: 'March' },
        { value: 4, label: 'April' },
        { value: 5, label: 'May' },
        { value: 6, label: 'June' },
        { value: 7, label: 'July' },
        { value: 8, label: 'August' },
        { value: 9, label: 'September' },
        { value: 10, label: 'October' },
        { value: 11, label: 'November' },
        { value: 12, label: 'December' }
      ],
      years: Array.from({ length: 5 }, (_, i) => currentYear - i)
    };
  },

  computed: {
    pendingRequestsCount() {
      return this.shiftRequests.filter(req => req.status === 'pending').length;
    },
    firstDayOfNextMonth() {
      const today = new Date();
      const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
      return nextMonth.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    },
    daysUntilMonthEnd() {
      const today = new Date();
      const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
      return Math.ceil((lastDay - today) / (1000 * 60 * 60 * 24));
    }
  },

  watch: {
    activeTab(newTab) {
      if (newTab === 'requests' && this.shiftRequests.length === 0) {
        this.loadShiftRequests();
        this.loadAvailableShifts();
      }
    }
  },

  mounted() {
    this.loadCurrentShift();
    this.loadShiftHistory();
  },

  methods: {
    async loadCurrentShift() {
      try {
        const { data } = await axios.get('/user/profile');
        if (data.success && data.data.shift) {
          this.currentShift = data.data.shift;
        }
      } catch (error) {
        this.showToast('Failed to load shift information', 'error');
      }
    },

    async loadShiftHistory() {
      try {
        this.loading = true;
        const { data } = await axios.get('/user/my-shift/history', {
          params: {
            month: this.filters.month,
            year: this.filters.year
          }
        });

        if (data.success) {
          this.shiftHistory = data.data || [];
        }
      } catch (error) {
        this.showToast('Failed to load shift history', 'error');
      } finally {
        this.loading = false;
      }
    },

    clearFilters() {
      const now = new Date();
      this.filters.month = now.getMonth() + 1;
      this.filters.year = now.getFullYear();
      this.loadShiftHistory();
    },

    getShiftBadgeClass(shiftType) {
      const type = shiftType?.toLowerCase();
      const classes = {
        'day': 'bg-yellow-100 text-yellow-800',
        'night': 'bg-indigo-100 text-indigo-800',
        'mid': 'bg-purple-100 text-purple-800',
        'graveyard': 'bg-slate-100 text-slate-800'
      };
      return classes[type] || 'bg-gray-100 text-gray-800';
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    calculateDuration(entry) {
      if (!entry.effective_date) return 'N/A';

      const start = new Date(entry.effective_date);
      const end = entry.end_date ? new Date(entry.end_date) : new Date();

      const diffTime = Math.abs(end - start);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays < 7) return `${diffDays} day${diffDays !== 1 ? 's' : ''}`;
      if (diffDays < 30) {
        const weeks = Math.floor(diffDays / 7);
        return `${weeks} week${weeks !== 1 ? 's' : ''}`;
      }
      const months = Math.floor(diffDays / 30);
      return `${months} month${months !== 1 ? 's' : ''}`;
    },

    async loadShiftRequests() {
      try {
        this.loadingRequests = true;
        const { data } = await axios.get('/user/my-shift/requests');
        if (data.success) {
          this.shiftRequests = data.data;
        }
      } catch (error) {
        this.showToast('Failed to load shift requests', 'error');
      } finally {
        this.loadingRequests = false;
      }
    },

    async loadAvailableShifts() {
      try {
        const { data } = await axios.get('/user/my-shift/available-shifts');
        if (data.success) {
          this.availableShifts = data.data;
        }
      } catch (error) {
        this.showToast('Failed to load available shifts', 'error');
      }
    },

    async submitRequest() {
      try {
        this.submitting = true;
        const { data } = await axios.post('/user/my-shift/request-change', this.requestForm);

        if (data.success) {
          this.showToast(data.message || 'Shift change request submitted successfully!', 'success');
          this.closeRequestModal();
          this.loadShiftRequests();
        }
      } catch (error) {
        const message = error.response?.data?.message || 'Failed to submit shift change request';
        this.showToast(message, 'error');
      } finally {
        this.submitting = false;
      }
    },

    openRequestModal() {
      this.showRequestModal = true;
      this.loadAvailableShifts();
    },

    closeRequestModal() {
      this.showRequestModal = false;
      this.requestForm = {
        requested_shift_id: '',
        reason: ''
      };
    },

    showToast(message, type = 'info') {
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
  }
};
</script>

<style scoped>
.motion-safe\:animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
