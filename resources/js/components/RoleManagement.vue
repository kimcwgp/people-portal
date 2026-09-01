<template>
  <div class="p-6 space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Manage Roles</h1>
      </div>
      <button
        @click="openCreateModal"
        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors"
      >
        + ROLE
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
          <input
            v-model="searchTerm"
            @input="handleSearch"
            type="text"
            placeholder="Search role..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm text-gray-600 whitespace-nowrap">Show:</label>
          <select
            v-model="perPage"
            @change="changePerPage"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="10">10 per page</option>
            <option value="25">25 per page</option>
            <option value="50">50 per page</option>
            <option value="100">100 per page</option>
          </select>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <table class="w-full">
        <thead class="bg-green-600 text-white">
          <tr>
            <th class="text-left p-4 font-semibold">ROLE NAME</th>
            <th class="text-center p-4 font-semibold">ACTION</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50">
            <td class="p-4">
              <div class="font-medium text-gray-900">{{ role.name }}</div>
              <div class="text-sm text-gray-500">
                {{ role.permissions_count || 0 }} permission(s) assigned
              </div>
            </td>
            <td class="p-4 text-center">
              <div class="flex justify-center space-x-2">
                <button
                  @click="editRole(role)"
                  class="bg-green-600 text-white p-2 rounded hover:bg-green-700"
                  title="Edit"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                  </svg>
                </button>
                <button
                  @click="viewRole(role)"
                  class="bg-green-600 text-white p-2 rounded hover:bg-green-700"
                  title="View"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                  </svg>
                </button>
                <button
                  @click="confirmDelete(role)"
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

      <div v-if="!roles.length && !loading" class="text-center py-12">
        <h3 class="mt-2 text-sm font-medium text-gray-900">No roles found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchTerm ? 'Try a different search term.' : 'Get started by creating a new role.' }}
        </p>
      </div>
    </div>

    <!-- Pagination Controls -->
    <div v-if="pagination.total > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <!-- Results Info -->
        <div class="text-sm text-gray-600">
          Showing <span class="font-medium">{{ pagination.from }}</span> to 
          <span class="font-medium">{{ pagination.to }}</span> of 
          <span class="font-medium">{{ pagination.total }}</span> results
        </div>

        <!-- Page Navigation -->
        <div class="flex items-center gap-2">
          <!-- Previous Button -->
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            :class="pagination.current_page === 1 ? 'bg-gray-100' : 'bg-white'"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>

          <!-- Page Numbers -->
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

          <!-- Mobile: Current Page Display -->
          <div class="sm:hidden px-4 py-2 border border-gray-300 rounded-lg bg-white">
            <span class="font-medium">{{ pagination.current_page }}</span>
            <span class="text-gray-500"> / {{ pagination.last_page }}</span>
          </div>

          <!-- Next Button -->
          <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            :class="pagination.current_page === pagination.last_page ? 'bg-gray-100' : 'bg-white'"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit/View Modal -->
    <div v-if="showCreateModal || showEditModal || showViewModal" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
      <div class="relative bg-white rounded-2xl p-6 max-w-6xl w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-semibold text-gray-900">
            {{ showViewModal ? 'VIEW ROLE' : showEditModal ? 'EDIT ROLE' : 'CREATE NEW ROLE' }}
          </h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div v-if="permissionsLoading" class="flex flex-col justify-center items-center py-20">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mb-4"></div>
          <p class="text-gray-600">Loading permissions...</p>
        </div>

        <form v-else @submit.prevent="saveRole" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">ROLE NAME</label>
            <input
              v-model="roleForm.name"
              type="text"
              :required="!showViewModal"
              :readonly="showViewModal"
              :class="[
                'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                showViewModal ? 'bg-gray-100 cursor-not-allowed' : ''
              ]"
              placeholder="Role Name"
            >
            <p v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name[0] }}</p>
          </div>

          <div v-if="Object.keys(allPermissions).length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-blue-900">
                  Selected: {{ roleForm.permissions.length }} / {{ totalPermissionsCount }} permissions
                </p>
                <p class="text-xs text-blue-700 mt-1">
                  {{ Object.keys(allPermissions).length }} modules available
                </p>
              </div>
              <button
                v-if="!showViewModal"
                type="button"
                @click="toggleAllPermissions"
                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              >
                {{ roleForm.permissions.length === totalPermissionsCount ? 'Deselect All' : 'Select All' }}
              </button>
            </div>
          </div>

          <div class="space-y-4">
            <label class="block text-sm font-medium text-gray-700 mb-4">PERMISSIONS BY MODULE</label>
            
            <div v-for="(moduleData, moduleName) in allPermissions" :key="moduleName" 
                 class="border border-gray-200 rounded-lg overflow-hidden">
              <div class="bg-gradient-to-r from-green-50 to-green-100 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <h4 class="font-semibold text-gray-900 text-sm uppercase">{{ moduleName }}</h4>
                  <span class="text-xs bg-white px-2 py-1 rounded-full text-gray-600">
                    {{ moduleData.permissions.length }} permissions
                  </span>
                </div>
                <button
                  v-if="!showViewModal"
                  type="button"
                  @click="toggleModulePermissions(moduleName)"
                  class="text-xs px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
                >
                  {{ isModuleFullySelected(moduleName) ? 'Deselect All' : 'Select All' }}
                </button>
              </div>

              <div class="p-4 bg-white">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                  <label
                    v-for="permission in moduleData.permissions"
                    :key="permission.name"
                    :class="[
                      'flex items-center gap-2 p-3 rounded-lg border cursor-pointer transition-all',
                      roleForm.permissions.includes(permission.name)
                        ? 'bg-green-50 border-green-300'
                        : 'bg-gray-50 border-gray-200 hover:border-gray-300',
                      showViewModal ? 'cursor-not-allowed opacity-60' : ''
                    ]"
                  >
                    <input
                      type="checkbox"
                      :value="permission.name"
                      v-model="roleForm.permissions"
                      :disabled="showViewModal"
                      class="w-4 h-4 text-green-600 rounded focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                    <span class="text-sm font-medium" :class="getActionColor(permission.action)">
                      {{ formatPermissionName(permission.name) }}
                    </span>
                  </label>
                </div>
              </div>
            </div>

            <div v-if="Object.keys(allPermissions).length === 0 && !permissionsLoading" 
                 class="text-center py-12 bg-gray-50 rounded-lg">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No permissions available</h3>
              <p class="mt-1 text-sm text-gray-500">Please run the permissions seeder first.</p>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-200">
            <button
              v-if="!showViewModal"
              type="submit"
              :disabled="roleLoading || !roleForm.name.trim()"
              class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] shadow-lg hover:shadow-xl"
            >
              <div v-if="roleLoading" class="flex items-center justify-center">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                Saving...
              </div>
              <span v-else>{{ showEditModal ? 'UPDATE ROLE' : 'CREATE ROLE' }}</span>
            </button>
            <button
              type="button"
              @click="closeModal"
              class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]"
            >
              {{ showViewModal ? 'CLOSE' : 'CANCEL' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false"></div>
      <div class="relative bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Delete Role</h3>
        <p class="text-gray-600 mb-6">
          Are you sure you want to delete the role "{{ roleToDelete?.name }}"? This action cannot be undone.
        </p>
        <div class="flex gap-3">
          <button
            @click="showDeleteModal = false"
            class="flex-1 px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg"
          >
            Cancel
          </button>
          <button
            @click="deleteRole"
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
      const colors = {
        'view': 'text-blue-700',
        'create': 'text-green-700',
        'edit': 'text-yellow-700',
        'delete': 'text-red-700',
        'cancel': 'text-orange-700',
        'export': 'text-purple-700'
      }
      return colors[action] || 'text-gray-700'
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