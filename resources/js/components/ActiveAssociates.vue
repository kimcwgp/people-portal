<template>
  <div class="min-h-screen bg-gray-50 p-4 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-7xl">
      <!-- Header -->
      <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Active Associates</h1>
        </div>
        
        <!-- Summary Stats -->
        <div class="flex items-center space-x-4 text-sm">
          <div class="flex items-center space-x-1">
            <div class="h-2 w-2 rounded-full bg-green-500"></div>
            <span class="text-gray-600">{{ summary.working }} Working</span>
          </div>
          <div class="flex items-center space-x-1">
            <div class="h-2 w-2 rounded-full bg-orange-500"></div>
            <span class="text-gray-600">{{ summary.on_break }} On Break</span>
          </div>
          <div class="text-gray-500">
            Total: {{ summary.total_active }}
          </div>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="mb-6 rounded-lg bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <!-- Search -->
          <div class="relative flex-1 max-w-md">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </div>
            <input
              v-model="search"
              type="text"
              placeholder="Search associates..."
              class="block w-full rounded-lg border border-gray-300 pl-10 pr-3 py-2 text-sm placeholder-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20"
            />
          </div>
          
          <!-- Auto Refresh Toggle -->
          <div class="flex items-center space-x-3">
            <label class="flex items-center cursor-pointer">
              <input
                v-model="autoRefresh"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Auto-refresh</span>
            </label>
            
            <button
              @click="loadAssociates"
              :disabled="loading"
              class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
            >
              <svg class="h-4 w-4 mr-2" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Refresh
            </button>
          </div>
        </div>
      </div>

      <!-- Associates Table -->
      <div class="rounded-lg bg-white shadow-sm overflow-hidden">
        <!-- Loading State -->
        <div v-if="loading && associates.length === 0" class="flex items-center justify-center py-12">
          <svg class="h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
        </div>
        
        <!-- Empty State -->
        <div v-else-if="filteredAssociates.length === 0 && !loading" class="py-12 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          <p class="mt-2 text-sm text-gray-500">
            {{ search ? 'No associates found matching your search.' : 'No active associates at the moment.' }}
          </p>
        </div>
        
        <!-- Associates Table -->
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Associate
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Time In
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Department
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="associate in filteredAssociates"
                :key="associate.id"
                class="hover:bg-gray-50 transition-colors"
              >
                <!-- Associate Info -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                        <span class="text-sm font-semibold text-white">{{ associate.avatar }}</span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ associate.name }}</div>
                      <div class="text-sm text-gray-500">{{ associate.email }}</div>
                    </div>
                  </div>
                </td>

                <!-- Status -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusClasses(associate.status_color)">
                    <div class="w-2 h-2 rounded-full mr-1.5" :class="getStatusDotClasses(associate.status_color)"></div>
                    {{ associate.status }}
                  </span>
                </td>

                <!-- Time In -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ associate.time_in_formatted || '--' }}</div>
                </td>

                <!-- Department -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-500">{{ associate.department || 'N/A' }}</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Last Updated -->
      <div v-if="lastUpdated" class="mt-4 text-center text-xs text-gray-500">
        Last updated: {{ lastUpdated.toLocaleTimeString() }}
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios';

export default {
  name: 'ActiveAssociates',
  
  data() {
    return {
      associates: [],
      summary: {
        total_active: 0,
        working: 0,
        on_break: 0
      },
      loading: false,
      search: '',
      autoRefresh: true,
      refreshInterval: null,
      lastUpdated: null,
    };
  },

  computed: {
    filteredAssociates() {
      if (!this.search.trim()) {
        return this.associates;
      }
      
      const searchTerm = this.search.toLowerCase();
      return this.associates.filter(associate => 
        associate.name.toLowerCase().includes(searchTerm) ||
        associate.email.toLowerCase().includes(searchTerm) ||
        (associate.department && associate.department.toLowerCase().includes(searchTerm))
      );
    }
  },

  watch: {
    autoRefresh(enabled) {
      if (enabled) {
        this.startAutoRefresh();
      } else {
        this.stopAutoRefresh();
      }
    }
  },

  async mounted() {
    await this.loadAssociates();
    if (this.autoRefresh) {
      this.startAutoRefresh();
    }
  },

  beforeUnmount() {
    this.stopAutoRefresh();
  },

  methods: {
    async loadAssociates() {
      try {
        this.loading = true;
        const params = new URLSearchParams();
        
        // Only send search if it's not empty to avoid server-side filtering conflicts
        // We'll filter on the client side for better UX
        
        const { data } = await axios.get('/user/active-associates', { params });
        
        if (data.success) {
          this.associates = data.data || [];
          this.summary = data.summary || { total_active: 0, working: 0, on_break: 0 };
          this.lastUpdated = new Date();
        }
      } catch (error) {
        console.error('Failed to load active associates:', error);
        this.showToast('Failed to load active associates', 'error');
      } finally {
        this.loading = false;
      }
    },

    startAutoRefresh() {
      // Refresh every 30 seconds
      this.refreshInterval = setInterval(() => {
        this.loadAssociates();
      }, 30000);
    },

    stopAutoRefresh() {
      if (this.refreshInterval) {
        clearInterval(this.refreshInterval);
        this.refreshInterval = null;
      }
    },

    getStatusClasses(statusColor) {
      const colorMap = {
        green: 'bg-green-100 text-green-800',
        orange: 'bg-orange-100 text-orange-800',
        yellow: 'bg-yellow-100 text-yellow-800',
        blue: 'bg-blue-100 text-blue-800',
        gray: 'bg-gray-100 text-gray-800',
      };
      return colorMap[statusColor] || colorMap.gray;
    },

    getStatusDotClasses(statusColor) {
      const colorMap = {
        green: 'bg-green-500',
        orange: 'bg-orange-500',
        yellow: 'bg-yellow-500',
        blue: 'bg-blue-500',
        gray: 'bg-gray-500',
      };
      return colorMap[statusColor] || colorMap.gray;
    },

    showToast(message, type = 'info', duration = 3000) {
      // Simple toast notification (reuse from your existing implementation)
      const toast = document.createElement('div');
      const bgColor = {
        'success': 'bg-green-500',
        'error': 'bg-red-500',
        'info': 'bg-blue-500'
      }[type] || 'bg-blue-500';
      
      toast.className = `fixed top-4 right-4 z-50 rounded-lg px-6 py-3 text-white shadow transition-all duration-300 ${bgColor}`;
      toast.textContent = message;
      
      document.body.appendChild(toast);
      
      setTimeout(() => {
        if (toast.parentNode) {
          document.body.removeChild(toast);
        }
      }, duration);
    }
  }
};
</script>