<template>
  <div class="p-2 sm:p-4 bg-canvas min-h-screen overflow-x-hidden">

    <!-- ================================================================
         Approval dialog: ONE modal, two views.
         A request card opens the list; picking a row swaps the same shell
         over to the detail view, with a back arrow instead of a second
         stacked modal. One scrim, one focus context, one Escape target.
         ================================================================ -->
    <div v-if="approvalModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4">
      <div class="absolute inset-0 bg-text/40" @click="closeApprovalModal"></div>

      <div
        ref="approvalModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="approval-modal-title"
        tabindex="-1"
        class="relative z-10 flex max-h-[88vh] w-full flex-col overflow-hidden rounded-xl bg-surface shadow-2xl outline-none sm:rounded-2xl"
        :class="showingDetail ? 'max-w-2xl' : 'max-w-3xl'"
      >
        <div class="h-1 flex-shrink-0 bg-accent"></div>

        <!-- Header swaps with the view -->
        <div class="flex flex-shrink-0 items-start gap-3 border-b border-border px-4 py-3 sm:px-6 sm:py-4">
          <button
            v-if="showingDetail"
            @click="backToList"
            class="-ml-1 mt-0.5 flex-shrink-0 rounded p-1.5 text-text-muted transition-colors hover:bg-surface-sunken hover:text-text"
            aria-label="Back to list"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>

          <img v-if="showingDetail" :src="selectedRequest.avatar" :alt="selectedRequest.name"
               class="h-11 w-11 flex-shrink-0 rounded-full ring-2 ring-accent/30" @error="handleImageError">

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h2 id="approval-modal-title" class="truncate text-base font-semibold text-text sm:text-lg">
                {{ showingDetail ? getRequestTypeLabel(selectedRequest.type) : listModalTitle }}
              </h2>
              <span v-if="showingDetail" :class="getStatusBadgeClass(selectedRequest.status)"
                    class="rounded px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide">
                {{ selectedRequest.status }}
              </span>
            </div>
            <p class="mt-0.5 truncate text-sm text-text-muted">
              <template v-if="showingDetail">
                {{ selectedRequest.name }}
                <span v-if="selectedRequest.created_at"> &middot; {{ formatRelativeDate(selectedRequest.created_at) }}</span>
              </template>
              <template v-else>
                {{ pendingCount }} pending {{ pendingCount === 1 ? 'request' : 'requests' }}
              </template>
            </p>
          </div>

          <button @click="closeApprovalModal" class="-mr-1 flex-shrink-0 p-2 text-text-subtle hover:text-text-muted" aria-label="Close">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Body swaps with the view -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
          <template v-if="showingDetail">
          <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-muted">Request type</dt>
              <dd class="mt-1 text-sm text-text">{{ selectedRequest.leave_type || getRequestTypeLabel(selectedRequest.type) }}</dd>
            </div>

            <div v-if="selectedRequest.start_date">
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-muted">
                {{ selectedRequest.end_date && selectedRequest.end_date !== selectedRequest.start_date ? 'Dates' : 'Date' }}
              </dt>
              <dd class="mt-1 text-sm text-text">
                {{ formatRequestDate(selectedRequest.start_date) }}
                <template v-if="selectedRequest.end_date && selectedRequest.end_date !== selectedRequest.start_date">
                  &ndash; {{ formatRequestDate(selectedRequest.end_date) }}
                </template>
              </dd>
            </div>

            <div v-if="selectedRequest.duration">
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-muted">Duration</dt>
              <dd class="mt-1 text-sm text-text">{{ selectedRequest.duration }}</dd>
            </div>

            <div v-if="selectedRequest.email">
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-muted">Email</dt>
              <dd class="mt-1 truncate text-sm text-text">{{ selectedRequest.email }}</dd>
            </div>

            <div>
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-text-muted">Submitted</dt>
              <dd class="mt-1 text-sm text-text">{{ selectedRequest.submitted_at || formatRequestDate(selectedRequest.created_at) }}</dd>
            </div>
          </dl>

          <div class="my-5 border-t border-border"></div>

          <div v-if="selectedRequest.type === 'shift'" class="mb-4 sm:mb-6 p-3 sm:p-4 bg-status-purple/60 rounded-lg">
            <h3 class="text-xs sm:text-sm font-medium text-text mb-2 sm:mb-3">Shift Change Details</h3>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 text-xs sm:text-sm">
              <div v-if="selectedRequest.current_shift">
                <span class="text-text-muted">Current Shift:</span>
                <span class="ml-2 text-text">{{ selectedRequest.current_shift }}</span>
              </div>
              <div v-if="selectedRequest.requested_shift">
                <span class="text-text-muted">Requested Shift:</span>
                <span class="ml-2 text-text font-semibold">{{ selectedRequest.requested_shift }}</span>
              </div>
              <div v-if="selectedRequest.effective_date" class="col-span-2">
                <span class="text-text-muted">Effective Date:</span>
                <span class="ml-2 text-text">{{ selectedRequest.effective_date }}</span>
              </div>
            </div>
          </div>

          <div v-if="selectedRequest.type === 'timesheet' && selectedRequest.timesheet_detail" class="mb-4 sm:mb-6 overflow-hidden rounded-lg border border-border">
            <div class="flex items-center justify-between gap-3 border-b border-border bg-status-green px-3 py-2 sm:px-4">
              <h3 class="text-xs sm:text-sm font-medium text-status-text">
                Time Entries &middot; {{ selectedRequest.timesheet_detail.week_label }}
              </h3>
              <span class="flex-shrink-0 rounded bg-surface px-2 py-0.5 text-[11px] font-semibold text-text">
                {{ formatSheetHours(selectedRequest.timesheet_detail.total_hours) }} hrs
              </span>
            </div>

            <div v-if="!selectedRequest.timesheet_detail.entries || !selectedRequest.timesheet_detail.entries.length" class="px-3 py-6 text-center sm:px-4">
              <p class="text-sm text-text-muted">No lines on this timesheet.</p>
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full min-w-[720px]">
                <thead class="border-b border-border bg-surface-sunken">
                  <tr>
                    <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-text-muted">Project / Task</th>
                    <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-text-muted">Time Type</th>
                    <th v-for="day in selectedRequest.timesheet_detail.days" :key="day.key"
                        :class="['px-2 py-2 text-center text-[11px] font-semibold uppercase tracking-wider', day.is_weekend ? 'text-text-subtle' : 'text-text-muted']">
                      <span class="block">{{ day.label }}</span>
                      <span class="block text-[10px] font-normal">{{ day.day_of_month }}</span>
                      <span class="block text-[11px] font-semibold text-text">{{ formatSheetHours(day.total) }}</span>
                    </th>
                    <th class="px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wider text-text-muted">Total</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-border">
                  <tr v-for="entry in selectedRequest.timesheet_detail.entries" :key="entry.id">
                    <td class="px-3 py-2 align-top">
                      <span class="block text-sm text-text">{{ entry.project_name || '—' }}</span>
                      <span v-if="entry.project_ticket" class="block text-[11px] text-text-muted">{{ entry.project_ticket }}</span>
                      <span v-if="entry.memo" class="block text-[11px] text-text-muted">{{ entry.memo }}</span>
                    </td>
                    <td class="px-3 py-2 align-top">
                      <span v-if="entry.time_type" :class="sheetTintClass(entry.time_type.tint)"
                            class="inline-block rounded px-2 py-0.5 text-[11px] font-medium">
                        {{ entry.time_type.name }}
                      </span>
                      <span v-else class="text-[11px] text-text-subtle">&mdash;</span>
                    </td>
                    <td v-for="day in selectedRequest.timesheet_detail.days" :key="day.key"
                        :class="['px-2 py-2 text-center text-sm align-top', day.is_weekend ? 'text-text-muted' : 'text-text']">
                      {{ entry[day.key + '_hours'] ? formatSheetHours(entry[day.key + '_hours']) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-center align-top text-sm font-semibold text-text">
                      {{ formatSheetHours(entry.row_total) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="selectedRequest.reason" class="mb-4 sm:mb-6">
            <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-text-muted">Reason / Notes</p>
            <div class="rounded-lg border border-border bg-surface-sunken p-3 sm:p-4">
              <p class="text-sm text-text whitespace-pre-wrap">{{ selectedRequest.reason }}</p>
            </div>
          </div>

          <div v-if="(selectedRequest.status === 'pending' || selectedRequest.status === 'PENDING') && ['leave','shift','timesheet'].includes(selectedRequest.type)" class="sticky bottom-0 -mx-4 sm:-mx-6 mt-4 border-t border-border bg-surface px-4 sm:px-6 py-3">
            <div v-if="showRejectionNote" class="w-full mb-4">
              <label class="block text-xs sm:text-sm font-medium text-text mb-2">Reason for Rejection</label>
              <textarea 
                v-model="rejectionNote" 
                placeholder="Please provide a reason for rejecting this request..."
                rows="3"
                class="w-full text-sm border border-border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-danger resize-none"
              ></textarea>
              <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 mt-3">
                <button @click="handleRequestAction('reject')" :disabled="!rejectionNote.trim() || processing" class="w-full sm:flex-1 px-4 py-2 bg-danger hover:bg-danger/90 disabled:bg-danger/40 text-white text-sm rounded-lg">
                  {{ processing ? 'Rejecting...' : 'Confirm Rejection' }}
                </button>
                <button @click="cancelRejection" class="w-full sm:flex-1 px-4 py-2 bg-surface-sunken hover:bg-canvas border border-border text-text text-sm rounded-lg">
                  Cancel
                </button>
              </div>
            </div>

            <div v-else class="w-full flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
              <button 
                @click="handleRequestAction('approve')" 
                :disabled="processing"
                class="w-full sm:flex-1 px-4 sm:px-6 py-2 sm:py-3 bg-success hover:bg-success/90 disabled:bg-success/40 text-white text-sm sm:text-base font-medium rounded-xl transition-colors flex items-center justify-center space-x-2"
              >
                <svg v-if="!processing" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ processing ? 'Processing...' : 'Approve' }}</span>
              </button>
              
              <button 
                @click="showRejectionNote = true" 
                :disabled="processing"
                class="w-full sm:flex-1 px-4 sm:px-6 py-2 sm:py-3 bg-danger hover:bg-danger/90 disabled:bg-danger/40 text-white text-sm sm:text-base font-medium rounded-xl transition-colors flex items-center justify-center space-x-2"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>Reject</span>
              </button>
            </div>
          </div>

          <div v-else-if="!['leave','shift','timesheet'].includes(selectedRequest.type)" class="sticky bottom-0 -mx-4 sm:-mx-6 mt-4 border-t border-border bg-surface px-4 sm:px-6 py-3">
            <div class="text-center py-4">
              <span class="inline-flex items-center px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium bg-surface-sunken text-text">
                This request type can only be viewed here. Please use the appropriate module to approve/reject.
              </span>
            </div>
          </div>

          <div v-else class="sticky bottom-0 -mx-4 sm:-mx-6 mt-4 border-t border-border bg-surface px-4 sm:px-6 py-3">
            <div class="text-center py-4">
              <span :class="getStatusBadgeClass(selectedRequest.status)" class="inline-flex items-center px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium">
                {{ selectedRequest.status === 'approved' ? 'Already Approved' : 'Already Rejected' }}
              </span>
            </div>
          </div>
          </template>


          <template v-else>
          <div class="space-y-2 sm:space-y-3">
            <button
              v-for="request in getRequestsByType(listModalType)"
              :key="`${request.type}-${request.id}`"
              @click="viewRequest(request)"
              class="flex w-full items-center gap-3 rounded-lg border border-border bg-surface-sunken px-3 py-2.5 text-left transition-colors hover:border-accent hover:bg-canvas"
            >
              <img :src="request.avatar" :alt="request.name" class="h-9 w-9 flex-shrink-0 rounded-full" @error="handleImageError">

              <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                  <span class="truncate text-sm font-semibold text-text">{{ request.name }}</span>
                  <span :class="getStatusBadgeClass(request.status)" class="flex-shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide">
                    {{ request.status }}
                  </span>
                </span>
                <span class="mt-0.5 block truncate text-xs text-text-muted">
                  {{ request.leave_type || getRequestTypeLabel(request.type) }}
                  <template v-if="request.duration"> &middot; {{ request.duration }}</template>
                </span>
                <span class="block text-[11px] text-text-muted">{{ formatRelativeDate(request.created_at) }}</span>
              </span>

              <span class="flex flex-shrink-0 items-center gap-2 text-right">
                <span>
                  <span class="block whitespace-nowrap text-xs font-medium text-text">{{ formatCompactDate(request.start_date) }}</span>
                  <span v-if="request.start_date !== request.end_date" class="block whitespace-nowrap text-[11px] text-text-muted">
                    &ndash; {{ formatCompactDate(request.end_date) }}
                  </span>
                </span>
                <svg class="h-4 w-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </span>
            </button>

            <div v-if="getRequestsByType(listModalType).length === 0" class="py-10 text-center">
              <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-surface-sunken">
                <svg class="h-6 w-6 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <p class="text-sm text-text-muted">Nothing left to approve here.</p>
            </div>
          </div>
          </template>

        </div>
      </div>
    </div>

    <!-- Time Out Confirmation Modal -->
    <div v-if="showTimeOutConfirmation" class="fixed inset-0 flex items-center justify-center z-50 p-4">
      <div class="absolute inset-0 bg-text/40" @click="showTimeOutConfirmation = false"></div>
      <div class="bg-surface rounded-xl sm:rounded-2xl p-4 sm:p-6 max-w-md w-full mx-4 transform transition-all shadow-2xl relative z-10">
        <div class="text-center">
          <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 bg-red-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
          </div>
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">Confirm Time Out</h3>
          <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">Are you sure you want to time out? This will end your work day and any active breaks.</p>
          
          <div class="bg-gray-50 rounded-lg p-3 sm:p-4 mb-4 sm:mb-6 text-left">
            <div class="grid grid-cols-2 gap-3 sm:gap-4 text-xs sm:text-sm">
              <div>
                <span class="text-gray-500">Time In:</span>
                <span class="font-medium text-gray-900 ml-2">{{ formatTo12Hour(timeIn) }}</span>
              </div>
              <div>
                <span class="text-gray-500">Working Hours:</span>
                <span class="font-medium text-gray-900 ml-2">{{ workingHours }}</span>
              </div>
            </div>
            <div v-if="activeBreak" class="mt-2 pt-2 border-t border-gray-200">
              <span class="text-amber-600 text-xs">⚠️ Active {{ activeBreak.label || activeBreak.type }} will be ended</span>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
            <button @click="showTimeOutConfirmation = false" class="w-full sm:flex-1 px-4 py-2 text-sm sm:text-base text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
              Cancel
            </button>
            <button @click="confirmTimeOut" :disabled="clockingIn" class="w-full sm:flex-1 px-4 py-2 text-sm sm:text-base text-white bg-red-600 hover:bg-red-700 rounded-xl transition-colors disabled:opacity-50">
              {{ clockingIn ? 'Processing...' : 'Time Out' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Greeting -->
    <div class="flex items-center justify-between mb-3 sm:mb-4 bg-white shadow-sm px-4 sm:px-6 py-3 rounded-xl sm:rounded-2xl">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Hello, {{ firstName }}!</h1>
    </div>

    <!-- Attendance card -->
    <!-- z-20 + no overflow-hidden here: the action dropdown has to escape this
         card. The watermark gets its own clipping wrapper instead. -->
    <div class="bg-gray-900 rounded-xl sm:rounded-2xl p-4 sm:p-6 text-white relative z-20 mb-3 sm:mb-4">
      <div class="absolute inset-0 overflow-hidden rounded-xl sm:rounded-2xl pointer-events-none">
        <div class="absolute right-0 top-0 w-40 h-40 sm:w-64 sm:h-64 opacity-10">
          <img src="@/assets/cwgp-logo.webp" alt="" class="w-full h-full object-cover" />
        </div>
      </div>

      <div class="relative z-10">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-4 sm:mb-6">
          <div class="min-w-0">
            <p class="text-white/70 text-xs sm:text-sm mb-1">Current Date</p>
            <p class="text-base sm:text-lg lg:text-xl font-semibold truncate">
              {{ currentTime }}, {{ formatCurrentDate() }}
            </p>
            <div class="flex items-center space-x-2 mt-2">
              <div :class="['w-2 h-2 rounded-full', statusDotClass]"></div>
              <span class="text-xs sm:text-sm text-white/70">{{ status }}</span>
            </div>
          </div>

          <!-- Time in / out, with the break actions in a dropdown -->
          <div class="relative sm:w-56 flex-shrink-0">
            <button @click="showTimeActionDropdown = !showTimeActionDropdown" :disabled="clockingIn || loading" :class="['w-full rounded-lg sm:rounded-xl px-4 py-3 transition-colors flex items-center justify-between gap-2', clockingIn || loading ? 'opacity-50 cursor-not-allowed' : 'shadow-lg', getButtonColor()]">
              <span class="flex items-center gap-2 min-w-0">
                <svg v-if="clockingIn" class="w-4 h-4 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="font-semibold truncate">{{ clockingIn ? 'Processing...' : getCurrentActionText() }}</span>
              </span>
              <svg :class="['w-4 h-4 flex-shrink-0 transition-transform', showTimeActionDropdown ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

          <!-- Dropdown Menu -->
          <div v-if="showTimeActionDropdown && !clockingIn && !loading"
               class="absolute top-full left-0 right-0 z-30 mt-2 overflow-hidden rounded-xl border border-border bg-surface shadow-xl sm:rounded-2xl">
            <button
              v-for="action in timeActions"
              :key="action.key"
              @click="handleTimeAction(action.key)"
              class="flex w-full items-center gap-3 border-b border-border px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-surface-sunken"
            >
              <span :class="['flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg', action.tint]">
                <svg class="h-4 w-4 text-status-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="action.icon" />
                </svg>
              </span>
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-medium text-text">{{ action.label }}</span>
                <span class="block text-xs text-text-muted">{{ action.hint }}</span>
              </span>
            </button>
          </div>

          <div v-if="showTimeActionDropdown" @click="showTimeActionDropdown = false" class="fixed inset-0 z-0"></div>
          </div>
        </div>

        <!-- Glass tiles, per the design spec: white at 10%, hairline at 50% -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-4">
          <div class="bg-white/10 border border-white/50 rounded-lg p-3">
            <p class="text-white/70 text-xs mb-1">Time In</p>
            <p class="text-lg sm:text-xl font-bold truncate">{{ formatTo12Hour(timeIn) || '--:--' }}</p>
          </div>
          <div class="bg-white/10 border border-white/50 rounded-lg p-3">
            <p class="text-white/70 text-xs mb-1">Time Out</p>
            <p class="text-lg sm:text-xl font-bold truncate">{{ formatTo12Hour(timeOut) || '--:--' }}</p>
          </div>
          <div class="bg-white/10 border border-white/50 rounded-lg p-3">
            <p class="text-white/70 text-xs mb-1">Total Working Hours</p>
            <p class="text-lg sm:text-xl font-bold truncate">{{ workingHours || '--.--' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Leave credits -->
    <div class="bg-surface rounded-xl sm:rounded-2xl shadow-sm p-3 sm:p-4 mb-3 sm:mb-4">
      <h2 class="text-xs sm:text-sm font-semibold tracking-wide text-text-muted uppercase mb-3">Available Leave Credits</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 sm:gap-3">
        <div v-for="credit in leaveCreditTiles" :key="credit.label" class="rounded-lg border border-border px-3 py-2">
          <p class="text-xs text-text-muted">{{ credit.label }}</p>
          <p class="text-base sm:text-lg font-bold text-text">
            {{ credit.value }}<span class="text-xs font-medium text-text-muted ml-0.5">{{ credit.unit }}</span>
          </p>
        </div>
      </div>
    </div>

    <!-- For My Approval -->
    <div class="bg-surface rounded-xl sm:rounded-2xl shadow-sm p-3 sm:p-4 mb-3 sm:mb-4">
      <h2 class="text-xs sm:text-sm font-semibold tracking-wide text-text-muted uppercase mb-3">Requests</h2>

      <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3 sm:gap-3">
        <button
          v-for="card in requestCards"
          :key="card.type"
          @click="expandRequestType(card.type)"
          :class="['flex items-center gap-3 rounded-lg border px-3 py-2.5 text-left transition-colors',
                   card.count > 0 ? [card.fill, 'border-border hover:brightness-95'] : 'bg-surface-sunken border-border hover:bg-canvas']"
        >
          <span :class="['flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-md',
                         card.count > 0 ? 'bg-white/70 text-text' : 'bg-border text-text-muted']">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon" />
            </svg>
          </span>

          <span class="min-w-0 flex-1">
            <span :class="['block truncate text-sm font-semibold', card.count > 0 ? 'text-status-text' : 'text-text']">{{ card.label }}</span>
            <span :class="['block text-xs', card.count > 0 ? 'text-status-text/70' : 'text-text-muted']">
              {{ card.count > 0 ? `${card.count} pending` : 'Nothing pending' }}
            </span>
          </span>

          <span v-if="card.count > 0" class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-accent text-xs font-bold text-text">
            {{ card.count }}
          </span>
        </button>
      </div>

      <div v-if="requests.length === 0 && !loading" class="mt-3 text-center text-sm text-gray-500">
        All caught up -- nothing needs your attention right now.
      </div>
    </div>

    <!-- Announcements + holidays, matched height -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 sm:gap-4 items-stretch lg:min-h-[420px]">
      <!-- Holidays for the current month -- the heading follows the calendar -->
      <div class="lg:col-span-2 min-h-[260px] lg:min-h-0">
        <div class="bg-surface rounded-xl sm:rounded-2xl shadow-sm flex h-full flex-col">
          <div class="p-3 sm:p-4 pb-2 sm:pb-3 border-b border-border flex-shrink-0">
            <h3 class="text-xs sm:text-sm font-semibold tracking-wide text-text-muted uppercase">
              Holiday for the month of {{ holidayMonthName }}
            </h3>
          </div>

          <div class="flex-1 overflow-y-auto p-4 sm:p-5">
            <div v-if="loadingHolidays" class="text-sm text-gray-400">Loading holidays...</div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div v-for="group in holidayGroups" :key="group.key">
                <p :class="['text-sm font-semibold mb-2', group.headingClass]">{{ group.label }}</p>
                <ul v-if="group.items.length" class="space-y-2">
                  <li v-for="holiday in group.items" :key="holiday.id" class="text-sm text-gray-700">
                    <span class="font-medium">{{ holiday.name }}</span>
                    <span class="block text-xs text-gray-500">{{ holiday.date_label }} &middot; {{ holiday.day_label }}</span>
                  </li>
                </ul>
                <p v-else class="text-sm text-gray-500">None</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="lg:col-span-3 min-h-[320px] lg:min-h-0">
      <div class="bg-surface rounded-xl sm:rounded-2xl shadow-sm overflow-hidden flex h-full flex-col">
        <div class="p-3 sm:p-4 pb-2 sm:pb-3 border-b border-border flex-shrink-0">
          <div class="flex items-center justify-between">
            <h3 class="text-xs sm:text-sm font-semibold tracking-wide text-text-muted uppercase">Company Announcement</h3>
          </div>
        </div>

        <div class="flex-1 flex flex-col overflow-hidden" v-if="bulletinImages.length > 0">
          <div class="relative flex-1 overflow-hidden">
            <div class="absolute top-0 left-0 right-0 bg-gradient-to-b from-black/80 to-transparent z-10 p-3 sm:p-4">
              <h4 class="text-white text-sm sm:text-lg font-bold mb-0.5 sm:mb-1 truncate">{{ bulletinImages[currentBulletinSlide]?.title || 'Company Update' }}</h4>
              <p class="text-white/80 text-xs sm:text-sm">{{ formatFullDate(bulletinImages[currentBulletinSlide]?.created_at) }}</p>
            </div>

            <div class="flex transition-transform duration-500 ease-in-out h-full" :style="{ transform: `translateX(-${currentBulletinSlide * 100}%)` }">
              <div v-for="(image, index) in bulletinImages" :key="index" class="w-full flex-shrink-0 relative group cursor-pointer h-full overflow-hidden bg-gray-100" @click="openImageModal(image)">
                <div class="relative flex items-center justify-center h-full p-2">
                  <img :src="image.url" :alt="image.title || 'Company Announcement'" class="max-w-full max-h-full object-contain transition-transform duration-300 group-hover:scale-105" @error="handleImageError">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-black/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                  <div class="bg-white/90 text-gray-800 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg shadow-lg">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 inline mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                    </svg>
                    <span class="text-xs sm:text-sm">Click to view</span>
                  </div>
                </div>
              </div>
            </div>

            <button v-if="bulletinImages.length > 1" @click="prevBulletinSlide" class="absolute left-2 sm:left-4 top-1/2 transform -translate-y-1/2 w-8 h-8 sm:w-12 sm:h-12 bg-black/60 hover:bg-black/80 text-white rounded-full flex items-center justify-center transition-colors z-20 shadow-lg">
              <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
            </button>
            <button v-if="bulletinImages.length > 1" @click="nextBulletinSlide" class="absolute right-2 sm:right-4 top-1/2 transform -translate-y-1/2 w-8 h-8 sm:w-12 sm:h-12 bg-black/60 hover:bg-black/80 text-white rounded-full flex items-center justify-center transition-colors z-20 shadow-lg">
              <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>

          <div v-if="bulletinImages.length > 1" class="flex justify-center space-x-1.5 sm:space-x-2 py-3 sm:py-4 px-4 sm:px-6 border-t border-gray-100">
            <button v-for="(image, index) in bulletinImages" :key="index" @click="currentBulletinSlide = index" :class="['w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full transition-all', currentBulletinSlide === index ? 'bg-blue-500 w-4 sm:w-6' : 'bg-gray-300 hover:bg-gray-400']"></button>
          </div>
        </div>

        <div v-else class="flex-1 flex items-center justify-center p-6 sm:p-12">
          <div class="text-center">
            <div class="w-16 h-16 sm:w-24 sm:h-24 mx-auto mb-4 sm:mb-6 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl sm:rounded-2xl flex items-center justify-center">
              <svg class="w-8 h-8 sm:w-12 sm:h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
              </svg>
            </div>
            <h4 class="text-base sm:text-lg font-semibold text-gray-700 mb-2">No Announcements</h4>
            <p class="text-sm sm:text-base text-gray-500">Check back later for company updates and announcements.</p>
          </div>
        </div>
      </div>
      </div>
      <!-- Image Modal -->
      <div v-if="viewImageModal" class="fixed inset-0 bg-black/90 flex items-center justify-center z-50 p-4" @click="viewImageModal = null">
        <div class="max-w-4xl w-full max-h-full flex flex-col">
          <img :src="viewImageModal.url" :alt="viewImageModal.title" class="max-w-full max-h-[80vh] object-contain rounded-lg" @click.stop>
          <div v-if="viewImageModal.title" class="text-center mt-4">
            <p class="text-white text-base sm:text-lg font-medium">{{ viewImageModal.title }}</p>
            <p class="text-white/80 text-sm">{{ formatFullDate(viewImageModal.created_at) }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios';

export default {
  data() {
    return {
      loading: false,
      currentTime: '',
      currentDate: '',
      status: 'OUT',
      isLoggedIn: false,
      showTimeActionDropdown: false,
      dailyNotes: '',
      userId: null,
      timeIn: null,
      timeOut: null,
      workingHours: '0h 0m',
      breakTimeRemaining: '—',
      bulletinImages: [],
      viewImageModal: null,
      currentBulletinSlide: 0,
      activeBreak: null,
      clockingIn: false,
      refreshInterval: null,
      slideInterval: null,
      bulletinSlideInterval: null,
      timeTicker: null,
      currentSlide: 0,
      showTimeOutConfirmation: false,
      isAutoTimeOut: false,
      autoTimeOutInterval: null,
      announcements: [],
      vlRemaining: 0,
      slRemaining: 0,
      vlCarryover: 0,
      birthdayLeave: 1,
      ptoRemaining: 0,
      ctoRemainingHours: 0,
      holidays: { partners: [], people: [] },
      holidayMonthLabel: '',
      loadingHolidays: false,
      userRole: null,
      requests: [],
      listModalType: null,
      selectedRequest: null,
      processing: false,
      rejectionNote: '',
      showRejectionNote: false,
      userName: '',
      userPermissions: [],
    };
  },

  computed: {
    statusDotClass() {
      if (!this.isLoggedIn) return 'bg-red-400';
      if (this.activeBreak) return 'bg-yellow-300 animate-pulse';
      return 'bg-green-400 animate-pulse';
    },
    
    displayBirthdayLeave() {
      const leave = parseFloat(this.birthdayLeave);
      return leave > 0 ? Math.floor(leave) : 0;
    },
    
    birthdayLeaveStatus() {
      const leave = parseFloat(this.birthdayLeave);
      return leave > 0 ? 'Available' : 'Used';
    },

    // "Juan Dela Cruz" -> "Juan". Falls back to the whole string when the
    // account has no space in its name (e.g. an email-only login).
    // Mirrors the original conditional dropdown exactly:
    //   clocked out          -> Time In
    //   clocked in, no break -> Time Out / Lunch / BRB
    //   on a break           -> End <break>
    timeActions() {
      if (!this.isLoggedIn) {
        return [{ key: 'time_in', label: 'Time In', hint: 'Start your work day', tint: 'bg-status-green',
                  icon: 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1' }];
      }

      if (this.activeBreak) {
        return [{ key: 'break_end', label: `End ${this.activeBreak.label || this.activeBreak.type}`,
                  hint: 'Return to work', tint: 'bg-status-green',
                  icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }];
      }

      return [
        { key: 'time_out',    label: 'Time Out',          hint: 'End your work day',    tint: 'bg-status-red',
          icon: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1' },
        { key: 'break_lunch', label: 'Start Lunch Break', hint: 'Take your lunch break', tint: 'bg-status-yellow',
          icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
        { key: 'break_brb',   label: 'Be Right Back',     hint: 'Quick break (BRB)',     tint: 'bg-status-purple',
          icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
      ];
    },

    requestCards() {
      return [
        { type: 'leave',    label: 'Leave Requests', fill: 'bg-status-blue',
          icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
        { type: 'shift',    label: 'Shift Changes',  fill: 'bg-status-purple',
          icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
        { type: 'timesheet', label: 'Time Entries',   fill: 'bg-status-green',
          icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
      ].map(card => ({ ...card, count: this.getRequestsByType(card.type).length }));
    },

    // The dialog is open whenever a type is selected; the detail view is just
    // a second face of the same shell.
    approvalModalOpen() {
      return this.listModalType !== null;
    },

    showingDetail() {
      return this.selectedRequest !== null;
    },

    pendingCount() {
      return this.listModalType ? this.getRequestsByType(this.listModalType).length : 0;
    },

    listModalTitle() {
      const titles = {
        leave: 'Leave Requests',
        shift: 'Shift Change Requests',
        timesheet: 'Weekly Time Entries',
      };
      return titles[this.listModalType] || 'Requests';
    },

    leaveCreditTiles() {
      return [
        { label: 'PTO', value: this.ptoRemaining, unit: 'd' },
        { label: 'VL', value: this.vlRemaining, unit: 'd' },
        { label: 'SL', value: this.slRemaining, unit: 'd' },
        { label: 'BDO', value: this.displayBirthdayLeave, unit: 'd' },
        { label: 'CTO', value: this.ctoRemainingHours, unit: 'hrs' },
      ];
    },

    // Falls back to the browser's month so the heading is never blank while
    // the request is still in flight.
    holidayMonthName() {
      return this.holidayMonthLabel || new Date().toLocaleDateString('en-US', { month: 'long' });
    },

    holidayGroups() {
      return [
        { key: 'partners', label: 'Partners', headingClass: 'text-accent', items: this.holidays.partners || [] },
        { key: 'people', label: 'People', headingClass: 'text-accent', items: this.holidays.people || [] },
      ];
    },

    firstName() {
      if (!this.userName) return 'there';
      return this.userName.trim().split(/\s+/)[0];
    },

  },

  async mounted() {
    this.userId = this.getUserId();
    this.userName = this.getUserName();
    document.addEventListener('keydown', this.onModalKeydown);
    this.updateTime();
    this.startIntervals();

    Promise.all([
      this.loadUserPermissions(),
      this.loadDashboardData(),
      this.loadBulletinImages(),
      this.loadHolidays()
    ]).catch(() => {});
  },

  watch: {
    // Lock the page behind the dialog and park focus inside it.
    approvalModalOpen(open) {
      document.body.style.overflow = open ? 'hidden' : '';
      if (open) {
        this.$nextTick(() => this.$refs.approvalModal?.focus());
      }
    },
  },

  beforeUnmount() {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', this.onModalKeydown);
    this.clearIntervals();
    if (this.timeTicker) clearInterval(this.timeTicker);
    if (this.bulletinSlideInterval) clearInterval(this.bulletinSlideInterval);
  },

  methods: {
    // No month/year params: the API defaults to the current month, so the
    // panel rolls over on its own.
    async loadHolidays() {
      try {
        this.loadingHolidays = true;
        const { data } = await axios.get('/user/holidays/month');

        if (data.success) {
          this.holidays = {
            partners: data.data.partners || [],
            people: data.data.people || [],
          };
          this.holidayMonthLabel = data.data.month_name || '';
        }
      } catch (error) {
        // A missing 'view holidays' permission just leaves the panel empty.
        this.holidays = { partners: [], people: [] };
      } finally {
        this.loadingHolidays = false;
      }
    },

    getUserName() {
      try {
        for (const store of [localStorage, sessionStorage]) {
          const raw = store.getItem('user');
          if (raw) {
            const u = JSON.parse(raw);
            if (u.name || u.username || u.email) return u.name || u.username || u.email;
          }
        }
      } catch (e) {
        // fall through to the default below
      }
      return '';
    },

    getUserId() {
      const userData = JSON.parse(localStorage.getItem('user') || '{}');
      if (userData.id) return userData.id;
      
      const token = localStorage.getItem('auth_token');
      if (token) {
        try {
          const payload = JSON.parse(atob(token.split('.')[1]));
          return payload.sub || payload.user_id;
        } catch (e) {
          return null;
        }
      }
      return null;
    },

    async loadUserPermissions() {
      try {
        const userData = JSON.parse(localStorage.getItem('user') || '{}');
        this.userRole = userData.role || null;
        this.userPermissions = userData.permissions || [];

        if (!this.userRole) {
          const { data } = await axios.get('/user/profile');
          this.userRole = data.data?.role || data.role || null;
          this.userPermissions = data.data?.permissions || data.permissions || [];
        }
      } catch (error) {
        this.userRole = null;
        this.userPermissions = [];
      }
    },

    startIntervals() {
      this.timeTicker = setInterval(this.updateTime, 1000);
      this.slideInterval = setInterval(() => {
        if (this.announcements.length > 0) {
          this.currentSlide = (this.currentSlide + 1) % this.announcements.length;
        }
      }, 5000);
      this.bulletinSlideInterval = setInterval(() => {
        if (this.bulletinImages.length > 1) {
          this.nextBulletinSlide();
        }
      }, 8000);
      this.refreshInterval = setInterval(this.loadDashboardData, 3600000);
      this.autoTimeOutInterval = setInterval(this.checkAutoTimeOut, 3600000);
    },

    clearIntervals() {
      if (this.refreshInterval) clearInterval(this.refreshInterval);
      if (this.slideInterval) clearInterval(this.slideInterval);
      if (this.bulletinSlideInterval) clearInterval(this.bulletinSlideInterval);
      if (this.autoTimeOutInterval) clearInterval(this.autoTimeOutInterval);
    },

    nextBulletinSlide() {
      if (this.bulletinImages.length > 0) {
        this.currentBulletinSlide = (this.currentBulletinSlide + 1) % this.bulletinImages.length;
      }
    },

    prevBulletinSlide() {
      if (this.bulletinImages.length > 0) {
        this.currentBulletinSlide = this.currentBulletinSlide === 0 
          ? this.bulletinImages.length - 1 
          : this.currentBulletinSlide - 1;
      }
    },

    async checkAutoTimeOut() {
      try {
        if (!this.userId) return;

        const response = await axios.post('/user/dashboard/check-auto-timeout');

        if (response.data.timeout) {
          this.isLoggedIn = false;
          this.status = 'OUT';
          this.activeBreak = null;
          
          await this.loadDashboardData();
          this.showToast('You have been automatically timed out due to shift end', 'warning');
          this.isAutoTimeOut = true;
        }
      } catch (error) {
      }
    },

    async loadDashboardData() {
      try {
        const { data } = await axios.get('/user/dashboard');

        const responseData = data.data || data;
        if (Array.isArray(responseData.requests)) this.requests = responseData.requests;

        const a = responseData.attendance || {};

        if (responseData.stats) {
          this.vlRemaining = responseData.stats.vl_remaining ?? this.vlRemaining;
          this.slRemaining = responseData.stats.sl_remaining ?? this.slRemaining;
          this.vlCarryover = responseData.stats.vl_carried_over_remaining ?? this.vlCarryover;
          this.birthdayLeave = responseData.stats.birthday_leave_available ?? this.birthdayLeave;
          this.ptoRemaining = responseData.stats.pto_remaining ?? this.ptoRemaining;
          this.ctoRemainingHours = responseData.stats.cto_remaining_hours ?? this.ctoRemainingHours;
        }

        this.status = a.status || 'OUT';
        this.isLoggedIn = !!a.is_logged_in;
        this.timeIn = a.time_in || null;
        this.timeOut = a.time_out || null;
        this.workingHours = a.working_hours || '0h 0m';
        this.activeBreak = a.active_break || null;

        if (Array.isArray(responseData.announcements)) this.announcements = responseData.announcements;
      } catch (error) {
        this.showToast('Failed to load dashboard data', 'error');
      }
    },

    async clockInOut() {
      if (this.clockingIn || this.loading) return;
      try {
        this.clockingIn = true;
        const payload = {};
        if (this.dailyNotes.trim()) {
          payload.notes = this.dailyNotes.trim();
        }

        const { data } = await axios.post('/user/dashboard/clock', payload);
        const responseData = data.data || data;
        
        if (responseData.attendance) {
          const a = responseData.attendance;
          this.status = a.status;
          this.isLoggedIn = !!a.is_logged_in;
          this.timeIn = a.time_in;
          this.timeOut = a.time_out;
          this.workingHours = a.working_hours;
          this.breakTimeRemaining = a.break_time_remaining ?? '—';
          this.activeBreak = a.active_break || null;
        }
        
        this.dailyNotes = '';
        this.showToast(responseData.message || data.message || 'Clock action completed successfully', 'success');
      } catch (error) {
        this.showToast(error.response?.data?.message || 'Failed to clock in/out', 'error');
      } finally {
        this.clockingIn = false;
      }
    },

    async confirmTimeOut() {
      if (this.isAutoTimeOut) {
        this.isAutoTimeOut = false;
        this.showTimeOutConfirmation = false;
        return;
      }
      this.showTimeOutConfirmation = false;
      await this.clockInOut();
    },

    async startBreak(type) {
      try {
        const payload = { type };
        if (this.dailyNotes.trim()) {
          payload.notes = this.dailyNotes.trim();
        }

        await axios.post('/user/dashboard/breaks/start', payload);
        await this.loadDashboardData();
        
        this.dailyNotes = '';
        this.showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} break started`, 'success');
      } catch (e) {
        this.showToast(e.response?.data?.message || `Failed to start ${type} break`, 'error');
      }
    },

    async endBreak(type) {
      try {
        const payload = { type };
        if (this.dailyNotes.trim()) {
          payload.notes = this.dailyNotes.trim();
        }

        await axios.post('/user/dashboard/breaks/end', payload);
        await this.loadDashboardData();
        
        this.dailyNotes = '';
        this.showToast(`Break ended`, 'success');
      } catch (e) {
        this.showToast(e.response?.data?.message || `Failed to end break`, 'error');
      }
    },

    getButtonColor() {
      // Warning is a bright orange -- white on it is only 2.64:1, so the
      // break state carries navy text instead.
      if (!this.isLoggedIn) return 'bg-success hover:bg-success/90 text-white';
      if (this.activeBreak) return 'bg-warning hover:bg-warning/90 text-text';
      return 'bg-danger hover:bg-danger/90 text-white';
    },

    getCurrentActionText() {
      if (!this.isLoggedIn) return 'Time In';
      if (this.activeBreak) return `End ${this.activeBreak.label || this.activeBreak.type}`;
      return 'Select Action';
    },

    handleTimeAction(action) {
      this.showTimeActionDropdown = false;
      
      switch(action) {
        case 'time_in':
          this.clockInOut();
          break;
        case 'time_out':
          this.showTimeOutConfirmation = true;
          break;
        case 'break_lunch':
          this.startBreak('lunch');
          break;
        case 'break_brb':
          this.startBreak('brb');
          break;
        case 'break_end':
          if (this.activeBreak) {
            this.endBreak(this.activeBreak.type);
          }
          break;
      }
    },

    calculateElapsedTime(startTime) {
      if (!startTime) return '0m';
      
      const start = new Date(startTime);
      const now = new Date();
      const diffMinutes = Math.floor((now - start) / (1000 * 60));
      
      if (diffMinutes < 60) return `${diffMinutes}m`;
      
      const hours = Math.floor(diffMinutes / 60);
      const minutes = diffMinutes % 60;
      
      if (minutes === 0) return `${hours}h`;
      return `${hours}h ${minutes}m`;
    },

    updateTime() {
      const now = new Date();
      this.currentTime = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }).toLowerCase();
      this.currentDate = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      
      if (this.activeBreak && this.activeBreak.type === 'brb') {
        this.$forceUpdate();
      }
    },

    formatCurrentDate() {
      const now = new Date();
      const options = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };
      const formatted = now.toLocaleDateString('en-US', options);
      
      if (window.innerWidth < 640) {
        return now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      }
      return formatted;
    },

    formatTo12Hour(value) {
      if (!value) return value;
      const parsed = new Date(value);
      if (!isNaN(parsed)) {
        return parsed.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }).toLowerCase();
      }
      if (typeof value === 'string' && /^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
        const [h, m] = value.split(':');
        const d = new Date();
        d.setHours(Number(h), Number(m), 0, 0);
        return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }).toLowerCase();
      }
      return value;
    },

    formatFullDate(value) {
      if (!value) return '';
      const d = new Date(value);
      if (isNaN(d)) return value;
      
      if (window.innerWidth < 640) {
        return d.toLocaleDateString('en-US', {
          month: 'short',
          day: 'numeric',
          year: 'numeric'
        });
      }
      
      return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },

    formatRequestDate(dateStr) {
      if (!dateStr) return 'N/A';
      try {
        const date = new Date(dateStr);
        if (isNaN(date)) return dateStr;
        
        if (window.innerWidth < 640) {
          return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric'
          });
        }
        
        return date.toLocaleDateString('en-US', { 
          month: 'long', 
          day: 'numeric', 
          year: 'numeric' 
        });
      } catch (e) {
        return dateStr;
      }
    },

    handleImageError(event) {
      event.target.src = `https://ui-avatars.com/api/?name=${event.target.alt}&background=random&color=fff`;
    },

    getRequestsByType(type) {
      return this.requests.filter(request => request.type === type);
    },

    // A card opens the scrollable list modal rather than expanding in place.
    expandRequestType(type) {
      this.listModalType = type;
    },

    closeApprovalModal() {
      this.listModalType = null;
      this.selectedRequest = null;
      this.showRejectionNote = false;
      this.rejectionNote = '';
    },

    // Back arrow: drop the detail, keep the list open behind it.
    backToList() {
      this.selectedRequest = null;
      this.showRejectionNote = false;
      this.rejectionNote = '';
    },

    onModalKeydown(event) {
      if (event.key !== 'Escape' || !this.approvalModalOpen) return;

      event.stopPropagation();
      // Escape steps back one level rather than blowing the whole thing away.
      if (this.showingDetail) {
        this.backToList();
      } else {
        this.closeApprovalModal();
      }
    },

    viewRequest(request) {
      this.selectedRequest = { ...request };
      this.showRejectionNote = false;
      this.rejectionNote = '';
    },

    async handleRequestAction(action) {
      if (!this.selectedRequest || this.processing) return;

      if (!['leave', 'shift', 'timesheet'].includes(this.selectedRequest.type)) {
        this.showToast('This request type is not supported in dashboard', 'error');
        return;
      }

      if (action === 'reject' && !this.rejectionNote.trim()) {
        this.showRejectionNote = true;
        return;
      }

      this.processing = true;
      try {
        let payload = {};
        if (action === 'reject') {
          payload.rejection_note = this.rejectionNote.trim();
        }

        let endpoint;
        if (this.selectedRequest.type === 'leave') {
          endpoint = `/user/dashboard/leaves/${this.selectedRequest.id}/${action}`;
        } else if (this.selectedRequest.type === 'shift') {
          endpoint = `/user/dashboard/shift-change/${this.selectedRequest.id}/${action}`;
        } else if (this.selectedRequest.type === 'timesheet') {
          endpoint = `/user/dashboard/timesheets/${this.selectedRequest.id}/${action}`;
        }

        await axios.post(endpoint, payload);

        this.requests = this.requests.filter(r =>
          !(r.id === this.selectedRequest.id && r.type === this.selectedRequest.type)
        );

        const actionText = action === 'approve' ? 'approved' : 'rejected';
        const requestTypeLabels = {
          'leave': 'Leave',
          'shift': 'Shift change',
          'timesheet': 'Timesheet'
        };
        const requestType = requestTypeLabels[this.selectedRequest.type] || 'Request';
        this.showToast(
          `${requestType} request ${actionText} for ${this.selectedRequest.name}`,
          action === 'approve' ? 'success' : 'info'
        );

        this.selectedRequest = null;
        this.showRejectionNote = false;
        this.rejectionNote = '';
      } catch (error) {
        this.showToast(
          error.response?.data?.message || `Failed to ${action} request`,
          'error'
        );
      } finally {
        this.processing = false;
      }
    },

    cancelRejection() {
      this.showRejectionNote = false;
      this.rejectionNote = '';
    },

    getRequestTypeLabel(type) {
      const labels = {
        'leave': 'Leave Request',
        'attendance': 'Attendance Correction',
        'shift': 'Shift Change',
        'timesheet': 'Weekly Time Entries'
      };
      return labels[type] || 'Request';
    },

    /** Decimal hours as H:MM, matching the Time Entries grid. */
    formatSheetHours(value) {
      const hours = Number(value || 0);
      const whole = Math.floor(hours);
      const minutes = Math.round((hours - whole) * 60);
      return `${whole}:${String(minutes).padStart(2, '0')}`;
    },

    /** Literal classes so Tailwind's scanner compiles them. */
    sheetTintClass(tint) {
      const tints = {
        green:  'bg-status-green text-status-text',
        orange: 'bg-status-orange text-status-text',
        purple: 'bg-status-purple text-status-text',
        blue:   'bg-status-blue text-status-text',
        yellow: 'bg-status-yellow text-status-text',
        indigo: 'bg-status-indigo text-status-text',
        cyan:   'bg-status-cyan text-status-text',
        red:    'bg-status-red text-status-text',
        gray:   'bg-status-gray text-status-text',
      };
      return tints[tint] || tints.gray;
    },

    getStatusBadgeClass(status) {
      const statusLower = (status || '').toLowerCase();
      if (statusLower === 'approved') return 'bg-status-green text-status-text';
      if (statusLower === 'rejected') return 'bg-status-red text-status-text';
      return 'bg-status-yellow text-status-text';
    },

    showToast(message, type = 'info') {
      const toast = document.createElement('div');
      toast.className = `fixed top-4 right-4 px-4 sm:px-6 py-2 sm:py-3 rounded-lg text-white z-50 transition-all duration-300 text-sm sm:text-base max-w-[90vw] sm:max-w-md ${
        type === 'success' ? 'bg-green-500' :
        type === 'error'   ? 'bg-red-500'   :
        type === 'warning' ? 'bg-yellow-500': 'bg-blue-500'
      }`;
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(() => toast.style.opacity = '1', 100);
      setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => document.body.removeChild(toast), 300); }, 3000);
    },

    async loadBulletinImages() {
      try {
        const { data } = await axios.get('/hr/announcements');
        this.bulletinImages = Array.isArray(data?.data) ? data.data : [];
        this.currentBulletinSlide = 0;
      } catch (e) {
        this.bulletinImages = [];
      }
    },

    openImageModal(image) {
      this.viewImageModal = image;
    },

    formatCompactDate(dateStr) {
      if (!dateStr) return 'N/A';
      try {
        const date = new Date(dateStr);
        if (isNaN(date)) return dateStr;
        
        const now = new Date();
        const options = { month: 'short', day: 'numeric' };
        if (date.getFullYear() !== now.getFullYear()) {
          options.year = 'numeric';
        }
        return date.toLocaleDateString('en-US', options);
      } catch (e) {
        return dateStr;
      }
    },

    formatRelativeDate(dateStr) {
      if (!dateStr) return '';
      try {
        const date = new Date(dateStr);
        const now = new Date();
        const diffInHours = Math.floor((now - date) / (1000 * 60 * 60));
        
        if (diffInHours < 1) return 'Just now';
        if (diffInHours < 24) return `${diffInHours}h ago`;
        
        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) return `${diffInDays}d ago`;
        
        const diffInWeeks = Math.floor(diffInDays / 7);
        if (diffInWeeks < 4) return `${diffInWeeks}w ago`;
        
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      } catch (e) {
        return '';
      }
    }
  }
};
</script>

<style scoped>
@media (max-width: 640px) {
  .text-3xl { font-size: 1.75rem; }
  .text-4xl { font-size: 2rem; }
}

@keyframes fadeIn { 
  from { opacity: 0; } 
  to { opacity: 1; } 
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.fixed.inset-0 { animation: fadeIn 0.3s ease-in-out; }
.transform:hover { transition: transform 0.2s ease-in-out; }
.transition-all { transition: all 0.3s ease-in-out; }

.fixed.inset-0 .bg-white {
  animation: modalSlideIn 0.3s ease-out;
}

.flex.transition-transform {
  transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.hover\:scale-105:hover {
  transform: scale(1.05);
}

.hover\:scale-102:hover {
  transform: scale(1.02);
}

.overflow-x-auto::-webkit-scrollbar,
.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
  height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track,
.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb,
.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover,
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.-z-10 { z-index: -10; }
.-z-20 { z-index: -20; }
.transform.rotate-1 { transform: rotate(1deg); }
.transform.rotate-2 { transform: rotate(2deg); }

body {
  overflow-x: hidden;
}

@media (max-width: 640px) {
  button {
    min-height: 44px;
  }
}
</style>