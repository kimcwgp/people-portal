<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">My Team's Stand Up</h1>
        
        <button
          @click="exportData"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="!loading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <svg v-else class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ loading ? 'Exporting...' : 'Export CSV' }}
        </button>
      </div>
      
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <div>
            <input
              v-model="filters.userName"
              type="text"
              placeholder="Search by name..."
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>

          <div>
            <input
              v-model="filters.startDate"
              type="date"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>
          
          <div>
            <input
              v-model="filters.endDate"
              type="date"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
          </div>

          <div>
            <select 
              v-model="perPage" 
              @change="changePerPage"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option v-for="option in perPageOptions" :key="option" :value="option">
                {{ option }} per page
              </option>
            </select>
          </div>

          <div class="flex space-x-2">
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

      <div v-if="!loading && standups.length > 0" class="mb-4">
        <p class="text-sm text-gray-600">
          Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} stand-up records
        </p>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <div v-else-if="standups.length === 0" class="rounded-lg bg-white p-8 shadow-sm text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012 2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
        </svg>
        <h3 class="mt-3 text-lg font-semibold text-gray-900">No stand-up records found</h3>
        <p class="mt-1 text-gray-600">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'Your team hasn\'t submitted any stand-up updates yet.' }}
        </p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="standup in standups"
          :key="standup.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200"
        >
          <div class="p-3 sm:p-4">
            <div class="flex flex-col space-y-3 sm:flex-row sm:items-start sm:justify-between sm:space-y-0 mb-3">
              <div class="flex items-center space-x-3">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 truncate">{{ standup.user?.name }}</h3>
                  <div class="mt-1 sm:hidden">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      {{ getProjectName(standup) }}
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center justify-between sm:justify-end sm:flex-col sm:items-end space-x-3 sm:space-x-0 sm:space-y-1">
                <div class="hidden sm:block">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    {{ getProjectName(standup) }}
                  </span>
                </div>
                
                <div class="flex items-center space-x-2">
                  <span v-if="standup.is_today" class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">
                    Today
                  </span>
                  <span v-else-if="standup.is_recent" class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                    Recent
                  </span>
                  <div class="text-right">
                    <p class="text-xs font-medium text-gray-900">{{ standup.relative_date }}</p>
                    <p class="text-xs text-gray-500">Submitted</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs font-semibold text-gray-500 mb-1">STAND-UP DATE</p>
                <p class="text-sm font-semibold text-gray-900">{{ standup.formatted_standup_date }}</p>
              </div>

              <div class="bg-gray-50 rounded-lg p-2">
                <div class="flex items-center mb-2">
                  <p class="text-xs font-semibold text-gray-700">NOTES</p>
                </div>
                <p class="text-sm text-gray-900 whitespace-pre-wrap line-clamp-3">
                  {{ standup.notes || 'No notes provided' }}
                </p>
              </div>

              <div class="bg-gray-50 rounded-lg p-2">
                <div class="flex items-center mb-2">
                  <p class="text-xs font-semibold text-gray-700">TIME TRACKED</p>
                </div>
                <p class="text-sm text-gray-900 font-semibold">{{ standup.formatted_time }}</p>
              </div>

              <div class="bg-gray-50 rounded-lg p-2">
                <div class="flex items-center mb-2">
                  <p class="text-xs font-semibold text-gray-700">IMPEDIMENTS</p>
                  <span v-if="!standup.has_impediments" class="ml-1 text-xs text-green-600 font-medium">(None)</span>
                </div>
                <p class="text-sm text-gray-900 whitespace-pre-wrap line-clamp-3">
                  {{ standup.impediments || 'No impediments reported' }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="standups.length > 0" class="mt-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-4">
          <div class="flex items-center justify-between sm:hidden">
            <button
              @click="changePage(currentPage - 1)"
              :disabled="currentPage <= 1"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Previous
            </button>
            
            <span class="text-sm text-gray-700 font-medium">
              Page {{ currentPage }}
            </span>
            
            <button
              @click="changePage(currentPage + 1)"
              :disabled="currentPage >= lastPage"
              class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              Next
            </button>
          </div>

          <div class="hidden sm:flex sm:flex-col sm:space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0">
            <div class="flex items-center text-sm text-gray-700">
              <span>Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} results</span>
            </div>
            
            <div class="flex items-center space-x-1">
              <button
                @click="changePage(currentPage - 1)"
                :disabled="currentPage <= 1"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Previous
              </button>
              
              <button
                @click="changePage(currentPage + 1)"
                :disabled="currentPage >= lastPage"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios'

