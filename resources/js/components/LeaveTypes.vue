<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Page Header -->
      <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Leave Types</h1>
          </div>
          <div class="mt-4 sm:mt-0">
            <button
              @click="openCreateModal"
              class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 shadow-sm"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Add Leave Type
            </button>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search leave types..."
                class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
              <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </div>
          </div>

          <div>
            <select 
              v-model="selectedPerPage" 
              @change="changePerPage"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option v-for="option in perPageOptions" :key="option" :value="option">
                {{ option }} per page
              </option>
            </select>
          </div>

          <div class="flex space-x-2 sm:col-span-2">
            <button
              @click="applyFilters"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex-1 sm:flex-none"
            >
              Filter
            </button>
            <button
              @click="clearFilters"
              class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex-1 sm:flex-none"
            >
              Clear
            </button>
          </div>
        </div>
      </div>

      <!-- Results Summary -->
      <div v-if="!loading && leaveTypes.length > 0" class="mb-4">
        <p class="text-sm text-gray-600">
          Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} leave types
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="leaveTypes.length === 0" class="rounded-lg bg-white p-8 shadow-sm text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-3 text-lg font-semibold text-gray-900">No leave types found</h3>
        <p class="mt-1 text-gray-600">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'Get started by creating a new leave type' }}
        </p>
      </div>

      <!-- Leave Types List -->
      <div v-else class="space-y-4">
        <div
          v-for="leaveType in leaveTypes"
          :key="leaveType.id"
          class="bg-white rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200"
        >
          <div class="p-3 sm:p-4">
            <div class="flex flex-col space-y-3 sm:flex-row sm:items-start sm:justify-between sm:space-y-0 mb-3">
              <div class="flex items-start space-x-3 flex-1 min-w-0">
                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-semibold text-gray-900 truncate">{{ leaveType.name }}</h3>
                </div>
              </div>
              
              <div class="flex items-center justify-between sm:justify-end sm:flex-col sm:items-end space-x-3 sm:space-x-0 sm:space-y-1">
                <div class="flex items-center space-x-2">
                  <div class="flex space-x-1">
                    <button
                      @click="editLeaveType(leaveType)"
                      class="p-1 text-blue-600 hover:text-blue-800 transition-colors"
                      title="Edit"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button
                      @click="confirmDelete(leaveType)"
                      class="p-1 text-red-600 hover:text-red-800 transition-colors"
                      title="Delete"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs font-semibold text-gray-500 mb-1">LEAVE TYPE CODE</p>
                <p class="text-sm font-semibold text-blue-600">
                  {{ leaveType.type }}
                </p>
              </div>

              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs font-semibold text-gray-500 mb-1">CREATED DATE</p>
                <p class="text-sm font-semibold text-gray-900">
                  {{ formatDate(leaveType.created_at) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="mt-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-4">
          <div class="flex items-center justify-between sm:hidden">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Previous
            </button>
            
            <span class="text-sm text-gray-700 font-medium">
              Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>
            
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Next
            </button>
          </div>

          <div class="hidden sm:flex sm:flex-col sm:space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex items-center text-sm text-gray-700">
              <span>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results</span>
            </div>
            
            <div class="flex items-center space-x-1">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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
                      ? 'bg-blue-600 text-white'
                      : page === '...'
                        ? 'text-gray-400 cursor-default'
                        : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>
              </div>
              
              <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-semibold mb-4">
          {{ editingLeaveType ? 'Edit Leave Type' : 'Create New Leave Type' }}
        </h2>
        <form @submit.prevent="saveLeaveType">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type Name *</label>
              <input
                v-model="currentLeaveType.name"
                type="text"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="e.g., Vacation Leave"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type Code *</label>
              <input
                v-model="currentLeaveType.type"
                type="text"
                required
                maxlength="50"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="e.g., VL"
              >
              <p class="text-xs text-gray-500 mt-1">Short code to identify this leave type</p>
            </div>
          </div>

          <div class="flex space-x-3 mt-6">
            <button
              type="button"
              @click="closeModal"
              class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : (editingLeaveType ? 'Update' : 'Create') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold mb-4">Confirm Delete</h3>
        <p class="text-gray-600 mb-6">
          Are you sure you want to delete "{{ leaveTypeToDelete?.name }}"? This action cannot be undone.
        </p>
        <div class="flex space-x-3">
          <button
            @click="showDeleteModal = false"
            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            Cancel
          </button>
          <button
            @click="deleteLeaveType"
            :disabled="deleting"
            class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors disabled:opacity-50"
          >
            {{ deleting ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios'
import { useNotification } from '@/composables/useNotification'

export default {
  name: 'LeaveTypes',
  setup() {
    const { showNotification } = useNotification()
    return { showNotification }
  },
  data() {
    return {
      loading: false,
      saving: false,
      deleting: false,
      searchQuery: '',
      leaveTypes: [],
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0
      },
      selectedPerPage: 10,
      perPageOptions: [10, 25, 50, 100],
      showModal: false,
      showDeleteModal: false,
      editingLeaveType: null,
      leaveTypeToDelete: null,
      currentLeaveType: {
        name: '',
        type: ''
      },
      searchTimeout: null
    }
  },
  computed: {
    hasActiveFilters() {
      return this.searchQuery
    }
  },
  mounted() {
    this.fetchLeaveTypes()
  },
  methods: {
    async fetchLeaveTypes(page = 1) {
      this.loading = true
      try {
        const params = {
          page,
          search: this.searchQuery,
          per_page: this.selectedPerPage,
          sort_by: 'created_at',
          sort_direction: 'desc'
        }
        
        const response = await axios.get('/user/leave-types', { params })
        
        this.leaveTypes = response.data.data || []
        this.pagination = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 10,
          total: response.data.total || 0,
          from: response.data.from || 0,
          to: response.data.to || 0
        }
      } catch (error) {
        console.error('Error fetching leave types:', error)
        this.showNotification('Failed to fetch leave types', 'error')
      } finally {
        this.loading = false
      }
    },

    applyFilters() {
      this.fetchLeaveTypes()
    },

    clearFilters() {
      this.searchQuery = ''
      this.fetchLeaveTypes()
    },

    changePerPage() {
      this.fetchLeaveTypes()
    },

    changePage(page) {
      if (page === '...' || page < 1 || page > this.pagination.last_page) {
        return
      }
      this.fetchLeaveTypes(page)
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },

    getPageNumbers() {
      const current = this.pagination.current_page
      const last = this.pagination.last_page
      const pages = []
      
      if (last <= 7) {
        for (let i = 1; i <= last; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(last)
        } else if (current >= last - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = last - 4; i <= last; i++) {
            pages.push(i)
          }
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(last)
        }
      }
      
      return pages
    },

    openCreateModal() {
      this.editingLeaveType = null
      this.currentLeaveType = {
        name: '',
        type: ''
      }
      this.showModal = true
    },

    editLeaveType(leaveType) {
      this.editingLeaveType = leaveType
      this.currentLeaveType = {
        name: leaveType.name,
        type: leaveType.type
      }
      this.showModal = true
    },

    async saveLeaveType() {
      this.saving = true
      try {
        if (this.editingLeaveType) {
          await axios.put(`/user/leave-types/${this.editingLeaveType.id}`, this.currentLeaveType)
          this.showNotification('Leave type updated successfully', 'success')
        } else {
          await axios.post('/user/leave-types', this.currentLeaveType)
          this.showNotification('Leave type created successfully', 'success')
        }
        this.closeModal()
        this.fetchLeaveTypes()
      } catch (error) {
        console.error('Error saving leave type:', error)
        this.showNotification('Failed to save leave type', 'error')
      } finally {
        this.saving = false
      }
    },

    confirmDelete(leaveType) {
      this.leaveTypeToDelete = leaveType
      this.showDeleteModal = true
    },

    async deleteLeaveType() {
      this.deleting = true
      try {
        await axios.delete(`/user/leave-types/${this.leaveTypeToDelete.id}`)
        this.showNotification('Leave type deleted successfully', 'success')
        this.showDeleteModal = false
        this.fetchLeaveTypes()
      } catch (error) {
        console.error('Error deleting leave type:', error)
        this.showNotification('Failed to delete leave type', 'error')
      } finally {
        this.deleting = false
      }
    },

    closeModal() {
      this.showModal = false
      this.editingLeaveType = null
    },

    formatDate(dateString) {
      if (!dateString) return '-'
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

  }
}
</script>