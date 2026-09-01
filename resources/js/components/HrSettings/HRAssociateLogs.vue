<template>
  <div class="min-h-screen bg-gray-50 p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-7xl">
      <!-- Header -->
      <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Associate Logs Management</h1>
        </div>
        <button
          @click="showForm = !showForm"
          class="flex items-center space-x-2 rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
          <span>Add Log Entry</span>
        </button>
      </div>

      <!-- Create Form -->
      <!-- Complete Improved Add Log Entry Form -->
      <div v-if="showForm" class="mb-8 rounded-xl bg-white shadow-lg border border-gray-200 overflow-hidden">
        <!-- Form Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="flex items-center justify-center h-8 w-8 rounded-full bg-white bg-opacity-20">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-semibold text-white">Add New Associate Log Entry</h2>
              </div>
            </div>
            <button
              @click="cancelForm"
              type="button"
              class="text-white hover:text-blue-100 transition-colors"
            >
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Form Content -->
        <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
          
          <!-- Employee Selection Section -->
          <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
            <div class="mb-4 flex items-center space-x-2">
              <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              <h3 class="text-lg font-medium text-gray-900">Select Employees</h3>
              <span class="text-sm text-gray-500">(Required)</span>
            </div>
            
            <!-- Search and Controls Header -->
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
              <!-- Search Bar -->
              <div class="mb-3">
                <div class="relative">
                  <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                  </div>
                  <input
                    v-model="userSearch"
                    type="text"
                    placeholder="Search employees by name, email, or department..."
                    class="block w-full rounded-lg border border-gray-300 pl-10 pr-10 py-3 text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all"
                  />
                  <div v-if="userSearch" class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <button
                      @click="userSearch = ''"
                      type="button"
                      class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Selection Controls -->
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <!-- Select All Controls -->
                <div class="flex items-center space-x-4">
                  <label class="flex items-center cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="isAllFilteredSelected"
                      :indeterminate.prop="isPartiallySelected"
                      @change="toggleSelectAllFiltered"
                      class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                    />
                    <span class="ml-2 text-sm font-medium text-gray-700">
                      Select All Visible ({{ filteredUsers.length }})
                    </span>
                  </label>
                  
                  <button
                    v-if="userSearch && filteredUsers.length < users.length"
                    @click="selectAllUsers"
                    type="button"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors"
                  >
                    Select All {{ users.length }} Users
                  </button>
                </div>
                
                <!-- Selection Summary -->
                <div class="flex items-center space-x-3">
                  <div class="text-sm text-gray-600">
                    <span class="font-semibold text-blue-600">{{ form.user_ids.length }}</span> 
                    of {{ users.length }} selected
                    <span v-if="selectionStats.percentage > 0" class="ml-1 text-xs text-gray-500">
                      ({{ selectionStats.percentage }}%)
                    </span>
                  </div>
                  
                  <button
                    v-if="form.user_ids.length > 0"
                    @click="clearAllSelections"
                    type="button"
                    class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-full transition-colors"
                  >
                    Clear All
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Selected Users Preview -->
            <div v-if="form.user_ids.length > 0" class="mb-4">
              <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <div class="mb-3 flex items-center justify-between">
                  <div class="text-sm font-medium text-blue-800 uppercase tracking-wide">
                    Selected Employees ({{ form.user_ids.length }})
                  </div>
                  <button
                    @click="showFullSelectionList = !showFullSelectionList"
                    type="button"
                    class="flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium"
                  >
                    {{ showFullSelectionList ? 'Hide Details' : 'Show Details' }}
                    <svg class="ml-1 h-4 w-4 transition-transform" :class="{ 'rotate-180': showFullSelectionList }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </button>
                </div>
                
                <!-- Compact Preview -->
                <div v-if="!showFullSelectionList" class="flex flex-wrap gap-2">
                  <div
                    v-for="user in selectedUsersPreview"
                    :key="user.id"
                    class="inline-flex items-center rounded-full bg-blue-100 pl-3 pr-2 py-1 text-sm text-blue-800"
                  >
                    <span class="font-medium">{{ user.name }}</span>
                    <button
                      @click="removeUser(user.id)"
                      type="button"
                      class="ml-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-200 text-blue-600 hover:bg-blue-300 hover:text-blue-800 transition-colors"
                    >
                      <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                  
                  <div v-if="form.user_ids.length > maxPreviewUsers" class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800">
                    <span>+{{ form.user_ids.length - maxPreviewUsers }} more</span>
                  </div>
                </div>
                
                <!-- Detailed List -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-48 overflow-y-auto">
                  <div
                    v-for="user in allSelectedUsers"
                    :key="user.id"
                    class="flex items-center justify-between bg-white rounded-lg px-3 py-2 shadow-sm"
                  >
                    <div class="min-w-0 flex-1">
                      <div class="font-medium text-gray-900 text-sm truncate">{{ user.name }}</div>
                      <div class="text-xs text-gray-500 truncate">{{ user.email }}</div>
                      <div v-if="user.department" class="text-xs text-gray-400">{{ user.department }}</div>
                    </div>
                    <button
                      @click="removeUser(user.id)"
                      type="button"
                      class="ml-2 text-gray-400 hover:text-red-500 transition-colors"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Employee List -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
              <!-- List Header -->
              <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <div class="flex items-center justify-between">
                  <h4 class="text-sm font-medium text-gray-900">
                    {{ userSearch ? `Search Results (${filteredUsers.length})` : `All Employees (${users.length})` }}
                  </h4>
                  
                  <!-- Quick Department Filters -->
                  <div v-if="availableDepartments.length > 1" class="flex flex-wrap gap-1">
                    <button
                      v-for="dept in availableDepartments.slice(0, 4)"
                      :key="dept"
                      @click="toggleDepartmentFilter(dept)"
                      type="button"
                      class="px-2 py-1 text-xs rounded-full transition-colors"
                      :class="selectedDepartments.includes(dept) 
                        ? 'bg-blue-100 text-blue-800 border border-blue-200' 
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    >
                      {{ dept }}
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Employee Grid -->
              <div class="max-h-80 overflow-y-auto">
                <!-- No results message -->
                <div v-if="filteredUsers.length === 0" class="p-8 text-center">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                  </svg>
                  <p class="mt-2 text-sm text-gray-500">
                    {{ userSearch ? 'No employees found matching your search.' : 'No employees available.' }}
                  </p>
                  <button
                    v-if="userSearch"
                    @click="userSearch = ''"
                    type="button"
                    class="mt-2 text-sm text-blue-600 hover:text-blue-800 transition-colors"
                  >
                    Clear search
                  </button>
                </div>
                
                <!-- Employee List -->
                <div v-else class="divide-y divide-gray-200">
                  <label
                    v-for="user in filteredUsers"
                    :key="user.id"
                    class="flex items-center p-4 hover:bg-gray-50 cursor-pointer transition-colors group"
                    :class="{ 'bg-blue-50 border-l-4 border-l-blue-500': form.user_ids.includes(user.id) }"
                  >
                    <!-- Checkbox -->
                    <input
                      type="checkbox"
                      :value="user.id"
                      v-model="form.user_ids"
                      class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
                    />
                    
                    <!-- User Avatar -->
                    <div class="ml-3 flex-shrink-0">
                      <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                        <span class="text-sm font-semibold text-white">
                          {{ user.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() }}
                        </span>
                      </div>
                    </div>
                    
                    <!-- User Info -->
                    <div class="ml-3 min-w-0 flex-1">
                      <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                          <p class="text-sm font-medium text-gray-900 truncate group-hover:text-blue-600 transition-colors">
                            {{ user.name }}
                          </p>
                          <p class="text-sm text-gray-500 truncate">{{ user.email }}</p>
                          <p v-if="user.department" class="text-xs text-gray-400 mt-1">
                            {{ user.department }}
                          </p>
                        </div>
                        
                        <!-- Selection Indicator -->
                        <div v-if="form.user_ids.includes(user.id)" class="flex-shrink-0 ml-3">
                          <div class="flex items-center justify-center h-6 w-6 rounded-full bg-blue-500">
                            <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                          </div>
                        </div>
                      </div>
                    </div>
                  </label>
                </div>
              </div>
            </div>
            
            <!-- Validation Error -->
            <p v-if="errors.user_ids" class="mt-3 text-sm text-red-600 flex items-center bg-red-50 p-3 rounded-lg border border-red-200">
              <svg class="h-4 w-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ errors.user_ids[0] }}
            </p>
          </div>

          <!-- Date and Entry Details Row -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Date -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                <svg class="inline h-4 w-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Date <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.date"
                type="date"
                required
                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all"
                :class="{ 'border-red-300 bg-red-50': errors.date }"
              />
              <p v-if="errors.date" class="mt-1 text-sm text-red-600 flex items-center">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ errors.date[0] }}
              </p>
            </div>

            <!-- File Upload -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                <svg class="inline h-4 w-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                Attachment (Optional)
              </label>
              <div class="relative">
                <input
                  @change="handleFileChange"
                  type="file"
                  accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                  class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all"
                  :class="{ 'border-red-300 bg-red-50': errors.attachment }"
                />
              </div>
              <p class="mt-1 text-xs text-gray-500">
                PDF, Word documents, or images (max 5MB)
              </p>
              <p v-if="errors.attachment" class="mt-1 text-sm text-red-600 flex items-center">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ errors.attachment[0] }}
              </p>
            </div>
          </div>

          <!-- Entry Details -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <svg class="inline h-4 w-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Entry Details <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.entry_details"
              rows="4"
              required
              placeholder="Enter detailed log information..."
              class="w-full resize-none rounded-lg border border-gray-300 px-4 py-3 text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 transition-all"
              :class="{ 'border-red-300 bg-red-50': errors.entry_details }"
            ></textarea>
            <div class="mt-1 flex justify-between items-center">
              <p v-if="errors.entry_details" class="text-sm text-red-600 flex items-center">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ errors.entry_details[0] }}
              </p>
              <p class="text-xs text-gray-500">
                {{ form.entry_details.length }}/1000 characters
              </p>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3 pt-6 border-t border-gray-200">
            <button
              type="button"
              @click="cancelForm"
              class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <svg v-if="submitting" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
              </svg>
              {{ submitting ? 'Creating Log Entry...' : `Create Log Entry${form.user_ids.length > 0 ? ` (${form.user_ids.length} users)` : ''}` }}
            </button>
          </div>
        </form>
      </div>

      <!-- Filters -->
      <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Filter by Employee</label>
            <select
              v-model="filters.user_id"
              @change="loadLogs"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Employees</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Search Entries</label>
            <input
              v-model="filters.search"
              @input="debounceSearch"
              type="text"
              placeholder="Search entry details..."
              class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="flex items-end">
            <button
              @click="clearFilters"
              class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>

      <!-- Logs Table -->
      <div class="rounded-lg bg-white shadow-sm">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <svg class="h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
        </div>
        
        <div v-else-if="logs.length === 0" class="py-12 text-center">
          <p class="text-gray-500">No associate logs found</p>
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Entry Details</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Attachment</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Created By</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
                <td class="px-4 py-3">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ log.user?.name }}</div>
                    <div class="text-sm text-gray-500">{{ log.user?.email }}</div>
                  </div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ log.date }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ log.entry_details }}</td>
                <td class="px-4 py-3">
                  <button
                    v-if="log.has_attachment"
                    @click="downloadAttachment(log.attachment)"
                    class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ log.attachment.filename }}
                  </button>
                  <span v-else class="text-gray-400 text-xs">No attachment</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ log.created_by }}</td>
                <td class="px-4 py-3">
                  <button
                    @click="handleDelete(log.id)"
                    class="text-red-600 hover:text-red-800 transition-colors"
                    title="Delete log"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios';

