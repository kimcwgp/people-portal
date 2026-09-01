<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center px-4">
    <div class="max-w-md w-full">
      <!-- Error Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
        <!-- Error Icon -->
        <div class="mb-6">
          <div class="mx-auto w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
        </div>

        <!-- Error Code -->
        <h1 class="text-6xl font-bold text-gray-800 mb-2">403</h1>

        <!-- Error Title -->
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Access Denied</h2>

        <!-- Error Message -->
        <p class="text-gray-600 mb-2">
          You don't have permission to access this page.
        </p>

        <!-- Attempted Path (if available) -->
        <p v-if="attemptedPath" class="text-sm text-gray-500 mb-4">
          Attempted to access: <span class="font-mono text-gray-700">{{ attemptedPath }}</span>
        </p>

        <!-- Required Permission (if available) -->
        <div v-if="requiredPermission" class="mb-6 p-4 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-500 mb-1">Required Permission:</p>
          <p class="text-sm font-mono font-semibold text-gray-700">{{ requiredPermission }}</p>
        </div>

        <p v-else class="text-gray-500 mb-6 text-sm">
          This page requires special permissions that your account doesn't have.
        </p>

        <!-- Action Buttons -->
        <div class="space-y-3">
          <button
            @click="goToDashboard"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
          >
            Go to Dashboard
          </button>

          <button
            @click="goBack"
            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-lg transition-colors duration-200"
          >
            Go Back
          </button>
        </div>

        <!-- Help Text -->
        <div class="mt-6 pt-6 border-t border-gray-200">
          <p class="text-sm text-gray-500">
            Need access to this page?
          </p>
          <p class="text-sm text-gray-600 font-medium mt-1">
            Contact your administrator to request the necessary permissions.
          </p>
        </div>
      </div>

      <!-- Additional Info -->
      <div class="mt-6 text-center">
        <p class="text-sm text-gray-500">
          Your current role: <span class="font-semibold text-gray-700">{{ userRole }}</span>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Unauthorized',
  data() {
    return {
      requiredPermission: null,
      userRole: 'User',
      attemptedPath: null
    }
  },
  mounted() {
    // Get required permission from route params or query
    this.requiredPermission = this.$route.query.permission || this.$route.params.permission
    this.attemptedPath = this.$route.query.attempted

    // Get user role from localStorage
    try {
      const userStr = localStorage.getItem('user') || sessionStorage.getItem('user')
      if (userStr) {
        const user = JSON.parse(userStr)
        if (user.roles && user.roles.length > 0) {
          this.userRole = user.roles[0]
        }
      }
    } catch (error) {
      console.error('Error getting user role:', error)
    }
  },
  methods: {
    goToDashboard() {
      this.$router.push({ name: 'dashboard' })
    },
    goBack() {
      // Go back to previous page, or dashboard if no history
      if (window.history.length > 1) {
        this.$router.go(-1)
      } else {
        this.$router.push({ name: 'dashboard' })
      }
    }
  }
}
</script>
