<template>
  <div class="min-h-screen bg-canvas p-0 sm:p-6 lg:p-8 pt-[env(safe-area-inset-top)]">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-[400px]">
      <div class="text-center">
        <svg class="motion-safe:animate-spin h-12 w-12 text-accent-solid mx-auto mb-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-text-muted">Loading profile...</p>
      </div>
    </div>

    <!-- Profile Content -->
    <div v-else-if="profile" class="mx-auto max-w-7xl px-2 sm:px-4 lg:px-6">
      <!-- Title bar, matching Attendance and Leave Requests -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Profile</h1>
        <button
          @click="toggleEditMode"
          :disabled="updating"
          class="flex flex-shrink-0 items-center justify-center gap-2 rounded-lg bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover disabled:opacity-50"
        >
            <svg v-if="!editMode" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          <span>{{ editMode ? 'Cancel' : 'Edit Profile' }}</span>
        </button>
      </div>

      <!-- Identity card -->
      <header class="mb-3 sm:mb-4 rounded-xl bg-surface p-4 shadow-sm sm:rounded-2xl sm:p-6">
        <!-- Profile Avatar and Basic Info -->
        <section class="mb-8 flex flex-col items-start gap-6 md:flex-row md:items-center md:gap-6" aria-labelledby="basic-info">
          <!-- Avatar -->
          <div class="relative shrink-0">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-accent-solid text-lg font-bold text-white sm:h-20 sm:w-20 sm:text-xl lg:h-24 lg:w-24 lg:text-2xl" aria-hidden="true">
              {{ profile.initials }}
            </div>
          </div>

          <!-- Basic Info -->
          <div class="w-full flex-1">
            <!-- Name (Editable) -->
            <div v-if="!editMode" class="text-center sm:text-left">
              <h2 id="basic-info" class="text-2xl font-bold text-text sm:text-3xl">{{ profile.name }}</h2>
              <p class="mt-1 text-text-muted">{{ profile.position_label }}</p>
            </div>
            <div v-else class="space-y-3">
              <div>
                <label class="mb-1 block text-sm font-medium text-text">Full Name</label>
                <input
                  v-model="editForm.name"
                  type="text"
                  class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                  :class="{ 'border-danger': errors.name }"
                />
                <p v-if="errors.name" class="mt-1 text-sm text-danger">{{ errors.name[0] }}</p>
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-text">Position Title</label>
                <input
                  v-model="editForm.position_name"
                  type="text"
                  class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                  :class="{ 'border-danger': errors.position_name }"
                />
                <p v-if="errors.position_name" class="mt-1 text-sm text-danger">{{ errors.position_name[0] }}</p>
              </div>
            </div>

            <!-- Status Badges -->
            <div class="mt-4 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
              <span class="inline-flex items-center rounded-full bg-status-green px-3 py-1 text-xs font-medium text-status-text">
                {{ profile.employment_status_label }}
              </span>

              <span :class="[
                'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium text-status-text',
                getEmploymentTypeColor(profile.employment_type)
              ]">
                {{ profile.employment_type_label }}
              </span>

              <span class="inline-flex items-center rounded-full bg-status-yellow px-3 py-1 text-xs font-medium text-status-text">
                {{ getShiftLabel() }}
              </span>

              <span class="inline-flex items-center rounded-full bg-status-orange px-3 py-1 text-xs font-medium text-status-text">
                {{ profile.leave_scheme_label }}
              </span>
            </div>
          </div>
        </section>

        <!-- Save/Cancel Buttons for Edit Mode -->
        <div v-if="editMode" class="flex flex-col items-stretch space-y-2 border-t pt-6 sm:flex-row sm:items-center sm:space-y-0 sm:space-x-3">
          <button
            @click="saveProfile"
            :disabled="updating"
            class="flex items-center space-x-2 rounded-lg bg-success px-6 py-2 text-white transition-colors hover:bg-success/90 disabled:opacity-50"
          >
            <svg v-if="updating" class="h-4 w-4 motion-safe:animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ updating ? 'Saving...' : 'Save Changes' }}</span>
          </button>

          <button
            @click="toggleEditMode"
            :disabled="updating"
            class="rounded-lg border border-border px-6 py-2 text-text transition-colors hover:bg-surface-sunken disabled:opacity-50"
          >
            Cancel
          </button>
        </div>
      </header>

      <!-- Tabs Navigation -->
      <div class="rounded-t-2xl bg-surface shadow-sm supports-[backdrop-filter]:bg-surface/70">
        <div class="border-b border-border">
          <!-- Mobile: Select -->
          <div class="sm:hidden p-3">
            <label for="tab-select" class="sr-only">Select section</label>
            <select id="tab-select" v-model="activeTab" class="w-full rounded-lg border border-border px-3 py-2 text-sm focus:border-accent focus:ring-2 focus:ring-accent">
              <option v-for="tab in tabs" :key="tab.id" :value="tab.id">{{ tab.name }}</option>
            </select>
          </div>

          <!-- Desktop: Tabs -->
          <nav
            class="scrollbar-hide hidden overflow-x-auto px-2 py-3 sm:flex sm:px-4 sm:py-4 lg:px-8"
            role="tablist"
            aria-label="Profile sections"
          >
            <button
              v-for="(tab, idx) in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              @keydown.left.prevent="focusPrevTab(idx)"
              @keydown.right.prevent="focusNextTab(idx)"
              :aria-selected="activeTab === tab.id"
              role="tab"
              :tabindex="activeTab === tab.id ? 0 : -1"
              :class="[
                'mr-3 inline-flex items-center space-x-1 whitespace-nowrap border-b-2 px-1 py-2 text-xs font-medium transition-colors sm:mr-6 sm:space-x-2 sm:text-sm lg:mr-8',
                activeTab === tab.id
                  ? 'border-accent text-accent-solid'
                  : 'border-transparent text-text-muted hover:border-border hover:text-text'
              ]"
            >
              <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path v-if="tab.id === 'personal'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                <path v-else-if="tab.id === 'employment'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"/>
                <path v-else-if="tab.id === 'logs'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span>{{ tab.name }}</span>
              <span v-if="tab.badge" :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                tab.badgeColor || 'bg-surface-sunken text-text'
              ]">
                {{ tab.badge }}
              </span>
            </button>
          </nav>
        </div>
      </div>

      <!-- Tabs Content -->
      <div class="rounded-b-2xl bg-surface shadow-sm">
        <!-- Personal Information Tab -->
        <section v-if="activeTab === 'personal'" class="p-4 sm:p-6 lg:p-8" role="tabpanel" aria-labelledby="personal-tab">
          <div class="sm:mx-auto sm:max-w-7xl">
            <form @submit.prevent="savePersonalInfo">
              <!-- Contact Information Section -->
              <div class="mb-6 sm:mb-8">
                <h3 class="mb-6 flex items-center text-lg font-semibold text-text">
                  <svg class="mr-2 h-5 w-5 text-accent-solid" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                  Contact Information
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Email Address</label>
                    <div class="flex items-center space-x-2 rounded-lg border border-border bg-surface-sunken px-4 py-2">
                      <svg class="h-5 w-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      <span class="break-all text-text">{{ profile.email }}</span>
                    </div>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Phone Number</label>
                    <input
                      v-model="personalForm.phone_number"
                      type="tel"
                      placeholder="e.g. +63 912 345 6789"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.phone_number'] }"
                    />
                    <p v-if="errors['personal_info.phone_number']" class="mt-1 text-sm text-danger">{{ errors['personal_info.phone_number'][0] }}</p>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Alternate Phone</label>
                    <input
                      v-model="personalForm.alternate_phone_number"
                      type="tel"
                      placeholder="e.g. +63 912 345 6789"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.alternate_phone_number'] }"
                    />
                  </div>
                </div>
              </div>

              <!-- Personal Details Section -->
              <div class="mb-6 sm:mb-8">
                <h3 class="mb-6 flex items-center text-lg font-semibold text-text">
                  <svg class="mr-2 h-5 w-5 text-accent-solid" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                  Personal Details
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2 xl:grid-cols-3">
                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Date of Birth</label>
                    <input
                      v-model="personalForm.date_of_birth"
                      type="date"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.date_of_birth'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Gender</label>
                    <select
                      v-model="personalForm.gender"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                    >
                      <option value="">Select Gender</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                    </select>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Marital Status</label>
                    <select
                      v-model="personalForm.marital_status"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                    >
                      <option value="">Select Status</option>
                      <option value="single">Single</option>
                      <option value="married">Married</option>
                      <option value="divorced">Divorced</option>
                      <option value="widowed">Widowed</option>
                    </select>
                  </div>

                  <div v-if="personalForm.marital_status === 'married'">
                    <label class="mb-2 block text-sm font-medium text-text">Spouse Name</label>
                    <input
                      v-model="personalForm.spouse_name"
                      type="text"
                      placeholder="Enter spouse name"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.spouse_name'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Number of Children</label>
                    <input
                      v-model="personalForm.num_children"
                      type="number"
                      min="0"
                      max="20"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.num_children'] }"
                    />
                  </div>
                </div>
              </div>

              <!-- Address Information Section -->
              <div class="mb-6 sm:mb-8">
                <h3 class="mb-6 flex items-center text-lg font-semibold text-text">
                  <svg class="mr-2 h-5 w-5 text-accent-solid" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  Address Information
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Permanent Address</label>
                    <textarea
                      v-model="personalForm.permanent_address"
                      rows="4"
                      placeholder="Enter permanent address"
                      class="w-full resize-none rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.permanent_address'] }"
                    ></textarea>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Current Address</label>
                    <textarea
                      v-model="personalForm.current_address"
                      rows="4"
                      placeholder="Enter current address"
                      class="w-full resize-none rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.current_address'] }"
                    ></textarea>
                    <div class="mt-2">
                      <label class="flex items-center">
                        <input
                          type="checkbox"
                          @change="copyPermanentAddress"
                          class="rounded border-border text-accent-solid focus:ring-accent"
                        />
                        <span class="ml-2 text-sm text-text-muted">Same as permanent address</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Emergency Contact Section -->
              <div class="mb-6 sm:mb-8">
                <h3 class="mb-6 flex items-center text-lg font-semibold text-text">
                  <svg class="mr-2 h-5 w-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                  Emergency Contact
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-3">
                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Contact Name</label>
                    <input
                      v-model="personalForm.emergency_contact_name"
                      type="text"
                      placeholder="Enter contact name"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.emergency_contact_name'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Contact Number</label>
                    <input
                      v-model="personalForm.emergency_contact_number"
                      type="tel"
                      placeholder="e.g. +63 912 345 6789"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.emergency_contact_number'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Relationship</label>
                    <input
                      v-model="personalForm.emergency_contact_relationship"
                      type="text"
                      placeholder="e.g. Spouse, Parent, Sibling"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.emergency_contact_relationship'] }"
                    />
                  </div>
                </div>
              </div>

              <!-- Government IDs Section -->
              <div class="mb-6 sm:mb-8">
                <h3 class="mb-6 flex items-center text-lg font-semibold text-text">
                  <svg class="mr-2 h-5 w-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                  </svg>
                  Government IDs (Philippines)
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:gap-6 sm:grid-cols-2 xl:grid-cols-4">
                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">TIN</label>
                    <input
                      v-model="personalForm.tin"
                      type="text"
                      placeholder="000-000-000-000"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.tin'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">SSS</label>
                    <input
                      v-model="personalForm.sss"
                      type="text"
                      placeholder="00-0000000-0"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.sss'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">PhilHealth</label>
                    <input
                      v-model="personalForm.philhealth"
                      type="text"
                      placeholder="00-000000000-0"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.philhealth'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Pag-IBIG</label>
                    <input
                      v-model="personalForm.pagibig"
                      type="text"
                      placeholder="0000-0000-0000"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['personal_info.pagibig'] }"
                    />
                  </div>
                </div>
              </div>

              <!-- Save Button -->
              <div class="sm:flex sm:justify-end sm:border-t sm:pt-6">
                <!-- Mobile sticky action bar -->
                <div class="sticky bottom-0 left-0 right-0 z-10 -mx-4 -mb-4 border-t bg-surface/90 p-3 backdrop-blur supports-[backdrop-filter]:bg-surface/70 sm:static sm:m-0 sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-0">
                  <button
                    type="submit"
                    :disabled="updatingPersonal"
                    class="flex w-full items-center justify-center space-x-2 rounded-lg bg-accent-solid px-6 py-2 text-white transition-colors hover:bg-accent-hover disabled:opacity-50 sm:w-auto"
                  >
                    <svg v-if="updatingPersonal" class="h-4 w-4 motion-safe:animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ updatingPersonal ? 'Saving...' : 'Save Personal Information' }}</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </section>

        <!-- Employment Details Tab -->
        <section v-if="activeTab === 'employment'" class="p-4 sm:p-6 lg:p-8" role="tabpanel" aria-labelledby="employment-tab">
          <div class="sm:mx-auto sm:max-w-7xl">
            <form @submit.prevent="saveEmploymentInfo">
              <!-- Employee Information Section -->
              <div class="mb-6 sm:mb-8">
                <h3 class="mb-6 flex items-center text-lg font-semibold text-text">
                  <svg class="mr-2 h-5 w-5 text-accent-solid" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                  Employee Information
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2 xl:grid-cols-4">
                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Employee ID</label>
                    <input
                      v-model="employmentForm.employee_id"
                      type="text"
                      placeholder="e.g. D2019-007"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['employment_info.employee_id'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Hire Date</label>
                    <input
                      :value="profile.employment_info?.hire_date || 'Not Set'"
                      type="text"
                      readonly
                      class="w-full rounded-lg border border-border bg-surface-sunken px-4 py-2 text-text-muted cursor-not-allowed"
                    />
                    <p class="mt-1 text-xs text-text-muted">This field is managed by HR. Contact HR to update.</p>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Regularization Date</label>
                    <input
                      :value="profile.employment_info?.regularization_date || 'Not Set'"
                      type="text"
                      readonly
                      class="w-full rounded-lg border border-border bg-surface-sunken px-4 py-2 text-text-muted cursor-not-allowed"
                    />
                    <p class="mt-1 text-xs text-text-muted">This field is managed by HR. Contact HR to update.</p>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Employment Status</label>
                    <input
                      :value="profile.employment_info?.employment_status || 'Not Set'"
                      type="text"
                      readonly
                      class="w-full rounded-lg border border-border bg-surface-sunken px-4 py-2 text-text-muted cursor-not-allowed"
                    />
                    <p class="mt-1 text-xs text-text-muted">This field is managed by HR. Contact HR to update.</p>
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Employment Type</label>
                    <select
                      v-model="employmentForm.employment_type"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['employment_info.employment_type'] }"
                    >
                      <option value="">Select Employment Type</option>
                      <option value="full_time">Full Time</option>
                      <option value="part_time">Part Time</option>
                      <option value="contract">Contract</option>
                      <option value="intern">Intern</option>
                      <option value="consultant">Consultant</option>
                    </select>
                    <p v-if="errors['employment_info.employment_type']" class="mt-1 text-sm text-danger">
                      {{ errors['employment_info.employment_type'][0] }}
                    </p>
                  </div>

                </div>
              </div>

              <!-- Position Information Section -->
              <div class="mb-6 sm:mb-8">
                <h3 class="mb-6 flex items-center text-lg font-semibold text-text">
                  <svg class="mr-2 h-5 w-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"/>
                  </svg>
                  Position Information
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2 xl:grid-cols-3">
                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Position Level</label>
                    <input
                      v-model="employmentForm.position_level"
                      type="text"
                      placeholder="e.g. Senior, Junior, Manager"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['employment_info.position_level'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Career Level</label>
                    <input
                      v-model="employmentForm.career_level"
                      type="text"
                      placeholder="e.g. Head, C-Level, Manager, Supervisor, Staff"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['employment_info.career_level'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Career Band</label>
                    <input
                      v-model="employmentForm.career_band"
                      type="text"
                      placeholder="e.g. Contributor, Supervisor, Specialist, Manager, Sr Manager"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['employment_info.career_band'] }"
                    />
                  </div>

                  <div>
                    <label class="mb-2 block text-sm font-medium text-text">Career Zone</label>
                    <input
                      v-model="employmentForm.career_zone"
                      type="text"
                      placeholder="e.g. Learning, Competent, Mature"
                      class="w-full rounded-lg border border-border px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-accent"
                      :class="{ 'border-danger': errors['employment_info.career_zone'] }"
                    />
                  </div>

                </div>
              </div>

              <!-- Save Button -->
              <div class="sm:flex sm:justify-end sm:border-t sm:pt-6">
                <div class="sticky bottom-0 left-0 right-0 z-10 -mx-4 -mb-4 border-t bg-surface/90 p-3 backdrop-blur supports-[backdrop-filter]:bg-surface/70 sm:static sm:m-0 sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-0">
                  <button
                    type="submit"
                    :disabled="updatingEmployment"
                    class="flex w-full items-center justify-center space-x-2 rounded-lg bg-accent-solid px-6 py-2 text-white transition-colors hover:bg-accent-hover disabled:opacity-50 sm:w-auto"
                  >
                    <svg v-if="updatingEmployment" class="h-4 w-4 motion-safe:animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ updatingEmployment ? 'Saving...' : 'Save Employment Information' }}</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </section>

        <!-- Associate Logs Tab -->
        <section v-if="activeTab === 'logs'" class="p-4 sm:p-6 lg:p-8" role="tabpanel" aria-labelledby="logs-tab">
          <!-- Loading State -->
          <div v-if="loadingLogs" class="flex items-center justify-center py-12">
            <svg class="motion-safe:animate-spin h-8 w-8 text-accent-solid" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>

          <!-- Associate Logs Content -->
          <div v-else>
            <div class="mb-6 flex items-center justify-between">
              <h3 class="text-lg font-semibold text-text">Associate Logs</h3>
              <span class="text-sm text-text-muted">{{ associateLogs.length }} entries</span>
            </div>

            <!-- Empty State -->
            <div v-if="associateLogs.length === 0" class="py-12 text-center">
              <svg class="mx-auto mb-4 h-16 w-16 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <h3 class="mb-2 text-lg font-medium text-text">No Associate Logs</h3>
              <p class="text-text-muted">No log entries have been created for this employee yet.</p>
            </div>

            <!-- Logs Table -->
            <div v-else class="overflow-hidden rounded-lg border border-border">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                  <thead class="bg-surface-sunken">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Date</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Entry Details</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Created By</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-text-muted uppercase tracking-wider">Attachments</th>
                    </tr>
                  </thead>
                  <tbody class="bg-surface divide-y divide-border">
                    <tr v-for="log in associateLogs" :key="log.id" class="hover:bg-surface-sunken">
                      <td class="px-4 py-3 text-sm text-text whitespace-nowrap">{{ log.date }}</td>
                      <td class="px-4 py-3 text-sm text-text">{{ log.entry_details }}</td>
                      <td class="px-4 py-3 text-sm text-text-muted whitespace-nowrap">{{ log.created_by || 'System' }}</td>
                      <td class="px-4 py-3 text-sm">
                        <div v-if="log.attachments && log.attachments.length > 0" class="flex flex-wrap gap-1">
                          <button
                            v-for="attachment in log.attachments"
                            :key="attachment.id"
                            @click="downloadAttachment(attachment)"
                            class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-accent-subtle text-text hover:bg-accent-subtle transition-colors cursor-pointer"
                          >
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ attachment.filename }}
                          </button>
                        </div>
                        <span v-else class="text-text-muted text-xs">No attachments</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="mx-auto mt-20 max-w-md text-center">
      <div class="rounded-lg border border-danger bg-status-red p-6">
        <svg class="mx-auto mb-4 h-12 w-12 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <h3 class="mb-2 text-lg font-medium text-text">Failed to Load Profile</h3>
        <p class="mb-4 text-danger">{{ error }}</p>
        <button @click="loadProfile" class="rounded-lg bg-danger px-4 py-2 text-white transition-colors hover:bg-danger/90">
          Try Again
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios';

