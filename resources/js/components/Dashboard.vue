<template>
  <div class="p-2 sm:p-4 bg-gray-50 min-h-screen overflow-x-hidden">

    <!-- Request Detail Modal -->
    <div v-if="selectedRequest" class="fixed inset-0 bg-gray-900 bg-opacity-20 flex items-center justify-center z-50 p-2 sm:p-4">
      <div class="bg-white rounded-xl sm:rounded-2xl max-w-2xl w-full max-h-[95vh] sm:max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sm:py-4 rounded-t-xl sm:rounded-t-2xl z-10">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
              <img :src="selectedRequest.avatar" :alt="selectedRequest.name" class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex-shrink-0" @error="handleImageError">
              <div class="min-w-0 flex-1">
                <h2 class="text-base sm:text-xl font-semibold text-gray-900 truncate">{{ selectedRequest.leave_type || 'Request' }}</h2>
                <p class="text-xs sm:text-sm text-gray-600 truncate">{{ selectedRequest.name }}</p>
              </div>
            </div>
            <button @click="selectedRequest = null" class="text-gray-400 hover:text-gray-600 p-2 flex-shrink-0">
              <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="p-4 sm:p-6">
          <div class="mb-4">
            <span :class="getRequestTypeBadgeClass(selectedRequest.type)" class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-medium">
              {{ getRequestTypeLabel(selectedRequest.type) }}
            </span>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:gap-6 mb-4 sm:mb-6">
            <div class="space-y-3 sm:space-y-4">
              <div>
                <label class="text-xs sm:text-sm font-medium text-gray-500">Employee</label>
                <p class="text-sm sm:text-base text-gray-900 mt-1">{{ selectedRequest.name }}</p>
                <p class="text-gray-600 text-xs sm:text-sm">{{ selectedRequest.email }}</p>
              </div>

              <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <div>
                  <label class="text-xs sm:text-sm font-medium text-gray-500">Request Type</label>
                  <p class="text-sm sm:text-base text-gray-900 mt-1">{{ selectedRequest.leave_type || 'General Request' }}</p>
                </div>

                <div>
                  <label class="text-xs sm:text-sm font-medium text-gray-500">Status</label>
                  <p class="text-gray-900 mt-1">
                    <span :class="getStatusBadgeClass(selectedRequest.status)" class="inline-flex items-center px-2 py-1 rounded text-xs font-medium">
                      {{ selectedRequest.status }}
                    </span>
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <div v-if="selectedRequest.start_date">
                  <label class="text-xs sm:text-sm font-medium text-gray-500">Start Date</label>
                  <p class="text-sm sm:text-base text-gray-900 mt-1">{{ formatRequestDate(selectedRequest.start_date) }}</p>
                </div>

                <div v-if="selectedRequest.end_date && selectedRequest.start_date !== selectedRequest.end_date">
                  <label class="text-xs sm:text-sm font-medium text-gray-500">End Date</label>
                  <p class="text-sm sm:text-base text-gray-900 mt-1">{{ formatRequestDate(selectedRequest.end_date) }}</p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <div v-if="selectedRequest.duration">
                  <label class="text-xs sm:text-sm font-medium text-gray-500">Duration</label>
                  <p class="text-sm sm:text-base text-gray-900 mt-1">{{ selectedRequest.duration }}</p>
                </div>

                <div>
                  <label class="text-xs sm:text-sm font-medium text-gray-500">Submitted</label>
                  <p class="text-sm sm:text-base text-gray-900 mt-1">{{ selectedRequest.submitted_at || formatRequestDate(selectedRequest.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>

          <div v-if="selectedRequest.type === 'overtime'" class="mb-4 sm:mb-6 p-3 sm:p-4 bg-orange-50 rounded-lg">
            <h3 class="text-xs sm:text-sm font-medium text-gray-700 mb-2 sm:mb-3">Overtime Details</h3>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 text-xs sm:text-sm">
              <div v-if="selectedRequest.time_in">
                <span class="text-gray-500">Time In:</span>
                <span class="ml-2 text-gray-900">{{ selectedRequest.time_in }}</span>
              </div>
              <div v-if="selectedRequest.time_out">
                <span class="text-gray-500">Time Out:</span>
                <span class="ml-2 text-gray-900">{{ selectedRequest.time_out }}</span>
              </div>
              <div v-if="selectedRequest.ot_hours">
                <span class="text-gray-500">OT Hours:</span>
                <span class="ml-2 text-gray-900">{{ selectedRequest.ot_hours }}h</span>
              </div>
              <div v-if="selectedRequest.project_name" class="col-span-2">
                <span class="text-gray-500">Project:</span>
                <span class="ml-2 text-gray-900">{{ selectedRequest.project_name }}</span>
              </div>
            </div>
          </div>

          <div v-if="selectedRequest.type === 'shift'" class="mb-4 sm:mb-6 p-3 sm:p-4 bg-purple-50 rounded-lg">
            <h3 class="text-xs sm:text-sm font-medium text-gray-700 mb-2 sm:mb-3">Shift Change Details</h3>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 text-xs sm:text-sm">
              <div v-if="selectedRequest.current_shift">
                <span class="text-gray-500">Current Shift:</span>
                <span class="ml-2 text-gray-900">{{ selectedRequest.current_shift }}</span>
              </div>
              <div v-if="selectedRequest.requested_shift">
                <span class="text-gray-500">Requested Shift:</span>
                <span class="ml-2 text-gray-900 font-semibold">{{ selectedRequest.requested_shift }}</span>
              </div>
              <div v-if="selectedRequest.effective_date" class="col-span-2">
                <span class="text-gray-500">Effective Date:</span>
                <span class="ml-2 text-gray-900">{{ selectedRequest.effective_date }}</span>
              </div>
            </div>
          </div>

          <div v-if="selectedRequest.reason" class="mb-4 sm:mb-6">
            <label class="text-xs sm:text-sm font-medium text-gray-500 block mb-2">Reason/Notes</label>
            <div class="bg-gray-50 rounded-lg p-3 sm:p-4">
              <p class="text-sm sm:text-base text-gray-900 whitespace-pre-wrap">{{ selectedRequest.reason }}</p>
            </div>
          </div>

          <div v-if="(selectedRequest.status === 'pending' || selectedRequest.status === 'PENDING') && (selectedRequest.type === 'leave' || selectedRequest.type === 'overtime' || selectedRequest.type === 'shift')" class="pt-4 border-t border-gray-200">
            <div v-if="showRejectionNote" class="w-full mb-4">
              <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Reason for Rejection</label>
              <textarea 
                v-model="rejectionNote" 
                placeholder="Please provide a reason for rejecting this request..."
                rows="3"
                class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"
              ></textarea>
              <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 mt-3">
                <button @click="handleRequestAction('reject')" :disabled="!rejectionNote.trim() || processing" class="w-full sm:flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-300 text-white text-sm rounded-lg">
                  {{ processing ? 'Rejecting...' : 'Confirm Rejection' }}
                </button>
                <button @click="cancelRejection" class="w-full sm:flex-1 px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 text-sm rounded-lg">
                  Cancel
                </button>
              </div>
            </div>

            <div v-else class="w-full flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
              <button 
                @click="handleRequestAction('approve')" 
                :disabled="processing"
                class="w-full sm:flex-1 px-4 sm:px-6 py-2 sm:py-3 bg-green-600 hover:bg-green-700 disabled:bg-green-300 text-white text-sm sm:text-base font-medium rounded-xl transition-colors flex items-center justify-center space-x-2"
              >
                <svg v-if="!processing" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ processing ? 'Processing...' : 'Approve' }}</span>
              </button>
              
              <button 
                @click="showRejectionNote = true" 
                :disabled="processing"
                class="w-full sm:flex-1 px-4 sm:px-6 py-2 sm:py-3 bg-red-600 hover:bg-red-700 disabled:bg-red-300 text-white text-sm sm:text-base font-medium rounded-xl transition-colors flex items-center justify-center space-x-2"
              >
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>Reject</span>
              </button>
            </div>
          </div>

          <div v-else-if="selectedRequest.type !== 'leave' && selectedRequest.type !== 'overtime' && selectedRequest.type !== 'shift'" class="pt-4 border-t border-gray-200">
            <div class="text-center py-4">
              <span class="inline-flex items-center px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium bg-gray-100 text-gray-700">
                This request type can only be viewed here. Please use the appropriate module to approve/reject.
              </span>
            </div>
          </div>

          <div v-else class="pt-4 border-t border-gray-200">
            <div class="text-center py-4">
              <span :class="getStatusBadgeClass(selectedRequest.status)" class="inline-flex items-center px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-medium">
                {{ selectedRequest.status === 'approved' ? 'Already Approved' : 'Already Rejected' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Time Out Confirmation Modal -->
    <div v-if="showTimeOutConfirmation" class="fixed inset-0 flex items-center justify-center z-50 p-4">
      <div class="absolute inset-0 bg-black bg-opacity-50" @click="showTimeOutConfirmation = false"></div>
      <div class="bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 max-w-md w-full mx-4 transform transition-all shadow-2xl relative z-10">
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

    <!-- Header - Hidden on mobile, shown on desktop -->
    <div class="hidden lg:flex items-center justify-between mb-4 bg-white shadow-sm px-6 py-3 rounded-2xl">
      <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4 mb-3 sm:mb-4">
      <!-- Time Action & Notes Column -->
      <div class="space-y-3 sm:space-y-4">
        <!-- Time Action Button with Dropdown -->
        <div class="relative">
          <button @click="showTimeActionDropdown = !showTimeActionDropdown" :disabled="clockingIn || loading" :class="['w-full text-white rounded-xl sm:rounded-2xl p-4 sm:p-6 text-left transition-all transform hover:scale-102 flex items-center justify-between', clockingIn || loading ? 'opacity-50 cursor-not-allowed' : 'shadow-lg', getButtonColor()]">
            <div class="flex items-center space-x-2 sm:space-x-3 min-w-0 flex-1">
              <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg v-if="!clockingIn" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getCurrentActionIcon()" />
                </svg>
                <svg v-else class="w-4 h-4 sm:w-5 sm:h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <span class="font-semibold text-base sm:text-lg block truncate">{{ clockingIn ? 'Processing...' : getCurrentActionText() }}</span>
                <span class="text-xs sm:text-sm text-white/80 truncate block">{{ getCurrentActionSubtext() }}</span>
              </div>
            </div>
            
            <div class="w-5 h-5 sm:w-6 sm:h-6 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0 ml-2">
              <svg :class="['w-3 h-3 sm:w-4 sm:h-4 transition-transform', showTimeActionDropdown ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </div>
          </button>

          <!-- Dropdown Menu -->
          <div v-if="showTimeActionDropdown && !clockingIn && !loading" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl sm:rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-10">
            <button v-if="!isLoggedIn" @click="handleTimeAction('time_in')" class="w-full px-4 sm:px-6 py-3 sm:py-4 text-left hover:bg-blue-50 transition-colors border-b border-gray-50 flex items-center space-x-2 sm:space-x-3">
              <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <span class="font-medium text-gray-900 text-sm sm:text-base">Time In</span>
                <p class="text-xs sm:text-sm text-gray-500">Start your work day</p>
              </div>
            </button>

            <button v-if="isLoggedIn && !activeBreak" @click="handleTimeAction('time_out')" class="w-full px-4 sm:px-6 py-3 sm:py-4 text-left hover:bg-red-50 transition-colors border-b border-gray-50 flex items-center space-x-2 sm:space-x-3">
              <div class="w-7 h-7 sm:w-8 sm:h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <span class="font-medium text-gray-900 text-sm sm:text-base">Time Out</span>
                <p class="text-xs sm:text-sm text-gray-500">End your work day</p>
              </div>
            </button>

            <template v-if="isLoggedIn && !activeBreak">
              <button @click="handleTimeAction('break_lunch')" class="w-full px-4 sm:px-6 py-3 sm:py-4 text-left hover:bg-amber-50 transition-colors border-b border-gray-50 flex items-center space-x-2 sm:space-x-3">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div class="min-w-0 flex-1">
                  <span class="font-medium text-gray-900 text-sm sm:text-base">Start Lunch Break</span>
                  <p class="text-xs sm:text-sm text-gray-500">Take your lunch break</p>
                </div>
              </button>

              <button @click="handleTimeAction('break_brb')" class="w-full px-4 sm:px-6 py-3 sm:py-4 text-left hover:bg-purple-50 transition-colors border-b border-gray-50 flex items-center space-x-2 sm:space-x-3">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                  </svg>
                </div>
                <div class="min-w-0 flex-1">
                  <span class="font-medium text-gray-900 text-sm sm:text-base">Be Right Back</span>
                  <p class="text-xs sm:text-sm text-gray-500">Quick break (BRB)</p>
                </div>
              </button>
            </template>

            <template v-if="activeBreak">
              <button @click="handleTimeAction('break_end')" class="w-full px-4 sm:px-6 py-3 sm:py-4 text-left hover:bg-green-50 transition-colors flex items-center space-x-2 sm:space-x-3">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div class="min-w-0 flex-1">
                  <span class="font-medium text-gray-900 text-sm sm:text-base">End {{ activeBreak.label || activeBreak.type }}</span>
                  <p class="text-xs sm:text-sm text-gray-500">Return to work</p>
                </div>
              </button>
            </template>
          </div>

          <div v-if="showTimeActionDropdown" @click="showTimeActionDropdown = false" class="fixed inset-0 z-0"></div>
        </div>

        <!-- Daily Notes -->
        <div class="bg-white rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm">
          <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">Daily Notes</label>
          <textarea v-model="dailyNotes" placeholder="Add notes for your work day..." rows="3" class="w-full text-sm border border-gray-200 rounded-lg sm:rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" :disabled="loading"></textarea>
          <p class="text-xs text-gray-500 mt-1">These notes will be saved with your time entries</p>
        </div>
      </div>

      <!-- Status Card -->
      <div class="lg:col-span-2">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl sm:rounded-2xl p-4 sm:p-6 text-white relative overflow-hidden min-h-[280px] sm:min-h-[320px]">
          <div class="absolute right-0 top-0 w-40 h-40 sm:w-64 sm:h-64 opacity-10">
            <img src="@/assets/cwgp-logo.webp" alt="CWGP Logo" class="w-full h-full object-cover" />
          </div>
          <div class="absolute right-4 top-4 sm:right-8 sm:top-8 w-12 h-12 sm:w-20 sm:h-20 bg-white/5 rounded-full"></div>
          <div class="absolute right-8 top-8 sm:right-16 sm:top-16 w-8 h-8 sm:w-12 sm:h-12 bg-white/10 rounded-full"></div>
          <div class="absolute right-12 top-12 sm:right-24 sm:top-24 w-4 h-4 sm:w-6 sm:h-6 bg-blue-300/30 rounded-full animate-pulse"></div>
          <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-transparent to-white/5"></div>

          <div class="relative z-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-6 mb-4 sm:mb-6">
              <div>
                <p class="text-blue-100 text-xs sm:text-sm mb-1">Current Time</p>
                <p class="text-2xl sm:text-3xl lg:text-4xl font-bold">{{ currentTime }}</p>
              </div>
              <div>
                <p class="text-blue-100 text-xs sm:text-sm mb-1">{{ formatCurrentDate() }}</p>
                <div class="flex items-center space-x-2">
                  <div :class="['w-2 h-2 sm:w-3 sm:h-3 rounded-full', statusDotClass]"></div>
                  <p class="text-lg sm:text-xl lg:text-2xl font-semibold">{{ status }}</p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:gap-4 mb-3 sm:mb-4">
              <div class="bg-white/10 backdrop-blur rounded-lg p-2 sm:p-3">
                <p class="text-blue-100 text-xs mb-1">Time In</p>
                <p class="text-base sm:text-xl font-bold truncate">{{ formatTo12Hour(timeIn) || '--:--' }}</p>
              </div>
              <div class="bg-white/10 backdrop-blur rounded-lg p-2 sm:p-3">
                <p class="text-blue-100 text-xs mb-1">Time Out</p>
                <p class="text-base sm:text-xl font-bold truncate">{{ formatTo12Hour(timeOut) || '--:--' }}</p>
              </div>
            </div>

            <!-- Stats Grid - Improved Layout -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-2 sm:gap-3">
              <!-- Working Hours -->
              <div class="bg-white/10 backdrop-blur rounded-lg p-2 sm:p-3 text-center">
                <p class="text-blue-100 text-xs mb-1">Total Working Hours</p>
                <p class="text-sm sm:text-base lg:text-lg font-bold truncate">{{ workingHours }}</p>
              </div>
              
              <!-- VL Carryover -->
              <div class="bg-white/10 backdrop-blur rounded-lg p-2 sm:p-3 text-center hover:bg-white/15 transition-colors">
                <p class="text-blue-100 text-xs mb-1">VL Carryover</p>
                <p class="text-sm sm:text-base lg:text-lg font-bold">{{ vlCarryover }}<span class="text-xs">d</span></p>
              </div>
              
              <!-- VL Remaining -->
              <div class="bg-white/10 backdrop-blur rounded-lg p-2 sm:p-3 text-center hover:bg-white/15 transition-colors">
                <p class="text-blue-100 text-xs mb-1">VL Remaining</p>
                <p class="text-sm sm:text-base lg:text-lg font-bold">{{ vlRemaining }}<span class="text-xs">d</span></p>
              </div>
              
              <!-- SL Remaining -->
              <div class="bg-white/10 backdrop-blur rounded-lg p-2 sm:p-3 text-center hover:bg-white/15 transition-colors">
                <p class="text-blue-100 text-xs mb-1">SL Remaining</p>
                <p class="text-sm sm:text-base lg:text-lg font-bold">{{ slRemaining }}<span class="text-xs">d</span></p>
              </div>
              
              <!-- Birthday Leave -->
              <div class="bg-white/10 backdrop-blur rounded-lg p-2 sm:p-3 text-center hover:bg-white/15 transition-colors">
                <p class="text-blue-100 text-xs mb-1">Birthday Leave</p>
                <p class="text-sm sm:text-base lg:text-lg font-bold">{{ displayBirthdayLeave }}<span class="text-xs">d</span></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Section -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 sm:gap-4">
      <!-- Requests Section -->
      <div class="lg:col-span-3 bg-white rounded-xl sm:rounded-2xl shadow-sm flex flex-col min-h-[400px] max-h-[600px] sm:max-h-[700px]">
        <div class="flex items-center justify-between p-4 sm:p-6 pb-3 sm:pb-4 flex-shrink-0 border-b sm:border-b-0">
          <h2 class="text-lg sm:text-xl font-semibold text-gray-800">For My Approval</h2>
        </div>
        
        <!-- Request Type Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 px-4 sm:px-6 flex-shrink-0">
          <div @click="expandRequestType('leave')" :class="['relative p-4 sm:p-5 rounded-xl cursor-pointer transition-all duration-300 transform hover:scale-105 hover:shadow-lg group', getRequestsByType('leave').length > 0 ? 'bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200' : 'bg-gray-50 border border-gray-200']">
            <div v-if="getRequestsByType('leave').length > 1" class="absolute inset-0 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl transform rotate-1 -z-10 border border-blue-200"></div>
            <div v-if="getRequestsByType('leave').length > 2" class="absolute inset-0 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl transform rotate-2 -z-20 border border-blue-200"></div>
            <div class="relative z-10">
              <div class="flex items-center justify-between mb-2 sm:mb-3">
                <div :class="['w-8 h-8 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center', getRequestsByType('leave').length > 0 ? 'bg-blue-500' : 'bg-gray-400']">
                  <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div v-if="getRequestsByType('leave').length > 0" class="bg-blue-500 text-white text-xs font-bold rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center animate-pulse">
                  {{ getRequestsByType('leave').length }}
                </div>
              </div>
              <h3 class="font-semibold text-sm sm:text-base text-gray-800 mb-1">Leave Requests</h3>
              <p class="text-xs sm:text-sm text-gray-600">{{ getRequestsByType('leave').length > 0 ? `${getRequestsByType('leave').length} pending` : 'No pending requests' }}</p>
              <div v-if="getRequestsByType('leave').length > 0" class="mt-2 sm:mt-3 pt-2 sm:pt-3 border-t border-blue-200">
                <div class="flex items-center space-x-2">
                  <img :src="getRequestsByType('leave')[0].avatar" :alt="getRequestsByType('leave')[0].name" class="w-5 h-5 sm:w-6 sm:h-6 rounded-full" @error="handleImageError">
                  <span class="text-xs text-gray-600 truncate">{{ getRequestsByType('leave')[0].name }}</span>
                </div>
              </div>
            </div>
          </div>

          <div @click="expandRequestType('overtime')" :class="['relative p-4 sm:p-5 rounded-xl cursor-pointer transition-all duration-300 transform hover:scale-105 hover:shadow-lg group', getRequestsByType('overtime').length > 0 ? 'bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200' : 'bg-gray-50 border border-gray-200']">
            <div v-if="getRequestsByType('overtime').length > 1" class="absolute inset-0 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl transform rotate-1 -z-10 border border-orange-200"></div>
            <div v-if="getRequestsByType('overtime').length > 2" class="absolute inset-0 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl transform rotate-2 -z-20 border border-orange-200"></div>
            <div class="relative z-10">
              <div class="flex items-center justify-between mb-2 sm:mb-3">
                <div :class="['w-8 h-8 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center', getRequestsByType('overtime').length > 0 ? 'bg-orange-500' : 'bg-gray-400']">
                  <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div v-if="getRequestsByType('overtime').length > 0" class="bg-orange-500 text-white text-xs font-bold rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center animate-pulse">
                  {{ getRequestsByType('overtime').length }}
                </div>
              </div>
              <h3 class="font-semibold text-sm sm:text-base text-gray-800 mb-1">Overtime</h3>
              <p class="text-xs sm:text-sm text-gray-600">{{ getRequestsByType('overtime').length > 0 ? `${getRequestsByType('overtime').length} pending` : 'No pending requests' }}</p>
              <div v-if="getRequestsByType('overtime').length > 0" class="mt-2 sm:mt-3 pt-2 sm:pt-3 border-t border-orange-200">
                <div class="flex items-center space-x-2">
                  <img :src="getRequestsByType('overtime')[0].avatar" :alt="getRequestsByType('overtime')[0].name" class="w-5 h-5 sm:w-6 sm:h-6 rounded-full" @error="handleImageError">
                  <span class="text-xs text-gray-600 truncate">{{ getRequestsByType('overtime')[0].name }}</span>
                </div>
              </div>
            </div>
          </div>

          <div @click="expandRequestType('shift')" :class="['relative p-4 sm:p-5 rounded-xl cursor-pointer transition-all duration-300 transform hover:scale-105 hover:shadow-lg group', getRequestsByType('shift').length > 0 ? 'bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200' : 'bg-gray-50 border border-gray-200']">
            <div v-if="getRequestsByType('shift').length > 1" class="absolute inset-0 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl transform rotate-1 -z-10 border border-purple-200"></div>
            <div v-if="getRequestsByType('shift').length > 2" class="absolute inset-0 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl transform rotate-2 -z-20 border border-purple-200"></div>
            <div class="relative z-10">
              <div class="flex items-center justify-between mb-2 sm:mb-3">
                <div :class="['w-8 h-8 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center', getRequestsByType('shift').length > 0 ? 'bg-purple-500' : 'bg-gray-400']">
                  <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                  </svg>
                </div>
                <div v-if="getRequestsByType('shift').length > 0" class="bg-purple-500 text-white text-xs font-bold rounded-full w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center animate-pulse">
                  {{ getRequestsByType('shift').length }}
                </div>
              </div>
              <h3 class="font-semibold text-sm sm:text-base text-gray-800 mb-1">Shift Changes</h3>
              <p class="text-xs sm:text-sm text-gray-600">{{ getRequestsByType('shift').length > 0 ? `${getRequestsByType('shift').length} pending` : 'No pending requests' }}</p>
              <div v-if="getRequestsByType('shift').length > 0" class="mt-2 sm:mt-3 pt-2 sm:pt-3 border-t border-purple-200">
                <div class="flex items-center space-x-2">
                  <img :src="getRequestsByType('shift')[0].avatar" :alt="getRequestsByType('shift')[0].name" class="w-5 h-5 sm:w-6 sm:h-6 rounded-full" @error="handleImageError">
                  <span class="text-xs text-gray-600 truncate">{{ getRequestsByType('shift')[0].name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Expanded Request List -->
        <div v-if="expandedRequestType" class="flex-1 overflow-hidden px-4 sm:px-6 pb-4 sm:pb-6 mt-3 sm:mt-0">
          <div class="flex items-center justify-between mb-3 sm:mb-4 pt-3 sm:pt-4 border-t border-gray-100">
            <h3 class="text-base sm:text-lg font-semibold text-gray-800 capitalize">{{ expandedRequestType }} Requests</h3>
            <button @click="expandedRequestType = null" class="text-gray-400 hover:text-gray-600 p-1">
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
          
          <div class="h-full overflow-y-auto pr-1 sm:pr-2 -mr-1 sm:-mr-2">
            <div class="space-y-2 sm:space-y-3">
              <div v-for="request in getRequestsByType(expandedRequestType)" :key="`${request.type}-${request.id}`" 
                   @click="viewRequest(request)" 
                   class="bg-gray-50 hover:bg-blue-50 rounded-lg p-3 sm:p-4 cursor-pointer transition-all duration-200 border border-transparent hover:border-blue-200">
                
                <div class="flex items-start sm:items-center justify-between gap-2 sm:gap-3">
                  <div class="flex items-start sm:items-center space-x-2 sm:space-x-3 flex-1 min-w-0">
                    <img :src="request.avatar" :alt="request.name" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex-shrink-0 mt-0.5 sm:mt-0" @error="handleImageError">
                    <div class="flex-1 min-w-0">
                      <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 mb-1">
                        <h4 class="font-medium text-sm sm:text-base text-gray-900 truncate">{{ request.name }}</h4>
                        <span :class="getStatusBadgeClass(request.status)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium flex-shrink-0 self-start sm:self-auto">
                          {{ request.status }}
                        </span>
                      </div>
                      <p class="text-xs sm:text-sm text-gray-600 truncate">
                        {{ request.leave_type || getRequestTypeLabel(request.type) }}
                        <span v-if="request.duration" class="text-gray-400"> • {{ request.duration }}</span>
                      </p>
                      <p class="text-xs text-gray-500 mt-1">{{ formatRelativeDate(request.created_at) }}</p>
                    </div>
                  </div>

                  <div class="flex flex-col sm:flex-row items-end sm:items-center gap-2 sm:gap-3 flex-shrink-0">
                    <div class="text-right">
                      <p class="text-xs sm:text-sm font-medium text-gray-900 whitespace-nowrap">
                        {{ formatCompactDate(request.start_date) }}
                      </p>
                      <p v-if="request.start_date !== request.end_date" class="text-xs text-gray-500 whitespace-nowrap">
                        - {{ formatCompactDate(request.end_date) }}
                      </p>
                    </div>
                    <svg class="w-3 h-3 sm:w-4 sm:h-4 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                  </div>
                </div>

                <div v-if="request.status === 'pending' || request.status === 'PENDING'" 
                     class="mt-2 sm:mt-3 pt-2 sm:pt-3 border-t border-gray-200 flex items-center justify-between">
                  <span class="text-xs text-gray-500">Tap to approve/reject</span>
                  <div class="flex items-center space-x-1">
                    <div class="w-1.5 h-1.5 bg-green-400 rounded-full"></div>
                    <div class="w-1.5 h-1.5 bg-red-400 rounded-full"></div>
                  </div>
                </div>
              </div>

              <div v-if="getRequestsByType(expandedRequestType).length === 0" class="text-center py-8 sm:py-12">
                <div class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 sm:mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <p class="text-gray-500 text-xs sm:text-sm">No {{ expandedRequestType }} requests found</p>
              </div>
            </div>
          </div>
        </div>

        <div v-if="requests.length === 0 && !loading && !expandedRequestType" class="flex-1 flex items-center justify-center p-4">
          <div class="text-center py-8 sm:py-12">
            <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 sm:mb-4 bg-gray-100 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 sm:w-8 sm:h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <h3 class="text-base sm:text-lg font-medium text-gray-700 mb-2">All Caught Up!</h3>
            <p class="text-sm sm:text-base text-gray-500">No pending requests need your attention right now.</p>
          </div>
        </div>
      </div>

      <!-- Announcements Section -->
      <div class="lg:col-span-2 bg-white rounded-xl sm:rounded-2xl shadow-sm overflow-hidden flex flex-col max-h-[500px] sm:max-h-[600px]">
        <div class="p-3 sm:p-4 pb-2 sm:pb-3 border-b border-gray-100 flex-shrink-0">
          <div class="flex items-center justify-between">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800">Company Announcements</h3>
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

      <!-- Image Modal -->
      <div v-if="viewImageModal" class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 p-4" @click="viewImageModal = null">
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
      showRejectionNote: false,
      rejectionNote: '',
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
      requests: [],
      vlRemaining: 0,
      slRemaining: 0,
      vlCarryover: 0,
      birthdayLeave: 1,
      userRole: null,
      userPermissions: [],
      expandedRequestType: null,
      selectedRequest: null,
      processing: false,
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
    }
  },

  async mounted() {
    this.userId = this.getUserId();
    this.updateTime();
    this.startIntervals();

    Promise.all([
      this.loadUserPermissions(),
      this.loadDashboardData(),
      this.loadBulletinImages()
    ]).catch(() => {});
  },

  beforeUnmount() {
    this.clearIntervals();
    if (this.timeTicker) clearInterval(this.timeTicker);
    if (this.bulletinSlideInterval) clearInterval(this.bulletinSlideInterval);
  },

  methods: {
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

    getRequestsByType(type) {
      if (!this.requests || !Array.isArray(this.requests)) return [];
      
      const typeMap = {
        'leave': ['leave', 'vacation', 'sick', 'personal'],
        'overtime': ['overtime', 'ot'],
        'attendance': ['attendance', 'correction', 'time'],
        'shift': ['shift', 'schedule', 'change']
      };
      
      return this.requests.filter(request => {
        const requestType = (request.type || '').toLowerCase();
        const requestLeaveType = (request.leave_type || '').toLowerCase();
        
        return typeMap[type]?.some(keyword => 
          requestType.includes(keyword) || requestLeaveType.includes(keyword)
        ) || false;
      });
    },

    expandRequestType(type) {
      const requestsOfType = this.getRequestsByType(type);
      if (requestsOfType.length > 0) {
        this.expandedRequestType = this.expandedRequestType === type ? null : type;
      } else {
        this.showToast(`No ${type} requests pending`, 'info');
      }
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
        const a = responseData.attendance || {};

        if (responseData.stats) {
          this.vlRemaining = responseData.stats.vl_remaining ?? this.vlRemaining;
          this.slRemaining = responseData.stats.sl_remaining ?? this.slRemaining;
          this.vlCarryover = responseData.stats.vl_carried_over_remaining ?? this.vlCarryover;
          this.birthdayLeave = responseData.stats.birthday_leave_available ?? this.birthdayLeave;
        }

        this.status = a.status || 'OUT';
        this.isLoggedIn = !!a.is_logged_in;
        this.timeIn = a.time_in || null;
        this.timeOut = a.time_out || null;
        this.workingHours = a.working_hours || '0h 0m';
        this.activeBreak = a.active_break || null;

        if (Array.isArray(responseData.announcements)) this.announcements = responseData.announcements;
        if (Array.isArray(responseData.requests)) this.requests = responseData.requests;
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
      if (!this.isLoggedIn) {
        return 'bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700';
      }
      if (this.activeBreak) {
        return 'bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700';
      }
      return 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700';
    },

    getCurrentActionText() {
      if (!this.isLoggedIn) return 'Time In';
      if (this.activeBreak) return `End ${this.activeBreak.label || this.activeBreak.type}`;
      return 'Select Action';
    },

    getCurrentActionSubtext() {
      if (!this.isLoggedIn) return 'Start your work day';
      if (this.activeBreak) return 'Return to work';
      return 'Choose time or break action';
    },

    getCurrentActionIcon() {
      if (!this.isLoggedIn) {
        return 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1';
      }
      if (this.activeBreak) {
        return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z';
      }
      return 'M19 9l-7 7-7-7';
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

    viewRequest(request) {
      this.selectedRequest = { ...request };
      this.showRejectionNote = false;
      this.rejectionNote = '';
    },

    async handleRequestAction(action) {
      if (!this.selectedRequest || this.processing) return;

      if (this.selectedRequest.type !== 'leave' && this.selectedRequest.type !== 'overtime' && this.selectedRequest.type !== 'shift') {
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
        } else if (this.selectedRequest.type === 'overtime') {
          endpoint = `/user/dashboard/overtime/${this.selectedRequest.id}/${action}`;
        } else if (this.selectedRequest.type === 'shift') {
          endpoint = `/user/dashboard/shift-change/${this.selectedRequest.id}/${action}`;
        }

        await axios.post(endpoint, payload);

        this.requests = this.requests.filter(r =>
          !(r.id === this.selectedRequest.id && r.type === this.selectedRequest.type)
        );

        const actionText = action === 'approve' ? 'approved' : 'rejected';
        const requestTypeLabels = {
          'leave': 'Leave',
          'overtime': 'Overtime',
          'shift': 'Shift change'
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
        'overtime': 'Overtime Request', 
        'attendance': 'Attendance Correction',
        'shift': 'Shift Change'
      };
      return labels[type] || 'Request';
    },

    getRequestTypeBadgeClass(type) {
      const classes = {
        'leave': 'bg-blue-100 text-blue-800',
        'overtime': 'bg-orange-100 text-orange-800',
        'attendance': 'bg-green-100 text-green-800',
        'shift': 'bg-purple-100 text-purple-800'
      };
      return classes[type] || 'bg-gray-100 text-gray-800';
    },

    getStatusBadgeClass(status) {
      const statusLower = (status || '').toLowerCase();
      if (statusLower === 'approved') return 'bg-green-100 text-green-800';
      if (statusLower === 'rejected') return 'bg-red-100 text-red-800';
      return 'bg-yellow-100 text-yellow-800';
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