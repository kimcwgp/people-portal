<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Page Header -->
      <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Clients</h1>
          </div>
          <div class="mt-4 sm:mt-0">
            <button
              @click="openCreateModal"
              class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 shadow-sm"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Add Client
            </button>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
          <div class="sm:col-span-2 lg:col-span-2">
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search clients..."
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

          <div class="flex space-x-2 sm:col-span-2 lg:col-span-3">
            <button
              @click="applyFilters"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex-1 sm:flex-none"
            >
              Search
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
      <div v-if="!loading && clients.length > 0" class="mb-4">
        <p class="text-sm text-gray-600">
          Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} clients
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="clients.length === 0" class="rounded-lg bg-white p-8 shadow-sm text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <h3 class="mt-3 text-lg font-semibold text-gray-900">No clients found</h3>
        <p class="mt-1 text-gray-600">
          {{ hasActiveFilters ? 'Try adjusting your search' : 'Get started by creating a new client' }}
        </p>
      </div>

      <!-- Clients List -->
      <div v-else class="space-y-4">
        <div
          v-for="client in clients"
          :key="client.id"
          class="bg-white rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200"
        >
          <div class="p-3 sm:p-4">
            <div class="flex flex-col space-y-3 sm:flex-row sm:items-start sm:justify-between sm:space-y-0 mb-3">
              <div class="flex items-start space-x-3 flex-1 min-w-0">
                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-semibold text-gray-900 truncate">{{ client.name }}</h3>
                  <p v-if="client.parent_company" class="text-sm text-gray-500 mt-1">{{ client.parent_company }}</p>
                  <div class="flex items-center mt-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ client.projects_count }} {{ client.projects_count === 1 ? 'Project' : 'Projects' }}
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center justify-between sm:justify-end sm:flex-col sm:items-end space-x-3 sm:space-x-0 sm:space-y-1">
                <div class="flex items-center space-x-2">
                  <div class="flex space-x-1">
                    <button
                      @click="editClient(client)"
                      class="p-1 text-blue-600 hover:text-blue-800 transition-colors"
                      title="Edit"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button
                      @click="confirmDelete(client)"
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs font-semibold text-gray-500 mb-1">CONTACT NAME</p>
                <p class="text-sm font-semibold text-gray-900">
                  {{ client.contact_name || 'Not Set' }}
                </p>
              </div>

              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs font-semibold text-gray-500 mb-1">CONTACT NUMBER</p>
                <p class="text-sm font-semibold text-gray-900">
                  {{ client.contact_number || 'Not Set' }}
                </p>
              </div>

              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs font-semibold text-gray-500 mb-1">DESCRIPTION</p>
                <p class="text-sm text-gray-900 line-clamp-2">
                  {{ client.description || 'No description' }}
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

    <!-- Create/Edit Client Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-semibold mb-4">
          {{ editingClient ? 'Edit Client' : 'Create New Client' }}
        </h2>
        <form @submit.prevent="saveClient">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Client Name *</label>
              <input
                v-model="currentClient.name"
                type="text"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter client name"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Parent Company</label>
              <input
                v-model="currentClient.parent_company"
                type="text"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter parent company (if any)"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
              <input
                v-model="currentClient.contact_name"
                type="text"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter contact person name"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
              <input
                v-model="currentClient.contact_number"
                type="text"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter contact number"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                v-model="currentClient.description"
                rows="3"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Client description..."
              ></textarea>
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
              {{ saving ? 'Saving...' : (editingClient ? 'Update' : 'Create') }}
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
          Are you sure you want to delete "{{ clientToDelete?.name }}"? 
          <span v-if="clientToDelete?.projects_count > 0" class="block mt-2 text-red-600 font-medium">
            This client has {{ clientToDelete.projects_count }} project(s). You must remove or reassign them first.
          </span>
          <span v-else class="block mt-2">This action cannot be undone.</span>
        </p>
        <div class="flex space-x-3">
          <button
            @click="showDeleteModal = false"
            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            Cancel
          </button>
          <button
            @click="deleteClient"
            :disabled="deleting || (clientToDelete?.projects_count > 0)"
            class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
  name: 'Clients',
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
      clients: [],
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
      editingClient: null,
      clientToDelete: null,
      currentClient: {
        name: '',
        parent_company: '',
        contact_name: '',
        contact_number: '',
        description: ''
      }
    }
  },
  computed: {
    hasActiveFilters() {
      return this.searchQuery
    }
  },
  mounted() {
    this.fetchClients()
  },
  methods: {
    async fetchClients(page = 1) {
        this.loading = true
        try {
            const params = {
            page,
            search: this.searchQuery,
            per_page: this.selectedPerPage
            }
            
            const response = await axios.get('/user/clients', { params })
            
            this.clients = response.data.data || []
            
            if (response.data.meta) {
            this.pagination = {
                current_page: response.data.meta.current_page || 1,
                last_page: response.data.meta.last_page || 1,
                per_page: response.data.meta.per_page || 10,
                total: response.data.meta.total || 0,
                from: response.data.meta.from || 0,
                to: response.data.meta.to || 0
            }
            }
        } catch (error) {
            console.error('Error fetching clients:', error)
            this.showNotification('Failed to fetch clients', 'error')
        } finally {
            this.loading = false
        }
    },

    applyFilters() {
      this.fetchClients()
    },

    clearFilters() {
      this.searchQuery = ''
      this.fetchClients()
    },

    changePerPage() {
      this.fetchClients()
    },

    changePage(page) {
      if (page === '...' || page < 1 || page > this.pagination.last_page) {
        return
      }
      this.fetchClients(page)
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
      this.editingClient = null
      this.currentClient = {
        name: '',
        parent_company: '',
        contact_name: '',
        contact_number: '',
        description: ''
      }
      this.showModal = true
    },

    editClient(client) {
      this.editingClient = client
      this.currentClient = {
        name: client.name || '',
        parent_company: client.parent_company || '',
        contact_name: client.contact_name || '',
        contact_number: client.contact_number || '',
        description: client.description || ''
      }
      this.showModal = true
    },

    async saveClient() {
      this.saving = true
      try {
        if (this.editingClient) {
          await axios.put(`/user/clients/${this.editingClient.id}`, this.currentClient)
          this.showNotification('Client updated successfully', 'success')
        } else {
          await axios.post('/user/clients', this.currentClient)
          this.showNotification('Client created successfully', 'success')
        }
        this.closeModal()
        this.fetchClients()
      } catch (error) {
        console.error('Error saving client:', error)
        const errorMessage = error.response?.data?.message || 'Failed to save client'
        this.showNotification(errorMessage, 'error')
      } finally {
        this.saving = false
      }
    },

    confirmDelete(client) {
      this.clientToDelete = client
      this.showDeleteModal = true
    },

    async deleteClient() {
      if (this.clientToDelete?.projects_count > 0) {
        this.showNotification('Cannot delete client with existing projects', 'error')
        return
      }

      this.deleting = true
      try {
        await axios.delete(`/user/clients/${this.clientToDelete.id}`)
        this.showNotification('Client deleted successfully', 'success')
        this.showDeleteModal = false
        this.fetchClients()
      } catch (error) {
        console.error('Error deleting client:', error)
        const errorMessage = error.response?.data?.message || 'Failed to delete client'
        this.showNotification(errorMessage, 'error')
      } finally {
        this.deleting = false
      }
    },

    closeModal() {
      this.showModal = false
      this.editingClient = null
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>