export default {
  name: 'HRAssociateLogs',
  
  data() {
    return {
      logs: [],
      users: [],
      loading: false,
      showForm: false,
      submitting: false,
      errors: {},
      searchTimeout: null,
      
      // Enhanced UI state
      userSearch: '',
      showFullSelectionList: false,
      selectedDepartments: [],
      maxPreviewUsers: 5,
      
      filters: {
        user_id: '',
        search: ''
      },
      
      form: {
        user_ids: [],
        entry_details: '',
        date: new Date().toISOString().split('T')[0],
        attachment: null
      }
    };
  },

  computed: {
    // Check if all users are selected (legacy - keep for compatibility)
    isAllSelected() {
      return this.users.length > 0 && this.form.user_ids.length === this.users.length;
    },

    // Filter users based on search and department filters
    filteredUsers() {
      let filtered = this.users;
      
      // Apply search filter
      if (this.userSearch.trim()) {
        const search = this.userSearch.toLowerCase();
        filtered = filtered.filter(user => 
          user.name.toLowerCase().includes(search) ||
          user.email.toLowerCase().includes(search) ||
          (user.department && user.department.toLowerCase().includes(search))
        );
      }
      
      // Apply department filter
      if (this.selectedDepartments.length > 0) {
        filtered = filtered.filter(user => 
          this.selectedDepartments.includes(user.department || 'Other')
        );
      }
      
      return filtered.sort((a, b) => a.name.localeCompare(b.name));
    },
    
    // Check if all filtered users are selected
    isAllFilteredSelected() {
      return this.filteredUsers.length > 0 && 
             this.filteredUsers.every(user => this.form.user_ids.includes(user.id));
    },
    
    // Check if some (but not all) filtered users are selected
    isPartiallySelected() {
      const selectedFiltered = this.filteredUsers.filter(user => 
        this.form.user_ids.includes(user.id)
      ).length;
      return selectedFiltered > 0 && selectedFiltered < this.filteredUsers.length;
    },
    
    // Get available departments for filtering
    availableDepartments() {
      const departments = new Set();
      this.users.forEach(user => {
        departments.add(user.department || 'Other');
      });
      return Array.from(departments).sort();
    },
    
    // Get all selected users with full info
    allSelectedUsers() {
      return this.users
        .filter(user => this.form.user_ids.includes(user.id))
        .sort((a, b) => a.name.localeCompare(b.name));
    },
    
    // Get preview of selected users (first few)
    selectedUsersPreview() {
      return this.allSelectedUsers.slice(0, this.maxPreviewUsers);
    },
    
    // Get selection statistics
    selectionStats() {
      const totalUsers = this.users.length;
      const selectedCount = this.form.user_ids.length;
      const filteredCount = this.filteredUsers.length;
      const selectedFilteredCount = this.filteredUsers.filter(user => 
        this.form.user_ids.includes(user.id)
      ).length;
      
      return {
        total: totalUsers,
        selected: selectedCount,
        filtered: filteredCount,
        selectedFiltered: selectedFilteredCount,
        percentage: totalUsers > 0 ? Math.round((selectedCount / totalUsers) * 100) : 0
      };
    }
  },

  watch: {
    // Auto-hide full selection list when selection changes significantly
    'form.user_ids'(newIds, oldIds) {
      if (Math.abs(newIds.length - oldIds.length) > 5) {
        this.showFullSelectionList = false;
      }
    }
  },

  async mounted() {
    await this.loadData();
  },

  methods: {
    async loadData() {
      await Promise.all([this.loadLogs(), this.loadUsers()]);
    },

    async loadLogs() {
      try {
        this.loading = true;
        const params = new URLSearchParams();
        if (this.filters.user_id) params.append('user_id', this.filters.user_id);
        if (this.filters.search) params.append('search', this.filters.search);
        
        const { data } = await axios.get(`/hr/associate-logs?${params}`);
        this.logs = data.data || [];
      } catch (error) {
        console.error('Failed to load logs:', error);
        this.showToast('Failed to load associate logs', 'error');
      } finally {
        this.loading = false;
      }
    },

    async loadUsers() {
      try {
        // FIXED: Use the correct endpoint
        const { data } = await axios.get('/hr/associate-logs/users');
        this.users = data.data || [];
      } catch (error) {
        console.error('Failed to load users:', error);
        this.showToast('Failed to load users', 'error');
      }
    },

    // Enhanced selection methods
    toggleSelectAllFiltered() {
      if (this.isAllFilteredSelected) {
        this.deselectAllFiltered();
      } else {
        this.selectAllFiltered();
      }
    },
    
    selectAllFiltered() {
      const filteredIds = this.filteredUsers.map(user => user.id);
      // Add filtered IDs that aren't already selected
      const newIds = filteredIds.filter(id => !this.form.user_ids.includes(id));
      this.form.user_ids = [...this.form.user_ids, ...newIds];
    },
    
    deselectAllFiltered() {
      const filteredIds = this.filteredUsers.map(user => user.id);
      this.form.user_ids = this.form.user_ids.filter(id => !filteredIds.includes(id));
    },
    
    selectAllUsers() {
      this.form.user_ids = this.users.map(user => user.id);
      this.userSearch = ''; // Clear search to show all selections
      this.selectedDepartments = []; // Clear department filters
    },
    
    clearAllSelections() {
      this.form.user_ids = [];
      this.showFullSelectionList = false;
    },
    
    removeUser(userId) {
      this.form.user_ids = this.form.user_ids.filter(id => id !== userId);
    },
    
    toggleDepartmentFilter(department) {
      const index = this.selectedDepartments.indexOf(department);
      if (index > -1) {
        this.selectedDepartments.splice(index, 1);
      } else {
        this.selectedDepartments.push(department);
      }
    },

    // Legacy method - keep for compatibility
    toggleSelectAll() {
      if (this.isAllSelected) {
        this.form.user_ids = [];
      } else {
        this.form.user_ids = this.users.map(user => user.id);
      }
    },

    // Enhanced form validation
    validateForm() {
      const errors = {};
      
      if (this.form.user_ids.length === 0) {
        errors.user_ids = ['Please select at least one employee.'];
      }
      
      if (!this.form.entry_details.trim()) {
        errors.entry_details = ['Entry details are required.'];
      } else if (this.form.entry_details.length > 1000) {
        errors.entry_details = ['Entry details cannot exceed 1000 characters.'];
      }
      
      if (!this.form.date) {
        errors.date = ['Date is required.'];
      }
      
      // File validation
      if (this.form.attachment) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        const fileExtension = this.form.attachment.name.split('.').pop().toLowerCase();
        
        if (this.form.attachment.size > maxSize) {
          errors.attachment = ['File size cannot exceed 5MB.'];
        } else if (!allowedTypes.includes(fileExtension)) {
          errors.attachment = ['File type not allowed. Please use PDF, Word documents, or images.'];
        }
      }
      
      this.errors = errors;
      return Object.keys(errors).length === 0;
    },

    async handleSubmit() {
      if (!this.validateForm()) {
        this.showToast('Please fix the errors in the form', 'error');
        return;
      }

      try {
        this.submitting = true;
        this.errors = {};
        
        const formData = new FormData();
        
        // FIXED: Send user_ids as individual array elements instead of JSON string
        this.form.user_ids.forEach((userId, index) => {
          formData.append(`user_ids[${index}]`, userId);
        });
        
        formData.append('entry_details', this.form.entry_details);
        formData.append('date', this.form.date);
        if (this.form.attachment) {
          formData.append('attachment', this.form.attachment);
        }

        const { data } = await axios.post('/hr/associate-logs', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (data.success) {
          // Add all created logs to the list
          if (Array.isArray(data.data)) {
            this.logs.unshift(...data.data);
          } else {
            this.logs.unshift(data.data);
          }
          
          // Get names of affected users for success message
          const affectedUsers = this.allSelectedUsers.map(user => user.name);
          const userCount = this.form.user_ids.length;
          
          let message;
          if (userCount === 1) {
            message = `Associate log created successfully for ${affectedUsers[0]}`;
          } else if (userCount <= 3) {
            message = `Associate log created successfully for ${affectedUsers.join(', ')}`;
          } else {
            message = `Associate log created successfully for ${userCount} employees`;
          }
          
          this.resetForm();
          this.showForm = false;
          this.showToast(message, 'success', 5000);
        }
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {};
          this.showToast('Please check the form for errors', 'error');
        } else {
          const errorMessage = error.response?.data?.message || 'Failed to create associate log';
          this.showToast(errorMessage, 'error');
        }
      } finally {
        this.submitting = false;
      }
    },

    async handleDelete(logId) {
      if (!confirm('Are you sure you want to delete this associate log?')) return;

      try {
        const { data } = await axios.delete(`/hr/associate-logs/${logId}`);
        
        if (data.success) {
          this.logs = this.logs.filter(log => log.id !== logId);
          this.showToast('Associate log deleted successfully', 'success');
        }
      } catch (error) {
        this.showToast(error.response?.data?.message || 'Failed to delete log', 'error');
      }
    },

    async downloadAttachment(attachment) {
      try {
        // The attachment.download_url should now work correctly
        const response = await axios.get(attachment.download_url, {
          responseType: 'blob',
          headers: { 'Accept': 'application/octet-stream' }
        });

        const blob = new Blob([response.data]);
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        
        link.href = url;
        link.download = attachment.filename;
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        this.showToast(`Downloading ${attachment.filename}`, 'info', 2000);
      } catch (error) {
        console.error('Download failed:', error);
        this.showToast('Failed to download file', 'error');
      }
    },

    handleFileChange(event) {
      const file = event.target.files[0] || null;
      this.form.attachment = file;
      
      // Clear any previous file errors
      if (this.errors.attachment) {
        delete this.errors.attachment;
        this.errors = { ...this.errors };
      }
      
      // Validate file immediately
      if (file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (file.size > maxSize) {
          this.errors = { ...this.errors, attachment: ['File size cannot exceed 5MB.'] };
          this.form.attachment = null;
          event.target.value = '';
        } else if (!allowedTypes.includes(fileExtension)) {
          this.errors = { ...this.errors, attachment: ['File type not allowed. Please use PDF, Word documents, or images.'] };
          this.form.attachment = null;
          event.target.value = '';
        }
      }
    },

    debounceSearch() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.loadLogs();
      }, 500);
    },

    clearFilters() {
      this.filters = { user_id: '', search: '' };
      this.loadLogs();
    },

    cancelForm() {
      this.showForm = false;
      this.resetForm();
    },

    resetForm() {
      this.form = {
        user_ids: [],
        entry_details: '',
        date: new Date().toISOString().split('T')[0],
        attachment: null
      };
      this.errors = {};
      this.userSearch = '';
      this.selectedDepartments = [];
      this.showFullSelectionList = false;
      
      // Reset file input
      const fileInput = document.querySelector('input[type="file"]');
      if (fileInput) {
        fileInput.value = '';
      }
    },

    // Enhanced toast notifications
    showToast(message, type = 'info', duration = 4000) {
      // Remove any existing toasts
      const existingToasts = document.querySelectorAll('.custom-toast');
      existingToasts.forEach(toast => toast.remove());
      
      const toast = document.createElement('div');
      toast.className = 'custom-toast fixed top-4 right-4 z-50 max-w-md rounded-lg shadow-lg transition-all duration-300 transform translate-x-full';
      
      const colors = {
        'success': 'bg-green-500 text-white',
        'error': 'bg-red-500 text-white',
        'warning': 'bg-yellow-500 text-black',
        'info': 'bg-blue-500 text-white'
      };
      
      const icons = {
        'success': `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`,
        'error': `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`,
        'warning': `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
        'info': `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`
      };
      
      toast.className += ` ${colors[type] || colors.info}`;
      
      toast.innerHTML = `
        <div class="flex items-center p-4">
          <div class="flex-shrink-0">
            ${icons[type] || icons.info}
          </div>
          <div class="ml-3 flex-1">
            <p class="text-sm font-medium">${message}</p>
          </div>
          <div class="ml-4 flex-shrink-0">
            <button onclick="this.closest('.custom-toast').remove()" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-gray-600 opacity-70 hover:opacity-100">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>
      `;
      
      document.body.appendChild(toast);
      
      // Animate in
      requestAnimationFrame(() => {
        toast.style.transform = 'translateX(0)';
      });
      
      // Auto remove
      setTimeout(() => {
        if (toast.parentNode) {
          toast.style.transform = 'translateX(full)';
          setTimeout(() => {
            if (toast.parentNode) {
              document.body.removeChild(toast);
            }
          }, 300);
        }
      }, duration);
    }
  }
};
</script>