<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto px-2">
      <!-- Header with Export/Import Buttons -->
      <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900">Leave Credits Management</h1>
        
        <div class="flex gap-2">
          <button
            @click="exportCredits"
            :disabled="loading"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 text-sm font-medium flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export CSV
          </button>
          <button
            @click="showImportModal = true"
            :disabled="loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 text-sm font-medium flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            Import CSV
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search Employee</label>
            <input
              v-model="search"
              type="text"
              placeholder="Search by name..."
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              @input="loadCredits"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Filter</label>
            <select
              v-model="filter"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              @change="loadCredits"
            >
              <option value="">All Employees</option>
              <option value="regular">Regular Only</option>
              <option value="probationary">Probationary Only</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
            <select
              v-model="year"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              @change="loadCredits"
            >
              <option :value="currentYear - 1">{{ currentYear - 1 }}</option>
              <option :value="currentYear">{{ currentYear }}</option>
              <option :value="currentYear + 1">{{ currentYear + 1 }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Per Page</label>
            <select
              v-model="perPage"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              @change="loadCredits"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-lg shadow-sm p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="text-gray-600 mt-2">Loading...</p>
      </div>

      <!-- Credits Table - Desktop View -->
      <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
        <!-- Desktop Table -->
        <div class="hidden lg:block overflow-x-auto">
          <table class="w-full">
            <thead>
              <!-- Section Headers Row -->
              <tr class="bg-gray-100 border-b-2 border-gray-300">
                <th rowspan="2" class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-300">Employee</th>
                <th rowspan="2" class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-300">Status</th>
                <th colspan="3" class="px-4 py-2 text-center text-xs font-bold text-blue-700 uppercase tracking-wider border-r border-gray-300 bg-blue-50">
                  Carryover from {{ year - 1 }}
                </th>
                <th colspan="4" class="px-4 py-2 text-center text-xs font-bold text-indigo-700 uppercase tracking-wider border-r border-gray-300 bg-indigo-50">
                  Vacation Leave {{ year }}
                </th>
                <th colspan="3" class="px-4 py-2 text-center text-xs font-bold text-purple-700 uppercase tracking-wider border-r border-gray-300 bg-purple-50">
                  Sick Leave {{ year }}
                </th>
                <th rowspan="2" class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
              </tr>
              <!-- Column Headers Row -->
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200 bg-blue-25">Total</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200 bg-blue-25">Used</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-300 bg-blue-25">Remaining</th>
                
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200 bg-indigo-25">Credits</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200 bg-indigo-25">Used</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200 bg-indigo-25">Remaining</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-300 bg-indigo-25">To {{ year + 1 }}</th>
                
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200 bg-purple-25">Credits</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200 bg-purple-25">Used</th>
                <th class="px-3 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-300 bg-purple-25">Remaining</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="employee in employees" :key="employee.id" class="hover:bg-gray-50">
                <td class="px-4 py-4 border-r border-gray-200">
                  <div>
                    <div class="font-medium text-gray-900">{{ employee.name }}</div>
                    <div class="text-sm text-gray-500">{{ employee.employee_id || 'N/A' }}</div>
                  </div>
                </td>
                <td class="px-4 py-4 border-r border-gray-300">
                  <span v-if="employee.is_regular" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                    Regular
                  </span>
                  <span v-else class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                    Probationary
                  </span>
                </td>
                <!-- Carryover Section -->
                <td class="px-4 py-4 text-center bg-blue-50/30 border-r border-gray-200">
                  <span class="text-sm text-blue-600 font-medium">{{ employee.credits.vl_carried_over || 0 }}</span>
                </td>
                <td class="px-4 py-4 text-center bg-blue-50/30 border-r border-gray-200">
                  <span class="text-sm text-orange-600 font-medium">{{ employee.credits.vl_carried_over_used || 0 }}</span>
                </td>
                <td class="px-4 py-4 text-center bg-blue-50/30 border-r border-gray-300">
                  <span class="text-sm text-green-600 font-medium">{{ employee.credits.vl_carried_over_remaining || 0 }}</span>
                </td>
                <!-- VL Section -->
                <td class="px-4 py-4 text-center bg-indigo-50/20 border-r border-gray-200">
                  <span class="text-sm font-medium text-gray-900">{{ employee.credits.vl_credits }}</span>
                </td>
                <td class="px-4 py-4 text-center bg-indigo-50/20 border-r border-gray-200">
                  <span class="text-sm text-gray-600">{{ employee.credits.vl_used }}</span>
                </td>
                <td class="px-4 py-4 text-center bg-indigo-50/20 border-r border-gray-200">
                  <span class="text-sm font-semibold" :class="employee.credits.vl_remaining < 2 ? 'text-red-600' : 'text-green-600'">
                    {{ employee.credits.vl_remaining }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center bg-indigo-50/20 border-r border-gray-300">
                  <span class="text-sm text-purple-600 font-medium">{{ calculateCarryOver(employee.credits.vl_remaining, 5) }}</span>
                </td>
                <!-- SL Section -->
                <td class="px-4 py-4 text-center bg-purple-50/20 border-r border-gray-200">
                  <span class="text-sm font-medium text-gray-900">{{ employee.credits.sl_credits }}</span>
                </td>
                <td class="px-4 py-4 text-center bg-purple-50/20 border-r border-gray-200">
                  <span class="text-sm text-gray-600">{{ employee.credits.sl_used }}</span>
                </td>
                <td class="px-4 py-4 text-center bg-purple-50/20 border-r border-gray-300">
                  <span class="text-sm font-semibold" :class="employee.credits.sl_remaining < 2 ? 'text-red-600' : 'text-green-600'">
                    {{ employee.credits.sl_remaining }}
                  </span>
                </td>
                <td class="px-4 py-4 text-center">
                  <button
                    @click="editCredits(employee)"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                  >
                    Edit
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile/Tablet Card View -->
        <div class="lg:hidden divide-y divide-gray-200">
          <div v-for="employee in employees" :key="employee.id" class="p-4 hover:bg-gray-50">
            <!-- Employee Header -->
            <div class="flex justify-between items-start mb-3">
              <div>
                <div class="font-medium text-gray-900">{{ employee.name }}</div>
                <div class="text-sm text-gray-500">{{ employee.employee_id || 'N/A' }}</div>
              </div>
              <div>
                <span v-if="employee.is_regular" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                  Regular
                </span>
                <span v-else class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                  Probationary
                </span>
              </div>
            </div>

            <!-- VL Section -->
            <div class="mb-3 pb-3 border-b border-gray-100">
              <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Vacation Leave (VL)</div>
              <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <span class="text-gray-600">Carried Over:</span>
                  <span class="font-medium text-blue-600 ml-1">{{ employee.credits.vl_carried_over || 0 }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Carried Used:</span>
                  <span class="font-medium text-orange-600 ml-1">{{ employee.credits.vl_carried_over_used || 0 }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Carried Remaining:</span>
                  <span class="font-medium text-green-600 ml-1">{{ employee.credits.vl_carried_over_remaining || 0 }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Credits {{ year }}:</span>
                  <span class="font-medium ml-1">{{ employee.credits.vl_credits }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Used {{ year }}:</span>
                  <span class="font-medium ml-1">{{ employee.credits.vl_used }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Remaining {{ year }}:</span>
                  <span class="font-semibold ml-1" :class="employee.credits.vl_remaining < 2 ? 'text-red-600' : 'text-green-600'">
                    {{ employee.credits.vl_remaining }}
                  </span>
                </div>
                <div class="col-span-2">
                  <span class="text-gray-600">To Carry Over ({{ year + 1 }}):</span>
                  <span class="font-medium text-purple-600 ml-1">{{ calculateCarryOver(employee.credits.vl_remaining, 5) }}</span>
                </div>
              </div>
            </div>

            <!-- SL Section -->
            <div class="mb-3">
              <div class="text-xs font-semibold text-gray-500 uppercase mb-2">Sick Leave (SL)</div>
              <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <span class="text-gray-600">Credits:</span>
                  <span class="font-medium ml-1">{{ employee.credits.sl_credits }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Used:</span>
                  <span class="font-medium ml-1">{{ employee.credits.sl_used }}</span>
                </div>
                <div>
                  <span class="text-gray-600">Remaining:</span>
                  <span class="font-semibold ml-1" :class="employee.credits.sl_remaining < 2 ? 'text-red-600' : 'text-green-600'">
                    {{ employee.credits.sl_remaining }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Action Button -->
            <div class="mt-3">
              <button
                @click="editCredits(employee)"
                class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
              >
                Edit Credits
              </button>
            </div>
          </div>
        </div>

        <!-- No data -->
        <div v-if="!loading && employees.length === 0" class="p-8 text-center text-gray-500">
          No employees found
        </div>
      </div>

      <!-- Pagination Controls -->
      <div v-if="pagination.total > 0" class="bg-white rounded-lg shadow-sm p-4 mt-4">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
          <div class="text-sm text-gray-600">
            Showing <span class="font-medium">{{ pagination.from }}</span> to 
            <span class="font-medium">{{ pagination.to }}</span> of 
            <span class="font-medium">{{ pagination.total }}</span> results
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="goToPage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>

            <div class="hidden sm:flex gap-1">
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="page !== '...' && goToPage(page)"
                :disabled="page === '...'"
                class="min-w-[40px] px-3 py-2 border rounded-lg transition-colors"
                :class="[
                  page === pagination.current_page
                    ? 'bg-blue-600 text-white border-blue-600 font-medium'
                    : page === '...'
                    ? 'border-gray-300 cursor-default'
                    : 'border-gray-300 hover:bg-gray-50'
                ]"
              >
                {{ page }}
              </button>
            </div>

            <div class="sm:hidden px-4 py-2 border border-gray-300 rounded-lg bg-white">
              <span class="font-medium">{{ pagination.current_page }}</span>
              <span class="text-gray-500"> / {{ pagination.last_page }}</span>
            </div>

            <button
              @click="goToPage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Edit Leave Credits</h3>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
              <p class="text-gray-900">{{ editingEmployee.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">VL Carried Over (from {{ year - 1 }})</label>
              <input
                v-model.number="editForm.vl_carried_over"
                type="number"
                step="0.25"
                min="0"
                max="5"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">VL Carried Over Used</label>
              <input
                v-model.number="editForm.vl_carried_over_used"
                type="number"
                step="0.25"
                min="0"
                :max="editForm.vl_carried_over"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
              <p class="text-xs text-gray-500 mt-1">How many carried over leaves have been used</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">VL Credits</label>
                <input
                  v-model.number="editForm.vl_credits"
                  type="number"
                  step="0.25"
                  min="0"
                  max="15"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SL Credits</label>
                <input
                  v-model.number="editForm.sl_credits"
                  type="number"
                  step="0.25"
                  min="0"
                  max="15"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">VL Used</label>
                <input
                  v-model.number="editForm.vl_used"
                  type="number"
                  step="0.25"
                  min="0"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SL Used</label>
                <input
                  v-model.number="editForm.sl_used"
                  type="number"
                  step="0.25"
                  min="0"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <button
              @click="closeEditModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              @click="saveCredits"
              :disabled="saving"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div v-if="toast.show" class="fixed bottom-4 right-4 bg-white rounded-lg shadow-lg p-4 max-w-sm z-50">
      <div class="flex items-center">
        <div :class="toast.type === 'success' ? 'text-green-600' : 'text-red-600'" class="mr-3">
          <svg v-if="toast.type === 'success'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </div>
        <p class="text-gray-900">{{ toast.message }}</p>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Import Leave Credits</h2>
          
          <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-800 mb-2"><strong>CSV Format:</strong></p>
            <p class="text-xs text-blue-700 font-mono mb-2">Name, Email, Employee ID, Hire Date, VL Credits, VL Used, VL Carried Over, VL Carried Over Used, SL Credits, SL Used, Birthday Leave</p>
            <p class="text-xs text-blue-600 mt-2">Note: Status column is excluded from import. Only credit values will be updated.</p>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Year</label>
            <select
              v-model="importYear"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option :value="currentYear - 1">{{ currentYear - 1 }}</option>
              <option :value="currentYear">{{ currentYear }}</option>
              <option :value="currentYear + 1">{{ currentYear + 1 }}</option>
            </select>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Upload CSV File</label>
            <input
              type="file"
              accept=".csv"
              @change="handleFileUpload"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>

          <div v-if="importResult" class="mb-4 p-4 rounded-lg" :class="importResult.errors.length > 0 ? 'bg-yellow-50 border border-yellow-200' : 'bg-green-50 border border-green-200'">
            <p class="text-sm font-medium mb-2" :class="importResult.errors.length > 0 ? 'text-yellow-800' : 'text-green-800'">
              {{ importResult.message }}
            </p>
            <div v-if="importResult.errors.length > 0" class="mt-2">
              <p class="text-xs font-medium text-yellow-700 mb-1">Errors:</p>
              <ul class="text-xs text-yellow-600 list-disc list-inside max-h-40 overflow-y-auto">
                <li v-for="(error, index) in importResult.errors" :key="index">{{ error }}</li>
              </ul>
            </div>
          </div>

          <div class="flex justify-end space-x-3">
            <button
              @click="closeImportModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Close
            </button>
            <button
              @click="importCredits"
              :disabled="!importFile || importing"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ importing ? 'Importing...' : 'Import' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'LeaveCredits',
  
  data() {
    return {
      loading: false,
      saving: false,
      employees: [],
      search: '',
      filter: '',
      year: new Date().getFullYear(),
      currentYear: new Date().getFullYear(),
      perPage: 10,
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0
      },
      showEditModal: false,
      editingEmployee: null,
      editForm: {
        vl_credits: 0,
        sl_credits: 0,
        vl_used: 0,
        sl_used: 0,
        vl_carried_over: 0,
        vl_carried_over_used: 0,
      },
      showImportModal: false,
      importFile: null,
      importYear: new Date().getFullYear(),
      importing: false,
      importResult: null,
      toast: {
        show: false,
        message: '',
        type: 'success'
      }
    };
  },

  computed: {
    visiblePages() {
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      const pages = [];

      if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i);
      } else {
        if (current <= 3) {
          for (let i = 1; i <= 5; i++) pages.push(i);
          pages.push('...');
          pages.push(last);
        } else if (current >= last - 2) {
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
    this.loadCredits();
  },

  methods: {
    calculateCarryOver(remaining, maxCarryOver = 5) {
      // Only show carryover projection if we're in December or later
      // This prevents showing projections during the year
      const currentDate = new Date();
      const currentMonth = currentDate.getMonth() + 1; // 1-12
      const currentYear = currentDate.getFullYear();
      
      // Only calculate if:
      // 1. It's December of the current year being viewed, OR
      // 2. The year being viewed is in the past
      if (currentYear < this.year || (currentYear === this.year && currentMonth >= 12)) {
        if (remaining <= 0) return 0;
        return Math.min(remaining, maxCarryOver);
      }
      
      return '-'; // Show dash instead of 0 when it's not yet time to calculate
    },

    async loadCredits(page = 1) {
      this.loading = true;
      try {
        const params = {
          year: this.year,
          search: this.search,
          filter: this.filter,
          per_page: this.perPage,
          page: page
        };
        
        const response = await axios.get('/hr/leave-credits', { params });
        
        if (response.data.success) {
          this.employees = response.data.data;
          this.pagination = {
            current_page: response.data.current_page || 1,
            last_page: response.data.last_page || 1,
            per_page: response.data.per_page || this.perPage,
            total: response.data.total || 0,
            from: response.data.from || 0,
            to: response.data.to || 0
          };
        }
      } catch (error) {
        this.showToast('Failed to load leave credits', 'error');
      } finally {
        this.loading = false;
      }
    },

    goToPage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.loadCredits(page);
      }
    },

    editCredits(employee) {
      this.editingEmployee = employee;
      this.editForm = {
        vl_credits: employee.credits.vl_credits,
        sl_credits: employee.credits.sl_credits,
        vl_used: employee.credits.vl_used,
        sl_used: employee.credits.sl_used,
        vl_carried_over: employee.credits.vl_carried_over || 0,
        vl_carried_over_used: employee.credits.vl_carried_over_used || 0,
      };
      this.showEditModal = true;
    },

    async saveCredits() {
      this.saving = true;
      try {
        await axios.put(`/hr/leave-credits/${this.editingEmployee.id}`, {
          year: this.year,
          vl_credits: this.editForm.vl_credits,
          sl_credits: this.editForm.sl_credits,
          vl_used: this.editForm.vl_used,
          sl_used: this.editForm.sl_used,
          vl_carried_over: this.editForm.vl_carried_over,
          vl_carried_over_used: this.editForm.vl_carried_over_used,
        });

        this.showToast('Leave credits updated successfully', 'success');
        this.closeEditModal();
        this.loadCredits();
      } catch (error) {
        this.showToast('Failed to update leave credits', 'error');
      } finally {
        this.saving = false;
      }
    },

    closeEditModal() {
      this.showEditModal = false;
      this.editingEmployee = null;
    },

    async exportCredits() {
      try {
        const response = await axios.get('/hr/leave-credits/export', {
          params: { 
            year: this.year,
            search: this.search,
            filter: this.filter
          },
          responseType: 'blob'
        });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `leave_credits_${this.year}_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        this.showToast('Leave credits exported successfully', 'success');
      } catch (error) {
        this.showToast('Failed to export leave credits', 'error');
      }
    },

    handleFileUpload(event) {
      this.importFile = event.target.files[0];
      this.importResult = null;
    },

    async importCredits() {
      if (!this.importFile) {
        this.showToast('Please select a file', 'error');
        return;
      }

      this.importing = true;
      this.importResult = null;

      try {
        const formData = new FormData();
        formData.append('file', this.importFile);
        formData.append('year', this.importYear);

        const response = await axios.post('/hr/leave-credits/import', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        this.importResult = response.data;
        
        if (response.data.imported > 0) {
          this.showToast(response.data.message, 'success');
          this.loadCredits();
        }
      } catch (error) {
        this.showToast('Failed to import leave credits', 'error');
        this.importResult = {
          message: error.response?.data?.message || 'Import failed',
          imported: 0,
          skipped: 0,
          errors: error.response?.data?.errors || ['Unknown error occurred']
        };
      } finally {
        this.importing = false;
      }
    },

    closeImportModal() {
      this.showImportModal = false;
      this.importFile = null;
      this.importResult = null;
      this.importYear = this.currentYear;
    },

    showToast(message, type = 'success') {
      this.toast = { show: true, message, type };
      setTimeout(() => {
        this.toast.show = false;
      }, 3000);
    }
  }
};
</script>
