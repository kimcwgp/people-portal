<template>
  <div class="p-4 bg-gray-50 h-screen overflow-auto">
    <!-- Header -->
    <div class="bg-white shadow-sm px-6 py-4 rounded-2xl mb-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">HR Announcements</h1>
        <button 
          v-if="canManageAnnouncements"
          @click="showCreateModal = true"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          <span>New Announcement</span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Announcements Grid -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
      <!-- Announcement Card -->
      <div 
        v-for="announcement in announcements" 
        :key="announcement.id"
        class="bg-white rounded-2xl shadow-sm overflow-hidden hover:shadow-md transition-shadow"
      >
        <!-- Image -->
        <div class="h-64 relative bg-gray-100">
          <img 
            v-if="announcement.url" 
            :src="announcement.url" 
            :alt="announcement.title"
            class="w-full h-full object-cover"
            @error="handleImageError"
          >
          <div 
            v-else 
            class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300"
          >
            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          
          <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
          
          <!-- Actions for admins/HR -->
          <div v-if="canManageAnnouncements" class="absolute top-4 right-4 flex space-x-2">
            <button 
              @click="editAnnouncement(announcement)"
              class="w-8 h-8 bg-white/20 hover:bg-white/30 text-white rounded-full flex items-center justify-center backdrop-blur-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </button>
            <button 
              @click="deleteAnnouncement(announcement.id)"
              class="w-8 h-8 bg-red-500/20 hover:bg-red-500/30 text-white rounded-full flex items-center justify-center backdrop-blur-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>

          <!-- Title Overlay -->
          <div class="absolute bottom-0 left-0 right-0 p-4">
            <h3 class="text-white font-semibold text-lg mb-1">{{ announcement.title }}</h3>
          </div>
        </div>

        <!-- Meta Information -->
        <div class="p-4">
          <div class="flex items-center text-sm text-gray-500 mb-3">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>{{ announcement.formatted_date }}</span>
          </div>

          <div class="text-sm text-gray-600">
            <span>By: {{ announcement.created_by }}</span>
          </div>

          <!-- View Full Button -->
          <button 
            @click="viewAnnouncement(announcement)"
            class="mt-3 w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-4 rounded-lg transition-colors text-sm font-medium"
          >
            View Full Image
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="announcements.length === 0" class="col-span-full">
        <div class="text-center py-12">
          <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <h3 class="text-lg font-medium text-gray-900 mb-2">No announcements yet</h3>
          <p class="text-gray-500 mb-4">
            {{ canManageAnnouncements ? 'Upload your first announcement image to get started.' : 'Check back later for company updates.' }}
          </p>
          <button 
            v-if="canManageAnnouncements"
            @click="showCreateModal = true"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
          >
            Upload Announcement
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-900">
            {{ editingAnnouncement ? 'Edit Announcement' : 'Upload New Announcement' }}
          </h2>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="saveAnnouncement" class="space-y-6">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Title (Optional)</label>
            <input 
              v-model="announcementForm.title"
              type="text" 
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter announcement title (optional)..."
            >
            <p class="text-xs text-gray-500 mt-1">Leave empty to use "Company Update" as default</p>
          </div>

          <!-- Image Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Image <span class="text-red-500">*</span>
            </label>
            <input 
              type="file" 
              @change="handleImageUpload"
              accept="image/*"
              :required="!editingAnnouncement"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <p class="text-xs text-gray-500 mt-1">Max file size: 5MB. Supported formats: JPG, PNG, GIF, WebP</p>
          </div>

          <!-- Image Preview -->
          <div v-if="imagePreview || (editingAnnouncement && editingAnnouncement.url)" class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Preview</label>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <img 
                :src="imagePreview || editingAnnouncement.url" 
                alt="Preview"
                class="w-full h-48 object-cover"
              >
            </div>
          </div>

          <!-- Actions -->
          <div class="flex space-x-3">
            <button
              type="button"
              @click="closeModal"
              class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 rounded-lg"
            >
              {{ saving ? 'Uploading...' : (editingAnnouncement ? 'Update' : 'Upload') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- View Announcement Modal -->
    <div v-if="viewingAnnouncement" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">
      <div class="max-w-4xl max-h-full w-full flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
          <div class="text-white">
            <h2 class="text-xl font-semibold">{{ viewingAnnouncement.title }}</h2>
            <div class="flex items-center mt-1">
              <span class="text-sm text-gray-300">{{ viewingAnnouncement.formatted_date }}</span>
            </div>
          </div>
          <button 
            @click="viewingAnnouncement = null" 
            class="text-white hover:text-gray-300 bg-black/20 hover:bg-black/40 rounded-lg p-2"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Image -->
        <div class="flex-1 flex items-center justify-center">
          <img 
            :src="viewingAnnouncement.url" 
            :alt="viewingAnnouncement.title"
            class="max-w-full max-h-full object-contain rounded-lg"
            @click.stop
          >
        </div>

        <!-- Footer -->
        <div class="text-center mt-4">
          <p class="text-sm text-gray-300">Posted by: {{ viewingAnnouncement.created_by }}</p>
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

    async deleteAnnouncement(id) {
      if (!confirm('Are you sure you want to delete this announcement? This action cannot be undone.')) return

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
.transition-all {
  transition: all 0.3s ease-in-out;
}

.hover\:scale-105:hover {
  transform: scale(1.05);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.fixed.inset-0 {
  animation: fadeIn 0.3s ease-in-out;
}

@media (max-width: 1024px) {
  .lg\:grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
  .xl\:grid-cols-3 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .h-64 {
    height: 12rem;
  }
}
</style>