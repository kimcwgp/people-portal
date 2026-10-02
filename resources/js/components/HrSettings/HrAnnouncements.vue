<template>
  <div class="min-h-screen bg-canvas p-4">
    <div class="mx-auto max-w-full px-2 sm:px-4 lg:px-6 xl:px-8">
      <!-- Title bar, matching Attendance -->
      <div class="mb-3 sm:mb-4 flex items-center justify-between gap-4 rounded-xl bg-surface px-4 py-3 shadow-sm sm:rounded-2xl sm:px-6">
        <h1 class="text-xl font-bold text-text sm:text-2xl">HR Announcements</h1>
        <button
          v-if="canManageAnnouncements"
          @click="showCreateModal = true"
          class="flex flex-shrink-0 items-center gap-2 rounded-lg bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          <!-- The full label plus the title overflows a 375px screen. -->
          <span class="sm:hidden">New</span>
          <span class="hidden sm:inline">New Announcement</span>
        </button>
      </div>

      <!-- Announcements are images, so they stay a gallery rather than the
           table the record screens use. The card chrome is the shared one. -->
      <div class="bg-surface rounded-lg shadow-sm border border-border overflow-hidden">
        <div v-if="!loading && announcements.length > 0" class="border-b border-border px-4 py-2">
          <p class="text-xs text-text-muted">
            Showing {{ announcements.length }} announcement{{ announcements.length === 1 ? '' : 's' }}
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-accent"></div>
        </div>

        <!-- Empty -->
        <div v-else-if="announcements.length === 0" class="p-8 text-center">
          <svg class="mx-auto h-12 w-12 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <h3 class="mt-3 text-lg font-semibold text-text">No announcements yet</h3>
          <p class="mt-1 text-text-muted">
            {{ canManageAnnouncements ? 'Upload your first announcement image to get started.' : 'Check back later for company updates.' }}
          </p>
          <button
            v-if="canManageAnnouncements"
            @click="showCreateModal = true"
            class="mt-4 rounded-lg bg-accent-solid px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover"
          >
            Upload Announcement
          </button>
        </div>

        <!-- Gallery -->
        <div v-else class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="announcement in announcements"
            :key="announcement.id"
            class="overflow-hidden rounded-lg border border-border bg-surface transition-shadow hover:shadow-md"
          >
            <!-- Image -->
            <div class="relative h-48 bg-surface-sunken sm:h-56">
              <img
                v-if="announcement.url"
                :src="announcement.url"
                :alt="announcement.title"
                class="h-full w-full object-cover"
                @error="handleImageError"
              >
              <div v-else class="flex h-full w-full items-center justify-center">
                <svg class="h-14 w-14 text-text-subtle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>

              <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-text/60 to-transparent"></div>

              <!-- Actions for admins/HR -->
              <div v-if="canManageAnnouncements" class="absolute right-3 top-3 flex gap-2">
                <button
                  @click="editAnnouncement(announcement)"
                  class="flex h-8 w-8 items-center justify-center rounded-full bg-surface/80 text-text backdrop-blur-sm transition-colors hover:bg-surface"
                  title="Edit"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <button
                  @click="confirmDelete(announcement)"
                  class="flex h-8 w-8 items-center justify-center rounded-full bg-surface/80 text-danger backdrop-blur-sm transition-colors hover:bg-surface"
                  title="Delete"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>

              <!-- Title Overlay -->
              <div class="pointer-events-none absolute inset-x-0 bottom-0 p-4">
                <h3 class="text-lg font-semibold text-white">{{ announcement.title }}</h3>
              </div>
            </div>

            <!-- Meta Information -->
            <div class="p-4">
              <div class="mb-2 flex items-center gap-2 text-sm text-text-muted">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>{{ announcement.formatted_date }}</span>
              </div>

              <p class="text-sm text-text-muted">By: {{ announcement.created_by }}</p>

              <button
                @click="viewAnnouncement(announcement)"
                class="mt-3 w-full rounded-lg border border-border bg-surface-sunken px-4 py-2 text-sm font-medium text-text transition-colors hover:bg-canvas"
              >
                View Full Image
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
        <div class="relative bg-surface rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden">
          <div class="p-6 border-b border-border">
            <div class="flex items-center justify-between">
              <h3 class="text-xl font-semibold text-text">
                {{ editingAnnouncement ? 'Edit Announcement' : 'Upload New Announcement' }}
              </h3>
              <button @click="closeModal" class="p-2 rounded-lg hover:bg-surface-sunken">
                <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <form id="announcement-form" @submit.prevent="saveAnnouncement" class="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-220px)]">
            <div>
              <label class="block text-sm font-medium text-text mb-2">Title</label>
              <input
                v-model="announcementForm.title"
                type="text"
                class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
                placeholder="Enter announcement title (optional)..."
              >
              <p class="mt-1 text-xs text-text-muted">Leave empty to use "Company Update" as the title.</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-text mb-2">Image *</label>
              <input
                type="file"
                @change="handleImageUpload"
                accept="image/*"
                :required="!editingAnnouncement"
                class="w-full px-4 py-3 border border-border rounded-xl focus:ring-2 focus:ring-accent focus:border-transparent transition-all duration-200"
              >
              <p class="mt-1 text-xs text-text-muted">Max file size: 5MB. Supported formats: JPG, PNG, GIF, WebP.</p>
            </div>

            <div v-if="imagePreview || (editingAnnouncement && editingAnnouncement.url)">
              <label class="block text-sm font-medium text-text mb-2">Preview</label>
              <div class="overflow-hidden rounded-xl border border-border">
                <img :src="imagePreview || editingAnnouncement.url" alt="Preview" class="h-48 w-full object-cover">
              </div>
            </div>
          </form>

          <div class="flex gap-3 border-t border-border p-6">
            <button
              type="button"
              @click="closeModal"
              class="flex-1 rounded-xl border border-border bg-surface-sunken px-4 py-2.5 text-sm font-medium text-text transition-colors hover:bg-canvas"
            >
              Cancel
            </button>
            <button
              type="submit"
              form="announcement-form"
              :disabled="saving"
              class="flex-1 rounded-xl bg-accent-solid px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-hover disabled:opacity-50"
            >
              {{ saving ? 'Uploading...' : (editingAnnouncement ? 'Update' : 'Upload') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Delete confirmation, replacing the browser's own confirm() dialog -->
      <div v-if="pendingDelete" class="fixed inset-0 flex items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/50" @click="pendingDelete = null"></div>
        <div class="relative bg-surface rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-status-red rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-text mb-2">Delete Announcement</h3>
            <p class="text-text-muted mb-6">
              Delete &ldquo;{{ pendingDelete.title }}&rdquo;? This cannot be undone.
            </p>
            <div class="flex gap-3">
              <button @click="pendingDelete = null" class="flex-1 px-4 py-2 text-text bg-surface-sunken border border-border hover:bg-canvas rounded-xl">Cancel</button>
              <button @click="deleteAnnouncement" class="flex-1 px-4 py-2 text-white bg-danger hover:bg-danger/90 rounded-xl">Delete</button>
            </div>
          </div>
        </div>
      </div>

      <!-- View Announcement Modal -->
      <div v-if="viewingAnnouncement" class="fixed inset-0 flex items-center justify-center z-50 p-4">
        <div class="absolute inset-0 bg-black/75" @click="viewingAnnouncement = null"></div>
        <div class="relative flex max-h-full w-full max-w-4xl flex-col">
          <div class="mb-4 flex items-start justify-between gap-4">
            <div class="min-w-0 text-white">
              <h3 class="truncate text-xl font-semibold">{{ viewingAnnouncement.title }}</h3>
              <p class="mt-1 text-sm text-white/70">{{ viewingAnnouncement.formatted_date }}</p>
            </div>
            <button @click="viewingAnnouncement = null" class="flex-shrink-0 rounded-lg bg-white/10 p-2 text-white transition-colors hover:bg-white/20">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <div class="flex min-h-0 flex-1 items-center justify-center">
            <img
              :src="viewingAnnouncement.url"
              :alt="viewingAnnouncement.title"
              class="max-h-full max-w-full rounded-xl object-contain"
              @click.stop
            >
          </div>

          <p class="mt-4 text-center text-sm text-white/70">Posted by: {{ viewingAnnouncement.created_by }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/axios'

export default {
  name: 'HrAnnouncements',
  data() {
    return {
      announcements: [],
      loading: false,
      saving: false,
      showCreateModal: false,
      editingAnnouncement: null,
      viewingAnnouncement: null,
      pendingDelete: null,
      imagePreview: null,
      announcementForm: {
        title: '',
        image: null
      },
      canManageAnnouncements: false
    }
  },

  async mounted() {
    document.title = 'HR Announcements'
    await this.loadAnnouncements()
  },

  methods: {
    async loadAnnouncements() {
      try {
        this.loading = true
        const { data } = await axios.get('/hr/announcements')

        this.announcements = data.data || []
        this.canManageAnnouncements = data.meta?.can_manage || false
      } catch (error) {
        console.error('Error loading announcements:', error)
        this.showToast('Failed to load announcements', 'error')
      } finally {
        this.loading = false
      }
    },

    async saveAnnouncement() {
      try {
        this.saving = true
        const formData = new FormData()

        if (this.announcementForm.title) {
          formData.append('title', this.announcementForm.title)
        }

        if (this.announcementForm.image) {
          formData.append('image', this.announcementForm.image)
        } else if (!this.editingAnnouncement) {
          this.showToast('Please select an image', 'error')
          return
        }

        if (this.editingAnnouncement) {
          await axios.post(`/hr/announcements/${this.editingAnnouncement.id}`, formData, {
            headers: {
              'Content-Type': 'multipart/form-data',
              'X-HTTP-Method-Override': 'PUT'
            }
          })
          this.showToast('Announcement updated successfully', 'success')
        } else {
          await axios.post('/hr/announcements', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          })
          this.showToast('Announcement uploaded successfully', 'success')
        }

        this.closeModal()
        await this.loadAnnouncements()
      } catch (error) {
        console.error('Error saving announcement:', error)
        const message = error.response?.data?.message || 'Failed to save announcement'
        this.showToast(message, 'error')
      } finally {
        this.saving = false
      }
    },

    confirmDelete(announcement) {
      this.pendingDelete = announcement
    },

    async deleteAnnouncement() {
      const id = this.pendingDelete?.id
      if (!id) return
      this.pendingDelete = null

      try {
        await axios.delete(`/hr/announcements/${id}`)
        this.announcements = this.announcements.filter(a => a.id !== id)
        this.showToast('Announcement deleted successfully', 'success')
      } catch (error) {
        console.error('Error deleting announcement:', error)
        this.showToast('Failed to delete announcement', 'error')
      }
    },

    editAnnouncement(announcement) {
      this.editingAnnouncement = announcement
      this.announcementForm = {
        title: announcement.title === 'Company Update' ? '' : announcement.title,
        image: null
      }
      this.imagePreview = null
      this.showCreateModal = true
    },

    viewAnnouncement(announcement) {
      this.viewingAnnouncement = announcement
    },

    closeModal() {
      this.showCreateModal = false
      this.editingAnnouncement = null
      this.imagePreview = null
      this.announcementForm = {
        title: '',
        image: null
      }
    },

    handleImageUpload(event) {
      const file = event.target.files[0]
      if (file) {
        // Check file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
          this.showToast('File size must be less than 5MB', 'error')
          event.target.value = ''
          return
        }
        this.announcementForm.image = file

        // Create preview
        const reader = new FileReader()
        reader.onload = e => {
          this.imagePreview = e.target.result
        }
        reader.readAsDataURL(file)
      }
    },

    handleImageError(event) {
      event.target.style.display = 'none'
    },

    showToast(message, type = 'info') {
      const toast = document.createElement('div')
      toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 transition-all duration-300 ${
        type === 'success' ? 'bg-success' :
        type === 'error'   ? 'bg-danger'  :
        type === 'warning' ? 'bg-warning' : 'bg-accent-solid'
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
