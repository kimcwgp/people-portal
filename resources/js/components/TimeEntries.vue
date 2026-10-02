<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6">

      <!-- Title bar: mirrors the reference, with the view toggle on the right -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-3 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="min-w-0 truncate text-xl font-bold text-text sm:text-2xl">
          Time Entries<span v-if="view === 'grid' && sheet"> &mdash; {{ sheet.week_label }}</span>
        </h1>
        <button
          @click="view === 'grid' ? backToWeekly() : openWeek(todayIso)"
          class="flex-shrink-0 rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-text transition-colors hover:bg-accent-hover hover:text-white"
        >
          {{ view === 'grid' ? 'View Weekly' : 'This Week' }}
        </button>
      </div>

      <!-- ============================ WEEKLY LIST ============================ -->
      <div v-if="view === 'weekly'" class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
        <div class="flex flex-col gap-4 border-b border-border p-4 sm:flex-row sm:items-center">
          <div class="relative">
            <label for="te-from" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Date</label>
            <div class="flex items-center gap-1.5 rounded-lg border border-border px-3 py-2.5 focus-within:border-accent">
              <input id="te-from" type="date" v-model="range.startDate" class="bg-transparent text-sm text-text focus:outline-none">
              <span class="text-text-subtle">&ndash;</span>
              <input type="date" v-model="range.endDate" class="bg-transparent text-sm text-text focus:outline-none">
            </div>
          </div>
          <button @click="loadWeeks" class="rounded-lg bg-accent-solid px-5 py-2.5 text-sm font-medium text-white hover:bg-accent-hover">Filter</button>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
          <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-accent"></div>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b border-border bg-surface-sunken">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Start Date</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Total Hours</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Approval Status</th>
                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-text-muted">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="week in weeks" :key="week.week_start"
                  class="cursor-pointer transition-colors hover:bg-surface-sunken"
                  @click="openWeek(week.week_start)">
                <td class="px-4 py-3">
                  <p class="text-sm font-semibold text-text">{{ formatDate(week.week_start) }}</p>
                  <p class="text-xs text-text-muted">{{ week.week_label }}</p>
                </td>
                <td class="px-4 py-3 text-sm font-semibold text-text">{{ formatHours(week.total_hours) }}</td>
                <td class="px-4 py-3">
                  <span :class="statusClass(week.status)" class="rounded px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide">
                    {{ week.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <button @click.stop="openWeek(week.week_start)"
                          class="rounded-lg bg-accent p-2 text-text transition-colors hover:bg-accent-hover hover:text-white"
                          :aria-label="`Open ${week.week_label}`">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ============================ WEEK GRID ============================ -->
      <div v-else class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
        <div v-if="loading" class="flex justify-center py-12">
          <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-accent"></div>
        </div>

        <template v-else-if="sheet">
          <!-- Week toolbar: status, locked notice, submit -->
          <div class="flex flex-col gap-3 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-2">
              <button @click="stepWeek(-1)" class="rounded-lg border border-border px-3 py-1.5 text-sm text-text hover:bg-surface-sunken">Previous</button>
              <button @click="stepWeek(1)" class="rounded-lg border border-border px-3 py-1.5 text-sm text-text hover:bg-surface-sunken">Next</button>
              <!-- Which week you are on, next to the control that changes it. -->
              <span class="px-1 text-sm font-semibold text-text">{{ sheet.week_label }}</span>
              <span :class="statusClass(sheet.status)" class="rounded px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide">{{ sheet.status }}</span>
              <span v-if="!sheet.is_editable" class="text-xs text-text-muted">Locked &mdash; submitted for approval</span>
            </div>
            <div class="flex gap-2">
              <button v-if="sheet.is_editable" @click="addLine"
                      class="rounded-lg border border-border bg-surface-sunken px-4 py-2 text-sm font-medium text-text hover:bg-canvas">
                Add Line
              </button>
              <button v-if="sheet.is_editable" @click="submitWeek" :disabled="saving || !sheet.entries.length"
                      class="rounded-lg bg-accent-solid px-4 py-2 text-sm font-medium text-white hover:bg-accent-hover disabled:opacity-50">
                Submit Week
              </button>
            </div>
          </div>

          <div v-if="sheet.rejection_note" class="border-b border-border bg-status-red px-4 py-2">
            <p class="text-xs text-status-text"><strong>Sent back:</strong> {{ sheet.rejection_note }}</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full min-w-[900px]">
              <thead class="border-b border-border bg-surface-sunken">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Project/Task</th>
                  <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Project Ticket</th>
                  <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Time Type</th>
                  <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Memo</th>
                  <th v-for="day in sheet.days" :key="day.key"
                      :class="['px-2 py-2 text-center text-xs font-semibold uppercase tracking-wider', day.is_weekend ? 'text-text-subtle' : 'text-text-muted']">
                    <span class="block">{{ day.label }}</span>
                    <span class="block text-[11px] font-normal">{{ day.day_of_month }}</span>
                    <span class="block text-[11px] font-semibold text-text">{{ formatHours(day.total) }}</span>
                  </th>
                  <th class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider text-text-muted">
                    <span class="block">Total</span>
                    <span class="block text-[11px] font-semibold text-text">{{ formatHours(sheet.total_hours) }}</span>
                  </th>
                  <th v-if="sheet.is_editable" class="px-2 py-2"></th>
                </tr>
              </thead>

              <tbody class="divide-y divide-border">
                <tr v-for="entry in sheet.entries" :key="entry.id" class="hover:bg-surface-sunken">
                  <td class="px-3 py-2">
                    <select v-model="entry.project_id" :disabled="!sheet.is_editable" @change="saveLine(entry)"
                            class="w-40 rounded border border-border bg-transparent px-2 py-1 text-sm text-text focus:border-accent focus:outline-none disabled:opacity-60">
                      <option :value="null">&mdash;</option>
                      <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.project_name }}</option>
                    </select>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model="entry.project_ticket" :disabled="!sheet.is_editable" @change="saveLine(entry)"
                           class="w-28 rounded border border-border bg-transparent px-2 py-1 text-sm text-text focus:border-accent focus:outline-none disabled:opacity-60">
                  </td>
                  <td class="px-3 py-2">
                    <select v-model="entry.time_type_id" :disabled="!sheet.is_editable" @change="saveLine(entry)"
                            :class="['w-36 rounded border-0 px-2 py-1 text-sm focus:outline-none disabled:opacity-60', tintClass(entry)]">
                      <option v-for="t in timeTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model="entry.memo" :disabled="!sheet.is_editable" @change="saveLine(entry)"
                           class="w-48 rounded border border-border bg-transparent px-2 py-1 text-sm text-text focus:border-accent focus:outline-none disabled:opacity-60">
                  </td>
                  <td v-for="day in sheet.days" :key="day.key" class="px-1 py-2">
                    <input type="number" step="0.25" min="0" max="24"
                           v-model.number="entry[day.key + '_hours']" :disabled="!sheet.is_editable"
                           @change="saveLine(entry)"
                           :class="['w-16 rounded border px-1 py-1 text-center text-sm focus:border-accent focus:outline-none disabled:opacity-60',
                                    day.is_weekend ? 'border-border bg-surface-sunken text-text-muted' : 'border-border bg-transparent text-text']">
                  </td>
                  <td class="px-3 py-2 text-center text-sm font-semibold text-text">{{ formatHours(rowTotal(entry)) }}</td>
                  <td v-if="sheet.is_editable" class="px-2 py-2 text-center">
                    <button @click="removeLine(entry)" class="p-1.5 text-text-subtle hover:text-danger" aria-label="Remove line">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </td>
                </tr>

                <tr v-if="!sheet.entries.length">
                  <td :colspan="sheet.is_editable ? 13 : 12" class="px-4 py-10 text-center">
                    <p class="text-sm text-text-muted">No lines for this week yet.</p>
                    <button v-if="sheet.is_editable" @click="addLine" class="mt-2 text-sm font-medium text-accent-solid hover:text-accent-hover">
                      Add the first line
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </div>

      <p v-if="toast" class="mt-3 text-sm" :class="toastError ? 'text-danger' : 'text-success'">{{ toast }}</p>
    </div>
  </div>
