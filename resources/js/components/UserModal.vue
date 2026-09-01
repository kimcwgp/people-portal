<!-- UserModal.vue -->
<template>
  <div class="fixed inset-0 flex items-center justify-center z-50">
    <div class="absolute inset-0 bg-black/50" @click="$emit('close')"></div>
    <div class="relative bg-white rounded-2xl p-6 max-w-lg w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-semibold text-gray-900">
          {{ viewOnly ? 'User Details' : (isEditing ? 'Edit User' : 'Add New User') }}
        </h3>
        <button 
          @click="$emit('close')"
          class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- User Avatar and Name (View Mode Only) -->
      <div v-if="viewOnly" class="text-center border-b border-gray-100 pb-6 mb-6">
        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center mx-auto mb-3">
          <span class="text-white font-semibold text-lg">{{ getInitials(form.name) }}</span>
        </div>
        <h4 class="text-lg font-semibold text-gray-900">{{ form.name }}</h4>
        <p class="text-sm text-gray-600">{{ form.email }}</p>
      </div>

      <form @submit.prevent="!viewOnly && saveUser" class="space-y-4">
        <!-- Name -->
        <div v-if="!viewOnly">
          <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Enter full name"
          >
          <p v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name[0] }}</p>
        </div>

        <!-- Email -->
        <div v-if="!viewOnly">
          <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Enter email address"
          >
          <p v-if="errors.email" class="text-red-500 text-sm mt-1">{{ errors.email[0] }}</p>
        </div>

        <!-- Team -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Team</label>
          <div v-if="viewOnly" class="text-sm text-gray-900">
            <span v-if="form.team_name" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
              {{ form.team_name }}
            </span>
            <span v-else class="text-gray-400">No team assigned</span>
          </div>
          <select
            v-else
            v-model="form.team_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Select Team</option>
            <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
          </select>
          <p v-if="errors.team_id && !viewOnly" class="text-red-500 text-sm mt-1">{{ errors.team_id[0] }}</p>
        </div>

        <!-- Roles -->
        <div v-if="availableRoles && availableRoles.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-2">Roles</label>
          <div v-if="viewOnly" class="text-sm text-gray-900">
            <div v-if="form.roles && form.roles.length > 0" class="flex flex-wrap gap-1">
              <span v-for="role in form.roles" :key="role" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                {{ role }}
              </span>
            </div>
            <span v-else class="text-gray-400">No roles assigned</span>
          </div>
          <div v-else class="space-y-2 max-h-32 overflow-y-auto">
            <label v-for="role in availableRoles" :key="role.id" class="flex items-center">
              <input
                type="checkbox"
                :value="role.name"
                v-model="form.roles"
                class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 border-gray-300"
              >
              <span class="ml-2 text-sm text-gray-700">{{ role.name }}</span>
            </label>
          </div>
          <p v-if="errors.roles && !viewOnly" class="text-red-500 text-sm mt-1">{{ errors.roles[0] }}</p>
        </div>

        <!-- Status -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <div v-if="viewOnly" class="text-sm">
            <span :class="[
              'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full',
              form.status 
                ? 'bg-emerald-100 text-emerald-700' 
                : 'bg-amber-100 text-amber-700'
            ]">
              <span :class="[
                'w-1.5 h-1.5 rounded-full mr-1.5',
                form.status ? 'bg-emerald-400' : 'bg-amber-400'
              ]"></span>
              {{ form.status ? 'Active' : 'Inactive' }}
            </span>
          </div>
          <select
            v-else
            v-model="form.status"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </select>
          <p v-if="errors.status && !viewOnly" class="text-red-500 text-sm mt-1">{{ errors.status[0] }}</p>
        </div>

        <!-- Glip URL -->
        <div v-if="form.glip_url || !viewOnly">
          <label class="block text-sm font-medium text-gray-700 mb-1">Glip URL</label>
          <div v-if="viewOnly && form.glip_url" class="text-sm text-gray-900">
            <a :href="form.glip_url" target="_blank" class="text-blue-600 hover:text-blue-800 break-all">
              {{ form.glip_url }}
            </a>
          </div>
          <input
            v-else-if="!viewOnly"
            v-model="form.glip_url"
            type="url"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Enter glip URL"
          >
          <p v-if="errors.glip_url && !viewOnly" class="text-red-500 text-sm mt-1">{{ errors.glip_url[0] }}</p>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-4">
          <button
            v-if="viewOnly"
            type="button"
            @click="$emit('close')"
            class="w-full px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors"
          >
            Close
          </button>
          <template v-else>
            <button
              type="button"
              @click="$emit('close')"
              class="flex-1 px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="flex-1 px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
              </span>
              <span v-else>
                {{ isEditing ? 'Update User' : 'Create User' }}
              </span>
            </button>
          </template>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch } from 'vue'
import axios from 'axios'

export default {
  name: 'UserModal',
  props: {
    user: {
      type: Object,
      default: null
    },
    teams: {
      type: Array,
      default: () => []
    },
    availableRoles: {
      type: Array,
      default: () => []
    },
    viewOnly: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const loading = ref(false)
    const errors = ref({})

    const form = reactive({
      name: '',
      email: '',
      password: '',
      glip_url: '',
      team_id: '',
      team_name: '', // For display in view mode
      roles: [],
      status: true
    })

    const isEditing = computed(() => !!props.user)

    // Define resetForm FIRST
    const resetForm = () => {
      form.name = ''
      form.email = ''
      form.password = ''
      form.glip_url = ''
      form.team_id = ''
      form.team_name = ''
      form.roles = []
      form.status = true
    }

    // THEN the watch function
    watch(() => props.user, (user) => {
      if (user) {
        form.name = user.name || ''
        form.email = user.email || ''
        form.password = '' // Always empty for security
        form.glip_url = user.glip_url || ''
        form.team_id = user.team_id || ''
        form.team_name = user.team?.name || '' // For view mode display
        form.roles = user.roles?.map(role => role.name) || []
        form.status = user.status ?? true
      } else {
        resetForm()
      }
    }, { immediate: true })

    const saveUser = async () => {
      try {
        loading.value = true
        errors.value = {}

        // Prepare payload - exclude empty password for updates
        const payload = { ...form }
        if (isEditing.value && !payload.password) {
          delete payload.password
        }

        let response
        if (isEditing.value) {
          response = await axios.put(`/user/${props.user.id}`, payload)
        } else {
          response = await axios.post('/user', payload)
        }

        // NEW: If updating current user, update localStorage
        const currentUser = JSON.parse(localStorage.getItem('user') || '{}')
        if (currentUser.id === response.data.user.id) {
          localStorage.setItem('user', JSON.stringify(response.data.user))
          // Trigger layout to refresh
          window.dispatchEvent(new Event('user-updated'))
        }

        emit('saved', response.data.user)
      } catch (error) {
        if (error.response?.status === 422) {
          errors.value = error.response.data.errors || {}
        } else {
          console.error('Error saving user:', error)
          // You might want to show a toast notification here
        }
      } finally {
        loading.value = false
      }
    }

    const getInitials = (name) => {
      if (!name) return 'U'
      return name
        .split(' ')
        .map(n => n.charAt(0))
        .join('')
        .substring(0, 2)
        .toUpperCase()
    }

    return {
      loading,
      errors,
      form,
      isEditing,
      saveUser,
      resetForm,
      getInitials,
    }
  }
}
</script>