<template>
  <div class="flex h-screen bg-canvas">
    <!-- Overlay (mobile) -->
    <div v-if="isMobileMenuOpen" @click="toggleMobileMenu" class="fixed inset-0 bg-text/40 z-40 lg:hidden"/>

    <!-- Sidebar -->
    <aside
      :class="[
        'bg-surface border-r border-border transition-all duration-300 z-50 flex flex-col h-full shrink-0',
        'overflow-x-hidden',
        isCollapsed ? 'lg:w-20' : 'lg:w-72',
        'lg:relative fixed inset-y-0 left-0',
        isMobileMenuOpen ? 'translate-x-0 w-72 block' : '-translate-x-full w-72 lg:translate-x-0 hidden lg:block'
      ]"
    >
      <!-- Sidebar header -->
      <header class="p-4 border-b border-border flex items-center justify-between">
        <div v-if="!isCollapsed || isMobileMenuOpen" class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-full bg-text flex items-center justify-center">
            <span class="text-white font-semibold text-sm">{{ userInitials }}</span>
          </div>
          <div>
            <p class="text-xs text-text-muted">Hello</p>
            <p class="font-semibold text-text text-sm truncate max-w-[9rem]">{{ userName }}</p>
          </div>
        </div>
        <div v-else class="flex justify-center w-full">
          <div class="w-10 h-10 rounded-full bg-text flex items-center justify-center">
            <span class="text-white font-semibold text-sm">{{ userInitials }}</span>
          </div>
        </div>

        <!-- Desktop collapse -->
        <button @click="toggleSidebar" class="hidden lg:block p-2 rounded-lg hover:bg-canvas">
          <svg :class="['w-5 h-5 text-text-muted transition-transform', isCollapsed ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <!-- Mobile close -->
        <button @click="toggleMobileMenu" class="lg:hidden p-2 rounded-lg hover:bg-canvas">
          <svg class="w-5 h-5 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </header>

      <!-- Navigation -->
      <nav class="p-4 space-y-2 overflow-y-auto overflow-x-hidden flex-1">
        <template v-for="item in filteredMenuItems" :key="item.name">
          <!-- Regular menu item (no subItems) -->
          <router-link
            v-if="!item.subItems || !item.subItems.length"
            :to="{ name: item.name }"
            :class="[
              'flex items-center space-x-3 px-3 py-3 rounded-lg transition-all duration-200 group relative',
              isActive(item.name) ? 'bg-accent text-text font-semibold' : 'text-text-muted hover:bg-canvas hover:text-text'
            ]"
            @click="handleMenuClick"
          >
            <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
            <span v-if="!isCollapsed || isMobileMenuOpen" class="font-medium">{{ item.label }}</span>

            <!-- Tooltip when collapsed -->
            <div
              v-if="isCollapsed && !isMobileMenuOpen"
              class="absolute left-full ml-2 px-2 py-1 bg-text text-white text-sm rounded opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50"
            >
              {{ item.label }}
            </div>
          </router-link>

          <!-- Menu item with subItems -->
          <div v-else-if="item.subItems && item.subItems.length > 0" class="space-y-2">
            <div
              @click="toggleSubMenu(item.name)"
              :class="[
                'cursor-pointer flex items-center space-x-3 px-3 py-3 rounded-lg transition-all duration-200',
                'text-text-muted hover:bg-canvas hover:text-text'
              ]"
            >
              <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
              <span v-if="!isCollapsed || isMobileMenuOpen" class="font-medium">{{ item.label }}</span>
              <svg
                v-if="!isCollapsed || isMobileMenuOpen"
                :class="isSubMenuOpen(item.name) ? 'transform rotate-180' : ''"
                class="w-4 h-4 ml-auto transition-transform duration-200"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"/>
              </svg>
            </div>

            <!-- Render Sub Menu items -->
            <div v-show="isSubMenuOpen(item.name)" class="pl-6 space-y-2">
              <router-link
                v-for="subItem in item.subItems"
                :key="subItem.name"
                :to="{ name: subItem.name }"
                :class="[
                  'flex items-center space-x-3 px-3 py-3 rounded-lg transition-all duration-200',
                  isActive(subItem.name) ? 'bg-accent text-text font-semibold' : 'text-text-muted hover:bg-canvas hover:text-text'
                ]"
                @click="handleMenuClick"
              >
                <component :is="subItem.icon" class="w-5 h-5 flex-shrink-0" />
                <span v-if="!isCollapsed || isMobileMenuOpen" class="font-medium">{{ subItem.label }}</span>
              </router-link>
            </div>
          </div>
        </template>
      </nav>

      <!-- Logout Section -->
      <div class="p-4 mt-auto border-t border-border">
        <button
          @click="showLogoutConfirmation = true"
          class="flex items-center space-x-3 px-3 py-3 rounded-lg w-full text-text-muted hover:bg-status-red hover:text-danger group relative transition-colors"
        >
          <component :is="'LogoutIcon'" class="w-5 h-5 flex-shrink-0" />
          <span v-if="!isCollapsed || isMobileMenuOpen" class="font-medium">LOGOUT</span>

          <!-- Tooltip when collapsed -->
          <div
            v-if="isCollapsed && !isMobileMenuOpen"
            class="absolute left-full ml-2 px-2 py-1 bg-text text-white text-sm rounded opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 whitespace-nowrap z-50"
          >
            LOGOUT
          </div>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 flex flex-col min-h-0 min-w-0">
      <!-- Mobile header -->
      <div class="lg:hidden bg-surface border-b border-border p-4 flex items-center justify-between">
        <button @click="toggleMobileMenu" class="p-2 rounded-lg hover:bg-canvas">
          <svg class="w-6 h-6 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <h1 class="text-lg font-semibold text-text truncate">{{ currentTitle }}</h1>
        <div class="w-6" />
      </div>

      <div class="flex-1 overflow-auto">
        <router-view />
      </div>
    </main>

    <!-- Logout Modal -->
    <div v-if="showLogoutConfirmation" class="fixed inset-0 flex items-center justify-center z-50">
      <div class="absolute inset-0 bg-text/40" @click="showLogoutConfirmation = false"></div>
      <div class="relative bg-surface rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto mb-4 bg-status-red rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-text mb-2">Confirm Logout</h3>
          <p class="text-text-muted mb-6">Are you sure you want to logout? You will be redirected to the login page.</p>
          <div class="flex gap-3">
            <button @click="showLogoutConfirmation = false" class="flex-1 px-4 py-2 text-text bg-surface-sunken border border-border hover:bg-canvas rounded-xl">Cancel</button>
            <button @click="confirmLogout" class="flex-1 px-4 py-2 text-white bg-danger hover:bg-danger/90 rounded-xl">Logout</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Layout',
  data() {
    return {
      userName: '',
      userPermissions: [],
      isCollapsed: false,
      isMobileMenuOpen: false,
      showLogoutConfirmation: false,
      activeSubMenus: [],
      refreshInterval: null,
      menuItems: [
        { name: 'profile', label: 'PROFILE', icon: 'ProfileIcon', permission: 'view profile' },
        { name: 'dashboard', label: 'DASHBOARD', icon: 'DashboardIcon', permission: 'view dashboard' },

        {
          name: 'my-details',
          label: 'MY RECORDS',
          icon: 'DetailsIcon',
          subItems: [
            { name: 'attendance', label: 'ATTENDANCE', icon: 'AttendanceIcon', permission: 'view my attendance' },
            { name: 'time-entries', label: 'TIME ENTRIES', icon: 'TimeEntriesIcon', permission: 'view my time entries' },
            { name: 'leaves', label: 'LEAVE REQUESTS', icon: 'LeavesIcon', permission: 'view my leaves' },
            { name: 'shift', label: 'MY SHIFT', icon: 'MyShiftIcon', permission: 'view my shift' },
          ]
        },

          {
          name: 'my-team',
          label: 'MY TEAM',
          icon: 'TeamIcon',
          subItems: [
            { name: 'team-attendance', label: 'TEAM\'S ATTENDANCE', icon: 'TeamAttendanceIcon', permission: 'view team attendance' },
            { name: 'team-time-entries', label: 'TEAM\'S TIME ENTRIES', icon: 'TeamTimeEntriesIcon', permission: 'view team time entries' },
            { name: 'team-leaves', label: 'TEAM\'S LEAVES', icon: 'TeamLeavesIcon', permission: 'view team leaves' },
            { name: 'team-shift', label: 'TEAM\'S SHIFT', icon: 'TeamShiftIcon', permission: 'view team shift' },
          ]
        },

        {
          name: 'hr-settings',
          label: 'HR MODULES',
          icon: 'HrSettingsIcon',
          subItems: [
            { name: 'users', label: 'USER MANAGEMENT', icon: 'UsersIcon', permission: 'view users' },
            { name: 'roles', label: 'ROLE MANAGEMENT', icon: 'RolesIcon', permission: 'view roles' },
            { name: 'hr-announcements', label: 'HR ANNOUNCEMENTS', icon: 'HrAnnouncementIcon', permission: 'view hr announcements' },
            { name: 'leave-credits', label: 'LEAVE CREDITS', icon: 'LeaveCreditsIcon', permission: 'view leave credits' },
            { name: 'employee-regularization', label: 'EMPLOYEE REGULARIZATION', icon: 'RegularizationIcon', permission: 'edit employee regularization' },
            { name: 'holidays', label: 'HOLIDAYS', icon: 'HolidaysIcon', permission: 'create holidays' },
            { name: 'associate-logs', label: 'ASSOCIATE LOGS', icon: 'AssociateLogsIcon', permission: 'view associate logs' },
            { name: 'active-associates', label: 'ACTIVE ASSOCIATES', icon: 'ActiveAssociatesIcon', permission: 'view active associates' },
            { name: 'proxy-leaves', label: 'PROXY LEAVES', icon: 'ProxyLeavesIcon', permission: 'view proxy leaves' },
            { name: 'proxy-attendance', label: 'PROXY ATTENDANCE', icon: 'ProxyAttendanceIcon', permission: 'view proxy attendance' },
          ]
        },

                {
          name: 'settings',
          label: 'SETTINGS',
          icon: 'SettingsIcon',
          subItems: [
            { name: 'projects', label: 'PROJECTS', icon: 'ProjectsIcon', permission: 'view projects' },
            { name: 'clients', label: 'CLIENTS', icon: 'ClientsIcon', permission: 'view clients' },
            { name: 'leave-type', label: 'LEAVES TYPE', icon: 'LeavesTypeIcon', permission: 'view leave types' },
            { name: 'shifts', label: 'SHIFTS', icon: 'ShiftsIcon', permission: 'view shifts' },
          ]
        },
      ],
    }
  },
  computed: {
    userInitials() {
      if (!this.userName) return 'U'
      return this.userName
        .split(' ')
        .map(n => n.charAt(0))
        .join('')
        .substring(0, 2)
        .toUpperCase()
    },
    currentTitle() {
      return this.$route.meta?.title || 'app'
    },
    filteredMenuItems() {
      return this.menuItems.map(item => {
        if (item.subItems) {
          const filteredSubItems = item.subItems.filter(subItem => {
            if (subItem.permission) {
              return this.hasPermission(subItem.permission)
            }
            return true
          })
          return { ...item, subItems: filteredSubItems }
        }
        return item
      }).filter(item => {
        // Check permission for top-level items
        if (item.permission && !this.hasPermission(item.permission)) {
          return false
        }
        // For items with subItems, only show if they have at least one visible subItem
        if (item.subItems) {
          return item.subItems.length > 0
        }
        return true
      })
    }
  },
  watch: {
    $route() {
      if (this.isMobileMenuOpen) this.isMobileMenuOpen = false
      // Refresh permissions on route change
      this.refreshPermissions()
    },
  },
  mounted() {
    this.loadUserData()
    window.addEventListener('resize', this.handleResize)
    window.addEventListener('user-updated', this.loadUserData)
    this.handleResize()

    // Refresh permissions immediately on mount
    this.refreshPermissions()

    // Set up periodic refresh every 5 minutes
    this.refreshInterval = setInterval(() => {
      this.refreshPermissions()
    }, 5 * 60 * 1000) // 5 minutes
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.handleResize)
    window.removeEventListener('user-updated', this.loadUserData)

    // Clear refresh interval
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval)
    }
  },
  methods: {
    isActive(name) {
      return this.$route.name === name
    },
    toggleSidebar() {
      this.isCollapsed = !this.isCollapsed
    },
    toggleMobileMenu() {
      this.isMobileMenuOpen = !this.isMobileMenuOpen
    },
    handleMenuClick() {
      if (this.isMobileMenuOpen) this.isMobileMenuOpen = false
    },
    handleResize() {
      if (window.innerWidth >= 1024) this.isMobileMenuOpen = false
    },
    loadUserData() {
      try {
        const ls = localStorage.getItem('user')
        if (ls) {
          const u = JSON.parse(ls)
          this.userName = u.name || u.username || u.email || 'User'
          this.userPermissions = u.permissions || []
          return
        }
        const ss = sessionStorage.getItem('user')
        if (ss) {
          const u = JSON.parse(ss)
          this.userName = u.name || u.username || u.email || 'User'
          this.userPermissions = u.permissions || []
          return
        }
        const token = localStorage.getItem('token') || sessionStorage.getItem('token')
        if (token) {
          try {
            const payload = JSON.parse(atob(token.split('.')[1]))
            this.userName = payload.name || payload.username || payload.email || 'User'
            this.userPermissions = payload.permissions || []
            return
          } catch {}
        }
        this.userName = 'User'
        this.userPermissions = []
      } catch {
        this.userName = 'User'
        this.userPermissions = []
      }
    },
    hasPermission(permission) {
      return this.userPermissions.includes(permission)
    },
    async refreshPermissions() {
      try {
        const token = localStorage.getItem('token') || sessionStorage.getItem('token')
        if (!token) return

        const response = await fetch('/api/user/profile', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        })

        if (!response.ok) {
          // If unauthorized, don't update anything
          if (response.status === 401) {
            return
          }
          throw new Error('Failed to fetch permissions')
        }

        const data = await response.json()

        if (data.success && data.data) {
          // Update permissions
          this.userPermissions = data.data.permissions || []

          // Update localStorage/sessionStorage
          const storageKey = localStorage.getItem('user') ? 'localStorage' : 'sessionStorage'
          const storage = storageKey === 'localStorage' ? localStorage : sessionStorage

          const userStr = storage.getItem('user')
          if (userStr) {
            const user = JSON.parse(userStr)
            user.permissions = data.data.permissions || []
            user.roles = data.data.roles || []
            storage.setItem('user', JSON.stringify(user))
          }
        }
      } catch (error) {
        // Silently fail - don't disrupt user experience
      }
    },
    confirmLogout() {
      this.showLogoutConfirmation = false
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      sessionStorage.removeItem('token')
      sessionStorage.removeItem('user')
      if (this.$store?.dispatch) this.$store.dispatch('auth/logout').catch(() => {})
      this.$router.push({ name: 'login' })
    },
    toggleSubMenu(menuName) {
      const index = this.activeSubMenus.indexOf(menuName);
      if (index === -1) {
        this.activeSubMenus.push(menuName);
      } else {
        this.activeSubMenus.splice(index, 1);
      }
    },
    isSubMenuOpen(menuName) {
      return this.activeSubMenus.includes(menuName);
    }
  },
  components: {
    ProfileIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>`
    },
    DashboardIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>`
    },
    DetailsIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>`
    },
    UsersIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a1.5 1.5 0 010 3 1.5 1.5 0 010-3z" />
        </svg>`
    },
    RolesIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>`
    },
    AttendanceIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>`
    },
    TimeEntriesIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>`
    },
    LeavesIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>`
    },
    AssociateLogsIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>`
    },
    ActiveAssociatesIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>`
    },
    TeamIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>`
    },
    TeamAttendanceIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          <circle cx="18" cy="6" r="3" fill="currentColor" opacity="0.3"/>
        </svg>`
    },
    TeamTimeEntriesIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          <circle cx="19" cy="5" r="2" fill="currentColor" opacity="0.3"/>
        </svg>`
    },
    TeamLeavesIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          <circle cx="18" cy="5" r="2" fill="currentColor" opacity="0.3"/>
        </svg>`
    },
    TeamShiftIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
          <circle cx="19" cy="5" r="2" fill="currentColor" opacity="0.3"/>
        </svg>`
    },
    LogoutIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>`
    },
    
    HrSettingsIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>`
    },

    HrAnnouncementIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
        </svg>`
    },

    LeaveCreditsIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>`
    },

    HolidaysIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l.7 1.5 1.6.2-1.2 1.1.3 1.6-1.4-.8-1.4.8.3-1.6-1.2-1.1 1.6-.2z"/>
        </svg>`
    },

    RegularizationIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" stroke="green" d="M15 10l2 2 4-4"/>
        </svg>`
    },

    ProxyLeavesIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" stroke="green" d="M9 11l3 3L22 4"/>
        </svg>`
    },


    ProxyAttendanceIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" stroke="orange" d="M16 8l-4 4m0-4l4 4"/>
        </svg>`
    },

    SettingsIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>`
    },

    ProjectsIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>`
    },

    LeavesTypeIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>`
    },

    ClientsIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>`
    },

    ShiftsIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>`
    },

    MyShiftIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 3a2.828 2.828 0 114 4L7.5 20.5 2 22l1.5-5.5L17 3z" opacity="0.4"/>
        </svg>`
    },

    RolesIcon: {
      template: `
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>`
    },
  },
}
</script>