export default {
  name: 'TeamStandup',
  data() {
    return {
      standups: [],
      loading: true,
      currentPage: 1,
      lastPage: 1,
      totalItems: 0,
      perPage: 10,
      perPageOptions: [10, 15, 25, 50],
      filters: {
        userName: '',
        startDate: '',
        endDate: ''
      }
    }
  },

  computed: {
    hasActiveFilters() {
      return this.filters.userName || this.filters.startDate || this.filters.endDate
    }
  },

  async mounted() {
    document.title = 'Team Stand-up Overview'
    await this.loadStandups()
  },

  methods: {
    async loadStandups() {
      try {
        this.loading = true
        const params = { 
          page: this.currentPage,
          per_page: this.perPage
        }
        
        if (this.filters.userName) params.user_name = this.filters.userName
        if (this.filters.startDate) params.start_date = this.filters.startDate
        if (this.filters.endDate) params.end_date = this.filters.endDate

        const { data } = await axios.get('/user/my-team-standup/standup', { params })
        this.standups = data.data || []
        this.totalItems = data.meta.total
        this.currentPage = data.meta.current_page
        this.lastPage = data.meta.last_page
      } catch (error) {
        this.showToast('Failed to load team stand-ups', 'error')
      } finally {
        this.loading = false
      }
    },

    async exportData() {
      try {
        this.loading = true;
        
        const params = {};
        
        if (this.filters.userName) params.user_name = this.filters.userName;
        if (this.filters.startDate) params.start_date = this.filters.startDate;
        if (this.filters.endDate) params.end_date = this.filters.endDate;
        
        const response = await axios.get('/user/my-team-standup/export', { 
          params,
          responseType: 'blob'
        });
        
        const blob = new Blob([response.data], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.href = url;
        
        const contentDisposition = response.headers['content-disposition'];
        let filename = 'team_standups_export.csv';
        
        if (contentDisposition) {
          const filenameMatch = contentDisposition.match(/filename="(.+)"/);
          if (filenameMatch) {
            filename = filenameMatch[1];
          }
        }
        
        link.download = filename;
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        window.URL.revokeObjectURL(url);
        
        this.showToast('Export completed successfully', 'success');

      } catch (error) {
        if (error.response?.status === 401) {
          this.showToast('Authentication required. Please log in again.', 'error');
        } else if (error.response?.status === 403) {
          this.showToast('You do not have permission to export this data.', 'error');
        } else {
          this.showToast('Failed to export data. Please try again.', 'error');
        }
      } finally {
        this.loading = false;
      }
    },

    applyFilters() {
      this.currentPage = 1
      this.loadStandups()
    },

    clearFilters() {
      this.filters = {
        userName: '',
        startDate: '',
        endDate: ''
      }
      this.currentPage = 1
      this.loadStandups()
    },

    changePerPage() {
      this.currentPage = 1
      this.loadStandups()
    },

    async changePage(page) {
      if (page < 1 || page > this.lastPage) {
        return
      }
      
      this.currentPage = page
      await this.loadStandups()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },

    getProjectName(standup) {
      if (standup.project) {
        return standup.project.project_name || standup.project.name || 'No Project';
      }
      return 'No Project';
    },

    showToast(message, type = 'info') {
      const toast = document.createElement('div')
      toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error'   ? 'bg-red-500'   :
        type === 'warning' ? 'bg-yellow-500': 'bg-blue-500'
      }`
      toast.textContent = message
      document.body.appendChild(toast)
      
      setTimeout(() => toast.style.opacity = '1', 100)
      setTimeout(() => {
        toast.style.opacity = '0'
        setTimeout(() => document.body.removeChild(toast), 300)
      }, 3000)
    }
  }
}
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>