<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Title bar, matching Attendance -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Projects</h1>
        <button
          @click="openCreateModal"
          class="flex flex-shrink-0 items-center gap-2 rounded-lg bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Project
        </button>
      </div>

      <!-- Filters + results live in one card, as in Attendance -->
      <div class="bg-surface rounded-lg shadow-sm border border-border overflow-hidden">
        <!-- Notched-outline fields: the label sits on the border line.
             shrink-0 keeps the set widths so they wrap instead of collapsing. -->
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="relative w-full shrink-0 sm:w-64">
            <label for="f-proj-search" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Search</label>
            <input
              id="f-proj-search"
              v-model="searchQuery"
              type="text"
              placeholder="Project name"
              class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text placeholder-text-subtle focus:border-accent focus:outline-none"
            >
          </div>

          <div class="relative w-full shrink-0 sm:w-52">
            <label for="f-proj-client" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Client</label>
            <select id="f-proj-client" v-model="filters.client_id"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option value="">All Clients</option>
              <option v-for="client in formData.clients" :key="client.id" :value="client.id">{{ client.name }}</option>
            </select>
          </div>

          <div class="relative w-full shrink-0 sm:w-52">
            <label for="f-proj-type" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Project Type</label>
            <select id="f-proj-type" v-model="filters.project_type_id"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option value="">All Project Types</option>
              <option v-for="type in formData.project_types" :key="type.id" :value="type.id">{{ type.name }}</option>
            </select>
          </div>

          <div class="relative w-full shrink-0 sm:w-40">
            <label for="f-proj-per-page" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Show</label>
            <select id="f-proj-per-page" v-model="selectedPerPage" @change="changePerPage"
                    class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text focus:border-accent focus:outline-none">
              <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }} per page</option>
            </select>
          </div>

          <div class="flex shrink-0 gap-2">
            <button @click="applyFilters"
                    class="flex-1 rounded-lg bg-accent-solid px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover sm:flex-none">
              Filter
            </button>
            <button @click="clearFilters"
                    class="flex-1 rounded-lg border border-border bg-surface-sunken px-5 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas sm:flex-none">
              Clear
            </button>
          </div>
        </div>

        <!-- Results summary -->
        <div v-if="!loading && projects.length > 0" class="border-b border-border px-4 py-2">
          <p class="text-xs text-text-muted">
            Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} projects
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="projects.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012 2v2M7 7h10"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No projects found</h3>
          <p class="mt-1 text-text-muted">
            {{ hasActiveFilters ? 'Try adjusting your filters' : 'Get started by creating a new project' }}
          </p>
        </div>

        <div v-else>
          <!-- Desktop Table -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full min-w-[820px]">
              <thead class="bg-surface-sunken border-b border-border">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Project</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Client</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Project Type</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Description</th>
                  <th class="px-4 py-3 text-center text-xs font-semibold text-text-muted uppercase tracking-wider">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="project in projects" :key="project.id" class="hover:bg-surface-sunken transition-colors">
                  <td class="px-4 py-3">
                    <p class="text-sm font-medium text-text">{{ project.project_name }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="text-sm text-text">{{ project.client?.name || 'No client assigned' }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <span v-if="project.project_type?.name" class="inline-flex items-center whitespace-nowrap rounded-md bg-status-purple px-2 py-1 text-xs font-medium text-status-text">
                      {{ project.project_type.name }}
                    </span>
                    <span v-else class="text-sm text-text-muted">Not Set</span>
                  </td>
                  <td class="px-4 py-3 max-w-xs min-w-[190px]">
                    <p class="text-sm text-text-muted line-clamp-2"
                       @mouseenter="showTip($event, project.description)" @mouseleave="hideTip">
                      {{ project.description || 'No description' }}
                    </p>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-2">
                      <button @click="editProject(project)" class="p-1.5 text-accent-solid hover:bg-accent-subtle rounded transition-colors" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </button>
                      <button @click="confirmDelete(project)" class="p-1.5 text-danger hover:bg-status-red rounded transition-colors" title="Delete">
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
            <div v-for="project in projects" :key="project.id" class="p-4 hover:bg-surface-sunken transition-colors">
              <div class="mb-3 flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-text">{{ project.project_name }}</p>
                  <p class="truncate text-xs text-text-muted">{{ project.client?.name || 'No client assigned' }}</p>
                </div>
                <span v-if="project.project_type?.name" class="inline-flex flex-shrink-0 items-center whitespace-nowrap rounded-md bg-status-purple px-2 py-1 text-xs font-medium text-status-text">
                  {{ project.project_type.name }}
                </span>
              </div>

              <div class="mb-3 space-y-2">
                <div class="text-sm">
                  <span class="text-text-muted">Description:</span>
                  <p class="mt-1 line-clamp-2 text-text">{{ project.description || 'No description' }}</p>
                </div>
              </div>

              <div class="flex gap-2">
                <button @click="editProject(project)" class="flex-1 rounded-lg bg-accent-solid px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-hover">Edit</button>
                <button @click="confirmDelete(project)" class="flex-1 rounded-lg bg-danger px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-danger/90">Delete</button>
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

      <!-- Create/Edit Project Modal -->
      <div v-if="showModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
        <div class="relative bg-surface rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden">
          <div class="p-6 border-b border-border">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-text">{{ editingProject ? 'Edit Project' : 'Create New Project' }}</h3>
              <button @click="closeModal" class="p-2 rounded-lg hover:bg-surface-sunken">
                <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <form id="project-form" @submit.prevent="saveProject" class="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-220px)]">
            <div>
              <label class="block text-sm font-medium text-text mb-2">Project Name *</label>
              <input v-model="currentProject.project_name" type="text" required
                     class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                     placeholder="Enter project name">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-text mb-2">Client *</label>
                <select v-model="currentProject.client_id" required
                        class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200">
                  <option value="">Select Client</option>
                  <option v-for="client in formData.clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-text mb-2">Project Type *</label>
                <select v-model="currentProject.project_type_id" required
                        class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200">
                  <option value="">Select Project Type</option>
                  <option v-for="type in formData.project_types" :key="type.id" :value="type.id">{{ type.name }}</option>
                </select>
                <p v-if="currentProject.project_type_id && formData.project_types.length > 0" class="mt-1 text-xs text-text-muted">
                  {{ formData.project_types.find(t => t.id == currentProject.project_type_id)?.description }}
                </p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-text mb-2">Description</label>
              <textarea v-model="currentProject.description" rows="3"
                        class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                        placeholder="Project description..."></textarea>
            </div>
          </form>

          <div class="flex gap-3 border-t border-border p-6">
            <button type="button" @click="closeModal"
                    class="flex-1 rounded-xl border border-border bg-surface-sunken px-4 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas">
              Cancel
            </button>
            <button type="submit" form="project-form" :disabled="saving"
                    class="flex-1 rounded-xl bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover disabled:opacity-50">
              {{ saving ? 'Saving...' : (editingProject ? 'Update' : 'Create') }}
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
            <h3 class="text-lg font-semibold text-text mb-2">Delete Project</h3>
            <p class="text-text-muted mb-6">
              Are you sure you want to delete &ldquo;{{ projectToDelete?.project_name }}&rdquo;? This action cannot be undone.
            </p>
            <div class="flex gap-3">
              <button @click="showDeleteModal = false" class="flex-1 px-4 py-2 text-text bg-surface-sunken border border-border hover:bg-canvas rounded-xl">Cancel</button>
              <button @click="deleteProject" :disabled="deleting" class="flex-1 px-4 py-2 text-white bg-danger hover:bg-danger/90 rounded-xl disabled:opacity-50">
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
  name: 'Projects',
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