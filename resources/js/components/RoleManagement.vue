<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Title bar, matching Attendance -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Role Management</h1>
        <button
          @click="openCreateModal"
          class="flex flex-shrink-0 items-center gap-2 rounded-lg bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          New Role
        </button>
      </div>

      <!-- Filters + results live in one card, as in Attendance -->
      <div class="bg-surface rounded-lg shadow-sm border border-border overflow-hidden">
        <!-- Notched-outline fields: the label sits on the border line.
             shrink-0 keeps the set widths so they wrap instead of collapsing. -->
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="relative w-full shrink-0 sm:w-72">
            <label for="f-role-search" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Search</label>
            <input
              id="f-role-search"
              v-model="searchTerm"
              @input="handleSearch"
              type="text"
              placeholder="Role name"
              class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text placeholder-text-subtle focus:border-accent focus:outline-none"
            >
          </div>

          <div class="relative w-full shrink-0 sm:w-44">
            <label for="f-role-per-page" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Show</label>
            <select
              id="f-role-per-page"
              v-model="perPage"
              @change="changePerPage"
              class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none"
            >
              <option value="10">10 per page</option>
              <option value="25">25 per page</option>
              <option value="50">50 per page</option>
              <option value="100">100 per page</option>
            </select>
          </div>

          <button
            v-if="searchTerm"
            @click="searchTerm = ''; handleSearch()"
            class="shrink-0 rounded-lg border border-border bg-surface-sunken px-5 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas"
          >
            Clear
          </button>
        </div>

        <!-- Results summary -->
        <div v-if="!loading && roles.length > 0" class="border-b border-border px-4 py-2">
          <p class="text-xs text-text-muted">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} roles
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="!roles.length" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No roles found</h3>
          <p class="mt-1 text-text-muted">
            {{ searchTerm ? 'Try a different search term.' : 'Get started by creating a new role.' }}
          </p>
        </div>

        <div v-else>
          <!-- Desktop Table -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full min-w-[640px]">
              <thead class="bg-surface-sunken border-b border-border">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Role Name</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Permissions</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold text-text-muted uppercase tracking-wider">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="role in roles" :key="role.id" class="hover:bg-surface-sunken transition-colors">
                  <td class="px-4 py-3">
                    <p class="text-sm font-medium text-text">{{ role.name }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center whitespace-nowrap rounded-md bg-status-purple px-2 py-1 text-xs font-medium text-status-text">
                      {{ role.permissions_count || 0 }} permission{{ (role.permissions_count || 0) === 1 ? '' : 's' }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-2">
                      <button @click="viewRole(role)" class="p-1.5 text-accent-solid hover:bg-accent-subtle rounded transition-colors" title="View">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                      </button>
                      <button @click="editRole(role)" class="p-1.5 text-accent-solid hover:bg-accent-subtle rounded transition-colors" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </button>
                      <button @click="confirmDelete(role)" class="p-1.5 text-danger hover:bg-status-red rounded transition-colors" title="Delete">
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
          <div class="lg:hidden divide-y divide-border">
            <div v-for="role in roles" :key="role.id" class="p-4 hover:bg-surface-sunken transition-colors">
              <div class="mb-3 flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-text">{{ role.name }}</p>
                <span class="inline-flex flex-shrink-0 items-center whitespace-nowrap rounded-md bg-status-purple px-2 py-1 text-xs font-medium text-status-text">
                  {{ role.permissions_count || 0 }} permission{{ (role.permissions_count || 0) === 1 ? '' : 's' }}
                </span>
              </div>
              <div class="flex gap-2">
                <button @click="viewRole(role)" class="flex-1 rounded-lg border border-border bg-surface-sunken px-3 py-2 text-sm font-medium text-text transition-colors hover:bg-canvas">View</button>
                <button @click="editRole(role)" class="flex-1 rounded-lg bg-accent-solid px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-hover">Edit</button>
                <button @click="confirmDelete(role)" class="flex-1 rounded-lg bg-danger px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-danger/90">Delete</button>
              </div>
            </div>
          </div>

          <!-- Pagination sits inside the same card as the table -->
          <div v-if="pagination.total > 0" class="border-t border-border px-4 py-3">
            <div class="flex items-center justify-between sm:hidden">
              <button
                @click="goToPage(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Previous
              </button>
              <span class="text-sm text-text">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
              <button
                @click="goToPage(pagination.current_page + 1)"
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
                  @click="goToPage(pagination.current_page - 1)"
                  :disabled="pagination.current_page <= 1"
                  class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  Previous
                </button>

                <button
                  v-for="page in visiblePages"
                  :key="page"
                  @click="page !== '...' && goToPage(page)"
                  :disabled="page === '...'"
                  class="min-w-[40px] rounded-md border px-3 py-2 text-sm font-medium transition-colors"
                  :class="page === pagination.current_page
                    ? 'bg-accent-solid border-accent-solid text-white'
                    : page === '...'
                      ? 'border-transparent text-text-muted cursor-default'
                      : 'border-border bg-surface text-text-muted hover:bg-surface-sunken'"
                >
                  {{ page }}
                </button>

                <button
                  @click="goToPage(pagination.current_page + 1)"
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

      <!-- Create/Edit/View Modal -->
      <div v-if="showCreateModal || showEditModal || showViewModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
        <div class="relative bg-surface rounded-2xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
          <div class="p-6 border-b border-border">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-text">
                {{ showViewModal ? 'View Role' : showEditModal ? 'Edit Role' : 'Create New Role' }}
              </h3>
              <button @click="closeModal" class="p-2 rounded-lg hover:bg-surface-sunken">
                <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <div v-if="permissionsLoading" class="flex flex-col items-center justify-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent mb-4"></div>
            <p class="text-text-muted">Loading permissions...</p>
          </div>

          <template v-else>
            <form id="role-form" @submit.prevent="saveRole" class="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-220px)]">
              <div>
                <label class="block text-sm font-medium text-text mb-2">Role Name *</label>
                <input
                  v-model="roleForm.name"
                  type="text"
                  :required="!showViewModal"
                  :readonly="showViewModal"
                  class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                  :class="showViewModal ? 'bg-surface-sunken cursor-not-allowed' : ''"
                  placeholder="Role Name"
                >
                <p v-if="errors.name" class="mt-1 text-sm text-danger">{{ errors.name[0] }}</p>
              </div>

              <div v-if="Object.keys(allPermissions).length > 0" class="rounded-xl bg-accent-subtle p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-medium text-text">
                      Selected: {{ roleForm.permissions.length }} / {{ totalPermissionsCount }} permissions
                    </p>
                    <p class="mt-1 text-xs text-text-muted">
                      {{ Object.keys(allPermissions).length }} modules available
                    </p>
                  </div>
                  <button
                    v-if="!showViewModal"
                    type="button"
                    @click="toggleAllPermissions"
                    class="rounded-lg bg-accent-solid px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-hover"
                  >
                    {{ roleForm.permissions.length === totalPermissionsCount ? 'Deselect All' : 'Select All' }}
                  </button>
                </div>
              </div>

              <div class="space-y-4">
                <label class="block text-sm font-medium text-text">Permissions by Module</label>

                <div v-for="(moduleData, moduleName) in allPermissions" :key="moduleName"
                     class="overflow-hidden rounded-xl border border-border">
                  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-surface-sunken px-4 py-3">
                    <div class="flex items-center gap-3">
                      <h4 class="text-sm font-semibold uppercase text-text">{{ moduleName }}</h4>
                      <span class="rounded-full bg-surface px-2 py-1 text-xs text-text-muted">
                        {{ moduleData.permissions.length }} permissions
                      </span>
                    </div>
                    <button
                      v-if="!showViewModal"
                      type="button"
                      @click="toggleModulePermissions(moduleName)"
                      class="rounded-lg bg-accent-solid px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-accent-hover"
                    >
                      {{ isModuleFullySelected(moduleName) ? 'Deselect All' : 'Select All' }}
                    </button>
                  </div>

                  <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <label
                      v-for="permission in moduleData.permissions"
                      :key="permission.name"
                      class="flex cursor-pointer items-center gap-2 rounded-lg border p-3 transition-colors"
                      :class="[
                        roleForm.permissions.includes(permission.name)
                          ? 'border-accent bg-accent-subtle'
                          : 'border-border bg-surface-sunken hover:border-border-strong',
                        showViewModal ? 'cursor-not-allowed opacity-60' : ''
                      ]"
                    >
                      <input
                        type="checkbox"
                        :value="permission.name"
                        v-model="roleForm.permissions"
                        :disabled="showViewModal"
                        class="h-4 w-4 flex-shrink-0 rounded border-border text-accent-solid focus:ring-accent disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                      <span class="text-sm font-medium" :class="getActionColor(permission.action)">
                        {{ formatPermissionName(permission.name) }}
                      </span>
                    </label>
                  </div>
                </div>

                <div v-if="Object.keys(allPermissions).length === 0" class="rounded-xl bg-surface-sunken py-12 text-center">
                  <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                  <h3 class="mt-3 text-lg font-semibold text-text">No permissions available</h3>
                  <p class="mt-1 text-text-muted">Please run the permissions seeder first.</p>
                </div>
              </div>
            </form>

            <div class="flex flex-col gap-3 border-t border-border p-6 sm:flex-row">
              <button
                type="button"
                @click="closeModal"
                class="flex-1 rounded-xl border border-border bg-surface-sunken px-4 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas"
              >
                {{ showViewModal ? 'Close' : 'Cancel' }}
              </button>
              <button
                v-if="!showViewModal"
                type="submit"
                form="role-form"
                :disabled="roleLoading || !roleForm.name.trim()"
                class="flex-1 rounded-xl bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="roleLoading" class="flex items-center justify-center gap-2">
                  <span class="h-4 w-4 animate-spin rounded-full border-b-2 border-white"></span>
                  Saving...
                </span>
                <span v-else>{{ showEditModal ? 'Update Role' : 'Create Role' }}</span>
              </button>
            </div>
          </template>
        </div>
      </div>

      <!-- Delete Modal -->
      <div v-if="showDeleteModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false"></div>
        <div class="relative bg-surface rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-status-red rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-text mb-2">Delete Role</h3>
            <p class="text-text-muted mb-6">
              Are you sure you want to delete the role &ldquo;{{ roleToDelete?.name }}&rdquo;? This action cannot be undone.
            </p>
            <div class="flex gap-3">
              <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 text-text bg-surface-sunken border border-border hover:bg-canvas rounded-xl">Cancel</button>
              <button @click="deleteRole" class="flex-1 px-4 py-2 text-white bg-danger hover:bg-danger/90 rounded-xl">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useNotification } from '@/composables/useNotification'

