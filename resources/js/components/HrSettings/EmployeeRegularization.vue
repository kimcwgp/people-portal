<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="mx-auto px-2">
      <!-- Header -->
      <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900">Employee Regularization</h1>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Search Employee</label>
            <input
              v-model="search"
              type="text"
              placeholder="Search by name..."
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              @input="loadEmployees"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status Filter</label>
            <select
              v-model="statusFilter"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              @change="loadEmployees"
            >
              <option value="">All Employees</option>
              <option value="probationary">Probationary</option>
              <option value="regular">Regular</option>
              <option value="resigned">Resigned</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Per Page</label>
            <select
              v-model="perPage"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              @change="loadEmployees"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-lg shadow-sm p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="text-gray-600 mt-2">Loading...</p>
      </div>

      <!-- Employees Table -->
      <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Employee Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Current Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Hire Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Regularization Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="employee in employees.data" :key="employee.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900">{{ employee.name }}</td>
                <td class="px-4 py-3 text-sm">
                  <span :class="getStatusBadgeClass(employee.employment_status)">
                    {{ employee.employment_status || 'N/A' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ formatDate(employee.date_hired) }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ formatDate(employee.regularization_date) }}</td>
                <td class="px-4 py-3 text-sm">
                  <button
                    @click="openEditModal(employee)"
                    class="text-blue-600 hover:text-blue-900 font-medium"
                  >
                    Edit Status
                  </button>
                </td>
              </tr>
              <tr v-if="!employees.data || employees.data.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                  No employees found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="employees.data && employees.data.length > 0" class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-sm text-gray-700">
              Showing {{ employees.from || 0 }} to {{ employees.to || 0 }} of {{ employees.total || 0 }} results
            </div>
            <div class="flex gap-2">
              <button
                v-for="link in employees.links"
                :key="link.label"
                @click="changePage(link.url)"
                :disabled="!link.url"
                v-html="link.label"
                :class="[
                  'px-3 py-1 rounded text-sm',
                  link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100',
                  !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                ]"
              ></button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Status Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/50 bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Edit Employment Status</h2>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
            <p class="text-gray-900 font-medium">{{ selectedEmployee?.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Current Status</label>
            <p class="text-gray-600">{{ selectedEmployee?.employment_status || 'N/A' }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Hire Date <span class="text-red-500">*</span></label>
            <input
              v-model="editForm.hire_date"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <p class="text-xs text-gray-500 mt-1">When the employee was hired</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">New Status <span class="text-red-500">*</span></label>
            <select
              v-model="editForm.employment_status"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Select Status</option>
              <option value="Probationary">Probationary</option>
              <option value="Regular">Regular</option>
              <option value="Resigned">Resigned</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Regularization Date</label>
            <input
              v-model="editForm.regularization_date"
              type="date"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <p class="text-xs text-gray-500 mt-1">Leave empty to auto-calculate (6 months from hire date)</p>
          </div>

          <div v-if="editError" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ editError }}
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button
            @click="showEditModal = false"
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="saveEmploymentStatus"
            :disabled="saving || !editForm.employment_status"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from '@/axios';

const loading = ref(false);
const saving = ref(false);
const search = ref('');
const statusFilter = ref('');
const perPage = ref(25);
const employees = ref({ data: [], links: [] });

const showEditModal = ref(false);
const selectedEmployee = ref(null);
const editForm = ref({
  hire_date: '',
  employment_status: '',
  regularization_date: ''
});
const editError = ref('');

const loadEmployees = async (page = 1) => {
  loading.value = true;
  try {
    const response = await axios.get('/hr/employees/regularization', {
      params: {
        page,
        per_page: perPage.value,
        search: search.value,
        status: statusFilter.value
      }
    });
    employees.value = response.data;
  } catch (error) {
    employees.value = { data: [], links: [] };
  } finally {
    loading.value = false;
  }
};

const changePage = (url) => {
  if (!url) return;
  const page = new URL(url).searchParams.get('page');
  loadEmployees(page);
};

const openEditModal = (employee) => {
  selectedEmployee.value = employee;
  editForm.value = {
    hire_date: employee.date_hired || '',
    employment_status: employee.employment_status || '',
    regularization_date: employee.regularization_date || ''
  };
  editError.value = '';
  showEditModal.value = true;
};

const saveEmploymentStatus = async () => {
  if (!editForm.value.hire_date) {
    editError.value = 'Hire date is required';
    return;
  }
  if (!editForm.value.employment_status) {
    editError.value = 'Please select an employment status';
    return;
  }

  saving.value = true;
  editError.value = '';

  try {
    await axios.put(`/hr/employees/${selectedEmployee.value.id}/regularization`, editForm.value);
    showEditModal.value = false;
    await loadEmployees();
  } catch (error) {
    editError.value = error.response?.data?.message || 'Failed to save employment status';
  } finally {
    saving.value = false;
  }
};

const getStatusBadgeClass = (status) => {
  const baseClass = 'px-2 py-1 text-xs font-semibold rounded-full';
  switch (status?.toLowerCase()) {
    case 'regular':
      return `${baseClass} bg-green-100 text-green-800`;
    case 'probationary':
      return `${baseClass} bg-yellow-100 text-yellow-800`;
    case 'resigned':
      return `${baseClass} bg-gray-100 text-gray-800`;
    default:
      return `${baseClass} bg-gray-100 text-gray-800`;
  }
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

onMounted(() => {
  loadEmployees();
});
</script>
