<template>
    <div class="min-h-screen bg-gray-50 p-6">
      <!-- Header Section -->
      <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <h1 class="text-3xl font-bold text-gray-900 mb-2">My Overtime</h1>
          <div class="mt-4 sm:mt-0">
            <button
              @click="showCreateModal = true"
              class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 shadow-sm"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              New Overtime
            </button>
          </div>
        </div>
      </div>

      <!-- Filters and Actions -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <!-- Single responsive row with proper spacing -->
        <div class="flex flex-wrap items-center gap-4 lg:gap-6">
          
          <!-- Status Filter Buttons - Takes more space on larger screens -->
          <div class="flex flex-wrap gap-2 min-w-0">
            <button
              v-for="status in statusFilters"
              :key="status.value"
              @click="filters.status = status.value"
              :class="[
                'px-4 py-2.5 text-sm font-medium rounded-lg transition-colors whitespace-nowrap',
                filters.status === status.value
                  ? 'bg-blue-600 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'
              ]"
            >
              {{ status.label }}
              <span v-if="status.count > 0" :class="[
                'ml-2 px-2 py-0.5 text-xs rounded-full',
                filters.status === status.value 
                  ? 'bg-blue-100 text-blue-800' 
                  : 'bg-blue-100 text-blue-800'
              ]">
                {{ status.count }}
              </span>
            </button>
          </div>

          <!-- Date Range Section -->
          <div class="flex items-center gap-3 text-sm">
            <span class="text-gray-700 font-medium whitespace-nowrap hidden sm:inline">Date:</span>
            <input
              type="date"
              v-model="filters.startDate"
              class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-40"
            >
            <span class="text-gray-400 text-sm">to</span>
            <input
              type="date"
              v-model="filters.endDate"
              class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-40"
            >
          </div>

          <!-- Project Filter -->
          <div class="flex items-center gap-3 min-w-0">
            <span class="text-gray-700 font-medium text-sm whitespace-nowrap hidden sm:inline">Project:</span>
            <select
              v-model="filters.projectId"
              class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-0 w-40 lg:w-48"
            >
              <option value="">All Projects</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.project_name }}
              </option>
            </select>
          </div>

          <!-- Action Buttons - Grouped together -->
          <div class="flex gap-3 ml-auto">
            <button
              @click="applyFilters"
              class="bg-blue-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors whitespace-nowrap"
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

      <!-- Total Approved OT Hours Summary -->
      <div v-if="!loading" class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl shadow-sm border border-green-200 p-6 mb-8">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="bg-green-600 rounded-full p-3">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600">Total Approved Overtime Hours</p>
              <p v-if="filters.startDate && filters.endDate" class="text-xs text-gray-500 mt-0.5">
                {{ formatDate(filters.startDate) }} - {{ formatDate(filters.endDate) }}
              </p>
            </div>
          </div>
          <div class="text-right">
            <p class="text-3xl font-bold text-green-700">{{ formattedTotalApprovedOtHours }}</p>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Overtime Table -->
      <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full table-auto">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Date
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Project
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Time
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Duration
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Notes
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Approver
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Project Manager
                </th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="overtime in overtimes" :key="overtime.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">
                    {{ formatDate(overtime.ot_date) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="overtime.project" class="text-sm text-gray-900">
                    {{ overtime.project.name }}
                  </div>
                  <div v-else class="text-sm text-gray-400">No project</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">
                    {{ overtime.time_in }} - {{ overtime.time_out }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">
                    {{ overtime.formatted_ot_hours }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                        :class="getStatusClass(overtime.status)">
                    {{ overtime.status }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div v-if="overtime.rejection_note || overtime.cancellation_reason" class="text-sm max-w-xs">
                    <p v-if="overtime.rejection_note" class="text-red-600 line-clamp-2">
                      <span class="font-semibold">{{ overtime.status === 'CANCELLED' ? 'Cancelled: ' : 'Rejected: ' }}</span>{{ overtime.rejection_note }}
                    </p>
                    <p v-else-if="overtime.cancellation_reason" class="text-red-600 line-clamp-2">
                      <span class="font-semibold">Cancelled: </span>{{ overtime.cancellation_reason }}
                    </p>
                  </div>
                  <div v-else-if="overtime.notes" class="text-sm text-gray-600 max-w-xs line-clamp-2">
                    {{ overtime.notes }}
                  </div>
                  <div v-else class="text-sm text-gray-400">-</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="overtime.approver" class="text-sm text-gray-900">
                    {{ overtime.approver.name }}
                  </div>
                  <div v-else class="text-sm text-gray-400">--</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="overtime.project_manager" class="text-sm text-gray-900">
                    {{ overtime.project_manager.name }}
                  </div>
                  <div v-else class="text-sm text-gray-400">--</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex space-x-2">
                    <button
                      v-if="canCancelOvertime(overtime)"
                      @click="openCancelModal(overtime)"
                      class="text-red-600 hover:text-red-700 text-xs font-medium"
                    >
                      Cancel
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="overtimes.length === 0">
                <td colspan="9" class="px-6 py-12 text-center">
                  <div class="flex flex-col items-center">
                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-500 text-lg font-medium mb-2">No overtime records found</p>
                    <p class="text-gray-400">Try adjusting your filters or create a new overtime record</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Cancel Modal -->
      <div v-if="showCancelModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full border border-gray-100">
          <div class="p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-semibold text-gray-900">Cancel Overtime Request</h2>
              <button
                @click="closeCancelModal"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <form @submit.prevent="submitCancellation" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Reason for Cancellation *
                </label>
                <textarea 
                  v-model="cancellationReason" 
                  rows="4" 
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" 
                  placeholder="Please provide a reason for cancelling this overtime request..."
                  required
                ></textarea>
              </div>

              <div class="flex items-center justify-end space-x-3 pt-4">
                <button
                  type="button"
                  @click="closeCancelModal"
                  class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
                >
                  Keep Request
                </button>
                <button
                  type="submit"
                  :disabled="cancelSubmitting"
                  class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                >
                  <svg v-if="cancelSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ cancelSubmitting ? 'Cancelling...' : 'Cancel Request' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Create Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto border border-gray-100">
          <div class="p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-semibold text-gray-900">Create New Overtime</h2>
              <button
                @click="closeModal"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Overtime Date *</label>
                  <input
                    type="date"
                    v-model="form.ot_date"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Project *</label>
                  <select
                    v-model="form.project_id"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Select Project</option>
                    <option v-for="project in projects" :key="project.id" :value="project.id">
                      {{ project.project_name }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Time In *</label>
                  <input
                    type="time"
                    v-model="form.time_in"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Time Out *</label>
                  <input
                    type="time"
                    v-model="form.time_out"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                </div>
              </div>

              <!-- Supervisor Info Display -->
              <div v-if="supervisorInfo" class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                  <span class="text-sm text-blue-800">
                    <strong>Approver:</strong> {{ supervisorInfo.name }} ({{ supervisorInfo.email }})
                  </span>
                </div>
              </div>

              <!-- Warning if no supervisor -->
              <div v-else class="bg-yellow-50 rounded-lg p-4">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                  <span class="text-sm text-yellow-800">
                    <strong>Warning:</strong> No immediate supervisor assigned. Please contact HR to set up your supervisor before submitting overtime.
                  </span>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea
                  v-model="form.notes"
                  rows="4"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                  placeholder="Additional notes about the overtime work..."
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Project Manager</label>
                <select
                  v-model="form.project_manager_id"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Select Project Manager (Optional)</option>
                  <option v-for="manager in projectManagers" :key="manager.id" :value="manager.id">
                    {{ manager.name }} ({{ manager.email }})
                  </option>
                </select>
              </div>

              <!-- Duration Preview -->
              <div v-if="form.time_in && form.time_out" class="bg-green-50 rounded-lg p-4">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span class="text-sm text-green-800">
                    <strong>Duration:</strong> {{ calculateDuration() }}
                  </span>
                </div>
              </div>

              <div class="flex items-center justify-end space-x-3 pt-4">
                <button
                  type="button"
                  @click="closeModal"
                  class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="submitting || !supervisorInfo"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                >
                  <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Create Overtime
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
</template>

<script>
import Layout from './Layout.vue';
import axios from 'axios';

export default {
  components: { Layout },
  
  data() {
    return {
      overtimes: [],
      projects: [],
      projectManagers: [],
      supervisorInfo: null,
      stats: {},
      totalApprovedOtHours: 0,
      formattedTotalApprovedOtHours: '0h',
      loading: true,
      submitting: false,
      showCreateModal: false,
      showCancelModal: false,
      cancellingOvertime: null,
      cancellationReason: '',
      cancelSubmitting: false,
      filters: {
        startDate: '',
        endDate: '',
        projectId: '',
        status: ''
      },
      statusFilters: [
        { label: 'All', value: '', count: 0 },
        { label: 'Pending', value: 'PENDING', count: 0 },
        { label: 'Approved', value: 'APPROVED', count: 0 },
        { label: 'Rejected', value: 'REJECTED', count: 0 },
        { label: 'Cancelled', value: 'CANCELLED', count: 0 }
      ],
      form: {
        ot_date: this.getTodayDate(),
        project_id: '',
        project_manager_id: '',
        time_in: '',
        time_out: '',
        notes: ''
      }
    };
  },

  watch: {
    'filters.status'() {
      this.loadOvertimes();
    }
  },

  mounted() {
    this.initializeFilters();
    this.loadOvertimes();
    this.loadProjects();
    this.loadProjectManagers();
    this.loadSupervisorInfo();
    this.loadStats();
  },  

  methods: {
    initializeFilters() {
      const now = new Date();
      const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
      const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      
      this.filters.startDate = startOfMonth.toISOString().split('T')[0];
      this.filters.endDate = endOfMonth.toISOString().split('T')[0];
    },

    async loadOvertimes() {
      this.loading = true;
      try {
        const params = {};
        if (this.filters.startDate) params.start_date = this.filters.startDate;
        if (this.filters.endDate) params.end_date = this.filters.endDate;
        if (this.filters.projectId) params.project_id = this.filters.projectId;
        if (this.filters.status) params.status = this.filters.status;

        const response = await axios.get('/user/my-overtime', { params });
        this.overtimes = response.data.data;
        
        // Get total approved OT hours from response
        this.totalApprovedOtHours = response.data.total_approved_ot_hours || 0;
        this.formattedTotalApprovedOtHours = response.data.formatted_total_approved_ot_hours || '0h';
      } catch (error) {
        this.showToast('Failed to load overtime records', 'error');
      } finally {
        this.loading = false;
      }
    },

    async loadStats() {
      try {
        const params = {};
        if (this.filters.startDate) params.start_date = this.filters.startDate;
        if (this.filters.endDate) params.end_date = this.filters.endDate;
        if (this.filters.projectId) params.project_id = this.filters.projectId;
        
        const response = await axios.get('/user/my-overtime/stats', { params });
        this.stats = response.data.data || {};
        this.updateStatusCounts();
      } catch (error) {
        // Silent fail for stats
      }
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

    canCancelOvertime(overtime) {
      return ['PENDING', 'APPROVED'].includes(overtime.status);
    },

    openCancelModal(overtime) {
      this.cancellingOvertime = overtime;
      this.cancellationReason = '';
      this.showCancelModal = true;
    },

    closeCancelModal() {
      this.showCancelModal = false;
      this.cancellingOvertime = null;
      this.cancellationReason = '';
    },

    async submitCancellation() {
      if (!this.cancellationReason.trim()) {
        this.showToast('Please provide a reason for cancellation', 'error');
        return;
      }

      this.cancelSubmitting = true;
      try {
        const response = await axios.put(`/user/my-overtime/${this.cancellingOvertime.id}/cancel`, {
          cancellation_reason: this.cancellationReason
        });

        if (response.data.success) {
          this.showToast(response.data.message, 'success');
          this.closeCancelModal();
          await this.loadOvertimes();
          await this.loadStats();
        } else {
          this.showToast(response.data.message || 'Failed to cancel overtime', 'error');
        }
      } catch (error) {
        if (error.response?.status === 422) {
          const first = Object.values(error.response.data.errors || {})[0]?.[0];
          const message = first || error.response.data.message || 'Validation failed';
          this.showToast(message, 'error');
        } else {
          this.showToast(error.response?.data?.message || 'Failed to cancel overtime', 'error');
        }
      } finally {
        this.cancelSubmitting = false;
      }
    },

    async loadProjectManagers() {
      try {
        const response = await axios.get('/user/my-overtime/project-managers');
        this.projectManagers = response.data.data || response.data;
      } catch (error) {
        this.showToast('Failed to load project managers', 'error');
      }
    },

    applyFilters() {
      if (!this.filters.startDate || !this.filters.endDate) {
        this.showToast('Please select a valid date range', 'warning');
        return;
      }
      this.loadOvertimes();
      this.loadStats();
    },

    async loadProjects() {
      try {
        const response = await axios.get('/user/my-overtime/projects');
        this.projects = response.data.data || response.data;
      } catch (error) {
        this.showToast('Failed to load projects', 'error');
      }
    },

    async loadSupervisorInfo() {
      try {
        const response = await axios.get('/user/my-overtime/supervisor-info');
        this.supervisorInfo = response.data.data;
      } catch (error) {
        // Silent fail - handled in the UI
      }
    },

    async submitForm() {
      if (!this.supervisorInfo) {
        this.showToast('Cannot submit overtime without an assigned supervisor. Please contact HR.', 'error');
        return;
      }

      this.submitting = true;
      try {
        const response = await axios.post('/user/my-overtime', this.form);
        this.showToast('Overtime record created successfully!', 'success');
        this.closeModal();
        this.loadOvertimes();
        this.loadStats();
      } catch (error) {
        if (error.response?.status === 422) {
          const errors = error.response.data.errors;
          const errorMessages = errors ? Object.values(errors).flat() : [];
          const message = errorMessages[0] || error.response.data.message || 'Validation failed';
          this.showToast(message, 'error');
        } else {
          this.showToast(error.response?.data?.message || 'Failed to save overtime record', 'error');
        }
      } finally {
        this.submitting = false;
      }
    },

    clearFilters() {
      this.filters = {
        startDate: '',
        endDate: '',
        projectId: '',
        status: ''
      };
      this.loadOvertimes();
    },

    closeModal() {
      this.showCreateModal = false;
      this.form = {
        ot_date: this.getTodayDate(),
        project_id: '',
        project_manager_id: '',
        time_in: '',
        time_out: '',
        notes: ''
      };
    },

    getTodayDate() {
      return new Date().toISOString().split('T')[0];
    },

    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    truncateText(text, length = 100) {
      if (!text) return '';
      return text.length > length ? text.slice(0, length) + '...' : text;
    },

    getStatusClass(status) {
      const classes = {
        'PENDING': 'bg-yellow-100 text-yellow-800',
        'APPROVED': 'bg-green-100 text-green-800',
        'REJECTED': 'bg-red-100 text-red-800',
        'CANCELLED': 'bg-gray-100 text-gray-800'
      };
      return classes[status] || 'bg-gray-100 text-gray-800';
    },

    calculateDuration() {
      if (!this.form.time_in || !this.form.time_out) return '';
      
      const [inHours, inMinutes] = this.form.time_in.split(':').map(Number);
      const [outHours, outMinutes] = this.form.time_out.split(':').map(Number);
      
      const inTotalMinutes = inHours * 60 + inMinutes;
      const outTotalMinutes = outHours * 60 + outMinutes;
      
      let diffMinutes = outTotalMinutes - inTotalMinutes;
      
      // Handle case where time_out is next day
      if (diffMinutes < 0) {
        diffMinutes += 24 * 60;
      }
      
      const hours = Math.floor(diffMinutes / 60);
      const minutes = diffMinutes % 60;
      
      if (minutes > 0) {
        return `${hours}h ${minutes}m`;
      }
      return `${hours}h`;
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
    }
  }
};
</script>