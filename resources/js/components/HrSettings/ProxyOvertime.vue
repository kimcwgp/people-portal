<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Page Header -->
      <div class="mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Proxy Overtime</h1>
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
              <span v-if="selectedCutoffPeriod !== 'custom'" class="text-xs text-gray-500">(auto-filled by cut-off)</span>
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
              <span v-if="selectedCutoffPeriod !== 'custom'" class="text-xs text-gray-500">(auto-filled by cut-off)</span>
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
          <div class="relative">
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

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Project</label>
            <select 
              v-model="filters.project_id" 
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All</option>
              <option v-for="project in formData.projects" :key="project.id" :value="project.id">
                {{ project.project_name }}
              </option>
            </select>
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
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select 
              v-model="filters.status" 
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All</option>
              <option value="PENDING">Pending</option>
              <option value="APPROVED">Approved</option>
              <option value="REJECTED">Rejected</option>
            </select>
          </div>

          <div class="sm:col-span-2 lg:col-span-4 flex flex-col sm:flex-row gap-2">
            <button
              @click="applyFilters"
              class="w-full sm:flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
            >
              Filter
            </button>
            <button
              @click="clearFilters"
              class="w-full sm:flex-1 px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-yellow-600 transition-colors"
            >
              Reset
            </button>
          </div>
        </div>
      </div>

      <!-- Results Count -->
      <div v-if="!loading && overtimes.length > 0" class="mb-3 sm:mb-4">
        <p class="text-xs sm:text-sm text-gray-600">
          Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} overtime requests
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12 sm:py-16">
        <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="overtimes.length === 0" class="rounded-lg bg-white p-6 sm:p-8 shadow-sm text-center">
        <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        <h3 class="mt-3 text-base sm:text-lg font-semibold text-gray-900">No overtime requests found</h3>
        <p class="mt-1 text-sm sm:text-base text-gray-600">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'No proxy overtime requests to display' }}
        </p>
      </div>

      <!-- Desktop Table -->
      <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="hidden lg:block overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Employee</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Project</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">OT Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time In</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time Out</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">OT Hours</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Approved By</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr 
                v-for="overtime in overtimes" 
                :key="overtime.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3">
                  <div>
                    <p class="text-sm font-semibold text-gray-900">{{ overtime.user?.name || 'Unknown' }}</p>
                    <p class="text-sm text-gray-500">{{ overtime.user?.current_job_information?.position_name || 'No Position' }}</p>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm text-gray-900">{{ overtime.project?.name || '-' }}</p>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm text-gray-900">{{ formatDate(overtime.ot_date) }}</p>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm text-gray-900">{{ overtime.time_in || '-' }}</p>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm text-gray-900">{{ overtime.time_out || '-' }}</p>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm font-medium text-gray-900">{{ overtime.formatted_ot_hours || '0h' }}</p>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm text-gray-900">{{ overtime.approver?.name || '-' }}</p>
                </td>
                <td class="px-4 py-3">
                  <span :class="getStatusClass(overtime.status)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap">
                    {{ overtime.status }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      v-if="overtime.status === 'PENDING'"
                      @click="editOvertime(overtime)"
                      class="p-1.5 text-yellow-600 hover:bg-yellow-50 rounded transition-colors"
                      title="Edit"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button
                      @click="confirmDelete(overtime)"
                      class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"
                      title="Delete"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="lg:hidden divide-y divide-gray-200">
          <div 
            v-for="overtime in overtimes" 
            :key="overtime.id"
            class="p-4 hover:bg-gray-50 transition-colors"
          >
            <div class="flex justify-between items-start mb-3">
              <div class="flex-1">
                <h3 class="text-base font-semibold text-gray-900">{{ overtime.user?.name || 'Unknown' }}</h3>
                <p class="text-sm text-gray-500">{{ overtime.user?.current_job_information?.position_name || 'No Position' }}</p>
              </div>
              <span :class="getStatusClass(overtime.status)" class="ml-2 px-2.5 py-1 text-xs font-medium rounded-full whitespace-nowrap">
                {{ overtime.status }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
              <div>
                <p class="text-xs text-gray-500 mb-1">Project</p>
                <p class="text-sm font-medium text-gray-900">{{ overtime.project?.name || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">OT Date</p>
                <p class="text-sm font-medium text-gray-900">{{ formatDate(overtime.ot_date) }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Time In</p>
                <p class="text-sm font-medium text-gray-900">{{ overtime.time_in || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Time Out</p>
                <p class="text-sm font-medium text-gray-900">{{ overtime.time_out || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">OT Hours</p>
                <p class="text-sm font-medium text-gray-900">{{ overtime.formatted_ot_hours || '0h' }}</p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Approved By</p>
                <p class="text-sm font-medium text-gray-900">{{ overtime.approver?.name || '-' }}</p>
              </div>
            </div>

            <div v-if="overtime.notes" class="mb-3">
              <p class="text-xs text-gray-500 mb-1">Notes</p>
              <p class="text-sm text-gray-900 line-clamp-2">{{ overtime.notes }}</p>
            </div>

            <div class="flex gap-2 pt-3 border-t border-gray-100">
              <button
                v-if="overtime.status === 'PENDING'"
                @click="editOvertime(overtime)"
                class="flex-1 px-3 py-2 bg-yellow-50 text-yellow-700 text-sm font-medium rounded-lg hover:bg-yellow-100 transition-colors"
              >
                Edit
              </button>
              <button
                @click="confirmDelete(overtime)"
                class="flex-1 px-3 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-lg hover:bg-red-100 transition-colors"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="overtimes.length > 0" class="mt-4 sm:mt-6 flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4">
        <div class="flex items-center gap-2">
          <span class="text-xs sm:text-sm text-gray-700">Rows per page:</span>
          <select 
            v-model="perPage" 
            @change="changePerPage"
            class="border border-gray-300 rounded-lg px-2 sm:px-3 py-1.5 text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>

        <div class="flex items-center gap-1 sm:gap-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            :class="[
              'px-2 sm:px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium transition-colors',
              pagination.current_page === 1
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
            ]"
          >
            Previous
          </button>

          <template v-for="page in getPageNumbers()" :key="page">
            <button
              v-if="page === '...'"
              disabled
              class="px-2 sm:px-3 py-1.5 text-xs sm:text-sm text-gray-400 cursor-default"
            >
              ...
            </button>
            <button
              v-else
              @click="changePage(page)"
              :class="[
                'px-2 sm:px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium transition-colors',
                page === pagination.current_page
                  ? 'bg-blue-600 text-white'
                  : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
          </template>

          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            :class="[
              'px-2 sm:px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium transition-colors',
              pagination.current_page === pagination.last_page
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
            ]"
          >
            Next
          </button>
        </div>
      </div>

      <!-- Edit Modal -->
      <div 
        v-if="showModal" 
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.self="closeModal"
      >
        <div class="flex min-h-screen items-center justify-center p-4">
          <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
          
          <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
              <h2 class="text-xl font-bold text-gray-900">Edit Overtime Request</h2>
              <button 
                @click="closeModal"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <form @submit.prevent="saveOvertime" class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                  <select 
                    v-model="currentOvertime.user_id"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Select employee</option>
                    <option v-for="user in formData.users" :key="user.id" :value="user.id">
                      {{ user.name }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Project</label>
                  <select 
                    v-model="currentOvertime.project_id"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Select project</option>
                    <option v-for="project in formData.projects" :key="project.id" :value="project.id">
                      {{ project.project_name }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">OT Date</label>
                  <input 
                    v-model="currentOvertime.ot_date"
                    type="date"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Time In</label>
                  <input 
                    v-model="currentOvertime.time_in"
                    type="time"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Time Out</label>
                  <input 
                    v-model="currentOvertime.time_out"
                    type="time"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                </div>

                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                  <textarea 
                    v-model="currentOvertime.notes"
                    rows="3"
                    required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  ></textarea>
                </div>
              </div>

              <div class="mt-6 flex gap-3">
                <button
                  type="button"
                  @click="closeModal"
                  class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="saving"
                  class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div 
        v-if="showDeleteModal" 
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.self="showDeleteModal = false"
      >
        <div class="flex min-h-screen items-center justify-center p-4">
          <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
          
          <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
            </div>

            <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Overtime Request</h3>
            <p class="text-sm text-gray-600 text-center mb-6">
              Are you sure you want to delete this overtime request for 
              <span class="font-medium">{{ overtimeToDelete?.user?.name }}</span>? 
              This action cannot be undone.
            </p>

            <div class="flex gap-3">
              <button
                @click="showDeleteModal = false"
                class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                @click="deleteOvertime"
                :disabled="deleting"
                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {{ deleting ? 'Deleting...' : 'Delete' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ProxyOvertime',
  
  data() {
    return {
      overtimes: [],
      formData: {
        users: [],
        projects: []
      },
      filters: {
        from_date: '',
        to_date: '',
        associate_name: '',
        approved_by: '',
        project_id: '',
        status: ''
      },
      // CUT-OFF PERIOD DATA
      cutoffPeriods: [],
      selectedCutoffPeriod: '',
      currentCutoffInfo: null,
      // END CUT-OFF PERIOD DATA
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
      showDeleteModal: false,
      editingOvertime: null,
      overtimeToDelete: null,
      currentOvertime: {
        user_id: '',
        project_id: '',
        ot_date: '',
        time_in: '',
        time_out: '',
        notes: ''
      },
      saving: false,
      deleting: false,
      userSearchQuery: '',
      approverSearchQuery: '',
      userSuggestions: [],
      approverSuggestions: [],
      showUserDropdown: false,
      showApproverDropdown: false,
      searchTimeout: null
    }
  },

  computed: {
    hasActiveFilters() {
      return Object.values(this.filters).some(val => val !== '') || this.selectedCutoffPeriod
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
    this.fetchOvertimes()

    this.$nextTick(() => {
      this.autoSelectCurrentPeriod()
    })
  },

  methods: {
    // CUT-OFF PERIOD METHODS
    async fetchCutoffPeriods() {
      try {
        const response = await axios.get('/hr/proxy-overtime/cutoff-periods')
        this.cutoffPeriods = response.data.data
        
        this.$nextTick(() => {
          this.autoSelectCurrentPeriod()
        })
      } catch (error) {
        console.error('Error fetching cut-off periods:', error)
      }
    },

    autoSelectCurrentPeriod() {
      const currentPeriod = this.cutoffPeriods.find(p => p.is_current)
      if (currentPeriod && !this.selectedCutoffPeriod) {
        this.selectedCutoffPeriod = currentPeriod.id
        this.handleCutoffChange()
      }
    },

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
        this.fetchOvertimes(1)
      }
    },

    onManualDateChange() {
      if (this.selectedCutoffPeriod === 'custom') {
        if (this.filters.from_date && this.filters.to_date) {
          this.currentCutoffInfo = {
            label: `${this.formatDate(this.filters.from_date)} - ${this.formatDate(this.filters.to_date)}`
          }
        }
      }
    },
    // END CUT-OFF PERIOD METHODS

    async fetchFormData() {
      try {
        const response = await axios.get('/hr/proxy-overtime/form-data')
        this.formData = response.data.data
        
        // Set currentCutoffInfo from form data if not already set
        if (response.data.data.current_cutoff && !this.currentCutoffInfo) {
          this.currentCutoffInfo = response.data.data.current_cutoff
        }
      } catch (error) {
        console.error('Error fetching form data:', error)
        this.showToast('Failed to load form data', 'error')
      }
    },

    async fetchOvertimes(page = 1) {
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
        if (this.filters.project_id) params.project_id = this.filters.project_id
        if (this.filters.associate_name) params.associate_name = this.filters.associate_name
        if (this.filters.approved_by) params.approved_by = this.filters.approved_by
        if (this.filters.status) params.status = this.filters.status

        const response = await axios.get('/hr/proxy-overtime', { params })
        this.overtimes = response.data.data
        this.pagination = response.data.meta

        // Update current cutoff info from response if available
        if (response.data.meta.current_cutoff && !this.currentCutoffInfo) {
          this.currentCutoffInfo = response.data.meta.current_cutoff
        }
      } catch (error) {
        console.error('Error fetching overtimes:', error)
        this.showToast('Failed to load overtime requests', 'error')
      } finally {
        this.loading = false
      }
    },

    applyFilters() {
      this.fetchOvertimes(1)
    },

    clearFilters() {
      this.filters = {
        from_date: '',
        to_date: '',
        associate_name: '',
        approved_by: '',
        project_id: '',
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
          const response = await axios.get('/hr/proxy-overtime/form-data', {
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
          const response = await axios.get('/hr/proxy-overtime/form-data', {
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

    changePerPage() {
      this.fetchOvertimes(1)
    },

    changePage(page) {
      if (page === '...' || page < 1 || page > this.pagination.last_page) {
        return
      }
      this.fetchOvertimes(page)
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },

    getPageNumbers() {
      const current = this.pagination.current_page
      const last = this.pagination.last_page
      const pages = []
      
      if (last <= 7) {
        for (let i = 1; i <= last; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(last)
        } else if (current >= last - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = last - 4; i <= last; i++) {
            pages.push(i)
          }
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(last)
        }
      }
      
      return pages
    },

    editOvertime(overtime) {
      this.editingOvertime = overtime
      
      this.currentOvertime = {
        user_id: parseInt(overtime.user_id),
        project_id: parseInt(overtime.project_id),
        ot_date: overtime.ot_date ? overtime.ot_date.split('T')[0] : '',
        time_in: this.convertTo24Hour(overtime.time_in) || '',
        time_out: this.convertTo24Hour(overtime.time_out) || '',
        notes: overtime.notes || ''
      }
      
      this.showModal = true
    },

    async saveOvertime() {
      this.saving = true
      try {
        await axios.put(`/hr/proxy-overtime/${this.editingOvertime.id}`, this.currentOvertime)
        this.showToast('Overtime request updated successfully', 'success')
        this.closeModal()
        this.fetchOvertimes()
      } catch (error) {
        console.error('Error saving overtime:', error)
        const errorMessage = error.response?.data?.message || 'Failed to save overtime request'
        this.showToast(errorMessage, 'error')
      } finally {
        this.saving = false
      }
    },

    confirmDelete(overtime) {
      this.overtimeToDelete = overtime
      this.showDeleteModal = true
    },

    async deleteOvertime() {
      this.deleting = true
      try {
        await axios.delete(`/hr/proxy-overtime/${this.overtimeToDelete.id}`)
        this.showToast('Overtime request deleted successfully', 'success')
        this.showDeleteModal = false
        this.fetchOvertimes()
      } catch (error) {
        console.error('Error deleting overtime:', error)
        this.showToast('Failed to delete overtime request', 'error')
      } finally {
        this.deleting = false
      }
    },

    closeModal() {
      this.showModal = false
      this.editingOvertime = null
    },

    getStatusClass(status) {
      switch (status) {
        case 'PENDING': return 'bg-yellow-100 text-yellow-800'
        case 'APPROVED': return 'bg-green-100 text-green-800'
        case 'REJECTED': return 'bg-red-100 text-red-800'
        default: return 'bg-gray-100 text-gray-800'
      }
    },

    convertTo24Hour(time12h) {
      if (!time12h) return ''
      
      const [time, modifier] = time12h.split(' ')
      let [hours, minutes] = time.split(':')
      
      if (hours === '12') {
        hours = '00'
      }
      
      if (modifier === 'PM') {
        hours = parseInt(hours, 10) + 12
      }
      
      return `${hours.toString().padStart(2, '0')}:${minutes}`
    },

    formatDate(date) {
      if (!date) return ''
      return new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
      })
    },

    async exportToCSV() {
      try {
        const params = {}
        
        // Always add date filters since a period is always selected
        if (this.filters.from_date) params.from_date = this.filters.from_date
        if (this.filters.to_date) params.to_date = this.filters.to_date
        
        // Add other filters
        if (this.filters.project_id) params.project_id = this.filters.project_id
        if (this.filters.associate_name) params.associate_name = this.filters.associate_name
        if (this.filters.approved_by) params.approved_by = this.filters.approved_by
        if (this.filters.status) params.status = this.filters.status

        const response = await axios.get('/hr/proxy-overtime/export', { 
          params,
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        
        // Extract filename from response or use default
        const contentDisposition = response.headers['content-disposition']
        let filename = `proxy_overtimes_${new Date().toISOString().split('T')[0]}.csv`
        if (contentDisposition) {
          const filenameMatch = contentDisposition.match(/filename="([^"]+)"/i)
          if (filenameMatch) {
            filename = filenameMatch[1]
          }
        }
        
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        
        this.showToast('CSV exported successfully', 'success')
      } catch (error) {
        console.error('Error exporting CSV:', error)
        this.showToast('Failed to export CSV', 'error')
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