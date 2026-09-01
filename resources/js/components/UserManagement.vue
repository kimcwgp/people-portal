<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors"
      >
        + ADD USER
      </button>
    </div>

    <!-- Stats Cards -->
    <div v-if="stats" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
        <p class="text-sm text-gray-600">Total Users</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.total_users }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
        <p class="text-sm text-gray-600">Active</p>
        <p class="text-2xl font-bold text-green-600">{{ stats.active_users }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
        <p class="text-sm text-gray-600">Inactive</p>
        <p class="text-2xl font-bold text-amber-600">{{ stats.inactive_users }}</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Search -->
        <div>
          <input
            v-model="filters.search"
            @input="handleSearch"
            type="text"
            placeholder="Search users..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
        </div>

        <!-- Team Filter -->
        <div>
          <select
            v-model="filters.team_id"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">All Teams</option>
            <option v-for="team in filterOptions.teams" :key="team.id" :value="team.id">
              {{ team.name }}
            </option>
          </select>
        </div>

        <!-- Role Filter -->
        <div>
          <select
            v-model="filters.role_id"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">All Roles</option>
            <option v-for="role in filterOptions.roles" :key="role.id" :value="role.id">
              {{ role.name }}
            </option>
          </select>
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="filters.status"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>

        <!-- Per Page -->
        <div class="flex items-center gap-2">
          <label class="text-sm text-gray-600 whitespace-nowrap">Show:</label>
          <select
            v-model="perPage"
            @change="changePerPage"
            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>
      
      <!-- Clear Filters Button -->
      <div class="mt-3">
        <button
          @click="clearFilters"
          class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm"
        >
          Clear All Filters
        </button>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-green-600 text-white">
            <tr>
              <th class="text-left p-4 font-semibold">USER</th>
              <th class="text-left p-4 font-semibold">ROLE</th>
              <th class="text-left p-4 font-semibold">TEAM</th>
              <th class="text-left p-4 font-semibold">SHIFT</th>
              <th class="text-left p-4 font-semibold">SUPERVISOR</th>
              <th class="text-center p-4 font-semibold">STATUS</th>
              <th class="text-center p-4 font-semibold">ACTION</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
              <td class="p-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center font-semibold">
                    {{ user.initials }}
                  </div>
                  <div>
                    <div class="font-medium text-gray-900">{{ user.name }}</div>
                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td class="p-4">
                <div v-if="user.roles && user.roles.length > 0" class="flex flex-wrap gap-1">
                  <span
                    v-for="role in user.roles"
                    :key="role.id"
                    class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full font-medium"
                  >
                    {{ role.name }}
                  </span>
                </div>
                <span v-else class="text-sm text-gray-400 italic">No role</span>
              </td>
              <td class="p-4">
                <span v-if="user.team" class="text-sm text-gray-900">
                  {{ user.team.name }}
                </span>
                <span v-else class="text-sm text-gray-400 italic">No team</span>
              </td>
              <td class="p-4">
                <span v-if="user.shift" class="text-xs text-gray-900">
                  {{ user.shift.label }}
                </span>
                <span v-else class="text-sm text-gray-400 italic">No shift</span>
              </td>
              <td class="p-4">
                <span v-if="user.immediate_supervisor" class="text-sm text-gray-900">
                  {{ user.immediate_supervisor.name }}
                </span>
                <span v-else class="text-sm text-gray-400 italic">No supervisor</span>
              </td>
              <td class="p-4 text-center">
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-xs font-medium',
                    user.status ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'
                  ]"
                >
                  {{ user.status_text }}
                </span>
              </td>
              <td class="p-4 text-center">
                <div class="flex justify-center space-x-2">
                  <button
                    @click="viewUser(user)"
                    class="bg-blue-600 text-white p-2 rounded hover:bg-blue-700"
                    title="View"
                  >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                      <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                  <button
                    @click="editUser(user)"
                    class="bg-green-600 text-white p-2 rounded hover:bg-green-700"
                    title="Edit"
                  >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                    </svg>
                  </button>
                  <button
                    @click="confirmDelete(user)"
                    class="bg-red-600 text-white p-2 rounded hover:bg-red-700"
                    title="Delete"
                  >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="loading" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
        </div>

        <div v-if="!users.length && !loading" class="text-center py-12">
          <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ hasActiveFilters ? 'Try adjusting your filters.' : 'Get started by adding a new user.' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Pagination Controls -->
    <div v-if="pagination.total > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
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
                  ? 'bg-green-600 text-white border-green-600 font-medium'
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

    <!-- Create/Edit User Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
      <div class="relative bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-semibold text-gray-900">
            {{ showEditModal ? 'Edit User' : 'Create New User' }}
          </h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveUser" class="space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
            <input
              v-model="userForm.name"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="John Doe"
            >
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
            <input
              v-model="userForm.email"
              type="email"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="john@example.com"
            >
          </div>

          <!-- Glip URL -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Glip URL *</label>
            <input
              v-model="userForm.glip_url"
              type="url"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="https://glip.com/user/..."
            >
          </div>

          <!-- Role Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
            <select
              v-model="userForm.role_id"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Select a role</option>
              <option v-for="role in filterOptions.roles" :key="role.id" :value="role.id">
                {{ role.name }}
              </option>
            </select>
          </div>

          <!-- Team -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
            <select
              v-model="userForm.team_id"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">No team</option>
              <option v-for="team in filterOptions.teams" :key="team.id" :value="team.id">
                {{ team.name }}
              </option>
            </select>
          </div>

          <!-- Shift -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Shift</label>
            <select
              v-model="userForm.shift_id"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">No shift</option>
              <option v-for="shift in filterOptions.shifts" :key="shift.id" :value="shift.id">
                {{ shift.label }}
              </option>
            </select>
          </div>

          <!-- Supervisor -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Supervisor</label>
            <select
              v-model="userForm.immediate_sup_id"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">No supervisor</option>
              <option v-for="supervisor in filterOptions.supervisors" :key="supervisor.id" :value="supervisor.id">
                {{ supervisor.name }}
              </option>
            </select>
          </div>

          <!-- Status -->
          <div>
            <label class="flex items-center gap-2">
              <input
                v-model="userForm.status"
                type="checkbox"
                class="w-4 h-4 text-green-600 rounded focus:ring-2 focus:ring-green-500"
              >
              <span class="text-sm font-medium text-gray-700">Active</span>
            </label>
          </div>

          <!-- Submit Buttons -->
          <div class="flex gap-3 pt-4">
            <button
              type="submit"
              :disabled="userLoading"
              class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
            >
              <span v-if="userLoading">Saving...</span>
              <span v-else>{{ showEditModal ? 'Update User' : 'Create User' }}</span>
            </button>
            <button
              type="button"
              @click="closeModal"
              class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- View User Modal -->
    <div v-if="showViewModal" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="absolute inset-0 bg-black/50" @click="showViewModal = false"></div>
      <div class="relative bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-semibold text-gray-900">User Details</h3>
          <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div v-if="selectedUser" class="space-y-6">
          <!-- User Header -->
          <div class="flex items-center gap-4 pb-6 border-b">
            <div>
              <h4 class="text-xl font-semibold text-gray-900">{{ selectedUser.name }}</h4>
              <p class="text-sm text-gray-600">{{ selectedUser.email }}</p>
              <a 
                v-if="selectedUser.glip_url"
                :href="selectedUser.glip_url" 
                target="_blank"
                class="text-xs text-blue-600 hover:text-blue-800 hover:underline"
              >
                {{ selectedUser.glip_url }}
              </a>
              <span
                :class="[
                  'inline-block mt-2 px-3 py-1 rounded-full text-xs font-medium',
                  selectedUser.status ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'
                ]"
              >
                {{ selectedUser.status_text }}
              </span>
            </div>
          </div>

          <!-- Details Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Role -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-2">Role</label>
              <div v-if="selectedUser.roles && selectedUser.roles.length > 0" class="flex flex-wrap gap-2">
                <span
                  v-for="role in selectedUser.roles"
                  :key="role.id"
                  class="px-3 py-1 bg-purple-100 text-purple-800 text-sm rounded-full font-medium"
                >
                  {{ role.name }}
                </span>
              </div>
              <span v-else class="text-sm text-gray-400 italic">No role assigned</span>
            </div>

            <!-- Team -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-2">Team</label>
              <p class="text-sm text-gray-900">
                {{ selectedUser.team ? selectedUser.team.name : 'No team assigned' }}
              </p>
            </div>

            <!-- Shift -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-2">Shift</label>
              <p class="text-sm text-gray-900">
                {{ selectedUser.shift ? selectedUser.shift.label : 'No shift assigned' }}
              </p>
            </div>

            <!-- Supervisor -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-2">Immediate Supervisor</label>
              <p class="text-sm text-gray-900">
                {{ selectedUser.immediate_supervisor ? selectedUser.immediate_supervisor.name : 'No supervisor assigned' }}
              </p>
            </div>

            <!-- Created At -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-2">Created At</label>
              <p class="text-sm text-gray-900">
                {{ selectedUser.created_at ? new Date(selectedUser.created_at).toLocaleString() : '-' }}
              </p>
            </div>

            <!-- Updated At -->
            <div>
              <label class="block text-sm font-medium text-gray-600 mb-2">Last Updated</label>
              <p class="text-sm text-gray-900">
                {{ selectedUser.updated_at ? new Date(selectedUser.updated_at).toLocaleString() : '-' }}
              </p>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-3 pt-6 border-t">
            <button
              @click="editUserFromView"
              class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
            >
              Edit User
            </button>
            <button
              @click="showViewModal = false"
              class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false"></div>
      <div class="relative bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Delete User</h3>
        <p class="text-gray-600 mb-6">
          Are you sure you want to delete "{{ userToDelete?.name }}"? This action cannot be undone.
        </p>
        <div class="flex gap-3">
          <button
            @click="showDeleteModal = false"
            class="flex-1 px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg"
          >
            Cancel
          </button>
          <button
            @click="deleteUser"
            class="flex-1 px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import { useNotification } from '@/composables/useNotification'

