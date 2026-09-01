<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Page Header -->
      <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Projects</h1>
          </div>
          <div class="mt-4 sm:mt-0">
            <button
              @click="openCreateModal"
              class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 shadow-sm"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Add Project
            </button>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
          <div>
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search project names..."
                class="w-full border border-gray-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
              <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </div>
          </div>

          <div>
            <select 
              v-model="filters.client_id" 
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Clients</option>
              <option v-for="client in formData.clients" :key="client.id" :value="client.id">
                {{ client.name }}
              </option>
            </select>
          </div>

          <div>
            <select 
              v-model="filters.project_type_id" 
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Project Types</option>
              <option v-for="type in formData.project_types" :key="type.id" :value="type.id">
                {{ type.name }}
              </option>
            </select>
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

          <div class="flex space-x-2 sm:col-span-2 lg:col-span-2">
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
      <div v-if="!loading && projects.length > 0" class="mb-4">
        <p class="text-sm text-gray-600">
          Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} projects
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="projects.length === 0" class="rounded-lg bg-white p-8 shadow-sm text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012 2v2M7 7h10"/>
        </svg>
        <h3 class="mt-3 text-lg font-semibold text-gray-900">No projects found</h3>
        <p class="mt-1 text-gray-600">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'Get started by creating a new project' }}
        </p>
      </div>

      <!-- Projects List -->
      <div v-else class="space-y-4">
        <div
          v-for="project in projects"
          :key="project.id"
          class="bg-white rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200"
        >
          <div class="p-3 sm:p-4">
            <div class="flex flex-col space-y-3 sm:flex-row sm:items-start sm:justify-between sm:space-y-0 mb-3">
              <div class="flex items-start space-x-3 flex-1 min-w-0">
                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-semibold text-gray-900 truncate">{{ project.project_name }}</h3>
                  <p class="text-sm text-gray-500 mt-1">{{ project.client?.name || 'No client assigned' }}</p>
                </div>
              </div>
              
              <div class="flex items-center justify-between sm:justify-end sm:flex-col sm:items-end space-x-3 sm:space-x-0 sm:space-y-1">
                <div class="flex items-center space-x-2">
                  <div class="flex space-x-1">
                    <button
                      @click="editProject(project)"
                      class="p-1 text-blue-600 hover:text-blue-800 transition-colors"
                      title="Edit"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                    <button
                      @click="confirmDelete(project)"
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
                <p class="text-xs font-semibold text-gray-500 mb-1">PROJECT TYPE</p>
                <p class="text-sm font-semibold text-blue-600">
                  {{ project.project_type?.name || 'Not Set' }}
                </p>
              </div>

              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs font-semibold text-gray-500 mb-1">PROJECT MANAGER</p>
                <p class="text-sm font-semibold text-gray-900">
                  {{ project.project_manager?.name || 'Unassigned' }}
                </p>
              </div>

              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs font-semibold text-gray-500 mb-1">DESCRIPTION</p>
                <p class="text-sm text-gray-900 line-clamp-2">
                  {{ project.description || 'No description' }}
                </p>
              </div>
            </div>

            <div v-if="project.glip_url" class="mt-3">
              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs font-semibold text-gray-500 mb-1">GLIP</p>
                <a
                  :href="project.glip_url"
                  target="_blank"
                  class="text-sm text-blue-600 hover:text-blue-800 flex items-center space-x-1"
                >
                  <span>View Chat</span>
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                  </svg>
                </a>
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

    <!-- Create/Edit Project Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-semibold mb-4">
          {{ editingProject ? 'Edit Project' : 'Create New Project' }}
        </h2>
        <form @submit.prevent="saveProject">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Project Name *</label>
              <input
                v-model="currentProject.project_name"
                type="text"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter project name"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Client *</label>
              <select
                v-model="currentProject.client_id"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Select Client</option>
                <option v-for="client in formData.clients" :key="client.id" :value="client.id">
                  {{ client.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Project Type *</label>
              <select
                v-model="currentProject.project_type_id"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Select Project Type</option>
                <option v-for="type in formData.project_types" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
              <p v-if="currentProject.project_type_id && formData.project_types.length > 0" class="text-xs text-gray-500 mt-1">
                {{ formData.project_types.find(t => t.id == currentProject.project_type_id)?.description }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Project Manager</label>
              <select
                v-model="currentProject.pm_id"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Select PM</option>
                <option v-for="pm in formData.project_managers" :key="pm.id" :value="pm.id">
                  {{ pm.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Webhook URL</label>
              <input
                v-model="currentProject.glip_url"
                type="url"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="https://glip.com/..."
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea
                v-model="currentProject.description"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Project description..."
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
              {{ saving ? 'Saving...' : (editingProject ? 'Update' : 'Create') }}
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
          Are you sure you want to delete "{{ projectToDelete?.project_name }}"? This action cannot be undone.
        </p>
        <div class="flex space-x-3">
          <button
            @click="showDeleteModal = false"
            class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            Cancel
          </button>
          <button
            @click="deleteProject"
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
  name: 'Projects',
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
      projects: [],
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0,
        from: 0,
        to: 0
      },
      formData: {
        clients: [],
        project_managers: [],
        project_types: []
      },
      filters: {
        client_id: '',
        project_type_id: ''
      },
      selectedPerPage: 10,
      perPageOptions: [10, 25, 50, 100],
      showModal: false,
      showDeleteModal: false,
      editingProject: null,
      projectToDelete: null,
      currentProject: {
        project_name: '',
        client_id: '',
        project_type_id: '',
        pm_id: '',
        glip_url: '',
        description: ''
      }
    }
  },
  computed: {
    hasActiveFilters() {
      return this.filters.client_id || this.filters.project_type_id || this.searchQuery
    }
  },
  mounted() {
    this.fetchFormData()
    this.fetchProjects()
  },
  methods: {
    async fetchProjects(page = 1) {
      this.loading = true
      try {
        const params = {
          page,
          search: this.searchQuery,
          per_page: this.selectedPerPage,
          ...this.filters
        }
        
        const response = await axios.get('/user/projects', { params })
        
        this.projects = response.data.data || []
        this.pagination = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 10,
          total: response.data.total || 0,
          from: response.data.from || 0,
          to: response.data.to || 0
        }
      } catch (error) {
        console.error('Error fetching projects:', error)
        this.showNotification('Failed to fetch projects', 'error')
      } finally {
        this.loading = false
      }
    },

    async fetchFormData() {
      try {
        const response = await axios.get('/user/projects/form-data')
        this.formData = response.data.data
      } catch (error) {
        console.error('Error fetching form data:', error)
      }
    },

    applyFilters() {
      this.fetchProjects()
    },

    clearFilters() {
      this.filters = {
        client_id: '',
        project_type_id: ''
      }
      this.searchQuery = ''
      this.fetchProjects()
    },

    changePerPage() {
      this.fetchProjects()
    },

    changePage(page) {
      if (page === '...' || page < 1 || page > this.pagination.last_page) {
        return
      }
      this.fetchProjects(page)
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
      this.editingProject = null
      this.currentProject = {
        project_name: '',
        client_id: '',
        project_type_id: '',
        pm_id: '',
        glip_url: '',
        description: ''
      }
      this.showModal = true
    },

    editProject(project) {
      this.editingProject = project
      this.currentProject = {
        project_name: project.project_name,
        client_id: project.client_id || project.client?.id || '',
        project_type_id: project.project_type_id || project.project_type?.id || '',
        pm_id: project.pm_id || project.project_manager?.id || '',
        glip_url: project.glip_url || '',
        description: project.description || ''
      }
      this.showModal = true
    },

    async saveProject() {
      this.saving = true
      try {
        if (this.editingProject) {
          await axios.put(`/user/projects/${this.editingProject.id}`, this.currentProject)
          this.showNotification('Project updated successfully', 'success')
        } else {
          await axios.post('/user/projects', this.currentProject)
          this.showNotification('Project created successfully', 'success')
        }
        this.closeModal()
        this.fetchProjects()
      } catch (error) {
        console.error('Error saving project:', error)
        this.showNotification('Failed to save project', 'error')
      } finally {
        this.saving = false
      }
    },

    confirmDelete(project) {
      this.projectToDelete = project
      this.showDeleteModal = true
    },

    async deleteProject() {
      this.deleting = true
      try {
        await axios.delete(`/user/projects/${this.projectToDelete.id}`)
        this.showNotification('Project deleted successfully', 'success')
        this.showDeleteModal = false
        this.fetchProjects()
      } catch (error) {
        console.error('Error deleting project:', error)
        this.showNotification('Failed to delete project', 'error')
      } finally {
        this.deleting = false
      }
    },

    closeModal() {
      this.showModal = false
      this.editingProject = null
    },

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