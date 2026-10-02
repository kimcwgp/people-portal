<template>
  <div class="min-h-screen bg-gray-50 p-3 sm:p-6 lg:p-8">
    <div class="mx-auto max-w-6xl">
      <!-- Header -->
      <div class="mb-4 flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:rounded-2xl sm:p-6">
        <div>
          <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Holidays</h1>
          <p class="mt-1 text-sm text-gray-500">
            Maintains the Partners and People calendars shown on everyone's dashboard.
          </p>
        </div>
        <button
          v-if="canCreate"
          @click="openCreate"
          class="flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Holiday
        </button>
      </div>

      <!-- Filters -->
      <div class="mb-4 grid grid-cols-1 gap-3 rounded-xl bg-white p-4 shadow-sm sm:grid-cols-3 sm:rounded-2xl">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700">Year</label>
          <select v-model="filters.year" @change="loadHolidays" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
            <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700">Calendar</label>
          <select v-model="filters.calendar" @change="loadHolidays" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
            <option value="">All calendars</option>
            <option value="partners">Partners</option>
            <option value="people">People</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-700">Search</label>
          <input
            v-model="filters.search"
            @input="debouncedSearch"
            type="text"
            placeholder="Holiday name..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <!-- List -->
      <div class="overflow-hidden rounded-xl bg-white shadow-sm sm:rounded-2xl">
        <div v-if="loading" class="p-8 text-center text-sm text-gray-500">Loading holidays...</div>

        <div v-else-if="holidays.length === 0" class="p-8 text-center">
          <p class="font-medium text-gray-700">No holidays found</p>
          <p class="mt-1 text-sm text-gray-500">Try a different year or calendar, or add one.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
              <tr>
                <th class="px-4 py-3 font-medium">Date</th>
                <th class="px-4 py-3 font-medium">Holiday</th>
                <th class="px-4 py-3 font-medium">Calendar</th>
                <th class="px-4 py-3 font-medium">Type</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 text-right font-medium">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="holiday in holidays" :key="holiday.id" class="hover:bg-gray-50">
                <td class="whitespace-nowrap px-4 py-3">
                  <p class="font-medium text-gray-900">{{ holiday.date_label }}</p>
                  <p class="text-xs text-gray-500">{{ holiday.day_label }}</p>
                </td>
                <td class="px-4 py-3">
                  <p class="font-medium text-gray-900">{{ holiday.name }}</p>
                  <p v-if="holiday.description" class="text-xs text-gray-500">{{ holiday.description }}</p>
                </td>
                <td class="px-4 py-3">
                  <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', calendarClass(holiday.calendar)]">
                    {{ holiday.calendar === 'partners' ? 'Partners' : 'People' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ holiday.type_label }}</td>
                <td class="px-4 py-3">
                  <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-medium', holiday.is_active ? 'bg-status-green text-status-text' : 'bg-status-gray text-status-text']">
                    {{ holiday.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-right">
                  <button v-if="canEdit" @click="openEdit(holiday)" class="mr-3 text-sm font-medium text-blue-600 hover:text-blue-800">Edit</button>
                  <button v-if="canDelete" @click="confirmDelete(holiday)" class="text-sm font-medium text-red-600 hover:text-red-800">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create / edit modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
      <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">{{ editing ? 'Edit Holiday' : 'Add Holiday' }}</h2>

        <div class="space-y-3">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
            <input v-model="form.name" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-300': errors.name }" />
            <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name[0] }}</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Date</label>
            <input v-model="form.date" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500" :class="{ 'border-red-300': errors.date }" />
            <p v-if="errors.date" class="mt-1 text-xs text-red-500">{{ errors.date[0] }}</p>
          </div>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Calendar</label>
              <select v-model="form.calendar" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                <option value="people">People</option>
                <option value="partners">Partners</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
              <select v-model="form.type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500">
                <option value="regular">Regular Holiday</option>
                <option value="special_non_working">Special (Non-Working)</option>
                <option value="company">Company Holiday</option>
              </select>
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Description <span class="text-gray-400">(optional)</span></label>
            <textarea v-model="form.description" rows="2" class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"></textarea>
          </div>

          <label class="flex items-center gap-2 text-sm text-gray-700">
            <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
            Active (shown on the dashboard)
          </label>

          <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
        </div>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
          <button @click="closeModal" :disabled="saving" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Cancel</button>
          <button @click="save" :disabled="saving" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
            {{ saving ? 'Saving...' : (editing ? 'Save Changes' : 'Add Holiday') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete confirmation -->
    <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="deleteTarget = null"></div>
      <div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="mb-2 text-lg font-semibold text-gray-900">Delete holiday</h2>
        <p class="mb-5 text-sm text-gray-600">
          Remove <span class="font-medium">{{ deleteTarget.name }}</span> on {{ deleteTarget.date_label }}? It will stop showing on the dashboard.
        </p>
        <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
          <button @click="deleteTarget = null" :disabled="saving" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50">Cancel</button>
          <button @click="destroy" :disabled="saving" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">
            {{ saving ? 'Deleting...' : 'Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios';

export default {
  name: 'Holidays',

  data() {
    const currentYear = new Date().getFullYear();

    return {
      holidays: [],
      loading: false,
      saving: false,
      showModal: false,
      editing: null,
      deleteTarget: null,
      errors: {},
      formError: '',
      permissions: [],
      searchTimer: null,
      filters: {
        year: currentYear,
        calendar: '',
        search: '',
      },
      form: this.blankForm(),
    };
  },

  computed: {
    yearOptions() {
      const current = new Date().getFullYear();
      return [current - 1, current, current + 1, current + 2];
    },
    canCreate() { return this.permissions.includes('create holidays'); },
    canEdit()   { return this.permissions.includes('edit holidays'); },
    canDelete() { return this.permissions.includes('delete holidays'); },
  },

  mounted() {
    this.loadPermissions();
    this.loadHolidays();
  },

  beforeUnmount() {
    if (this.searchTimer) clearTimeout(this.searchTimer);
  },

  methods: {
    blankForm() {
      return { name: '', date: '', calendar: 'people', type: 'regular', description: '', is_active: true };
    },

    loadPermissions() {
      try {
        const raw = localStorage.getItem('user') || sessionStorage.getItem('user');
        this.permissions = raw ? (JSON.parse(raw).permissions || []) : [];
      } catch (e) {
        this.permissions = [];
      }
    },

    calendarClass(calendar) {
      return calendar === 'partners'
        ? 'bg-status-orange text-status-text'
        : 'bg-status-blue text-status-text';
    },

    debouncedSearch() {
      if (this.searchTimer) clearTimeout(this.searchTimer);
      this.searchTimer = setTimeout(this.loadHolidays, 300);
    },

    async loadHolidays() {
      try {
        this.loading = true;
        const { data } = await axios.get('/user/holidays', { params: this.filters });
        this.holidays = data.success ? data.data : [];
      } catch (error) {
        this.holidays = [];
      } finally {
        this.loading = false;
      }
    },

    openCreate() {
      this.editing = null;
      this.form = this.blankForm();
      this.errors = {};
      this.formError = '';
      this.showModal = true;
    },

    openEdit(holiday) {
      this.editing = holiday;
      this.form = {
        name: holiday.name,
        date: holiday.date,
        calendar: holiday.calendar,
        type: holiday.type,
        description: holiday.description || '',
        is_active: holiday.is_active,
      };
      this.errors = {};
      this.formError = '';
      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
      this.editing = null;
    },

    async save() {
      try {
        this.saving = true;
        this.errors = {};
        this.formError = '';

        const { data } = this.editing
          ? await axios.put(`/user/holidays/${this.editing.id}`, this.form)
          : await axios.post('/user/holidays', this.form);

        if (data.success) {
          this.closeModal();
          await this.loadHolidays();
        } else {
          this.formError = data.message || 'Something went wrong.';
        }
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {};
          this.formError = error.response.data.message || '';
        } else {
          this.formError = error.response?.data?.message || 'Failed to save holiday.';
        }
      } finally {
        this.saving = false;
      }
    },

    confirmDelete(holiday) {
      this.deleteTarget = holiday;
    },

    async destroy() {
      try {
        this.saving = true;
        await axios.delete(`/user/holidays/${this.deleteTarget.id}`);
        this.deleteTarget = null;
        await this.loadHolidays();
      } catch (error) {
        this.deleteTarget = null;
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
