<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Page Header -->
      <div class="mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Proxy Leave</h1>
            <!-- Cut-off Period Info Badge -->
            <div v-if="currentCutoffInfo" class="mt-2 inline-flex items-center px-3 py-1 bg-blue-50 border border-blue-200 rounded-lg">
              <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span class="text-sm font-medium text-blue-900">{{ currentCutoffInfo.label }}</span>
            </div>
          </div>
          <div>
            <button
              @click="exportToCSV"
              class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200 shadow-sm"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Export CSV
            </button>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <!-- Cut-off Period Selector -->
        <div class="mb-4 pb-4 border-b border-gray-200">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Pay Period / Cut-off
          </label>
          <select 
            v-model="selectedCutoffPeriod" 
            @change="handleCutoffChange"
            class="w-full sm:w-auto min-w-[300px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <!-- First Cut-off (10th-24th) -->
            <optgroup v-if="firstCutoffPeriods.length > 0" label="First Cut-off (10th - 24th)">
              <option 
                v-for="period in firstCutoffPeriods" 
                :key="period.id" 
                :value="period.id"
              >
                {{ period.label }} {{ period.is_current ? '(Current)' : '' }}
              </option>
            </optgroup>
            
            <!-- Second Cut-off (25th-9th) -->
            <optgroup v-if="secondCutoffPeriods.length > 0" label="Second Cut-off (25th - 9th)">
              <option 
                v-for="period in secondCutoffPeriods" 
                :key="period.id" 
                :value="period.id"
              >
                {{ period.label }} {{ period.is_current ? '(Current)' : '' }}
              </option>
            </optgroup>
            
            <!-- Custom Option -->
            <option value="custom">Custom Date Range</option>
          </select>
        </div>

        <!-- Date Range Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              From
              <span v-if="selectedCutoffPeriod !== 'custom'" class="text-xs text-gray-500">(auto-filled)</span>
            </label>
            <div class="relative">
              <input
                v-model="displayFromDate"
                type="date"
                :disabled="selectedCutoffPeriod !== 'custom'"
                @change="onManualDateChange"
                :class="[
                  'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
                  selectedCutoffPeriod !== 'custom' ? 'bg-gray-50 cursor-not-allowed text-gray-600 pr-8' : ''
                ]"
              >
              <div v-if="selectedCutoffPeriod !== 'custom'" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              To
              <span v-if="selectedCutoffPeriod !== 'custom'" class="text-xs text-gray-500">(auto-filled)</span>
            </label>
            <div class="relative">
              <input
                v-model="displayToDate"
                type="date"
                :disabled="selectedCutoffPeriod !== 'custom'"
                @change="onManualDateChange"
                :class="[
                  'w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
                  selectedCutoffPeriod !== 'custom' ? 'bg-gray-50 cursor-not-allowed text-gray-600 pr-8' : ''
                ]"
              >
              <div v-if="selectedCutoffPeriod !== 'custom'" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
            </div>
          </div>

          <!-- Associate Name with Autocomplete -->
          <div class="sm:col-span-2 relative">
            <label class="block text-sm font-medium text-gray-700 mb-1">Associate Name</label>
            <input
              v-model="userSearchQuery"
              @input="searchUsers(userSearchQuery)"
              @focus="showUserDropdown = userSuggestions.length > 0"
              @blur="hideUserDropdown"
              type="text"
              placeholder="Type to search..."
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            
            <div 
              v-if="showUserDropdown && userSuggestions.length > 0"
              class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
            >
              <div
                v-for="user in userSuggestions"
                :key="user.id"
                @mousedown="selectUser(user)"
                class="px-4 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
              >
                <div class="font-medium text-sm text-gray-900">{{ user.name }}</div>
                <div class="text-xs text-gray-500">{{ user.email }}</div>
                <div v-if="user.current_job_information" class="text-xs text-gray-400">
                  {{ user.current_job_information.position_name }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
          <!-- Approved By with Autocomplete -->
          <div class="relative">
            <label class="block text-sm font-medium text-gray-700 mb-1">Approved By</label>
            <input
              v-model="approverSearchQuery"
              @input="searchApprovers(approverSearchQuery)"
              @focus="showApproverDropdown = approverSuggestions.length > 0"
              @blur="hideApproverDropdown"
              type="text"
              placeholder="Type to search..."
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            
            <div 
              v-if="showApproverDropdown && approverSuggestions.length > 0"
              class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
            >
              <div
                v-for="approver in approverSuggestions"
                :key="approver.id"
                @mousedown="selectApprover(approver)"
                class="px-4 py-2 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
              >
                <div class="font-medium text-sm text-gray-900">{{ approver.name }}</div>
                <div class="text-xs text-gray-500">{{ approver.email }}</div>
                <div v-if="approver.current_job_information" class="text-xs text-gray-400">
                  {{ approver.current_job_information.position_name }}
                </div>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
            <select
              v-model="filters.leave_type_id"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All</option>
              <option v-for="type in leaveTypes" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              v-model="filters.status"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>

          <div class="lg:col-span-3 flex gap-2">
            <button
              @click="applyFilters"
              class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200"
            >
              Filter
            </button>
            <button
              @click="clearFilters"
              class="flex-1 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition-colors duration-200"
            >
              Reset
            </button>
          </div>
        </div>
      </div>

      <!-- Results Count -->
      <div class="mb-4 text-sm text-gray-600">
        {{ pagination.total ? `Showing ${pagination.from || 0} to ${pagination.to || 0} of ${pagination.total} leave requests` : 'No leave requests found' }}
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approved By</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="loading">
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                  <div class="flex justify-center items-center">
                    <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </div>
                </td>
              </tr>
              <tr v-else-if="leaves.length === 0">
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                  No leave requests found
                </td>
              </tr>
              <tr v-else v-for="leave in leaves" :key="leave.id" class="hover:bg-gray-50">
                <td class="px-4 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ leave.user?.name || 'N/A' }}</div>
                  <div class="text-xs text-gray-500">{{ leave.user?.current_job_information?.position_name || 'N/A' }}</div>
                </td>
                <td class="px-4 py-4 text-sm text-gray-900">{{ leave.leave_type?.name || 'N/A' }}</td>
                <td class="px-4 py-4 text-sm text-gray-900">{{ formatDate(leave.start_date) }}</td>
                <td class="px-4 py-4 text-sm text-gray-900">{{ formatDate(leave.end_date) }}</td>
                <td class="px-4 py-4 text-sm text-gray-900">{{ leave.duration_text || leave.duration }}</td>
                <td class="px-4 py-4 text-sm text-gray-900">{{ leave.approver?.name || 'N/A' }}</td>
                <td class="px-4 py-4">
                  <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getStatusClass(leave.status)}`">
                    {{ leave.status }}
                  </span>
                </td>
                <td class="px-4 py-4">
                  <div class="flex gap-2">
                    <button
                      @click="editLeave(leave)"
                      class="text-blue-600 hover:text-blue-800"
                      title="Edit"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button
                      @click="deleteLeave(leave)"
                      class="text-red-600 hover:text-red-800"
                      title="Delete"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-700">Show:</span>
          <select
            v-model="perPage"
            @change="changePerPage"
            class="border border-gray-200 rounded-lg px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>

        <div class="flex gap-2">
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            :class="['px-4 py-2 border rounded-lg text-sm font-medium transition-colors', 
                     pagination.current_page <= 1 ? 'border-gray-200 text-gray-400 cursor-not-allowed' : 'border-gray-300 text-gray-700 hover:bg-gray-50']"
          >
            Previous
          </button>

          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="['px-4 py-2 border rounded-lg text-sm font-medium transition-colors',
                     page === pagination.current_page ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50']"
          >
            {{ page }}
          </button>

          <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            :class="['px-4 py-2 border rounded-lg text-sm font-medium transition-colors',
                     pagination.current_page >= pagination.last_page ? 'border-gray-200 text-gray-400 cursor-not-allowed' : 'border-gray-300 text-gray-700 hover:bg-gray-50']"
          >
            Next
          </button>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-2xl font-bold text-gray-900">Edit Leave Request</h2>
              <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <form @submit.prevent="saveLeave" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                  <select v-model="currentLeave.user_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select employee</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type</label>
                  <select v-model="currentLeave.leaves_type_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select leave type</option>
                    <option v-for="type in leaveTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                  <input v-model="currentLeave.start_date" type="date" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                  <input v-model="currentLeave.end_date" type="date" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                  <select v-model="currentLeave.duration" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="All Day">All Day</option>
                    <option value="Half Day (8am to 12nn)">Half Day (8am to 12nn)</option>
                    <option value="Half Day (1pm to 5pm)">Half Day (1pm to 5pm)</option>
                    <option value="Custom">Custom</option>
                  </select>
                </div>

                <div v-if="currentLeave.duration === 'Custom'">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Time In</label>
                  <input v-model="currentLeave.time_in" type="time" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div v-if="currentLeave.duration === 'Custom'">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Time Out</label>
                  <input v-model="currentLeave.time_out" type="time" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <textarea v-model="currentLeave.reason" rows="3" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
              </div>

              <div class="flex gap-2 justify-end">
                <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                  Cancel
                </button>
                <button type="submit" :disabled="saving" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                  {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Toast Notification -->
      <div v-if="toast.show" :class="`fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg ${toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'} text-white`">
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ProxyLeaves',
  
  data() {
    return {
      leaves: [],
      users: [],
      leaveTypes: [],
      filters: {
        from_date: '',
        to_date: '',
        associate_name: '',
        approved_by: '',
        leave_type_id: '',
        status: ''
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0
      },
      perPage: 10,
      loading: false,
      showModal: false,
      editingLeave: null,
      currentLeave: {
        user_id: '',
        leaves_type_id: '',
        start_date: '',
        end_date: '',
        duration: 'All Day',
        time_in: '',
        time_out: '',
        reason: ''
      },
      saving: false,
      toast: {
        show: false,
        message: '',
        type: 'success'
      },
      userSearchQuery: '',
      approverSearchQuery: '',
      userSuggestions: [],
      approverSuggestions: [],
      showUserDropdown: false,
      showApproverDropdown: false,
      searchTimeout: null,
      // Cut-off period properties
      cutoffPeriods: [],
      selectedCutoffPeriod: '',
      currentCutoffInfo: null
    }
  },

  computed: {
    visiblePages() {
      const pages = []
      const current = this.pagination.current_page
      const last = this.pagination.last_page
      
      if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i)
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) pages.push(i)
          pages.push('...')
          pages.push(last)
        } else if (current >= last - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = last - 4; i <= last; i++) pages.push(i)
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) pages.push(i)
          pages.push('...')
          pages.push(last)
        }
      }
      
      return pages
    },

    displayFromDate: {
      get() {
        return this.filters.from_date || ''
      },
      set(value) {
        this.filters.from_date = value
      }
    },

    displayToDate: {
      get() {
        return this.filters.to_date || ''
      },
      set(value) {
        this.filters.to_date = value
      }
    },

    firstCutoffPeriods() {
      return this.cutoffPeriods.filter(period => {
        const startDate = new Date(period.start_date)
        return startDate.getDate() === 10
      })
    },

    secondCutoffPeriods() {
      return this.cutoffPeriods.filter(period => {
        const startDate = new Date(period.start_date)
        return startDate.getDate() === 25
      })
    }
  },

  mounted() {
    this.fetchCutoffPeriods()
    this.fetchFormData()
    this.fetchLeaves()
    
    this.$nextTick(() => {
      this.autoSelectCurrentPeriod()
    })
  },

  methods: {
    /**
     * Fetch available cut-off periods
     */
    async fetchCutoffPeriods() {
      try {
        const response = await axios.get('/hr/proxy-leaves/cutoff-periods')
        this.cutoffPeriods = response.data.data
        
        this.$nextTick(() => {
          this.autoSelectCurrentPeriod()
        })
      } catch (error) {
        console.error('Error fetching cut-off periods:', error)
      }
    },

    /**
     * Auto-select the current cut-off period
     */
    autoSelectCurrentPeriod() {
      const currentPeriod = this.cutoffPeriods.find(p => p.is_current)
      if (currentPeriod && !this.selectedCutoffPeriod) {
        this.selectedCutoffPeriod = currentPeriod.id
        this.handleCutoffChange()
      }
    },

    /**
     * Handle cut-off period selection change
     */
    handleCutoffChange() {
      if (this.selectedCutoffPeriod === 'custom') {
        this.currentCutoffInfo = null
        return
      }

      const period = this.cutoffPeriods.find(p => p.id === this.selectedCutoffPeriod)
      if (period) {
        this.filters.from_date = period.start_date
        this.filters.to_date = period.end_date
        this.currentCutoffInfo = {
          label: period.label
        }
        this.fetchLeaves(1)
      }
    },

    /**
     * Handle manual date changes when in custom mode
     */
    onManualDateChange() {
      if (this.selectedCutoffPeriod === 'custom') {
        if (this.filters.from_date && this.filters.to_date) {
          this.currentCutoffInfo = {
            label: `${this.formatDate(this.filters.from_date)} - ${this.formatDate(this.filters.to_date)}`
          }
        }
      }
    },

    async fetchFormData() {
      try {
        const response = await axios.get('/hr/proxy-leaves/form-data')
        this.users = response.data.data.users
        this.leaveTypes = response.data.data.leave_types
        
        // Update current cutoff info if available
        if (response.data.data.current_cutoff && !this.currentCutoffInfo) {
          this.currentCutoffInfo = response.data.data.current_cutoff
        }
      } catch (error) {
        console.error('Error fetching form data:', error)
        this.showToast('Failed to load form data', 'error')
      }
    },

    async fetchLeaves(page = 1) {
      this.loading = true
      try {
        const params = {
          page,
          per_page: this.perPage
        }

        // Always add date filters since a period is always selected
        if (this.filters.from_date) params.from_date = this.filters.from_date
        if (this.filters.to_date) params.to_date = this.filters.to_date

        // Add other filters
        if (this.filters.associate_name) params.associate_name = this.filters.associate_name
        if (this.filters.approved_by) params.approved_by = this.filters.approved_by
        if (this.filters.leave_type_id) params.leave_type_id = this.filters.leave_type_id
        if (this.filters.status) params.status = this.filters.status

        const response = await axios.get('/hr/proxy-leaves', { params })
        this.leaves = response.data.data
        this.pagination = response.data.meta

        // Update current cutoff info from response if available
        if (response.data.meta.current_cutoff && !this.currentCutoffInfo) {
          this.currentCutoffInfo = response.data.meta.current_cutoff
        }
      } catch (error) {
        console.error('Error fetching leaves:', error)
        this.showToast('Failed to load leave requests', 'error')
      } finally {
        this.loading = false
      }
    },

    async exportToCSV() {
      try {
        const params = {}
        
        // Always add date filters since a period is always selected
        if (this.filters.from_date) params.from_date = this.filters.from_date
        if (this.filters.to_date) params.to_date = this.filters.to_date
        
        // Add other filters
        if (this.filters.associate_name) params.associate_name = this.filters.associate_name
        if (this.filters.approved_by) params.approved_by = this.filters.approved_by
        if (this.filters.leave_type_id) params.leave_type_id = this.filters.leave_type_id
        if (this.filters.status) params.status = this.filters.status

        const response = await axios.get('/hr/proxy-leaves/export', {
          params,
          responseType: 'blob'
        })

        const blob = new Blob([response.data], { type: 'text/csv' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        
        // Extract filename from response headers or use default
        const contentDisposition = response.headers['content-disposition']
        let filename = `proxy_leaves_${new Date().toISOString().split('T')[0]}.csv`
        if (contentDisposition) {
          const filenameMatch = contentDisposition.match(/filename="([^"]+)"/i)
          if (filenameMatch) {
            filename = filenameMatch[1]
          }
        }
        
        link.download = filename
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)

        this.showToast('Export successful', 'success')
      } catch (error) {
        console.error('Error exporting:', error)
        this.showToast('Export failed', 'error')
      }
    },

    applyFilters() {
      this.fetchLeaves(1)
    },

    clearFilters() {
      this.filters = {
        from_date: '',
        to_date: '',
        associate_name: '',
        approved_by: '',
        leave_type_id: '',
        status: ''
      }
      this.userSearchQuery = ''
      this.approverSearchQuery = ''
      this.userSuggestions = []
      this.approverSuggestions = []
      this.showUserDropdown = false
      this.showApproverDropdown = false
      
      // Reset to current period
      this.selectedCutoffPeriod = ''
      this.currentCutoffInfo = null
      this.autoSelectCurrentPeriod()
    },

    goToPage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.fetchLeaves(page)
      }
    },

    changePerPage() {
      this.fetchLeaves(1)
    },

    /**
     * Search users with debounce for autocomplete
     */
    searchUsers(query) {
      clearTimeout(this.searchTimeout)
      
      if (query.length < 2) {
        this.userSuggestions = []
        this.showUserDropdown = false
        return
      }
      
      this.searchTimeout = setTimeout(async () => {
        try {
          const response = await axios.get('/hr/proxy-leaves/form-data', {
            params: { q: query, limit: 10 }
          })
          this.userSuggestions = response.data.data.users
          this.showUserDropdown = true
        } catch (error) {
          console.error('Error searching users:', error)
        }
      }, 300)
    },
    
    /**
     * Search approvers with debounce for autocomplete
     */
    searchApprovers(query) {
      clearTimeout(this.searchTimeout)
      
      if (query.length < 2) {
        this.approverSuggestions = []
        this.showApproverDropdown = false
        return
      }
      
      this.searchTimeout = setTimeout(async () => {
        try {
          const response = await axios.get('/hr/proxy-leaves/form-data', {
            params: { q: query, limit: 10 }
          })
          this.approverSuggestions = response.data.data.users
          this.showApproverDropdown = true
        } catch (error) {
          console.error('Error searching approvers:', error)
        }
      }, 300)
    },
    
    selectUser(user) {
      this.userSearchQuery = user.name
      this.filters.associate_name = user.name
      this.showUserDropdown = false
    },
    
    selectApprover(approver) {
      this.approverSearchQuery = approver.name
      this.filters.approved_by = approver.name
      this.showApproverDropdown = false
    },
    
    hideUserDropdown() {
      setTimeout(() => {
        this.showUserDropdown = false
      }, 200)
    },
    
    hideApproverDropdown() {
      setTimeout(() => {
        this.showApproverDropdown = false
      }, 200)
    },

    editLeave(leave) {
      this.editingLeave = leave
      this.currentLeave = {
        user_id: leave.user_id || '',
        leaves_type_id: leave.leaves_type_id || '',
        start_date: leave.start_date || '',
        end_date: leave.end_date || '',
        duration: leave.duration || 'All Day',
        time_in: leave.time_in || '',
        time_out: leave.time_out || '',
        reason: leave.reason || ''
      }
      this.showModal = true
    },

    async saveLeave() {
      this.saving = true
      try {
        await axios.put(`/hr/proxy-leaves/${this.editingLeave.id}`, this.currentLeave)
        this.showToast('Leave request updated successfully', 'success')
        this.closeModal()
        this.fetchLeaves()
      } catch (error) {
        console.error('Error updating leave:', error)
        this.showToast(error.response?.data?.message || 'Failed to update leave request', 'error')
      } finally {
        this.saving = false
      }
    },

    async deleteLeave(leave) {
      if (!confirm(`Are you sure you want to delete this leave request for ${leave.user?.name}?`)) return

      try {
        await axios.delete(`/hr/proxy-leaves/${leave.id}`)
        this.showToast('Leave request deleted successfully', 'success')
        this.fetchLeaves()
      } catch (error) {
        console.error('Error deleting leave:', error)
        this.showToast('Failed to delete leave request', 'error')
      }
    },

    closeModal() {
      this.showModal = false
      this.editingLeave = null
      this.currentLeave = {
        user_id: '',
        leaves_type_id: '',
        start_date: '',
        end_date: '',
        duration: 'All Day',
        time_in: '',
        time_out: '',
        reason: ''
      }
    },

    showToast(message, type = 'success') {
      this.toast = { show: true, message, type }
      setTimeout(() => {
        this.toast.show = false
      }, 3000)
    },

    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
      })
    },

    getStatusClass(status) {
      const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }
  }
}
</script>