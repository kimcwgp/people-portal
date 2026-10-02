<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6">

      <!-- Title bar, matching Time Entries -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-3 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="min-w-0 truncate text-xl font-bold text-text sm:text-2xl">
          Team's Time Entries<span v-if="view === 'grid' && member"> &mdash; {{ member.name }}</span>
        </h1>
        <div class="flex flex-shrink-0 items-center gap-2">
          <button
            v-if="view === 'grid'"
            @click="backToRoster"
            class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-text transition-colors hover:bg-accent-hover hover:text-white"
          >
            Back to Team
          </button>
          <button
            @click="exportData"
            :disabled="exporting"
            class="flex items-center gap-2 rounded-lg bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover disabled:opacity-50"
          >
            <svg v-if="!exporting" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="hidden sm:inline">{{ exporting ? 'Exporting...' : 'Export CSV' }}</span>
          </button>
        </div>
      </div>

      <!-- ============================ TEAM ROSTER ============================ -->
      <div v-if="view === 'roster'" class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
        <!-- Week stepper + search -->
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="flex shrink-0 gap-2">
            <button @click="stepWeek(-1)"
                    class="rounded-lg border border-border bg-surface px-4 py-2.5 text-sm font-medium text-text transition-colors hover:bg-surface-sunken">
              Previous
            </button>
            <button @click="stepWeek(1)"
                    class="rounded-lg border border-border bg-surface px-4 py-2.5 text-sm font-medium text-text transition-colors hover:bg-surface-sunken">
              Next
            </button>
          </div>

          <p class="shrink-0 text-sm font-semibold text-text">{{ weekLabel || '&nbsp;' }}</p>

          <div class="relative w-full shrink-0 sm:w-56">
            <label for="tte-name" class="absolute -top-2 left-2.5 z-10 bg-surface px-1 text-xs text-text-muted">Employee</label>
            <input id="tte-name" type="text" v-model="search" @keyup.enter="loadRoster" placeholder="Search by name"
                   class="w-full rounded-lg border border-border bg-transparent px-3 py-2.5 text-sm text-text placeholder:text-text-subtle focus:border-accent focus:outline-none">
          </div>

          <div class="flex shrink-0 gap-2">
            <button @click="loadRoster"
                    class="rounded-lg bg-accent-solid px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover">
              Filter
            </button>
            <button @click="clearFilters"
                    class="rounded-lg border border-border bg-surface-sunken px-5 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas">
              Clear
            </button>
          </div>
        </div>

        <!-- Week summary -->
        <div v-if="!loading && totals" class="flex flex-wrap gap-x-6 gap-y-1 border-b border-border px-4 py-2">
          <p class="text-xs text-text-muted">{{ totals.members }} team {{ totals.members === 1 ? 'member' : 'members' }}</p>
          <p class="text-xs text-text-muted">{{ totals.submitted }} submitted</p>
          <p class="text-xs text-text-muted">{{ formatHours(totals.hours) }} logged</p>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
          <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-accent"></div>
        </div>

        <div v-else-if="!rows.length" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No team members found</h3>
          <p class="mt-1 text-text-muted">No one reports to you yet, or the search matched nobody.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[700px]">
            <thead class="border-b border-border bg-surface-sunken">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Employee</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Total Hours</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Lines</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-text-muted">Approval Status</th>
                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-text-muted">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="row in rows" :key="row.user_id"
                  class="cursor-pointer transition-colors hover:bg-surface-sunken"
                  @click="openMember(row)">
                <td class="px-4 py-3">
                  <p class="whitespace-nowrap text-sm font-semibold text-text">{{ row.name }}</p>
                  <p class="whitespace-nowrap text-xs text-text-muted">{{ row.email }}</p>
                </td>
                <td class="px-4 py-3 text-sm font-semibold text-text">{{ formatHours(row.total_hours) }}</td>
                <td class="px-4 py-3 text-sm text-text-muted">{{ row.line_count }}</td>
                <td class="px-4 py-3">
                  <span :class="statusClass(row.status)" class="whitespace-nowrap rounded px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide">
                    {{ row.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <button @click.stop="openMember(row)"
                          class="rounded-lg bg-accent p-2 text-text transition-colors hover:bg-accent-hover hover:text-white"
                          :aria-label="`Open ${row.name}'s week`">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ============================ MEMBER WEEK GRID ============================ -->
      <div v-else class="overflow-hidden rounded-lg border border-border bg-surface shadow-sm">
        <div class="flex flex-wrap items-center gap-3 border-b border-border p-4">
          <div class="flex shrink-0 gap-2">
            <button @click="stepWeek(-1, true)"
                    class="rounded-lg border border-border bg-surface px-4 py-2.5 text-sm font-medium text-text transition-colors hover:bg-surface-sunken">
              Previous
            </button>
            <button @click="stepWeek(1, true)"
                    class="rounded-lg border border-border bg-surface px-4 py-2.5 text-sm font-medium text-text transition-colors hover:bg-surface-sunken">
              Next
            </button>
          </div>
          <p class="shrink-0 text-sm font-semibold text-text">{{ sheet?.week_label }}</p>
          <span v-if="sheet" :class="statusClass(sheet.status)" class="shrink-0 rounded px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide">
            {{ sheet.status }}
          </span>
          <p class="text-xs text-text-muted">Read only &mdash; approvals happen on the dashboard.</p>
        </div>

        <div v-if="sheet?.rejection_note" class="border-b border-border bg-status-red px-4 py-2">
          <p class="text-xs text-status-text"><strong>Sent back:</strong> {{ sheet.rejection_note }}</p>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
          <div class="h-10 w-10 animate-spin rounded-full border-b-2 border-accent"></div>
        </div>

        <div v-else-if="!sheet?.entries?.length" class="p-8 text-center">
          <p class="text-sm text-text-muted">{{ member?.name }} has not logged any time for this week.</p>
        </div>

        <div v-else class="overflow-x-auto">
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
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="entry in sheet.entries" :key="entry.id" class="hover:bg-surface-sunken">
                <td class="px-3 py-2 text-sm text-text">{{ entry.project_name || '—' }}</td>
                <td class="px-3 py-2 text-sm text-text-muted">{{ entry.project_ticket || '—' }}</td>
                <td class="px-3 py-2">
                  <span v-if="entry.time_type" :class="tintClass(entry.time_type.tint)"
                        class="inline-block whitespace-nowrap rounded px-2 py-0.5 text-xs font-medium">
                    {{ entry.time_type.name }}
                  </span>
                  <span v-else class="text-sm text-text-subtle">—</span>
                </td>
                <td class="px-3 py-2 text-sm text-text-muted">{{ entry.memo || '—' }}</td>
                <td v-for="day in sheet.days" :key="day.key"
                    :class="['px-2 py-2 text-center text-sm', day.is_weekend ? 'text-text-muted' : 'text-text']">
                  {{ entry[day.key + '_hours'] ? formatHours(entry[day.key + '_hours']) : '—' }}
                </td>
                <td class="px-3 py-2 text-center text-sm font-semibold text-text">{{ formatHours(entry.row_total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="toast" :class="['fixed bottom-4 right-4 z-50 rounded-lg px-4 py-2 text-sm text-white shadow-lg', toastError ? 'bg-danger' : 'bg-success']">
        {{ toast }}
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios';

// Literal classes so Tailwind's scanner compiles them.
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

const STATUS_CLASSES = {
  draft:         'bg-status-gray text-status-text',
  pending:       'bg-status-yellow text-status-text',
  approved:      'bg-status-green text-status-text',
  rejected:      'bg-status-red text-status-text',
  'not started': 'bg-surface-sunken text-text-muted',
};

/**
 * Calendar days must be read and written in the browser's own timezone.
 * toISOString() converts to UTC first, which east of Greenwich can report
 * yesterday -- and the API snaps a date to the Monday of its week, so a date
 * that slips to Sunday resolves to the wrong week.
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
  name: 'TeamTimeEntries',

  data() {
    return {
      view: 'roster',
      loading: false,
      weekDate: '',
      weekLabel: '',
      rows: [],
      totals: null,
      search: '',
      member: null,
      sheet: null,
      exporting: false,
      toast: '',
      toastError: false,
    };
  },

  async mounted() {
    document.title = "Team's Time Entries";
    this.weekDate = toLocalIso(new Date());
    await this.loadRoster();
  },

  methods: {
    /** Decimal hours as H:MM, matching the Time Entries grid. */
    formatHours(value) {
      const hours = Number(value || 0);
      const whole = Math.floor(hours);
      const minutes = Math.round((hours - whole) * 60);
      return `${whole}:${String(minutes).padStart(2, '0')}`;
    },

    tintClass(tint) {
      return TINT_CLASSES[tint] || TINT_CLASSES.gray;
    },

    statusClass(status) {
      return STATUS_CLASSES[status] || STATUS_CLASSES.draft;
    },

    notify(message, isError = false) {
      this.toast = message;
      this.toastError = isError;
      setTimeout(() => { this.toast = ''; }, 3000);
    },

    /** CSV of the selected week, matching the current employee filter. */
    async exportData() {
      this.exporting = true;
      try {
        const response = await axios.get('/user/my-team-time-entries/export', {
          params: { date: this.weekDate, user_name: this.search || undefined },
          responseType: 'blob',
        });

        const disposition = response.headers['content-disposition'] || '';
        const match = disposition.match(/filename="?([^"]+)"?/);
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'text/csv' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = match ? match[1] : 'team_time_entries.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        this.notify('Export completed successfully');
      } catch (e) {
        this.notify(e.response?.status === 403
          ? 'You do not have permission to export this data.'
          : 'Failed to export data. Please try again.', true);
      } finally {
        this.exporting = false;
      }
    },

    async loadRoster() {
      this.loading = true;
      try {
        const { data } = await axios.get('/user/my-team-time-entries', {
          params: { date: this.weekDate, user_name: this.search || undefined },
        });
        this.rows = data.data.rows;
        this.totals = data.data.totals;
        this.weekLabel = data.data.week_label;
        this.weekDate = data.data.week_start;
      } catch (e) {
        this.notify(e.response?.data?.message || 'Failed to load the team roster.', true);
      } finally {
        this.loading = false;
      }
    },

    async openMember(row) {
      this.member = { id: row.user_id, name: row.name, email: row.email };
      this.view = 'grid';
      await this.loadMemberWeek();
    },

    async loadMemberWeek() {
      if (!this.member) return;
      this.loading = true;
      try {
        const { data } = await axios.get(`/user/my-team-time-entries/${this.member.id}/week`, {
          params: { date: this.weekDate },
        });
        this.sheet = data.data.timesheet;
        this.member = data.data.member;
      } catch (e) {
        this.notify(e.response?.data?.message || 'Failed to load that week.', true);
        this.view = 'roster';
      } finally {
        this.loading = false;
      }
    },

    /** Move the week window; both views share one selected week. */
    async stepWeek(direction, inGrid = false) {
      const d = fromLocalIso(this.weekDate);
      d.setDate(d.getDate() + direction * 7);
      this.weekDate = toLocalIso(d);
      if (inGrid) {
        await this.loadMemberWeek();
      } else {
        await this.loadRoster();
      }
    },

    async backToRoster() {
      this.view = 'roster';
      this.member = null;
      this.sheet = null;
      await this.loadRoster();
    },

    async clearFilters() {
      this.search = '';
      this.weekDate = toLocalIso(new Date());
      await this.loadRoster();
    },
  },
};
</script>
