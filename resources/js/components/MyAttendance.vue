<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <div class="mb-3 sm:mb-4 flex items-center justify-between rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Attendance</h1>
      </div>
      
      <!-- Filters + results live in one card, as in the reference -->
      <div class="bg-surface rounded-lg shadow-sm border border-border overflow-hidden">
        <div class="flex flex-col gap-4 border-b border-border p-4 lg:flex-row lg:items-center lg:justify-between">
          <!-- Notched-outline fields: the label sits on the border line -->
          <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 lg:max-w-3xl">
            <div class="relative">
              <label for="f-date" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Date</label>
              <div id="f-date" class="flex items-center gap-1.5 rounded-lg border border-border px-3 py-2.5 focus-within:border-accent">
                <input type="date" v-model="filters.startDate"
                       class="w-full min-w-0 bg-transparent text-sm text-text focus:outline-none">
                <span class="text-text-subtle">&ndash;</span>
                <input type="date" v-model="filters.endDate"
                       class="w-full min-w-0 bg-transparent text-sm text-text focus:outline-none">
              </div>
            </div>

            <div class="relative">
              <label for="f-month" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Month</label>
              <select id="f-month" v-model="filters.month"
                      class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
                <option value="">All</option>
                <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
              </select>
            </div>

            <div class="relative">
              <label for="f-page" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Page</label>
              <select id="f-page" v-model="selectedPerPage" @change="changePerPage"
                      class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
                <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
              </select>
            </div>
          </div>

          <div class="flex gap-2">
            <button @click="applyFilters"
                    class="flex-1 rounded-lg bg-accent-solid px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover lg:flex-none">
              Filter
            </button>
            <button @click="clearFilters"
                    class="flex-1 rounded-lg border border-border bg-surface-sunken px-5 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas lg:flex-none">
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
            {{ hasActiveFilters ? 'Try adjusting your filters or date range' : 'No attendance records found' }}
          </p>
        </div>

      <!-- Attendance Table -->
      <div v-else>
        <!-- Desktop Table -->
        <div class="hidden lg:block overflow-x-auto">
          <table class="w-full">
            <thead class="bg-surface-sunken border-b border-border">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Time In / Time Out</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Working Hours</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-text-muted uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr 
                v-for="attendance in attendances" 
                :key="attendance.id || attendance.attendance_date"
                class="hover:bg-surface-sunken transition-colors"
              >
                <td class="px-4 py-3">
                  <p class="text-sm font-semibold text-text">{{ formatDay(attendance.attendance_date) }}</p>
                  <p class="text-xs text-text-muted">{{ formatDate(attendance.attendance_date) }}</p>
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
                    <p class="text-sm text-text">
                      {{ getTimeDisplay(attendance.time_in) }}
                    </p>
                    <p class="text-sm text-text">
                      {{ getTimeDisplay(attendance.time_out) }}
                    </p>
                  </div>
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
                  <!-- Multiple sessions actions -->
                  <div v-if="attendance.sessions && attendance.sessions.length > 1" class="space-y-1">
                    <div v-for="(session, index) in attendance.sessions" :key="index" class="flex flex-col items-center gap-2">
                      <span
                        v-if="session.correction?.status"
                        :class="getCorrectionStatusClass(session.correction.status)"
                        class="px-2 py-0.5 text-xs font-medium rounded-full whitespace-nowrap"
                      >
                        {{ getCorrectionStatusLabel(session.correction.status) }}
                      </span>
                      <button
                        v-if="canRequestCorrection(session)"
                        @click="openCorrectionModal(session)"
                        :disabled="session.correction?.status === 'pending' || session.correction?.status === 'approved'"
                        class="p-1.5 rounded transition-colors"
                        :class="[
                          (session.correction?.status === 'pending' || session.correction?.status === 'approved')
                            ? 'text-text-subtle cursor-not-allowed'
                            : 'text-accent-solid hover:bg-accent-subtle'
                        ]"
                        :title="getCorrectionButtonText(session)"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </button>
                      <span v-else-if="!canRequestCorrection(session)" class="text-xs text-text-subtle">-</span>
                    </div>
                  </div>
                  <!-- Single session actions -->
                  <div v-else class="flex flex-col items-center gap-2">
                    <span
                      v-if="attendance.correction?.status"
                      :class="getCorrectionStatusClass(attendance.correction.status)"
                      class="px-2 py-0.5 text-xs font-medium rounded-full whitespace-nowrap"
                    >
                      {{ getCorrectionStatusLabel(attendance.correction.status) }}
                    </span>
                    <button
                      v-if="canRequestCorrection(attendance)"
                      @click="openCorrectionModal(attendance)"
                      :disabled="attendance.correction?.status === 'pending' || attendance.correction?.status === 'approved'"
                      class="p-1.5 rounded transition-colors"
                      :class="[
                        (attendance.correction?.status === 'pending' || attendance.correction?.status === 'approved')
                          ? 'text-text-subtle cursor-not-allowed'
                          : 'text-accent-solid hover:bg-accent-subtle'
                      ]"
                      :title="getCorrectionButtonText(attendance)"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <span v-else-if="!canRequestCorrection(attendance)" class="text-xs text-text-subtle">-</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="lg:hidden divide-y divide-border">
          <div
            v-for="attendance in attendances"
            :key="attendance.id || attendance.attendance_date"
            class="p-4 hover:bg-surface-sunken transition-colors"
          >
            <!-- Header -->
            <div class="flex items-start justify-between mb-3">
              <div>
                <p class="text-sm font-semibold text-text">{{ formatDay(attendance.attendance_date) }}</p>
                <p class="text-xs text-text-muted">{{ formatDate(attendance.attendance_date) }}</p>
              </div>
              <span 
                v-if="getStatusLabel(attendance)" 
                :class="getStatusClass(attendance)" 
                class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
              >
                {{ getStatusLabel(attendance) }}
              </span>
            </div>

            <!-- Details -->
            <div class="space-y-2 mb-3">
              <div class="flex justify-between text-sm">
                <span class="text-text-muted">Time In/Out:</span>
                <!-- Multiple sessions display -->
                <div v-if="attendance.sessions && attendance.sessions.length > 1" class="text-right space-y-1">
                  <div v-for="(session, index) in attendance.sessions" :key="index" class="font-medium text-text">
                    {{ getTimeDisplay(session.time_in) }} / {{ getTimeDisplay(session.time_out) }}
                  </div>
                </div>
                <!-- Single session display -->
                <span v-else class="font-medium text-text">
                  {{ getTimeDisplay(attendance.time_in) }} / {{ getTimeDisplay(attendance.time_out) }}
                </span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-text-muted">Working Hours:</span>
                <!-- Multiple sessions display -->
                <div v-if="attendance.sessions && attendance.sessions.length > 1" class="text-right space-y-1">
                  <div v-for="(session, index) in attendance.sessions" :key="index" class="font-medium text-text">
                    {{ session.working_hours || '--:--' }}
                  </div>
                </div>
                <!-- Single session -->
                <span v-else class="font-medium text-text">{{ attendance.working_hours || '--:--' }}</span>
              </div>
              <div v-if="attendance.leave_info?.has_attachment">
                <button 
                  @click="viewAttachment(attendance.leave_info.attachment_url)" 
                  class="inline-flex items-center gap-1 text-xs text-accent-solid hover:text-accent-hover"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                  </svg>
                  View Medical Certificate
                </button>
              </div>
            </div>

            <!-- Actions -->
            <!-- Multiple sessions actions -->
            <div v-if="attendance.sessions && attendance.sessions.length > 1" class="pt-3 border-t border-border space-y-2">
              <div v-for="(session, index) in attendance.sessions" :key="index">
                <div v-if="canRequestCorrection(session)" class="flex items-center justify-between">
                  <span class="text-xs text-text-muted">Session {{ index + 1 }}:</span>
                  <div class="flex items-center gap-2">
                    <span
                      v-if="session.correction?.status"
                      :class="getCorrectionStatusClass(session.correction.status)"
                      class="px-2 py-1 text-xs font-medium rounded-full"
                    >
                      {{ getCorrectionStatusLabel(session.correction.status) }}
                    </span>
                    <button
                      @click="openCorrectionModal(session)"
                      :disabled="session.correction?.status === 'pending' || session.correction?.status === 'approved'"
                      class="px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                      :class="[
                        (session.correction?.status === 'pending' || session.correction?.status === 'approved')
                          ? 'bg-surface-sunken text-text-subtle cursor-not-allowed'
                          : 'bg-accent-solid text-white hover:bg-accent-hover'
                      ]"
                    >
                      {{ getCorrectionButtonText(session) }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <!-- Single session actions -->
            <div v-else-if="canRequestCorrection(attendance)" class="pt-3 border-t border-border">
              <div class="flex items-center justify-between">
                <span
                  v-if="attendance.correction?.status"
                  :class="getCorrectionStatusClass(attendance.correction.status)"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ getCorrectionStatusLabel(attendance.correction.status) }}
                </span>
                <button
                  @click="openCorrectionModal(attendance)"
                  :disabled="attendance.correction?.status === 'pending' || attendance.correction?.status === 'approved'"
                  class="px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                  :class="[
                    (attendance.correction?.status === 'pending' || attendance.correction?.status === 'approved')
                      ? 'bg-surface-sunken text-text-subtle cursor-not-allowed'
                      : 'bg-accent-solid text-white hover:bg-accent-hover'
                  ]"
                >
                  {{ getCorrectionButtonText(attendance) }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div><!-- /filters + table card -->

      <!-- Pagination -->
      <div v-if="pagination && pagination.total > pagination.per_page" class="mt-8">
        <div class="bg-surface rounded-lg shadow-sm border border-border px-4 py-4">
          <div class="flex items-center justify-between sm:hidden">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Previous
            </button>
            
            <span class="text-sm text-text font-medium">
              Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>
            
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Next
            </button>
          </div>

          <div class="hidden sm:flex sm:flex-col sm:space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex items-center text-sm text-text">
              <span>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results</span>
            </div>
            
            <div class="flex items-center space-x-1">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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
                    'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                    page === pagination.current_page
                      ? 'bg-accent text-text'
                      : page === '...'
                        ? 'text-text-subtle cursor-default'
                        : 'text-text bg-surface border border-border hover:bg-surface-sunken'
                  ]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page"
                class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Correction Request Modal -->
    <div v-if="showCorrectionModal" class="fixed inset-0 bg-text/40 flex items-center justify-center p-4 z-50">
      <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-surface shadow-2xl">
        <div class="h-1 flex-shrink-0 bg-accent"></div>
        <div class="p-5 sm:p-6">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-semibold text-text sm:text-lg">Request Attendance Correction</h2>
            <button
              @click="closeCorrectionModal"
              class="text-text-subtle hover:text-text-muted transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="mb-5 rounded-lg border border-border bg-surface-sunken px-4 py-3">
            <p class="text-sm text-text">
              <strong>Date:</strong> {{ formatDate(selectedAttendance?.attendance_date) }}
            </p>
          </div>

          <form @submit.prevent="submitCorrection" class="space-y-6">
            <!-- Current Times Display -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="rounded-lg border border-border bg-surface-sunken px-4 py-3">
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-text-muted">Current Time In</label>
                <p class="text-base font-semibold text-text">{{ getTimeDisplay(selectedAttendance?.time_in) }}</p>
              </div>
              <div class="rounded-lg border border-border bg-surface-sunken px-4 py-3">
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-text-muted">Current Time Out</label>
                <p class="text-base font-semibold text-text">{{ getTimeDisplay(selectedAttendance?.time_out) }}</p>
              </div>
            </div>

            <!-- Requested Times -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-text-muted">Requested Time In *</label>
                <input
                  type="time"
                  v-model="correctionForm.corrected_time_in"
                  class="w-full border border-border rounded-lg px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none"
                >
              </div>
              <div>
                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-text-muted">Requested Time Out *</label>
                <input
                  type="time"
                  v-model="correctionForm.corrected_time_out"
                  class="w-full border border-border rounded-lg px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none"
                >
              </div>
            </div>

            <!-- Reason -->
            <div>
              <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-text-muted">Reason for correction *</label>
              <textarea
                v-model="correctionForm.reason"
                rows="4"
                required
                class="w-full border border-border rounded-lg px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none resize-none"
                placeholder="Please explain why you need this correction..."
              ></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="closeCorrectionModal"
                class="rounded-lg border border-border bg-surface-sunken px-4 py-2 text-sm font-medium text-text transition-colors hover:bg-canvas"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="submitting"
                class="bg-accent-solid hover:bg-accent-hover text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
              >
                <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
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
import axios from '@/axios';