export default {
  name: 'UserManagement',
  setup() {
    const { showNotification } = useNotification()
    const loading = ref(false)
    const userLoading = ref(false)
    const users = ref([])
    const stats = ref(null)
    const filterOptions = ref({
      teams: [],
      shifts: [],
      supervisors: [],
      roles: []
    })
    
    const perPage = ref(10)
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0
    })

    const filters = reactive({
      search: '',
      team_id: '',
      shift_id: '',
      role_id: '',
      status: '',
      supervisor_id: '',
    })

    const userForm = reactive({
      name: '',
      email: '',
      glip_url: '',
      password: '',
      role_id: '',
      team_id: '',
      shift_id: '',
      immediate_sup_id: '',
      status: true
    })

    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const showViewModal = ref(false)
    const showDeleteModal = ref(false)
    const selectedUser = ref(null)
    const userToDelete = ref(null)

    const hasActiveFilters = computed(() => {
      return filters.search || filters.team_id || filters.shift_id || 
             filters.role_id || filters.status !== '' || filters.supervisor_id
    })

    const visiblePages = computed(() => {
      const current = pagination.value.current_page
      const last = pagination.value.last_page
      const pages = []

      if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i)
      } else {
        if (current <= 3) {
          for (let i = 1; i <= 5; i++) pages.push(i)
          pages.push('...')
          pages.push(last)
        } else if (current >= last - 2) {
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
    })

    let searchTimeout = null

    const fetchUsers = async (page = 1) => {
      try {
        loading.value = true
        const params = {
          per_page: perPage.value,
          page: page,
          ...filters
        }

        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === false) {
            delete params[key]
          }
        })

        const response = await axios.get('/users', { params })
        
        users.value = response.data.users.data || []
        pagination.value = {
          current_page: response.data.users.current_page,
          last_page: response.data.users.last_page,
          per_page: response.data.users.per_page,
          total: response.data.users.total,
          from: response.data.users.from,
          to: response.data.users.to
        }
        stats.value = response.data.stats
        filterOptions.value = response.data.filters
      } catch (error) {
        showNotification('Failed to load users', 'error')
      } finally {
        loading.value = false
      }
    }

    const fetchRoles = async () => {
      try {
        const response = await axios.get('/roles', {
          params: { per_page: 100 }
        })
        filterOptions.value.roles = response.data.roles || []
      } catch (error) {
        console.error('Failed to load roles:', error)
      }
    }

    const handleSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        fetchUsers(1)
      }, 500)
    }

    const applyFilters = () => {
      fetchUsers(1)
    }

    const clearFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = typeof filters[key] === 'boolean' ? false : ''
      })
      fetchUsers(1)
    }

    const changePerPage = () => {
      fetchUsers(1)
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        fetchUsers(page)
      }
    }

    const openCreateModal = async () => {
      await fetchRoles()
      resetForm()
      showCreateModal.value = true
    }

    const editUser = async (user) => {
      await fetchRoles()
      selectedUser.value = user
      
      try {
        // Fetch fresh user data from API to get current roles
        const response = await axios.get(`/users/${user.id}`)
        const userData = response.data.user
        
        userForm.name = userData.name
        userForm.email = userData.email
        userForm.glip_url = userData.glip_url || ''
        userForm.password = ''
        userForm.role_id = userData.roles && userData.roles.length > 0 ? userData.roles[0].id : ''
        userForm.team_id = userData.team_id || ''
        userForm.shift_id = userData.shift_id || ''
        userForm.immediate_sup_id = userData.immediate_sup_id || ''
        userForm.status = userData.status
      } catch (error) {
        // Fallback to user object data if API fails
        userForm.name = user.name
        userForm.email = user.email
        userForm.glip_url = user.glip_url || ''
        userForm.password = ''
        userForm.role_id = user.roles && user.roles.length > 0 ? user.roles[0].id : ''
        userForm.team_id = user.team_id || ''
        userForm.shift_id = user.shift_id || ''
        userForm.immediate_sup_id = user.immediate_sup_id || ''
        userForm.status = user.status
      }
      
      showEditModal.value = true
    }

    const viewUser = async (user) => {
      await fetchRoles()
      selectedUser.value = user
      showViewModal.value = true
    }

    const editUserFromView = () => {
      showViewModal.value = false
      editUser(selectedUser.value)
    }

    const confirmDelete = (user) => {
      userToDelete.value = user
      showDeleteModal.value = true
    }

    const saveUser = async () => {
      try {
        userLoading.value = true

        const payload = { ...userForm }
        if (showEditModal.value && !payload.password) {
          delete payload.password
        }

        if (showEditModal.value) {
          await axios.put(`/users/${selectedUser.value.id}`, payload)
          showNotification('User updated successfully!', 'success')
        } else {
          await axios.post('/users', payload)
          showNotification('User created successfully!', 'success')
        }

        await fetchUsers(pagination.value.current_page)
        closeModal()
      } catch (error) {
        showNotification(error.response?.data?.message || 'An error occurred', 'error')
      } finally {
        userLoading.value = false
      }
    }

    const deleteUser = async () => {
      try {
        await axios.delete(`/users/${userToDelete.value.id}`)
        await fetchUsers(pagination.value.current_page)
        showDeleteModal.value = false
        userToDelete.value = null
        showNotification('User deleted successfully!', 'success')
      } catch (error) {
        showNotification(error.response?.data?.message || 'Error deleting user', 'error')
      }
    }

    const closeModal = () => {
      showCreateModal.value = false
      showEditModal.value = false
      showViewModal.value = false
      selectedUser.value = null
      resetForm()
    }

    const resetForm = () => {
      userForm.name = ''
      userForm.email = ''
      userForm.glip_url = ''
      userForm.password = ''
      userForm.role_id = ''
      userForm.team_id = ''
      userForm.shift_id = ''
      userForm.immediate_sup_id = ''
      userForm.status = true
    }


    onMounted(() => {
      fetchUsers()
      fetchRoles()
    })

    return {
      loading,
      userLoading,
      users,
      stats,
      filterOptions,
      perPage,
      pagination,
      filters,
      userForm,
      showCreateModal,
      showEditModal,
      showViewModal,
      showDeleteModal,
      selectedUser,
      userToDelete,
      hasActiveFilters,
      visiblePages,
      handleSearch,
      applyFilters,
      clearFilters,
      changePerPage,
      goToPage,
      openCreateModal,
      editUser,
      editUserFromView,
      viewUser,
      confirmDelete,
      saveUser,
      deleteUser,
      closeModal
    }
  }
}
</script>