</template>

<script>
import axios from '@/axios';

const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

// Written out in full because Tailwind scans source text -- a computed
// `bg-status-${tint}` would never be generated.
const TINT_CLASSES = {
  green:  'bg-status-green text-status-text',
  orange: 'bg-status-orange text-status-text',
  purple: 'bg-status-purple text-status-text',
  blue:   'bg-status-blue text-status-text',
  yellow: 'bg-status-yellow text-status-text',
  indigo: 'bg-status-indigo text-status-text',
  cyan:   'bg-status-cyan text-status-text',
  red:    'bg-status-red text-status-text',
  gray:   'bg-status-gray text-status-text',
};

/**
 * Dates here are plain calendar days, so they must be read and written in the
 * browser's own timezone. toISOString() converts to UTC first, which east of
 * Greenwich rolls local midnight back to the previous day -- and since the API
 * snaps any date to the Monday of its week, a date that slips to Sunday
 * resolves to the WRONG week.
 */
const toLocalIso = (d) => {
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

/** Parse a YYYY-MM-DD as local midnight; `new Date(str)` would read it as UTC. */
const fromLocalIso = (iso) => {
  const [y, m, d] = iso.split('-').map(Number);
  return new Date(y, m - 1, d);
};

export default {
  name: 'TimeEntries',

  data() {
    return {
      view: 'weekly',
      weeks: [],
      sheet: null,
      projects: [],
      timeTypes: [],
      loading: false,
      saving: false,
      toast: '',
      toastError: false,
      range: { startDate: '', endDate: '' },
    };
  },

  computed: {
    todayIso() {
      return toLocalIso(new Date());
    },
  },

  mounted() {
    this.loadLookups();
    this.loadWeeks();
  },

  methods: {
    formatDate(iso) {
      if (!iso) return '';
      const [y, m, d] = iso.split('-');
      return `${Number(m)}/${Number(d)}/${y}`;
    },

    // NetSuite shows 40:00 rather than 40.0
    formatHours(value) {
      const hours = Number(value || 0);
      const whole = Math.floor(hours);
      const minutes = Math.round((hours - whole) * 60);
      return `${whole}:${String(minutes).padStart(2, '0')}`;
    },

    rowTotal(entry) {
      return DAYS.reduce((sum, day) => sum + Number(entry[`${day}_hours`] || 0), 0);
    },

    statusClass(status) {
      return {
        draft: 'bg-status-gray text-status-text',
        pending: 'bg-status-yellow text-status-text',
        approved: 'bg-status-green text-status-text',
        rejected: 'bg-status-red text-status-text',
      }[status] || 'bg-status-gray text-status-text';
    },

    tintClass(entry) {
      const tint = this.timeTypes.find(t => t.id === entry.time_type_id)?.tint;
      return TINT_CLASSES[tint] || TINT_CLASSES.gray;
    },

    notify(message, isError = false) {
      this.toast = message;
      this.toastError = isError;
      setTimeout(() => { this.toast = ''; }, 3000);
    },

    async loadLookups() {
      try {
        const [types, projects] = await Promise.all([
          axios.get('/user/time-entries/time-types'),
          axios.get('/user/time-entries/projects'),
        ]);
        this.timeTypes = types.data.data || [];
        this.projects = projects.data.data || [];
      } catch (e) {
        this.timeTypes = [];
        this.projects = [];
      }
    },

    async loadWeeks() {
      try {
        this.loading = true;
        const { data } = await axios.get('/user/time-entries', { params: this.range });
        this.weeks = data.data || [];
      } catch (e) {
        this.weeks = [];
      } finally {
        this.loading = false;
      }
    },

    async openWeek(date) {
      try {
        this.loading = true;
        this.view = 'grid';
        const { data } = await axios.get('/user/time-entries/week', { params: { date } });
        this.sheet = data.data;
      } catch (e) {
        this.notify('Could not open that week.', true);
        this.view = 'weekly';
      } finally {
        this.loading = false;
      }
    },

    stepWeek(direction) {
      const start = fromLocalIso(this.sheet.week_start);
      start.setDate(start.getDate() + direction * 7);
      this.openWeek(toLocalIso(start));
    },

    backToWeekly() {
      this.view = 'weekly';
      this.loadWeeks();
    },

    payload(entry) {
      const body = {
        project_id: entry.project_id,
        project_ticket: entry.project_ticket,
        time_type_id: entry.time_type_id,
        memo: entry.memo,
      };
      DAYS.forEach(day => { body[`${day}_hours`] = Number(entry[`${day}_hours`] || 0); });
      return body;
    },

    async addLine() {
      try {
        this.saving = true;
        const { data } = await axios.post(`/user/time-entries/${this.sheet.id}/entries`, {
          time_type_id: this.timeTypes[0]?.id,
        });
        this.sheet.entries.push(data.data);
        this.recalcDays();
      } catch (e) {
        this.notify(e.response?.data?.message || 'Could not add a line.', true);
      } finally {
        this.saving = false;
      }
    },

    async saveLine(entry) {
      try {
        this.saving = true;
        const { data } = await axios.put(
          `/user/time-entries/${this.sheet.id}/entries/${entry.id}`,
          this.payload(entry)
        );
        Object.assign(entry, data.data);
        this.recalcDays();
      } catch (e) {
        this.notify(e.response?.data?.message || 'Could not save that line.', true);
      } finally {
        this.saving = false;
      }
    },

    async removeLine(entry) {
      try {
        await axios.delete(`/user/time-entries/${this.sheet.id}/entries/${entry.id}`);
        this.sheet.entries = this.sheet.entries.filter(e => e.id !== entry.id);
        this.recalcDays();
      } catch (e) {
        this.notify(e.response?.data?.message || 'Could not remove that line.', true);
      }
    },

    // Keep the column and grand totals live without another round trip.
    recalcDays() {
      this.sheet.days = this.sheet.days.map(day => ({
        ...day,
        total: this.sheet.entries.reduce((sum, e) => sum + Number(e[`${day.key}_hours`] || 0), 0),
      }));
      this.sheet.total_hours = this.sheet.entries.reduce((sum, e) => sum + this.rowTotal(e), 0);
    },

    async submitWeek() {
      try {
        this.saving = true;
        const { data } = await axios.post(`/user/time-entries/${this.sheet.id}/submit`);
        this.sheet = data.data;
        this.notify('Timesheet submitted for approval.');
      } catch (e) {
        this.notify(e.response?.data?.message || 'Could not submit the week.', true);
      } finally {
        this.saving = false;
      }
    },
  },
};
</script>