export default {
  name: 'MyAttendance',
  data() {
    return {
      loading: true,
      attendances: [],
      currentPage: 1,
      pagination: null,
      filters: {
        startDate: '',
        endDate: '',
        month: ''
      },
      selectedPerPage: 10,
      perPageOptions: [10, 25, 50, 100],
      months: Array.from({ length: 12 }, (_, i) => {
        const month = new Date(0, i);
        return { 
          value: (i + 1).toString(),
          label: month.toLocaleString('default', { month: 'long' })
        };
      }),
      showCorrectionModal: false,
      selectedAttendance: null,
      submitting: false,
      correctionForm: {
        attendance_id: null,
        attendance_date: '',
        corrected_time_in: '',
        corrected_time_out: '',
        corrected_lunch_start: '',
        corrected_lunch_end: '',
        reason: ''
      }
    };
  },
  
  computed: {
    hasActiveFilters() {
      return this.filters.startDate || this.filters.endDate || this.filters.month
    }
  },
  
  mounted() {
    this.initializeFilters();
    this.loadAttendance();
  },
  
  methods: {
    initializeFilters() {
      const now = new Date();
      const dayOfWeek = now.getDay();
      const daysFromMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
      const startOfWeek = new Date(now.getFullYear(), now.getMonth(), now.getDate() - daysFromMonday);
      const endOfWeek = new Date(startOfWeek);
      endOfWeek.setDate(startOfWeek.getDate() + 6);
      
      this.filters.startDate = startOfWeek.toISOString().split('T')[0];
      this.filters.endDate = endOfWeek.toISOString().split('T')[0];
      this.filters.month = '';
    },

    getCorrectionStatusLabel(status) {
      switch (status) {
        case 'pending':  return 'Correction Pending';
        case 'approved': return 'Correction Approved';
        case 'rejected': return 'Correction Rejected';
        default:         return 'Correction';
      }
    },

    getCorrectionStatusClass(status) {
      switch (status) {
        case 'pending':  return 'bg-status-yellow text-status-text';
        case 'approved': return 'bg-status-green text-status-text';
        case 'rejected': return 'bg-status-red text-status-text';
        default:         return 'bg-surface-sunken text-text';
      }
    },

    getCorrectionButtonText(attendance) {
      const s = attendance.correction?.status;
      if (s === 'pending')  return 'Awaiting Approval';
      if (s === 'approved') return 'Approved';
      if (s === 'rejected') return 'Request Again';
      return 'Request Correction';
    },

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
      if (attendance.status === 'complete') {
        return 'Complete';
      }
      if (attendance.status === 'undertime') {
        return 'Undertime';
      }
      
      return null;
    },

    getTimeDisplay(timeString) {
      if (!timeString || timeString === 'On Leave' || timeString === 'Early Leave') {
        return timeString || '--:--';
      }
      return timeString || '--:--';
    },

    getBreakHoursClass(breakHours) {
      if (!breakHours || breakHours === '--:--') {
        return 'text-text-subtle';
      }
      
      const hoursMatch = breakHours.match(/(\d+)h/);
      const minutesMatch = breakHours.match(/(\d+)m/);
      
      const hours = hoursMatch ? parseInt(hoursMatch[1]) : 0;
      const minutes = minutesMatch ? parseInt(minutesMatch[1]) : 0;
      const totalMinutes = (hours * 60) + minutes;
      
      if (totalMinutes > 90) {
        return 'text-warning font-medium';
      } else if (totalMinutes > 60) {
        return 'text-warning font-medium';
      } else if (totalMinutes > 0) {
        return 'text-success';
      }
      
      return 'text-text-subtle';
    },

    canRequestCorrection(attendance) {
      if (!attendance.time_in) return false;
      if (attendance.is_weekend) return false;
      if (attendance.is_on_leave && !attendance.is_partial_leave) return false;
      
      const attendanceDate = new Date(attendance.attendance_date);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      return attendanceDate <= today;
    },

    openCorrectionModal(attendance) {
      this.selectedAttendance = attendance;
      this.showCorrectionModal = true;
      this.correctionForm = {
        attendance_id: attendance.id,
        attendance_date: attendance.attendance_date,
        corrected_time_in: '',
        corrected_time_out: '',
        reason: ''
      };
    },

    closeCorrectionModal() {
      this.showCorrectionModal = false;
      this.selectedAttendance = null;
      this.correctionForm = {
        attendance_id: null,
        attendance_date: '',
        corrected_time_in: '',
        corrected_time_out: '',
        corrected_lunch_start: '',
        corrected_lunch_end: '',
        reason: ''
      };
    },

    async submitCorrection() {
      this.submitting = true;
      try {
        // Prepare the payload, removing empty strings and the attendance_date field
        const payload = {
          attendance_id: this.correctionForm.attendance_id,
          reason: this.correctionForm.reason,
        };

        // Only include time fields if they have values
        if (this.correctionForm.corrected_time_in) {
          payload.corrected_time_in = this.correctionForm.corrected_time_in;
        }
        if (this.correctionForm.corrected_time_out) {
          payload.corrected_time_out = this.correctionForm.corrected_time_out;
        }
        if (this.correctionForm.corrected_lunch_start) {
          payload.corrected_lunch_start = this.correctionForm.corrected_lunch_start;
        }
        if (this.correctionForm.corrected_lunch_end) {
          payload.corrected_lunch_end = this.correctionForm.corrected_lunch_end;
        }

        const resp = await axios.post('/user/attendance-corrections', payload);

        // Check if the response indicates success
        if (resp.data?.success === false) {
          this.showToast(resp.data?.message || 'Failed to submit correction request', 'error');
          this.submitting = false;
          return;
        }

        const serverCorrection = resp?.data?.data;

        if (this.selectedAttendance) {
          // Vue 3: Direct assignment instead of $set
          this.selectedAttendance.correction = serverCorrection ? {
            id: serverCorrection.id,
            status: serverCorrection.status,
            corrected_time_in: serverCorrection.corrected_time_in,
            corrected_time_out: serverCorrection.corrected_time_out,
            corrected_lunch_start: serverCorrection.corrected_lunch_start,
            corrected_lunch_end: serverCorrection.corrected_lunch_end,
            reason: serverCorrection.reason,
            updated_at: serverCorrection.updated_at,
          } : {
            id: null,
            status: 'pending',
            corrected_time_in: this.correctionForm.corrected_time_in,
            corrected_time_out: this.correctionForm.corrected_time_out,
            corrected_lunch_start: this.correctionForm.corrected_lunch_start,
            corrected_lunch_end: this.correctionForm.corrected_lunch_end,
            reason: this.correctionForm.reason,
            updated_at: new Date().toISOString(),
          };
        }

        this.showToast(resp.data?.message || 'Correction request submitted successfully!', 'success');
        this.closeCorrectionModal();
        
        // Reload attendance to show updated data
        await this.loadAttendance();
      } catch (error) {
        console.error('Correction submission error:', error);
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat();
          this.showToast(errors[0] || 'Validation failed', 'error');
        } else {
          this.showToast(error.response?.data?.message || 'Failed to submit correction request', 'error');
        }
      } finally {
        this.submitting = false;
      }
    },
    
    async loadAttendance() {
      try {
        this.loading = true;
        const params = { 
          page: this.currentPage,
        };
        
        if (this.selectedPerPage !== 10) {
          params.per_page = this.selectedPerPage;
        }
        
        if (this.filters.startDate) params.startDate = this.filters.startDate;
        if (this.filters.endDate) params.endDate = this.filters.endDate;
        if (this.filters.month) params.month = this.filters.month;
        
        const { data } = await axios.get('/user/my-attendance', { params });
        
        this.attendances = data.data;
        this.pagination = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
          from: data.from,
          to: data.to
        };
      } catch (error) {
        this.showToast('Failed to load attendance data', 'error');
      } finally {
        this.loading = false;
      }
    },
    
    applyFilters() {
      if (this.filters.month) {
        this.filters.startDate = '';
        this.filters.endDate = '';
      } else {
        if (!this.filters.startDate || !this.filters.endDate) {
          this.showToast('Please select a valid date range', 'warning');
          return;
        }
      }
      
      this.currentPage = 1;
      this.loadAttendance();
    },

    clearFilters() {
      this.filters = {
        startDate: '',
        endDate: '',
        month: ''
      };
      this.selectedPerPage = 10;
      this.currentPage = 1;
      
      this.initializeFilters();
      this.loadAttendance();
    },

    changePerPage() {
      this.currentPage = 1;
      this.loadAttendance();
    },
    
    async changePage(page) {
      if (page === '...' || page < 1 || (this.pagination && page > this.pagination.last_page)) {
        return;
      }
      
      this.currentPage = page;
      await this.loadAttendance();
      window.scrollTo({ top: 0, behavior: 'smooth' });
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
        type === 'error'   ? 'bg-danger'  :
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
  }
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>