import { createRouter, createWebHistory } from 'vue-router'
import Layout from '@/components/Layout.vue'

const Login            = () => import('@/components/Login.vue')
const Unauthorized     = () => import('@/components/Unauthorized.vue')
const UserManagement   = () => import('@/components/UserManagement.vue')
const RoleManagement   = () => import('@/components/RoleManagement.vue')
const ShiftManagement  = () => import('@/components/Shifts.vue')
// const LoginLinkVerify  = () => import('@/components/LoginLinkVerify.vue')
const Dashboard        = () => import('@/components/Dashboard.vue')
const Profile          = () => import('@/components/Profile.vue')
const ActiveAssociates = () => import('@/components/ActiveAssociates.vue')

const Projects        = () => import('@/components/Projects.vue')
const LeaveTypes       = () => import('@/components/LeaveTypes.vue') 
const Clients          = () => import('@/components/Clients.vue')

// My Records Components
const MyLeaves         = () => import('@/components/MyLeaves.vue')
const MyAttendance     = () => import('@/components/MyAttendance.vue')
const MyStandup        = () => import('@/components/MyStandup.vue')
const MyOvertime       = () => import('@/components/MyOvertime.vue')
const MyShift          = () => import('@/components/MyShift.vue') 

// Team Components
const TeamAttendance   = () => import('@/components/TeamAttendance.vue')
const TeamStandup      = () => import('@/components/TeamStandup.vue')
const TeamLeaves       = () => import('@/components/TeamComponents/TeamLeaves.vue')
const TeamOvertime     = () => import('@/components/TeamComponents/TeamOvertime.vue')
const TeamShift        = () => import('@/components/TeamComponents/TeamShift.vue')

// HR
const HrAnnouncements = () => import('@/components/HrSettings/HrAnnouncements.vue')
const HRAssociateLogs  = () => import('@/components/HrSettings/HRAssociateLogs.vue')
const ProxyLeaves     = () => import('@/components/HrSettings/ProxyLeaves.vue')
const ProxyOvertime   = () => import('@/components/HrSettings/ProxyOvertime.vue')
const ProxyAttendance = () => import('@/components/HrSettings/ProxyAttendance.vue')
const LeaveCredits    = () => import('@/components/HrSettings/LeaveCredits.vue')
const EmployeeRegularization = () => import('@/components/HrSettings/EmployeeRegularization.vue')


