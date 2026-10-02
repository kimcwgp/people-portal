<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Title bar, matching Attendance -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Leave Requests</h1>
        <button
          @click="openLeaveModal()"
          class="flex flex-shrink-0 items-center gap-2 rounded-lg bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          File Leave
        </button>
      </div>

      <!-- Remaining credits for the year. These ignore the filters below. -->
      <div class="mb-3 sm:mb-4 rounded-xl bg-surface p-4 shadow-sm sm:rounded-2xl sm:px-6">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-text-muted">Available Leave Credits</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
          <div v-for="credit in leaveCreditTiles" :key="credit.label" class="rounded-lg border border-border px-3 py-2.5">
            <p class="text-xs text-text-muted">{{ credit.label }}</p>
            <p class="text-base sm:text-lg font-bold text-text">
              {{ credit.value }}<span class="ml-0.5 text-xs font-medium text-text-muted">{{ credit.unit }}</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Filters + results live in one card, as in Attendance -->
      <div class="bg-surface rounded-lg shadow-sm border border-border overflow-hidden">
        <!-- Notched-outline fields: the label sits on the border line.
             shrink-0 keeps the set widths so they wrap instead of collapsing. -->
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="relative w-full shrink-0 sm:w-52">
            <label for="f-status" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Status</label>
            <select id="f-status" v-model="filters.status"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option v-for="status in statusFilters" :key="status.value" :value="status.value">
                {{ status.label }} ({{ status.count }})
              </option>
            </select>
          </div>

          <div class="relative w-full shrink-0 sm:w-56">
            <label for="f-leave-type" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Leave Request</label>
            <select id="f-leave-type" v-model="filters.leaveTypeId"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option value="">All Leave Types</option>
              <option v-for="type in leaveTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
            </select>
          </div>

          <div class="relative w-full shrink-0 sm:w-72">
            <label for="f-date" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Date</label>
            <div id="f-date" class="flex items-center gap-1.5 rounded-lg border border-border px-3 py-2.5 focus-within:border-accent">
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No leave requests found</h3>
          <p class="mt-1 text-text-muted">
            {{ hasActiveFilters ? 'Try adjusting your filters' : 'You haven\'t submitted any leave requests yet.' }}
          </p>
        </div>

      <!-- Leaves Table -->
      <div v-else>
        <!-- Desktop Table -->
        <div class="hidden lg:block overflow-x-auto">
          <!-- Ten columns need real width; without a floor the dates squeeze
               onto three lines instead of the wrapper scrolling. -->
          <table class="w-full min-w-[1200px]">
            <thead class="bg-surface-sunken border-b border-border">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Leave Type</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Start Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">End Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Duration</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Reason</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Notes</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Attachment</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Submitted</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-text-muted uppercase tracking-wider">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr 
                v-for="leave in leaves" 
                :key="leave.id"
                class="hover:bg-surface-sunken transition-colors"
              >
                <td class="px-4 py-3">
                  <span class="inline-flex items-center whitespace-nowrap px-2 py-1 rounded-md text-xs font-medium bg-status-purple text-status-text">
                    {{ leave.leave_type?.name }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <p class="whitespace-nowrap text-sm text-text">{{ formatDate(leave.start_date) }}</p>
                  <p v-if="leave.time_in" class="text-xs text-text-muted">{{ formatTime(leave.time_in) }}</p>
                </td>
                <td class="px-4 py-3">
                  <p class="whitespace-nowrap text-sm text-text">{{ formatDate(leave.end_date) }}</p>
                  <p v-if="leave.time_out" class="text-xs text-text-muted">{{ formatTime(leave.time_out) }}</p>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm font-medium text-text">
                    {{ leave.formatted_duration || (leave.calculated_days + ' day' + (leave.calculated_days >= 2 ? 's' : '')) }}
                  </p>
                  <p class="text-xs text-text-muted">{{ leave.duration || 'All Day' }}</p>
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
                    @click="openAttachmentModal(leave.attachment)"
                    class="text-sm text-accent-solid hover:text-accent-hover flex items-center gap-1"
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
                  <p class="text-xs text-text-muted">{{ formatTime(leave.created_at?.split(' ')[1]) }}</p>
                </td>
                <td class="px-4 py-3">
                  <span :class="getStatusClass(leave.status)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap">
                    {{ leave.status.toUpperCase() }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      v-if="canEditLeave(leave)"
                      @click="openLeaveModal(leave)"
                      class="p-1.5 text-accent-solid hover:bg-accent-subtle rounded transition-colors"
                      title="Edit"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button
                      v-if="canCancelLeave(leave)"
                      @click="openCancelModal(leave)"
                      class="p-1.5 text-danger hover:bg-status-red rounded transition-colors"
                      title="Cancel"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="lg:hidden divide-y divide-border">
          <div
            v-for="leave in leaves"
            :key="leave.id"
            class="p-4 hover:bg-surface-sunken transition-colors"
          >
            <!-- Header -->
            <div class="flex items-start justify-between mb-3">
              <span class="inline-flex items-center whitespace-nowrap px-2 py-1 rounded-md text-xs font-medium bg-status-purple text-status-text">
                {{ leave.leave_type?.name }}
              </span>
              <span :class="getStatusClass(leave.status)" class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap">
                {{ leave.status.toUpperCase() }}
              </span>
            </div>

            <!-- Details -->
            <div class="space-y-2 mb-3">
              <div class="flex justify-between text-sm">
                <span class="text-text-muted">Dates:</span>
                <span class="font-medium text-text">{{ formatDateRange(leave.start_date, leave.end_date) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-text-muted">Duration:</span>
                <span class="font-medium text-text">
                  {{ leave.formatted_duration || (leave.calculated_days + ' day' + (leave.calculated_days >= 2 ? 's' : '')) }}
                  ({{ leave.duration || 'All Day' }})
                </span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-text-muted">Submitted:</span>
                <span class="font-medium text-text">{{ formatDate(leave.created_at) }}</span>
              </div>
              <div class="text-sm">
                <span class="text-text-muted">Reason:</span>
                <p class="text-text mt-1 line-clamp-2">{{ leave.reason || 'No reason provided' }}</p>
              </div>
              
              <!-- Notes Column (Rejection/Approval Notes) -->
              <div v-if="leave.rejection_note || leave.notes" class="text-sm">
                <span class="text-text-muted">Notes:</span>
                <p v-if="leave.rejection_note" class="text-danger mt-1 line-clamp-2">
                  <span class="font-semibold">{{ leave.status === 'cancelled' ? 'Cancelled: ' : 'Rejected: ' }}</span>{{ leave.rejection_note }}
                </p>
                <p v-else-if="leave.notes" class="text-text-muted mt-1 line-clamp-2">{{ leave.notes }}</p>
              </div>
              
              <div v-if="leave.attachment" class="flex justify-between text-sm">
                <span class="text-text-muted">Attachment:</span>
                <button
                  @click="openAttachmentModal(leave.attachment)"
                  class="text-accent-solid hover:text-accent-hover flex items-center gap-1"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                  </svg>
                  <span>View</span>
                </button>
              </div>
            </div>

            <!-- Actions -->
            <div v-if="canEditLeave(leave) || canCancelLeave(leave)" class="flex gap-2">
              <button
                v-if="canEditLeave(leave)"
                @click="openLeaveModal(leave)"
                class="flex-1 px-3 py-2 text-sm font-medium text-white bg-accent-solid hover:bg-accent-hover rounded-lg transition-colors"
              >
                Edit
              </button>
              <button
                v-if="canCancelLeave(leave)"
                @click="openCancelModal(leave)"
                class="flex-1 px-3 py-2 text-sm font-medium text-white bg-danger hover:bg-danger/90 rounded-lg transition-colors"
              >
                Cancel
              </button>
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
              class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Previous
            </button>
            
            <span class="text-sm text-text">
              Page {{ currentPage }} of {{ lastPage }}
            </span>
            
            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage >= lastPage"
              class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Next
            </button>
          </div>

          <div class="hidden sm:flex sm:flex-col sm:space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex items-center text-sm text-text">
              <span>Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} results</span>
            </div>
            
            <div class="flex items-center space-x-1">
              <button
                @click="changePage(currentPage - 1)"
                :disabled="currentPage <= 1"
                class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Previous
              </button>
              
              <button
                @click="changePage(currentPage + 1)"
                :disabled="currentPage >= lastPage"
                class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Next
              </button>
            </div>
          </div>
      </div>
      </div>

      <!-- Leave Modal -->
      <div v-if="showModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
        <div class="relative bg-surface rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden">
          <div class="p-6 border-b border-border">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-text">{{ editingLeave ? 'Edit Leave Request' : 'File New Leave Request' }}</h3>
              <button @click="closeModal" class="p-2 rounded-lg hover:bg-surface-sunken">
                <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <form @submit.prevent="submitLeave" class="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-140px)]">
            <div>
              <label class="block text-sm font-medium text-text mb-2">Leave Type *</label>
              <select v-model="form.leaves_type_id" class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200" required>
                <option value="">Select leave type</option>
                <option v-for="type in leaveTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
              </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-text mb-2">Start Date *</label>
                <input
                  v-model="form.start_date"
                  type="date"
                  :min="thirtyDaysAgoISO"
                  class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                  :class="{'border-danger': dateRangeError}"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-text mb-2">End Date *</label>
                <input
                  v-model="form.end_date"
                  type="date"
                  :min="form.start_date || thirtyDaysAgoISO"
                  class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                  :class="{'border-danger': dateRangeError}"
                  required
                />
              </div>
            </div>

            <div v-if="dateRangeError" class="bg-status-red rounded-xl p-4">
              <p class="text-sm text-danger flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ dateRangeError }}
              </p>
            </div>

            <div v-if="form.start_date && form.end_date" class="bg-accent-subtle rounded-xl p-4">
              <p class="text-sm text-text"><span class="font-medium">Duration:</span> {{ calculateFormDays() }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-text mb-2">Duration *</label>
              <select v-model="form.duration" class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200" required>
                <option value="All Day">All Day</option>
                <option value="Half Day (8am to 12nn)">Half Day (8am to 12nn)</option>
                <option value="Half Day (1pm to 5pm)">Half Day (1pm to 5pm)</option>
                <option value="Custom">Custom</option>
              </select>
            </div>

            <div v-if="form.duration === 'Custom'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-text mb-2">From *</label>
                <input v-model="form.time_in" type="time" class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-text mb-2">To *</label>
                <input v-model="form.time_out" type="time" class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200" required />
              </div>
            </div>

            <div v-if="requiresAttachment" class="space-y-2">
              <label class="block text-sm font-medium text-text">Medical Certificate *</label>
              <div class="border-2 border-dashed border-border rounded-xl p-6 text-center hover:border-accent transition-colors duration-200">
                <input type="file" @change="handleFileChange" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden" ref="fileInput" />
                <div v-if="!selectedFileName" @click="$refs.fileInput.click()" class="cursor-pointer">
                  <svg class="w-12 h-12 text-text-muted mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                  <p class="text-sm text-text-muted mb-2">Click to upload medical certificate</p>
                  <p class="text-xs text-text-muted">PDF, JPG, PNG, DOC, DOCX (max 2MB)</p>
                </div>
                <div v-else class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-sm text-text">{{ selectedFileName }}</span>
                  </div>
                  <button type="button" @click="removeFile" class="text-danger hover:text-danger p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-text mb-2">Reason *</label>
              <textarea v-model="form.reason" rows="4" class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200 resize-none" placeholder="Please provide a reason for your leave request..." required></textarea>
            </div>

            <div class="flex gap-3 pt-4">
              <button type="button" @click="closeModal" class="flex-1 px-6 py-3 text-text bg-surface-sunken hover:bg-canvas rounded-xl font-medium transition-colors duration-200">Cancel</button>
              <button type="submit" :disabled="submitting" class="flex-1 px-6 py-3 bg-accent-solid text-white rounded-xl font-medium hover:bg-accent-hover transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="submitting" class="flex items-center justify-center gap-2">
                  <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  {{ editingLeave ? 'Updating...' : 'Submitting...' }}
                </span>
                <span v-else>{{ editingLeave ? 'Update Leave' : 'Submit Leave' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Cancel Modal -->
      <div v-if="showCancelModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="closeCancelModal"></div>
        <div class="relative bg-surface rounded-2xl max-w-md w-full mx-4">
          <div class="p-6 border-b border-border">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-text">Cancel Leave Request</h3>
              <button @click="closeCancelModal" class="p-2 rounded-lg hover:bg-surface-sunken">
                <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>

          <form @submit.prevent="submitCancellation" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-text mb-2">
                Reason for Cancellation *
              </label>
              <textarea 
                v-model="cancellationReason" 
                rows="4" 
                class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200 resize-none" 
                placeholder="Please provide a reason for cancelling this leave request..."
                required
              ></textarea>
            </div>

            <div class="flex gap-3 pt-4">
              <button 
                type="button" 
                @click="closeCancelModal" 
                class="flex-1 px-6 py-3 text-text bg-surface-sunken hover:bg-canvas rounded-xl font-medium transition-colors duration-200"
              >
                Keep Leave
              </button>
              <button 
                type="submit" 
                :disabled="cancelSubmitting" 
                class="flex-1 px-6 py-3 bg-danger text-white rounded-xl font-medium hover:bg-danger/90 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="cancelSubmitting" class="flex items-center justify-center gap-2">
                  <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  Cancelling...
                </span>
                <span v-else>Cancel Leave</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Attachment Modal -->
      <div v-if="showAttachmentModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="closeAttachmentModal"></div>
        <div class="relative bg-surface rounded-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
          <div class="p-4 border-b border-border flex items-center justify-between">
            <h3 class="text-lg font-semibold text-text">Attachment</h3>
            <div class="flex items-center gap-2">
              <a :href="getAttachmentUrl(currentAttachment)" target="_blank" class="px-3 py-1 text-sm bg-accent-solid text-white rounded-lg hover:bg-accent-hover">Download</a>
              <button @click="closeAttachmentModal" class="p-2 rounded-lg hover:bg-surface-sunken">
                <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <div class="p-6 overflow-auto max-h-[calc(90vh-100px)]">
            <div v-if="isImageFile(currentAttachment)" class="text-center">
              <img :src="getAttachmentUrl(currentAttachment)" :alt="currentAttachment" class="max-w-full max-h-[70vh] mx-auto rounded-lg shadow-lg">
            </div>
            <div v-else class="text-center">
              <iframe v-if="getFileExtension(currentAttachment) === 'pdf'" :src="getAttachmentUrl(currentAttachment)" class="w-full h-[70vh] border rounded-lg"></iframe>
              <div v-else class="py-12">
                <div class="w-16 h-16 mx-auto mb-4 bg-surface-sunken rounded-full flex items-center justify-center">
                  <svg class="w-8 h-8 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-text-muted mb-4">{{ currentAttachment?.split('/').pop() }}</p>
                <p class="text-sm text-text-muted mb-4">This file type cannot be previewed. Click Download to view the file.</p>
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

      <!-- Success/Error Messages -->
      <div v-if="message" :class="['fixed top-4 right-4 p-4 rounded-xl shadow-lg z-50 transform transition-all duration-300', message.type === 'success' ? 'bg-success text-white' : 'bg-danger text-white']">
        <div class="flex items-center gap-2">
          <svg v-if="message.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>{{ message.text }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'MyLeaves',
  data() {
    return {
      leaves: [],
      leaveTypes: [],
      stats: {},
      loading: true,
      currentPage: 1,
      lastPage: 1,
      totalItems: 0,
      perPage: 10,
      currentStatus: '',
      filters: {
        status: '',
        leaveTypeId: '',
        startDate: '',
        endDate: ''
      },
      statusFilters: [
        { label: 'All Statuses', value: '', count: 0 },
        { label: 'Pending', value: 'pending', count: 0 },
        { label: 'Approved', value: 'approved', count: 0 },
        { label: 'Rejected', value: 'rejected', count: 0 },
        { label: 'Cancelled', value: 'cancelled', count: 0 }
      ],
      showModal: false,
      submitting: false,
      editingLeave: null,
      selectedFileName: '',
      message: null,
      tip: { show: false, text: '', x: 0, y: 0 },
      showAttachmentModal: false,
      currentAttachment: null,
      showCancelModal: false,
      cancellingLeave: null,
      cancellationReason: '',
      cancelSubmitting: false,
      allowedMimeTypes: [
        'application/pdf', 'image/jpeg', 'image/jpg', 'image/png',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
      ],
      maxAttachmentSizeBytes: 2 * 1024 * 1024,
      imageExtensions: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
      form: {
        leaves_type_id: '',
        start_date: '',
        end_date: '',
        reason: '',
        duration: 'All Day',
        time_in: '',
        time_out: '',
        attachment: null
      }
    }
  },
  computed: {
    // Remaining credits for the year, same five tiles as the dashboard.
    leaveCreditTiles() {
      const c = this.stats.credits || {}
      return [
        { label: 'PTO', value: c.pto ?? 0, unit: 'd' },
        { label: 'VL',  value: c.vl  ?? 0, unit: 'd' },
        { label: 'SL',  value: c.sl  ?? 0, unit: 'd' },
        { label: 'BDO', value: c.bdo ?? 0, unit: 'd' },
        { label: 'CTO', value: c.cto ?? 0, unit: 'hrs' },
      ]
    },

    todayISO() {
      const d = new Date()
      d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
      return d.toISOString().slice(0, 10)
    },
    thirtyDaysAgoISO() {
      const d = new Date()
      d.setDate(d.getDate() - 30)
      d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
      return d.toISOString().slice(0, 10)
    },
    hasActiveFilters() {
      return this.currentStatus || this.filters.leaveTypeId || this.filters.startDate || this.filters.endDate
    },
    requiresAttachment() {
      const t = this.leaveTypes.find(x => x.id == this.form.leaves_type_id)
      return t?.name?.includes('WITH Medical Certificate') || false
    },
    isValidDateRange() {
      if (!this.form.start_date || !this.form.end_date) return true
      return new Date(this.form.end_date) >= new Date(this.form.start_date)
    },
    dateRangeError() {
      if (!this.form.start_date || !this.form.end_date) return null
      if (!this.isValidDateRange) return 'End date must be on or after start date'
      const start = this.toDateOnly(this.form.start_date)
      const thirtyDaysAgo = this.toDateOnly(this.thirtyDaysAgoISO)
      if (start && thirtyDaysAgo && start < thirtyDaysAgo) return 'You can only file leaves up to 30 days in the past'
      return null
    }
  },
  watch: {
    'form.leaves_type_id'(newId) {
      const t = this.leaveTypes.find(x => x.id == newId)
      if (t && !t.name?.includes('WITH Medical Certificate')) this.removeFile()
    },
  },
  async mounted() {
    document.title = 'My Leaves'
    await this.loadData()
  },
  methods: {
    async loadData() {
      this.loading = true
      try {
        const params = { 
          page: this.currentPage,
          per_page: this.perPage
        }
        
        if (this.currentStatus) params.status = this.currentStatus
        if (this.filters.leaveTypeId) params.leave_type_id = this.filters.leaveTypeId
        if (this.filters.startDate) params.start_date = this.filters.startDate
        if (this.filters.endDate) params.end_date = this.filters.endDate

        // Stats params (without page and status)
        const statsParams = {}
        if (this.filters.leaveTypeId) statsParams.leave_type_id = this.filters.leaveTypeId
        if (this.filters.startDate) statsParams.start_date = this.filters.startDate
        if (this.filters.endDate) statsParams.end_date = this.filters.endDate

        const [leavesRes, typesRes, statsRes] = await Promise.all([
          axios.get('/user/my-leaves', { params }), 
          axios.get('/user/my-leaves/types'),
          axios.get('/user/my-leaves/stats', { params: statsParams })
        ])
        
        this.leaves = leavesRes.data.data || []
        this.totalItems = leavesRes.data.meta.total
        this.currentPage = leavesRes.data.meta.current_page
        this.lastPage = leavesRes.data.meta.last_page
        
        this.leaveTypes = typesRes.data.data || []
        this.stats = statsRes.data.data || {}
        this.updateStatusCounts()
      } catch (error) {
        this.showMessage('Failed to load leaves data', 'error')
      } finally {
        this.loading = false
      }
    },

    canEditLeave(leave) {
      return leave.status === 'pending'
    },

    canCancelLeave(leave) {
      return ['pending', 'approved'].includes(leave.status)
    },

    openCancelModal(leave) {
      this.cancellingLeave = leave
      this.cancellationReason = ''
      this.showCancelModal = true
    },

    closeCancelModal() {
      this.showCancelModal = false
      this.cancellingLeave = null
      this.cancellationReason = ''
    },

    async submitCancellation() {
      if (!this.cancellationReason.trim()) {
        this.showMessage('Please provide a reason for cancellation', 'error')
        return
      }

      this.cancelSubmitting = true
      try {
        const response = await axios.put(`/user/my-leaves/${this.cancellingLeave.id}/cancel`, {
          cancellation_reason: this.cancellationReason
        })

        if (response.data.success) {
          this.showMessage(response.data.message, 'success')
          this.closeCancelModal()
          await this.loadData()
        } else {
          this.showMessage(response.data.message || 'Failed to cancel leave', 'error')
        }
      } catch (error) {
        if (error.response?.status === 422) {
          const first = Object.values(error.response.data.errors || {})[0]?.[0]
          const message = first || error.response.data.message || 'Validation failed'
          this.showMessage(message, 'error')
        } else {
          this.showMessage(error.response?.data?.message || 'Failed to cancel leave', 'error')
        }
      } finally {
        this.cancelSubmitting = false
      }
    },

    applyFilters() {
      this.currentStatus = this.filters.status
      this.currentPage = 1
      this.loadData()
    },

    clearFilters() {
      this.filters = {
        status: '',
        leaveTypeId: '',
        startDate: '',
        endDate: ''
      }
      this.currentStatus = ''
      this.currentPage = 1
      this.loadData()
    },

    async changePage(page) {
      if (page < 1 || page > this.lastPage) return
      
      this.currentPage = page
      await this.loadData()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },

    updateStatusCounts() {
      this.statusFilters.forEach(filter => {
        if (filter.value === '') {
          // stats.total is the unfiltered count. totalItems is the paginated
          // total of whichever status is selected, so it would make the All
          // chip read "1" while Pending was active.
          filter.count = this.stats.total ?? this.totalItems ?? 0
        } else if (this.stats[filter.value] !== undefined) {
          filter.count = this.stats[filter.value]
        } else {
          filter.count = 0
        }
      })
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
        case 'pending': return 'bg-status-yellow text-status-text'
        case 'approved': return 'bg-status-green text-status-text'
        case 'rejected': return 'bg-status-red text-status-text'
        case 'cancelled': return 'bg-status-gray text-status-text'
        default: return 'bg-status-gray text-status-text'
      }
    },

    openLeaveModal(leave = null) {
      this.editingLeave = leave
      if (leave) {
        this.form = {
          leaves_type_id: leave.leaves_type_id,
          start_date: leave.start_date,
          end_date: leave.end_date,
          reason: leave.reason,
          duration: leave.duration || 'All Day',
          time_in: leave.time_in || '',
          time_out: leave.time_out || '',
          attachment: null
        }
        this.selectedFileName = leave.attachment ? leave.attachment.split('/').pop() : ''
      } else {
        this.resetForm()
      }
      this.showModal = true
    },

    closeModal() {
      this.showModal = false
      this.editingLeave = null
      this.resetForm()
    },

    resetForm() {
      this.form = {
        leaves_type_id: '',
        start_date: '',
        end_date: '',
        reason: '',
        duration: 'All Day',
        time_in: '',
        time_out: '',
        attachment: null
      }
      this.selectedFileName = ''
    },

    handleFileChange(e) {
      const file = e.target.files?.[0]
      if (!file) return
      if (file.size > this.maxAttachmentSizeBytes) { 
        this.showMessage('File size must be less than 2MB', 'error')
        return 
      }
      if (!this.allowedMimeTypes.includes(file.type)) { 
        this.showMessage('Please upload PDF, JPG, PNG, DOC, or DOCX files only', 'error')
        return 
      }
      this.form.attachment = file
      this.selectedFileName = file.name
    },

    removeFile() {
      this.form.attachment = null
      this.selectedFileName = ''
      if (this.$refs.fileInput) this.$refs.fileInput.value = ''
    },

    async submitLeave() {
      if (this.dateRangeError) { 
        this.showMessage(this.dateRangeError, 'error')
        return 
      }
      if (this.requiresAttachment && !this.form.attachment && !this.editingLeave?.attachment) {
        this.showMessage('Medical certificate is required for this leave type', 'error')
        return
      }
      
      this.submitting = true
      try {
        const fd = new FormData()
        fd.append('leaves_type_id', this.form.leaves_type_id)
        fd.append('start_date', this.form.start_date)
        fd.append('end_date', this.form.end_date)
        fd.append('reason', this.form.reason)
        fd.append('duration', this.form.duration)
        if (this.form.duration === 'Custom') {
          fd.append('time_in', this.form.time_in)
          fd.append('time_out', this.form.time_out)
        }
        if (this.form.attachment) fd.append('attachment', this.form.attachment)

        let res
        if (this.editingLeave) {
          res = await axios.post(`/user/my-leaves/${this.editingLeave.id}`, fd, { 
            headers: { 
              'Content-Type': 'multipart/form-data',
              'X-HTTP-Method-Override': 'PUT'
            } 
          })
        } else {
          res = await axios.post('/user/my-leaves', fd, { 
            headers: { 'Content-Type': 'multipart/form-data' } 
          })
        }

        if (res.data.success) {
          this.showMessage(res.data.message || 'Leave request processed successfully', 'success')
          this.closeModal()
          await this.loadData()
        } else {
          this.showMessage(res.data.message || 'Something went wrong', 'error')
        }
      } catch (err) {
        if (err.response?.status === 422) {
          const first = Object.values(err.response.data.errors || {})[0]?.[0]
          const message = first || err.response.data.message || 'Validation failed'
          this.showMessage(message, 'error')
        } else {
          this.showMessage(err.response?.data?.message || 'Failed to submit leave request', 'error')
        }
      } finally {
        this.submitting = false
      }
    },

    toDateOnly(val) {
      if (!val) return null
      const m = String(val).match(/^(\d{4})-(\d{2})-(\d{2})/)
      if (m) return new Date(+m[1], +m[2] - 1, +m[3])
      const d = new Date(val)
      if (!isNaN(d)) { 
        d.setHours(0,0,0,0)
        return d 
      }
      return null
    },

    calculateDays(a, b) {
      const start = this.toDateOnly(a)
      const end = this.toDateOnly(b)
      if (!start || !end) return 0
      return Math.max(1, Math.round((end - start) / 86400000) + 1)
    },

    calculateFormDays() {
      if (!this.form.start_date || !this.form.end_date) return '0 day'

      const days = this.calculateDays(this.form.start_date, this.form.end_date)

      // If half day is selected and it's a single day leave
      if (days === 1 && (this.form.duration === 'Half Day (8am to 12nn)' || this.form.duration === 'Half Day (1pm to 5pm)')) {
        return '0.5 day'  // Singular
      }

      // Pluralization: "day" for 0.5 and 1, "days" for 2 or more
      return days >= 2 ? `${days} days` : `${days} day`
    },

    formatDate(s) {
      const d = this.toDateOnly(s)
      return d ? d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : ''
    },

    formatTime(t) {
      if (!t) return ''
      const [h, m] = t.split(':')
      const d = new Date()
      d.setHours(+h, +m, 0, 0)
      return d.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
    },

    formatDateRange(a, b) {
      if (!a || !b) return ''
      const sa = this.formatDate(a), sb = this.formatDate(b)
      return a === b ? sa : `${sa} - ${sb}`
    },

    getAttachmentUrl(p) { 
      return p ? `/storage/${p}` : '' 
    },

    openAttachmentModal(p) { 
      this.currentAttachment = p
      this.showAttachmentModal = true 
    },

    closeAttachmentModal() { 
      this.showAttachmentModal = false
      this.currentAttachment = null 
    },

    getFileExtension(p) { 
      return p ? p.split('.').pop().toLowerCase() : '' 
    },

    isImageFile(p) { 
      return this.imageExtensions.includes(this.getFileExtension(p)) 
    },

    showMessage(text, type = 'success') {
      this.message = { text, type }
      setTimeout(() => { this.message = null }, 5000)
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