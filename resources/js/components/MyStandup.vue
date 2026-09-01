<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <h1 class="text-3xl font-bold text-gray-900 mb-2">Daily Standups</h1>
          <div class="mt-4 sm:mt-0">
            <button
              @click="openCreateModal"
              class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 shadow-sm"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              New Standup
            </button>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
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
              v-model="filters.projectId"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Projects</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.project_name }}
              </option>
            </select>
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
          Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} standup records
        </p>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <div v-else-if="standups.length === 0" class="rounded-lg bg-white p-8 shadow-sm text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-3 text-lg font-semibold text-gray-900">No standup records found</h3>
        <p class="mt-1 text-gray-600">
          {{ hasActiveFilters ? 'Try adjusting your filters' : 'You haven\'t submitted any standup updates yet.' }}
        </p>
      </div>

      <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <!-- Desktop Table -->
        <div class="hidden lg:block overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Project</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Notes</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time Tracked</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Impediments</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="standup in standups"
                :key="standup.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3">
                  <div class="flex flex-col">
                    <div class="flex items-center gap-1 mt-1">
                      <span v-if="isToday(standup.standup_date)" class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">
                        Today
                      </span>
                      <span v-else-if="isRecent(standup.standup_date)" class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                        Recent
                      </span>
                    </div>
                    <p class="text-sm font-semibold text-gray-900">{{ formatDate(standup.standup_date) }}</p>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    {{ getProjectName(standup) }}
                  </span>
                </td>
                <td class="px-4 py-3 max-w-xs">
                  <p class="text-sm text-gray-900 whitespace-pre-wrap">
                    {{ standup.notes || 'No notes provided' }}
                  </p>
                </td>
                <td class="px-4 py-3">
                  <p class="text-sm font-semibold text-gray-900">{{ standup.formatted_time }}</p>
                </td>
                <td class="px-4 py-3 max-w-xs">
                  <div class="flex items-start gap-1">
                    <p class="text-sm text-gray-900 whitespace-pre-wrap">
                      {{ standup.impediments || 'No impediments reported' }}
                    </p>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-center">
                    <button
                      @click="editStandup(standup)"
                      class="p-1.5 rounded transition-colors text-blue-600 hover:bg-blue-50"
                      title="Edit Standup"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Cards -->
        <div class="lg:hidden divide-y divide-gray-200">
          <div
            v-for="standup in standups"
            :key="standup.id"
            class="p-4 hover:bg-gray-50 transition-colors"
          >
            <!-- Header -->
            <div class="flex items-start justify-between mb-3">
              <div>
                <p class="text-sm font-semibold text-gray-900">{{ formatDate(standup.standup_date) }}</p>
                <div class="flex items-center gap-1 mt-1">
                  <span v-if="isToday(standup.standup_date)" class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">
                    Today
                  </span>
                  <span v-else-if="isRecent(standup.standup_date)" class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                    Recent
                  </span>
                </div>
              </div>
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                {{ getProjectName(standup) }}
              </span>
            </div>

            <!-- Details -->
            <div class="space-y-2 mb-3">
              <div class="text-sm">
                <span class="text-gray-500 font-medium">Notes:</span>
                <p class="text-gray-900 mt-1 whitespace-pre-wrap">{{ standup.notes || 'No notes provided' }}</p>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500 font-medium">Time Tracked:</span>
                <span class="font-semibold text-gray-900">{{ standup.formatted_time }}</span>
              </div>
              <div class="text-sm">
                <div class="flex items-center gap-1">
                  <span class="text-gray-500 font-medium">Impediments:</span>
                  <span v-if="!standup.impediments" class="text-xs text-green-600 font-medium">(None)</span>
                </div>
                <p class="text-gray-900 mt-1 whitespace-pre-wrap">{{ standup.impediments || 'No impediments reported' }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="pt-3 border-t border-gray-200 flex justify-end">
              <button
                @click="editStandup(standup)"
                class="px-3 py-2 text-sm font-medium rounded-lg transition-colors bg-blue-600 text-white hover:bg-blue-700"
              >
                Edit Standup
              </button>
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

    <div v-if="showModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto border border-gray-100">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">{{ modalTitle }}</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="bg-gray-50 rounded-lg p-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Standup Date *</label>
              <input
                type="date"
                v-model="form.standup_date"
                required
                class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
              <p class="text-xs text-gray-500 mt-1">
                {{ isCreateMode ? 'All standups will be created for this date' : 'Update standup date' }}
              </p>
            </div>

            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">
                  {{ isCreateMode ? 'Project Standups' : 'Standup Details' }}
                </h3>
                <button
                  v-if="isCreateMode"
                  type="button"
                  @click="addStandupEntry"
                  class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                  </svg>
                  Add Project
                </button>
              </div>

              <div v-for="(standup, index) in form.standups" :key="index" class="border border-gray-200 rounded-lg p-4 space-y-4">
                <div class="flex items-center justify-between">
                  <h4 class="font-medium text-gray-900">
                    {{ isCreateMode ? `Project ${index + 1}` : 'Standup Entry' }}
                  </h4>
                  <button
                    v-if="isCreateMode && form.standups.length > 1"
                    type="button"
                    @click="removeStandupEntry(index)"
                    class="text-red-500 hover:text-red-700 text-sm font-medium"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Project</label>
                  <select
                    v-model="standup.project_id"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Select Project (Optional)</option>
                    <option v-for="project in projects" :key="project.id" :value="project.id">
                      {{ project.project_name }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">What did you do today?</label>
                  <textarea
                    v-model="standup.notes"
                    rows="3"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                    placeholder="[DEV] create user module"
                  ></textarea>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Time Spent</label>
                  <div class="flex items-center space-x-2">
                    <input
                      type="number"
                      v-model.number="standup.hours"
                      min="0"
                      max="24"
                      class="w-20 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="0"
                    >
                    <span class="text-sm text-gray-600">hour(s)</span>
                    <input
                      type="number"
                      v-model.number="standup.minutes"
                      min="0"
                      max="59"
                      class="w-20 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="0"
                    >
                    <span class="text-sm text-gray-600">min</span>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Impediments/Blockers</label>
                  <textarea
                    v-model="standup.impediments"
                    rows="2"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                    placeholder="Any blockers or challenges you're facing?"
                  ></textarea>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t">
              <button
                type="button"
                @click="closeModal"
                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="submitting || !hasValidEntries"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
              >
                <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ submitButtonText }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      standups: [],
      projects: [],
      loading: true,
      submitting: false,
      showModal: false,
      modalMode: 'create',
      editingStandup: null,
      currentPage: 1,
      lastPage: 1,
      totalItems: 0,
      perPage: 10,
      perPageOptions: [10, 15, 25, 50],
      filters: {
        startDate: '',
        endDate: '',
        projectId: ''
      },
      form: {
        standup_date: this.getTodayDate(),
        standups: [
          {
            project_id: '',
            notes: '',
            impediments: '',
            hours: 0, 
            minutes: 0 
          }
        ]
      }
    };
  },

  computed: {
    hasValidEntries() {
      return this.form.standups.some(standup => 
        standup.notes?.trim() || 
        standup.impediments?.trim() || 
        standup.project_id ||
        (standup.hours > 0 || standup.minutes > 0)
      );
    },
    
    modalTitle() {
      return this.modalMode === 'create' ? 'Create Standups' : 'Edit Standup';
    },
    
    submitButtonText() {
      return this.modalMode === 'create' ? 'Create Standups' : 'Update Standup';
    },
    
    isEditMode() {
      return this.modalMode === 'edit';
    },
    
    isCreateMode() {
      return this.modalMode === 'create';
    },

    hasActiveFilters() {
      return this.filters.startDate || this.filters.endDate || this.filters.projectId;
    }
  },

  mounted() {
    this.initializeFilters();
    this.loadStandups();
    this.loadProjects();
  },

  methods: {
    initializeFilters() {
      const now = new Date();
      const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
      const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);
      
      this.filters.startDate = startOfMonth.toISOString().split('T')[0];
      this.filters.endDate = endOfMonth.toISOString().split('T')[0];
    },

    async loadStandups(page = 1) {
      this.loading = true;
      try {
        const params = {
          page,
          per_page: this.perPage,
          start_date: this.filters.startDate,
          end_date: this.filters.endDate,
          project_id: this.filters.projectId
        };

        const response = await axios.get('/user/my-standups', { params });
        this.standups = response.data.data;
        this.totalItems = response.data.meta.total;
        this.currentPage = response.data.meta.current_page;
        this.lastPage = response.data.meta.last_page;
      } catch (error) {
        // Silent fail or handle error
      } finally {
        this.loading = false;
      }
    },

    changePage(page) {
      this.loadStandups(page);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    changePerPage() {
      this.currentPage = 1;
      this.loadStandups();
    },

    applyFilters() {
      this.currentPage = 1;
      this.loadStandups();
    },

    async loadProjects() {
      try {
        const response = await axios.get('/user/my-standups/projects'); 
        this.projects = response.data.data || response.data;
      } catch (error) {
        this.showToast('Failed to load projects', 'error');
      }
    },
    
    openCreateModal() {
      this.modalMode = 'create';
      this.editingStandup = null;
      this.form = {
        standup_date: this.getTodayDate(),
        standups: [
          {
            project_id: '',
            notes: '',
            impediments: '',
            hours: 0,
            minutes: 0
          }
        ]
      };
      this.showModal = true;
    },

    editStandup(standup) {
      this.modalMode = 'edit';
      this.editingStandup = standup;
      
      let standupDate = standup.standup_date;
      if (standupDate) {
        if (standupDate instanceof Date) {
          standupDate = standupDate.toISOString().split('T')[0];
        } else if (typeof standupDate === 'string') {
          standupDate = standupDate.split('T')[0].split(' ')[0];
        }
      }
      
      this.form = {
        standup_date: standupDate,
        standups: [
          {
            project_id: standup.project_id || '',
            notes: standup.notes || '',
            impediments: standup.impediments || '',
            hours: standup.time_hours || 0,
            minutes: standup.time_minutes || 0
          }
        ]
      };
      this.showModal = true;
    },

    addStandupEntry() {
      if (this.form.standups.length < 10) {
        this.form.standups.push({
          project_id: '',
          notes: '',
          impediments: '',
          hours: 0, 
          minutes: 0 
        });
      }
    },

    removeStandupEntry(index) {
      if (this.form.standups.length > 1) {
        this.form.standups.splice(index, 1);
      }
    },

    async submitForm() {
      if (!this.hasValidEntries) {
        this.showToast('Please fill in at least one standup entry', 'warning');
        return;
      }

      this.submitting = true;
      try {
        let response;
        
        if (this.isCreateMode) {
          response = await axios.post('/user/my-standups', this.form);
          const count = response.data.count || 1;
          const message = count === 1 
            ? 'Standup created successfully!' 
            : `${count} standups created successfully!`;
          this.showToast(message, 'success');
        } else {
          const standupData = this.form.standups[0];
          standupData.standup_date = this.form.standup_date;
          
          response = await axios.put(`/user/my-standups/${this.editingStandup.id}`, standupData);
          this.showToast('Standup updated successfully!', 'success');
        }
        
        this.closeModal();
        this.loadStandups();
      } catch (error) {
        if (error.response?.status === 422) {
          const errors = error.response.data.errors;
          const errorMessages = errors ? Object.values(errors).flat() : [];
          const message = errorMessages[0] || error.response.data.message || 'Validation failed';
          this.showToast(message, 'error');
        } else {
          const action = this.isCreateMode ? 'save' : 'update';
          this.showToast(error.response?.data?.message || `Failed to ${action} standup`, 'error');
        }
      } finally {
        this.submitting = false;
      }
    },

    clearFilters() {
      this.filters = {
        startDate: '',
        endDate: '',
        projectId: ''
      };
      this.currentPage = 1;
      this.loadStandups();
    },

    closeModal() {
      this.showModal = false;
      this.modalMode = 'create';
      this.editingStandup = null;
    },

    getTodayDate() {
      return new Date().toISOString().split('T')[0];
    },

    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    },

    isToday(dateString) {
      const today = new Date().toISOString().split('T')[0];
      const standupDate = new Date(dateString).toISOString().split('T')[0];
      return today === standupDate;
    },

    isRecent(dateString) {
      const standupDate = new Date(dateString);
      const now = new Date();
      const diffTime = Math.abs(now - standupDate);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      return diffDays <= 7 && !this.isToday(dateString);
    },

    showToast(message, type = 'info') {
      const toast = document.createElement('div');
      toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error'   ? 'bg-red-500'   :
        type === 'warning' ? 'bg-yellow-500': 'bg-blue-500'
      }`;
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(() => toast.style.opacity = '1', 100);
      setTimeout(() => { 
        toast.style.opacity = '0'; 
        setTimeout(() => document.body.removeChild(toast), 300); 
      }, 3000);
    },

    getProjectName(standup) {
      if (standup.project) {
        return standup.project.project_name || standup.project.name || 'No Project';
      }
      return 'No Project';
    }
  }
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>