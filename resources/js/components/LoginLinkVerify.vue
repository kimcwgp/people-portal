<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-sky-50 to-white">
    <div class="relative z-10 bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4">
      <!-- Already Logged In State -->
      <div v-if="status === 'already-logged-in'" class="text-center">
        <div class="mb-6">
          <div class="w-20 h-20 bg-blue-500 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Already Logged In</h1>
        <p class="text-gray-600 mb-6">You're already logged in as {{ currentUserName }}.</p>
        <p class="text-sm text-gray-500 mb-6">This login link has expired or has already been used.</p>
        <div class="space-y-3">
          <button @click="goToDashboard" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
            Continue to Dashboard
          </button>
          <button @click="switchAccount" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-lg transition-colors">
            Switch Account
          </button>
        </div>
      </div>

      <!-- Processing State -->
      <div v-else-if="status === 'processing'" class="text-center">
        <div class="mb-6">
          <div class="w-20 h-20 bg-blue-500 rounded-full flex items-center justify-center mx-auto animate-pulse">
            <span class="text-3xl">🔑</span>
          </div>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Verifying Your Login</h1>
        <p class="text-gray-600 mb-6">Please wait...</p>
        <div class="flex justify-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
      </div>

      <!-- Success State -->
      <div v-else-if="status === 'success'" class="text-center">
        <div class="mb-6">
          <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Login Successful!</h1>
        <p class="text-gray-600 mb-6">Welcome back, {{ userName }}</p>
        <p class="text-sm text-gray-500">Redirecting to dashboard...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="status === 'error'" class="text-center">
        <div class="mb-6">
          <div class="w-20 h-20 bg-red-500 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </div>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Verification Failed</h1>
        <p class="text-gray-600 mb-6">{{ errorMessage }}</p>
        <button @click="goToLogin" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
          Back to Login
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../axios';

export default {
  data() {
    return {
      status: 'processing',
      userName: '',
      currentUserName: '',
      errorMessage: 'This link is invalid or has expired.',
    };
  },

  mounted() {
    // Check if user is already logged in FIRST
    const existingToken = localStorage.getItem('token');
    const existingUser = localStorage.getItem('user');
    
    if (existingToken && existingUser) {
      // User is already logged in
      try {
        const user = JSON.parse(existingUser);
        this.currentUserName = user.name || 'User';
        this.status = 'already-logged-in';
        
        // Prevent back button navigation while logged in
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
          history.go(1);
        };
      } catch (e) {
        // If there's an issue with the stored user data, proceed with verification
        this.verifyLoginLink();
      }
    } else {
      // User is not logged in, proceed with verification
      this.verifyLoginLink();
    }
  },

  methods: {
    async verifyLoginLink() {
      try {
        // Get token from the URL path
        const token = this.$route.params.token;
        if (!token) {
          this.status = 'error';
          this.errorMessage = 'Invalid link parameters.';
          return;
        }

        // Create a custom axios instance or add a flag to skip interceptor
        const response = await axios.post('/login/verify/' + token, {}, {
          // Add a custom property that your interceptor can check
          skipAuthRedirect: true,
          validateStatus: function (status) {
            // Don't throw error for 401/410 status codes
            return status < 500;
          }
        });

        if (response.data.success && response.status === 200) {
          // Store the token and user data
          localStorage.setItem('token', response.data.token);
          localStorage.setItem('user', JSON.stringify(response.data.user));
          this.userName = response.data.user.name;

          // Update axios headers
          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;

          this.status = 'success';

          // Redirect after 2 seconds
          setTimeout(() => {
            this.$router.replace({ name: 'dashboard' });
          }, 2000);
        } else {
          // Handle expired or invalid link
          this.status = 'error';
          if (response.status === 401 || response.status === 410) {
            this.errorMessage = 'This login link has expired or has already been used. Please request a new one.';
          } else {
            this.errorMessage = response.data.message || 'Verification failed';
          }
        }
      } catch (error) {
        console.error('Verification error:', error);
        this.status = 'error';
        this.errorMessage = error.response?.data?.message || 'Unable to verify your login link.';
      }
    },

    goToLogin() {
      this.$router.push({ name: 'login' });
    },

    goToDashboard() {
      this.$router.push({ name: 'dashboard' });
    },

    switchAccount() {
      // Log out current user and go to login
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      delete axios.defaults.headers.common['Authorization'];
      this.$router.replace({ name: 'login' });
    }
  }
};
</script>