export default {
  data() {
    return {
      loading: true,
      updating: false,
      updatingPersonal: false,
      updatingEmployment: false,
      editMode: false,
      error: null,
      profile: null,
      activeTab: 'personal',
      associateLogs: [],
      loadingLogs: false,
      editForm: {
        name: '',
        position_name: ''
      },
      personalForm: {
        date_of_birth: '',
        gender: '',
        marital_status: '',
        spouse_name: '',
        num_children: 0,
        phone_number: '',
        alternate_phone_number: '',
        permanent_address: '',
        current_address: '',
        emergency_contact_name: '',
        emergency_contact_number: '',
        emergency_contact_relationship: '',
        tin: '',
        sss: '',
        philhealth: '',
        pagibig: ''
      },
      employmentForm: {
        employee_id: '',
        hire_date: '',
        regularization_date: '',
        employment_type: '',
        position_level: '',
        career_level: '',
        career_band: '',
        career_zone: '',
      },
      errors: {},
      tabs: [
        { id: 'personal', name: 'Personal Info' },
        { id: 'employment', name: 'Employment' },
        { id: 'logs', name: 'Associate Logs' }
      ]
    };
  },

  async mounted() {
    await this.loadProfile();
    await this.loadAssociateLogs();
  },

  methods: {
    focusPrevTab(idx) {
      const prev = (idx - 1 + this.tabs.length) % this.tabs.length;
      this.activeTab = this.tabs[prev].id;
    },
    focusNextTab(idx) {
      const next = (idx + 1) % this.tabs.length;
      this.activeTab = this.tabs[next].id;
    },

    async loadAssociateLogs() {
      try {
        this.loadingLogs = true;
        const { data } = await axios.get('/user/profile?include_logs=1');
        
        if (data.success && data.data.associate_logs) {
          this.associateLogs = data.data.associate_logs;
        }
      } catch (error) {
        // Failed to load associate logs
      } finally {
        this.loadingLogs = false;
      }
    },

    async loadProfile() {
      try {
        this.loading = true;
        this.error = null;

        const { data } = await axios.get('/user/profile');

        if (data.success) {
          this.profile = data.data;
          this.editForm = {
            name: this.profile.name,
            position_name: this.profile.position_name || ''
          };

          // Initialize personal form with profile data
          this.personalForm = {
            date_of_birth: this.profile.personal_info?.date_of_birth || '',
            gender: this.profile.personal_info?.gender || '',
            marital_status: this.profile.personal_info?.marital_status || '',
            spouse_name: this.profile.personal_info?.spouse_name || '',
            num_children: this.profile.personal_info?.num_children || 0,
            phone_number: this.profile.personal_info?.phone_number || '',
            alternate_phone_number: this.profile.personal_info?.alternate_phone_number || '',
            permanent_address: this.profile.personal_info?.permanent_address || '',
            current_address: this.profile.personal_info?.current_address || '',
            emergency_contact_name: this.profile.personal_info?.emergency_contact_name || '',
            emergency_contact_number: this.profile.personal_info?.emergency_contact_number || '',
            emergency_contact_relationship: this.profile.personal_info?.emergency_contact_relationship || '',
            tin: this.profile.personal_info?.tin || '',
            sss: this.profile.personal_info?.sss || '',
            philhealth: this.profile.personal_info?.philhealth || '',
            pagibig: this.profile.personal_info?.pagibig || ''
          };

          this.employmentForm = {
            employee_id: this.profile.employment_info?.employee_id || '',
            hire_date: this.profile.employment_info?.hire_date || '',
            regularization_date: this.profile.employment_info?.regularization_date || '',
            employment_type: this.profile.employment_info?.employment_type || '',
            position_level: this.profile.employment_info?.position_level || '',
            career_level: this.profile.employment_info?.career_level || '',
            career_band: this.profile.employment_info?.career_band || '',
            career_zone: this.profile.employment_info?.career_zone || ''
          };
        } else {
          throw new Error(data.message || 'Failed to load profile');
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load profile data';
      } finally {
        this.loading = false;
      }
    },

    toggleEditMode() {
      this.editMode = !this.editMode;
      this.errors = {};

      if (this.editMode) {
        this.editForm = {
          name: this.profile.name,
          position_name: this.profile.position_name || ''
        };
      }
    },

    async saveProfile() {
      try {
        this.updating = true;
        this.errors = {};

        const { data } = await axios.put('/user/profile', this.editForm);

        if (data.success) {
          this.profile = data.data;
          this.editMode = false;
          this.showToast('Profile updated successfully!', 'success');
        } else {
          throw new Error(data.message || 'Failed to update profile');
        }
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {};
        } else {
          this.showToast(
            error.response?.data?.message || 'Failed to update profile',
            'error'
          );
        }
      } finally {
        this.updating = false;
      }
    },

    async savePersonalInfo() {
      try {
        this.updatingPersonal = true;
        this.errors = {};

        const { data } = await axios.put('/user/profile', {
          personal_info: this.personalForm
        });

        if (data.success) {
          this.profile = data.data;
          this.showToast('Personal information updated successfully!', 'success');
        } else {
          throw new Error(data.message || 'Failed to update personal information');
        }
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {};
        } else {
          this.showToast(
            error.response?.data?.message || 'Failed to update personal information',
            'error'
          );
        }
      } finally {
        this.updatingPersonal = false;
      }
    },

    async saveEmploymentInfo() {
      try {
        this.updatingEmployment = true;
        this.errors = {};

        const { data } = await axios.put('/user/profile', {
          employment_info: this.employmentForm
        });

        if (data.success) {
          this.profile = data.data;
          this.showToast('Employment information updated successfully!', 'success');
        } else {
          throw new Error(data.message || 'Failed to update employment information');
        }
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {};
        } else {
          this.showToast(
            error.response?.data?.message || 'Failed to update employment information',
            'error'
          );
        }
      } finally {
        this.updatingEmployment = false;
      }
    },

    copyPermanentAddress() {
      this.personalForm.current_address = this.personalForm.permanent_address;
    },

    // Literal classes so Tailwind's scanner compiles them.
    getEmploymentTypeColor(employmentType) {
      const colors = {
        full_time: 'bg-status-blue',
        part_time: 'bg-status-purple',
        contract: 'bg-status-orange',
        intern: 'bg-status-green',
        consultant: 'bg-status-yellow'
      };
      return colors[employmentType] || 'bg-status-gray';
    },

    getShiftLabel() {
      if (!this.profile?.shift) {
        return 'No Shift Assigned';
      }
      const shift = this.profile.shift;
      return shift.shift_label || `${shift.shift_type} (${shift.start_time} - ${shift.end_time})`;
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';

      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },

    showToast(message, type = 'info') {
      const toast = document.createElement('div');
      toast.setAttribute('role', 'status');
      toast.className = `fixed top-4 right-4 z-50 rounded-lg px-6 py-3 text-white shadow transition-all duration-300 ${
        type === 'success' ? 'bg-green-600' :
        type === 'error' ? 'bg-red-600' :
        type === 'warning' ? 'bg-yellow-600' : 'bg-blue-600'
      }`;
      toast.textContent = message;
      document.body.appendChild(toast);
      requestAnimationFrame(() => (toast.style.opacity = '1'));
      setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => document.body.removeChild(toast), 300);
      }, 3000);
    },

    async downloadAttachment(attachment) {
      try {
        const response = await axios.get(attachment.download_url, {
          responseType: 'blob', // Important: tells axios to expect a file
          headers: {
            'Accept': 'application/octet-stream',
          }
        });

        // Create a blob URL and trigger download
        const blob = new Blob([response.data]);
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        
        link.href = url;
        link.download = attachment.filename;
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        
        // Cleanup
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
      } catch (error) {
        if (error.response?.status === 401) {
          this.showToast('Please log in to download this file', 'error');
        } else if (error.response?.status === 404) {
          this.showToast('File not found', 'error');
        } else {
          this.showToast('Failed to download file', 'error');
        }
      }
    }
  }
};
</script>

<style scoped>
/**** Utilities ****/
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar { display: none; }
</style>