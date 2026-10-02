<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Title bar, matching Attendance -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Active Associates</h1>
        <button
          @click="loadAssociates"
          :disabled="loading"
          class="flex flex-shrink-0 items-center gap-2 rounded-lg bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover disabled:opacity-50"
        >
          <svg class="h-4 w-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Refresh
        </button>
      </div>

      <!-- Live headcount. This is a snapshot of right now, so the search below
           deliberately does not narrow it. -->
      <div class="mb-3 sm:mb-4 rounded-xl bg-surface p-4 shadow-sm sm:rounded-2xl sm:px-6">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-text-muted">Currently Clocked In</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div v-for="tile in summaryTiles" :key="tile.label" class="rounded-lg border border-border px-3 py-2.5">
            <p class="flex items-center gap-1.5 text-xs text-text-muted">
              <span v-if="tile.dot" class="h-2 w-2 rounded-full" :class="tile.dot"></span>
              {{ tile.label }}
            </p>
            <p class="text-base sm:text-lg font-bold text-text">{{ tile.value }}</p>
          </div>
        </div>
      </div>

      <!-- Filters + results live in one card, as in Attendance -->
      <div class="bg-surface rounded-lg shadow-sm border border-border overflow-hidden">
        <!-- Notched-outline fields: the label sits on the border line.
             shrink-0 keeps the set widths so they wrap instead of collapsing. -->
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="relative w-full shrink-0 sm:w-72">
            <label for="f-search" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Search</label>
            <input
              id="f-search"
              v-model="search"
              type="text"
              placeholder="Name, email or department"
              class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text placeholder-text-subtle focus:border-accent focus:outline-none"
            />
          </div>

          <label class="flex shrink-0 cursor-pointer items-center gap-2 rounded-lg border border-border px-3 py-2.5">
            <input v-model="autoRefresh" type="checkbox" class="h-4 w-4 rounded border-border text-accent-solid focus:ring-accent" />
            <span class="text-sm text-text">Auto-refresh</span>
          </label>

          <button
            v-if="search"
            @click="search = ''"
            class="shrink-0 rounded-lg border border-border bg-surface-sunken px-5 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas"
          >
            Clear
          </button>

          <p v-if="lastUpdated" class="ml-auto text-xs text-text-muted">
            Last updated {{ lastUpdated.toLocaleTimeString() }}
          </p>
        </div>

        <!-- Results summary -->
        <div v-if="!loading && filteredAssociates.length > 0" class="border-b border-border px-4 py-2">
          <p class="text-xs text-text-muted">
            Showing {{ filteredAssociates.length }} of {{ associates.length }} active associates
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading && associates.length === 0" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="filteredAssociates.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No active associates</h3>
          <p class="mt-1 text-text-muted">
            {{ search ? 'Try adjusting your search' : 'Nobody is clocked in at the moment.' }}
          </p>
        </div>

        <div v-else>
          <!-- Desktop Table -->
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full min-w-[760px]">
              <thead class="bg-surface-sunken border-b border-border">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Associate</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Time In</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-text-muted uppercase tracking-wider">Department</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="associate in filteredAssociates" :key="associate.id" class="hover:bg-surface-sunken transition-colors">
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                      <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-accent-solid">
                        <span class="text-sm font-semibold text-white">{{ associate.avatar }}</span>
                      </div>
                      <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-text">{{ associate.name }}</p>
                        <p class="truncate text-xs text-text-muted">{{ associate.email }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center whitespace-nowrap rounded-full px-2 py-1 text-xs font-medium" :class="getStatusClasses(associate.status_color)">
                      <span class="mr-1.5 h-2 w-2 rounded-full" :class="getStatusDotClasses(associate.status_color)"></span>
                      {{ associate.status }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <p class="whitespace-nowrap text-sm text-text">{{ associate.time_in_formatted || '--' }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <p class="text-sm text-text-muted">{{ associate.department || 'N/A' }}</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Cards -->
          <div class="lg:hidden divide-y divide-border">
            <div v-for="associate in filteredAssociates" :key="associate.id" class="p-4 hover:bg-surface-sunken transition-colors">
              <div class="mb-3 flex items-start justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                  <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-accent-solid">
                    <span class="text-sm font-semibold text-white">{{ associate.avatar }}</span>
                  </div>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-text">{{ associate.name }}</p>
                    <p class="truncate text-xs text-text-muted">{{ associate.email }}</p>
                  </div>
                </div>
                <span class="inline-flex flex-shrink-0 items-center whitespace-nowrap rounded-full px-2 py-1 text-xs font-medium" :class="getStatusClasses(associate.status_color)">
                  <span class="mr-1.5 h-2 w-2 rounded-full" :class="getStatusDotClasses(associate.status_color)"></span>
                  {{ associate.status }}
                </span>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Time In:</span>
                  <span class="font-medium text-text">{{ associate.time_in_formatted || '--' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-text-muted">Department:</span>
                  <span class="font-medium text-text">{{ associate.department || 'N/A' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios';

/* Tailwind only compiles classes it can read as literal text, so the status
   fills have to be spelled out rather than built from `status_color`. */
const STATUS_CLASSES = {
  green: 'bg-status-green text-status-text',
  orange: 'bg-status-orange text-status-text',
  yellow: 'bg-status-yellow text-status-text',
  blue: 'bg-status-blue text-status-text',
  gray: 'bg-status-gray text-status-text',
};

const DOT_CLASSES = {
  green: 'bg-success',
  orange: 'bg-warning',
  yellow: 'bg-warning',
  blue: 'bg-accent-solid',
  gray: 'bg-text-subtle',
};

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
    },

    summaryTiles() {
      return [
        { label: 'Working', value: this.summary.working, dot: 'bg-success' },
        { label: 'On Break', value: this.summary.on_break, dot: 'bg-warning' },
        { label: 'Total Active', value: this.summary.total_active, dot: '' },
      ];
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
      return STATUS_CLASSES[statusColor] || STATUS_CLASSES.gray;
    },

    getStatusDotClasses(statusColor) {
      return DOT_CLASSES[statusColor] || DOT_CLASSES.gray;
    },

    showToast(message, type = 'info', duration = 3000) {
      const toast = document.createElement('div');
      const bgColor = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'info': 'bg-accent-solid'
      }[type] || 'bg-accent-solid';

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
