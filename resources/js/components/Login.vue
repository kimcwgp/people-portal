<template>
  <div class="min-h-screen flex">
    <!-- Left Section (Login Form) -->
    <div class="w-full lg:w-1/2 flex flex-col bg-gray-50 relative">
      <!-- Form Container -->
      <div class="flex-1 flex justify-center items-center p-8">
        <div class="w-full max-w-md">
          <h2 class="text-4xl font-bold text-gray-800 mb-2">Welcome,</h2>
          <p class="text-gray-500 mb-10 text-2xl">sign in to continue</p>

          <form @submit.prevent="handleLogin" class="space-y-6">
            <div>
              <input
                v-model="email"
                type="email"
                id="email"
                autocomplete="username"
                placeholder="Email Address"
                required
                class="w-full px-6 py-6 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all placeholder-gray-600 text-gray-700 bg-white"
              />
            </div>

            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                id="password"
                autocomplete="current-password"
                placeholder="Password"
                required
                class="w-full px-6 py-6 pr-16 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all placeholder-gray-600 text-gray-700 bg-white"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                class="absolute inset-y-0 right-0 flex items-center pr-6 text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-4.72-4.72m14.408 14.408l-4.72-4.72"></path>
                </svg>
              </button>
            </div>

            <div class="flex justify-between items-center">
              <label class="flex items-center text-gray-600 cursor-pointer">
                <input
                  type="checkbox"
                  v-model="rememberMe"
                  class="mr-3 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span class="text-gray-600">Remember me</span>
              </label>
            </div>

            <!-- Sign In Button moved here -->
            <div class="pt-4">
              <button
                type="submit"
                :disabled="loading"
                class="w-full bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white font-semibold text-lg py-4 px-12 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
              >
                <span v-if="!loading">SIGN IN</span>
                <span v-else>SIGNING IN...</span>
              </button>
            </div>
          </form>

          <!-- Error Message -->
          <div v-if="error" class="text-red-500 text-center mt-4 p-4 bg-red-50 rounded-lg">
            {{ error }}
          </div>
        </div>
      </div>
    </div>

    <!-- Right Section (Illustration) -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
      <!-- Background gradient similar to the image -->
      <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-sky-50 to-white"></div>

      <!-- Decorative circles -->
      <div class="absolute -right-20 -top-20 w-96 h-96 bg-blue-100 rounded-full opacity-30 blur-3xl"></div>
      <div class="absolute -right-40 top-1/2 w-80 h-80 bg-sky-100 rounded-full opacity-20 blur-2xl"></div>
      <div class="absolute right-20 bottom-20 w-64 h-64 bg-blue-200 rounded-full opacity-25 blur-xl"></div>

      <!-- Content container -->
      <div class="relative z-10 flex items-center justify-center w-full p-12">
        <div class="text-center">
          <!-- Big Title -->
          <h1 class="text-5xl font-bold text-gray-800 mb-8">
            People <span class="text-blue-500">Portal</span>
          </h1>

          <div class="mx-auto mb-8 w-48 h-48 flex items-center justify-center">
            <img
              src="@/assets/cwgp-logo.webp"
              alt="CWGP Logo"
              class="w-full h-full object-contain drop-shadow-2xl"
            />
          </div>
          <p class="text-gray-600">Sign in to start your session</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "../axios";

export default {
  data() {
    return {
      email: "",
      password: "",
      rememberMe: false,
      showPassword: false,
      error: null,
      loading: false,
    };
  },
  methods: {
    async handleLogin() {
      try {
        this.error = null;
        this.loading = true;

        const response = await axios.post("/login", {
          email: this.email,
          password: this.password,
        });

        if (!response.data.success) {
          this.error = response.data.message || "Sign in failed. Please try again.";
          return;
        }

        const storage = this.rememberMe ? localStorage : sessionStorage;
        storage.setItem("token", response.data.token);
        storage.setItem("user", JSON.stringify(response.data.user));

        axios.defaults.headers.common["Authorization"] = `Bearer ${response.data.token}`;

        this.password = "";
        this.$router.replace({ name: "dashboard" });

      } catch (err) {
        if (err.response?.status === 401) {
          this.error = "These credentials do not match our records.";
        } else if (err.response?.status === 403) {
          this.error = err.response?.data?.message || "This account has been deactivated.";
        } else if (err.response?.status === 422) {
          this.error = "Please enter a valid email address and password.";
        } else if (err.response?.status === 429) {
          this.error = "Too many sign in attempts. Please try again in a minute.";
        } else {
          this.error = err.response?.data?.message || "Sign in failed. Please try again.";
        }
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>
