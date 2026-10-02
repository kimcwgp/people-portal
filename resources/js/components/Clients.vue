<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Title bar, matching Attendance -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Clients</h1>
        <button
          @click="openCreateModal"
          class="flex flex-shrink-0 items-center gap-2 rounded-lg bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Client
        </button>
      </div>

      <!-- Filters + results live in one card, as in Attendance -->
      <div class="bg-surface rounded-lg shadow-sm border border-border overflow-hidden">
        <!-- Notched-outline fields: the label sits on the border line.
             shrink-0 keeps the set widths so they wrap instead of collapsing. -->
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="relative w-full shrink-0 sm:w-72">
            <label for="f-client-search" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Search</label>
            <input
              id="f-client-search"
              v-model="searchQuery"
              type="text"
              placeholder="Client or parent company"
              class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text placeholder-text-subtle focus:border-accent focus:outline-none"
            >
          </div>

          <div class="relative w-full shrink-0 sm:w-40">
            <label for="f-client-per-page" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Show</label>
            <select id="f-client-per-page" v-model="selectedPerPage" @change="changePerPage"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }} per page</option>
            </select>
          </div>

          <div class="flex shrink-0 gap-2">
            <button @click="applyFilters"
                    class="flex-1 rounded-lg bg-accent-solid px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover sm:flex-none">
              Search
            </button>
            <button @click="clearFilters"
                    class="flex-1 rounded-lg border border-border bg-surface-sunken px-5 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas sm:flex-none">
              Clear
            </button>
          </div>
        </div>

        <!-- Results summary -->
        <div v-if="!loading && clients.length > 0" class="border-b border-border px-4 py-2">
          <p class="text-xs text-text-muted">
            Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} clients
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="clients.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No clients found</h3>
          <p class="mt-1 text-text-muted">
            {{ hasActiveFilters ? 'Try adjusting your search' : 'Get started by creating a new client' }}
          </p>
        </div>

        <div v-else>
          <!-- Desktop Table -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full min-w-[1000px]">
              <thead class="bg-surface-sunken border-b border-border">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Client</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Parent Company</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Contact Name</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Contact Number</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Projects</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Description</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold text-text-muted uppercase tracking-wider">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="client in clients" :key="client.id" class="hover:bg-surface-sunken transition-colors">
                  <td class="px-4 py-3">
                    <p class="text-sm font-medium text-text">{{ client.name }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="text-sm text-text">{{ client.parent_company || '-' }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="text-sm text-text">{{ client.contact_name || 'Not Set' }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ client.contact_number || 'Not Set' }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center whitespace-nowrap rounded-md bg-status-blue px-2 py-1 text-xs font-medium text-status-text">
                      {{ client.projects_count }} {{ client.projects_count === 1 ? 'Project' : 'Projects' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 max-w-xs min-w-[190px]">
                    <p class="text-sm text-text-muted line-clamp-2"
                       @mouseenter="showTip($event, client.description)" @mouseleave="hideTip">
                      {{ client.description || 'No description' }}
                    </p>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-2">
                      <button @click="editClient(client)" class="p-1.5 text-accent-solid hover:bg-accent-subtle rounded transition-colors" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </button>
                      <button @click="confirmDelete(client)" class="p-1.5 text-danger hover:bg-status-red rounded transition-colors" title="Delete">
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
            <div v-for="client in clients" :key="client.id" class="p-4 hover:bg-surface-sunken transition-colors">
              <div class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-text">{{ client.name }}</p>
                  <p v-if="client.parent_company" class="truncate text-xs text-text-muted">{{ client.parent_company }}</p>
                </div>
                <span class="inline-flex flex-shrink-0 items-center whitespace-nowrap rounded-md bg-status-blue px-2 py-1 text-xs font-medium text-status-text">
                  {{ client.projects_count }} {{ client.projects_count === 1 ? 'Project' : 'Projects' }}
                </span>
              </div>

              <div class="mb-3 space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Contact Name:</span>
                  <span class="font-medium text-text">{{ client.contact_name || 'Not Set' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Contact Number:</span>
                  <span class="font-medium text-text">{{ client.contact_number || 'Not Set' }}</span>
                </div>
                <div class="text-sm">
                  <span class="text-text-muted">Description:</span>
                  <p class="mt-1 line-clamp-2 text-text">{{ client.description || 'No description' }}</p>
                </div>
              </div>

              <div class="flex gap-2">
                <button @click="editClient(client)" class="flex-1 rounded-lg bg-accent-solid px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-hover">Edit</button>
                <button @click="confirmDelete(client)" class="flex-1 rounded-lg bg-danger px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-danger/90">Delete</button>
              </div>
            </div>
          </div>

          <!-- Pagination sits inside the same card as the table -->
          <div v-if="pagination.total > pagination.per_page" class="border-t border-border px-4 py-3">
            <div class="flex items-center justify-between sm:hidden">
              <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                class="px-3 py-2 text-sm font-medium text-text-muted bg-surface border border-border rounded-md hover:bg-surface-sunken disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Previous
              </button>
              <span class="text-sm text-text">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
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

                <button
                  v-for="page in getPageNumbers()"
                  :key="page"
                  @click="changePage(page)"
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

      <!-- Create/Edit Client Modal -->
      <div v-if="showModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
        <div class="relative bg-surface rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden">
          <div class="p-6 border-b border-border">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-text">{{ editingClient ? 'Edit Client' : 'Create New Client' }}</h3>
              <button @click="closeModal" class="p-2 rounded-lg hover:bg-surface-sunken">
                <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <form id="client-form" @submit.prevent="saveClient" class="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-220px)]">
            <div>
              <label class="block text-sm font-medium text-text mb-2">Client Name *</label>
              <input v-model="currentClient.name" type="text" required
                     class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                     placeholder="Enter client name">
            </div>

            <div>
              <label class="block text-sm font-medium text-text mb-2">Parent Company</label>
              <input v-model="currentClient.parent_company" type="text"
                     class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                     placeholder="Enter parent company (if any)">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-text mb-2">Contact Name</label>
                <input v-model="currentClient.contact_name" type="text"
                       class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                       placeholder="Enter contact person name">
              </div>
              <div>
                <label class="block text-sm font-medium text-text mb-2">Contact Number</label>
                <input v-model="currentClient.contact_number" type="text"
                       class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                       placeholder="Enter contact number">
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-text mb-2">Description</label>
              <textarea v-model="currentClient.description" rows="3"
                        class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                        placeholder="Client description..."></textarea>
            </div>
          </form>

          <div class="flex gap-3 border-t border-border p-6">
            <button type="button" @click="closeModal"
                    class="flex-1 rounded-xl border border-border bg-surface-sunken px-4 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas">
              Cancel
            </button>
            <button type="submit" form="client-form" :disabled="saving"
                    class="flex-1 rounded-xl bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover disabled:opacity-50">
              {{ saving ? 'Saving...' : (editingClient ? 'Update' : 'Create') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="showDeleteModal = false"></div>
        <div class="relative bg-surface rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-status-red rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-text mb-2">Delete Client</h3>
            <p class="text-text-muted mb-6">
              Are you sure you want to delete &ldquo;{{ clientToDelete?.name }}&rdquo;?
              <span v-if="clientToDelete?.projects_count > 0" class="mt-2 block font-medium text-danger">
                This client has {{ clientToDelete.projects_count }} project(s). You must remove or reassign them first.
              </span>
              <span v-else class="mt-2 block">This action cannot be undone.</span>
            </p>
            <div class="flex gap-3">
              <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 text-text bg-surface-sunken border border-border hover:bg-canvas rounded-xl">Cancel</button>
              <button @click="deleteClient" :disabled="deleting || (clientToDelete?.projects_count > 0)"
                      class="flex-1 px-4 py-2 text-white bg-danger hover:bg-danger/90 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed">
                {{ deleting ? 'Deleting...' : 'Delete' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Fixed so the table's overflow-x-auto cannot clip it -->
      <div v-if="tip.show" class="pointer-events-none fixed z-[60] max-w-sm rounded-lg bg-text px-3 py-2 text-xs text-white shadow-lg"
           :style="{ left: tip.x + 'px', top: tip.y + 'px' }">
        {{ tip.text }}
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
      tip: { show: false, text: '', x: 0, y: 0 },
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
    /** Only worth a tooltip when the cell is actually clamped. */
    showTip(event, text) {
      const el = event.currentTarget
      if (!text || el.scrollHeight <= el.clientHeight + 1) return
      const r = el.getBoundingClientRect()
      this.tip = {
        show: true,
        text,
        x: Math.min(r.left, window.innerWidth - 400),
        y: r.bottom + 8,
      }
    },

    hideTip() {
      this.tip.show = false
    },

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