const routes = [
  {
    path: '/',
    component: Layout,
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: { name: 'dashboard' } },
      { path: 'profile',   name: 'profile',   component: Profile,   meta: { title: 'Profile', permission: 'view profile' } },
      { path: 'dashboard', name: 'dashboard', component: Dashboard, meta: { title: 'Dashboard', permission: 'view dashboard' } },
      { path: 'users', name: 'users', component: UserManagement, meta: { title: 'User Management', permission: 'view users' } },
      { path: 'roles', name: 'roles', component: RoleManagement, meta: { title: 'Role Management', permission: 'view roles' } },
      { path: 'shifts', name: 'shifts', component: ShiftManagement, meta: { title: 'Shift Management', permission: 'view shifts' } },
      { path: 'associate-logs', name: 'associate-logs', component: HRAssociateLogs, meta: { title: 'Associate Logs', permission: 'view associate logs' } },
      { path: 'active-associates', name: 'active-associates', component: ActiveAssociates, meta: { title: 'Active Associates', permission: 'view active associates' } },

      // Admin routes
      { path: 'projects', name: 'projects', component: Projects, meta: { title: 'Projects', permission: 'view projects' } },
      { path: 'leave-type', name: 'leave-type', component: LeaveTypes, meta: { title: 'Leave Types', permission: 'view leave types' } },
      { path: 'clients', name: 'clients', component: Clients, meta: { title: 'Clients', permission: 'view clients' } },

      // My Records routes
      { path: 'attendance', name: 'attendance', component: MyAttendance, meta: { title: 'My Attendance', permission: 'view my attendance' } },
      { path: 'standup', name: 'standup', component: MyStandup, meta: { title: 'My Stand up', permission: 'view my standups' } },
      { path: 'leaves', name: 'leaves', component: MyLeaves, meta: { title: 'My Leaves', permission: 'view my leaves' } },
      { path: 'overtime', name: 'overtime', component: MyOvertime, meta: { title: 'My Overtime', permission: 'view my overtime' } },
      { path: 'shift', name: 'shift', component: MyShift, meta: { title: 'My Shift', permission: 'view my shift' } },

      // Team routes
      { path: 'team-attendance', name: 'team-attendance', component: TeamAttendance, meta: { title: 'My Team\'s Attendance', permission: 'view team attendance' } },
      { path: 'team-standup', name: 'team-standup', component: TeamStandup, meta: { title: 'My Team\'s Stand Up', permission: 'view team standups' } },
      { path: 'team-leaves', name: 'team-leaves', component: TeamLeaves, meta: { title: 'My Team\'s Leaves', permission: 'view team leaves' } },
      { path: 'team-overtime', name: 'team-overtime', component: TeamOvertime, meta: { title: 'My Team\'s Overtime', permission: 'view team overtime' } },
      { path: 'team-shift', name: 'team-shift', component: TeamShift, meta: { title: 'My Team\'s Shift', permission: 'view team shift' } },

      // HR Settings routes
      { path: 'hr-announcements', name: 'hr-announcements', component: HrAnnouncements, meta: { title: 'HR Announcements', permission: 'view hr announcements' } },
      { path: 'leave-credits', name: 'leave-credits', component: LeaveCredits, meta: { title: 'Leave Credits', permission: 'view leave credits' } },
      { path: 'employee-regularization', name: 'employee-regularization', component: EmployeeRegularization, meta: { title: 'Employee Regularization', permission: 'edit employee regularization' } },
      { path: 'proxy-leaves', name: 'proxy-leaves', component: ProxyLeaves, meta: { title: 'Proxy Leaves', permission: 'view proxy leaves' } },
      { path: 'proxy-overtime', name: 'proxy-overtime', component: ProxyOvertime, meta: { title: 'Proxy Overtime', permission: 'view proxy overtime' } },
      { path: 'proxy-attendance', name: 'proxy-attendance', component: ProxyAttendance, meta: { title: 'Proxy Attendance', permission: 'view proxy attendance' } },
      // add more children here...
    ],
  },

  // Guest routes (no layout)
  { path: '/login',        name: 'login',             component: Login,           meta: { guest: true, title: 'Login' } },
  // { path: '/login-link',   name: 'login-link-verify', component: LoginLinkVerify, meta: { guest: true, skipAuthCheck: true, title: 'Login Link' } },
  // { path: '/auto_login/:token', name: 'auto-login',   component: LoginLinkVerify, meta: { guest: true, skipAuthCheck: true, title: 'Auto Login' }, props: true },

  // Error pages (no layout)
  { path: '/unauthorized', name: 'unauthorized',       component: Unauthorized,    meta: { requiresAuth: true, title: 'Access Denied' } },

  // Fallback
  { path: '/:pathMatch(.*)*', redirect: { name: 'dashboard' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

// Helper function to get user permissions
function getUserPermissions() {
  try {
    const userStr = localStorage.getItem('user') || sessionStorage.getItem('user')
    if (userStr) {
      const user = JSON.parse(userStr)
      return user.permissions || []
    }
  } catch (error) {
    console.error('Error getting user permissions:', error)
  }
  return []
}

// Helper function to check if user has permission
function hasPermission(permission) {
  const permissions = getUserPermissions()
  return permissions.includes(permission)
}

// Guards
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token') || sessionStorage.getItem('token')

  if (to.meta.skipAuthCheck) return next()

  // Check authentication
  if (to.matched.some(r => r.meta.requiresAuth) && !token) {
    return next({ name: 'login' })
  }

  if (to.meta.guest && token && to.name !== 'login-link-verify') {
    return next({ name: 'dashboard' })
  }

  // Check permissions
  if (to.meta.permission) {
    if (!hasPermission(to.meta.permission)) {
      // User doesn't have permission, redirect to 403 page
      return next({
        name: 'unauthorized',
        query: {
          permission: to.meta.permission,
          attempted: to.path
        }
      })
    }
  }

  next()
})

// Page titles
router.afterEach((to) => {
  document.title = to.meta?.title ? `${to.meta.title} • People Portal` : 'People Portal'
})

export default router