export default {
  name: 'RoleManagement',
  setup() {
    const { showNotification } = useNotification()
    const loading = ref(false)
    const roleLoading = ref(false)
    const permissionsLoading = ref(false)
    const searchTerm = ref('')
    const perPage = ref(10)
    const roles = ref([])
    const allPermissions = ref({})
    const selectedRole = ref(null)
    const roleToDelete = ref(null)
    const errors = ref({})

    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const showViewModal = ref(false)
    const showDeleteModal = ref(false)

    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0
    })

    const roleForm = reactive({
      name: '',
      permissions: []
    })

    const totalPermissionsCount = computed(() => {
      return Object.values(allPermissions.value).reduce((total, module) => {
        return total + module.permissions.length
      }, 0)
    })

    const visiblePages = computed(() => {
      const current = pagination.value.current_page
      const last = pagination.value.last_page
      const pages = []

      if (last <= 7) {
        for (let i = 1; i <= last; i++) {
          pages.push(i)
        }
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

    const fetchRoles = async (page = 1) => {
      try {
        loading.value = true
        const response = await axios.get('/roles', {
          params: {
            per_page: perPage.value,
            page: page,
            search: searchTerm.value || undefined
          }
        })
        
        roles.value = response.data.roles || []
        pagination.value = response.data.pagination || {}
      } catch (error) {
        showNotification('Failed to load roles', 'error')
      } finally {
        loading.value = false
      }
    }

    const handleSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        fetchRoles(1)
      }, 500)
    }

    const changePerPage = () => {
      fetchRoles(1)
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        fetchRoles(page)
      }
    }

    const fetchPermissions = async () => {
      try {
        permissionsLoading.value = true
        const response = await axios.get('/roles/permissions')
        allPermissions.value = response.data.permissions || {}
      } catch (error) {
        showNotification('Failed to load permissions', 'error')
      } finally {
        permissionsLoading.value = false
      }
    }

    const openCreateModal = async () => {
      await fetchPermissions()
      roleForm.name = ''
      roleForm.permissions = []
      showCreateModal.value = true
    }

    const editRole = async (role) => {
      await fetchPermissions()
      selectedRole.value = role
      
      try {
        const response = await axios.get(`/roles/${role.id}`)
        roleForm.name = response.data.role.name
        roleForm.permissions = response.data.role.permissions || []
      } catch (error) {
        roleForm.name = role.name
        roleForm.permissions = []
      }
      
      showEditModal.value = true
    }

    const viewRole = async (role) => {
      await fetchPermissions()
      selectedRole.value = role
      
      try {
        const response = await axios.get(`/roles/${role.id}`)
        roleForm.name = response.data.role.name
        roleForm.permissions = response.data.role.permissions || []
      } catch (error) {
        roleForm.name = role.name
        roleForm.permissions = []
      }
      
      showViewModal.value = true
    }

    const confirmDelete = (role) => {
      roleToDelete.value = role
      showDeleteModal.value = true
    }

    const saveRole = async () => {
      try {
        roleLoading.value = true
        errors.value = {}

        const payload = {
          name: roleForm.name.trim(),
          permissions: roleForm.permissions
        }

        if (showEditModal.value) {
          await axios.put(`/roles/${selectedRole.value.id}`, payload)
          showNotification('Role updated successfully!', 'success')
        } else {
          await axios.post('/roles', payload)
          showNotification('Role created successfully!', 'success')
        }

        await fetchRoles(pagination.value.current_page)
        closeModal()
      } catch (error) {
        if (error.response?.status === 422) {
          errors.value = error.response.data.errors || {}
          showNotification('Please check the form for errors', 'error')
        } else {
          showNotification(error.response?.data?.message || 'An error occurred while saving', 'error')
        }
      } finally {
        roleLoading.value = false
      }
    }

    const deleteRole = async () => {
      try {
        await axios.delete(`/roles/${roleToDelete.value.id}`)
        await fetchRoles(pagination.value.current_page)
        showDeleteModal.value = false
        roleToDelete.value = null
        showNotification('Role deleted successfully!', 'success')
      } catch (error) {
        showNotification(error.response?.data?.message || 'Error deleting role', 'error')
      }
    }

    const closeModal = () => {
      showCreateModal.value = false
      showEditModal.value = false
      showViewModal.value = false
      selectedRole.value = null
      roleForm.name = ''
      roleForm.permissions = []
      errors.value = {}
    }

    const toggleAllPermissions = () => {
      if (roleForm.permissions.length === totalPermissionsCount.value) {
        roleForm.permissions = []
      } else {
        roleForm.permissions = []
        Object.values(allPermissions.value).forEach(module => {
          module.permissions.forEach(perm => {
            roleForm.permissions.push(perm.name)
          })
        })
      }
    }

    const toggleModulePermissions = (moduleName) => {
      const modulePerms = allPermissions.value[moduleName].permissions.map(p => p.name)
      const allSelected = modulePerms.every(p => roleForm.permissions.includes(p))
      
      if (allSelected) {
        roleForm.permissions = roleForm.permissions.filter(p => !modulePerms.includes(p))
      } else {
        modulePerms.forEach(p => {
          if (!roleForm.permissions.includes(p)) {
            roleForm.permissions.push(p)
          }
        })
      }
    }

    const isModuleFullySelected = (moduleName) => {
      const modulePerms = allPermissions.value[moduleName].permissions.map(p => p.name)
      return modulePerms.every(p => roleForm.permissions.includes(p))
    }

    const formatPermissionName = (permissionName) => {
      const parts = permissionName.split(' ')
      return parts[0].charAt(0).toUpperCase() + parts[0].slice(1)
    }

    const getActionColor = (action) => {
      // Destructive actions read danger, everything else stays in the
      // neutral ink so the permission grid does not turn into a rainbow.
      const colors = {
        'delete': 'text-danger',
        'cancel': 'text-danger',
      }
      return colors[action] || 'text-text'
    }


    onMounted(() => {
      fetchRoles()
    })

    return {
      loading,
      roleLoading,
      permissionsLoading,
      searchTerm,
      perPage,
      roles,
      allPermissions,
      selectedRole,
      roleToDelete,
      errors,
      showCreateModal,
      showEditModal,
      showViewModal,
      showDeleteModal,
      roleForm,
      pagination,
      totalPermissionsCount,
      visiblePages,
      handleSearch,
      changePerPage,
      goToPage,
      openCreateModal,
      editRole,
      viewRole,
      confirmDelete,
      saveRole,
      deleteRole,
      closeModal,
      toggleAllPermissions,
      toggleModulePermissions,
      isModuleFullySelected,
      formatPermissionName,
      getActionColor,
    }
  }
}
</script>