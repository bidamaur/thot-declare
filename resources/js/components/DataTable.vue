<template>
  <div class="space-y-2">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
      <div>
        <h1 class="text-lg font-semibold text-slate-900">{{ title }}</h1>
        <p class="text-xs text-slate-500">{{ subtitle }}</p>
      </div>
      
      <div v-if="enableFilters && filterColumn" class="flex items-center gap-2">
        <label class="text-xs font-medium text-slate-600">Filtrer {{ getColumnLabel(filterColumn) }} :</label>
        <div class="relative">
          <input
            type="month"
            v-model="filterDate"
            class="date-input"
            @change="fetchData"
          />
          <span class="material-icons text-slate-400 absolute left-2 top-1/2 -translate-y-1/2 text-xs pointer-events-none">filter_list</span>
        </div>
        <button v-if="filterDate" @click="clearFilter" class="px-2 py-0.5 text-xs bg-slate-200 rounded hover:bg-slate-300">Effacer</button>
        <button @click="showAll" class="px-2 py-0.5 text-xs bg-blue-100 rounded hover:bg-blue-200">Afficher tout</button>
        <button @click="exportToExcel" :disabled="data.length === 0" class="px-2 py-0.5 text-xs bg-emerald-100 rounded hover:bg-emerald-200" :class="data.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
          Excel
        </button>
      </div>
    </div>

    <!-- Table Card -->
    <div class="border border-slate-200 rounded bg-white">
      <div v-if="loading" class="flex justify-center items-center py-8">
        <div class="flex flex-col items-center gap-2">
          <div class="animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"></div>
          <p class="text-xs text-slate-500">Chargement...</p>
        </div>
      </div>
      
      <div v-else-if="error" class="p-4">
        <div class="flex items-center gap-2 p-2 bg-red-50 rounded">
          <span class="material-icons text-red-600 text-sm">error</span>
          <p class="text-red-700 text-xs">{{ error }}</p>
        </div>
      </div>
      
      <div v-else-if="data.length === 0" class="p-8 text-center">
        <span class="material-icons text-2xl text-slate-300 mb-1">inbox</span>
        <p class="text-slate-500 text-xs">Aucune donnée.</p>
      </div>
      
      <div v-show="data.length > 0" class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-2 py-1 text-left font-semibold text-slate-600 w-8">#</th>
              <th class="px-2 py-1 text-left">
                <input type="checkbox" @change="toggleAll" :checked="isAllSelected" class="w-3 h-3" />
              </th>
              <th v-for="col in columns" :key="col.key" class="table-header px-2 py-1 cursor-pointer select-none" @click="sortBy(col.key)">
                <div class="flex items-center gap-1">
                  <span>{{ col.label }}</span>
                  <span v-if="sortKey === col.key" class="material-icons text-xs">
                    {{ sortOrder === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
                  </span>
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(row, index) in paginatedData" :key="index" class="table-row">
              <td class="px-2 py-1 text-slate-500">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</td>
              <td class="px-2 py-1">
                <input type="checkbox" :value="row" v-model="selectedRows" class="w-3 h-3" />
              </td>
              <td v-for="col in columns" :key="col.key" class="table-cell px-2 py-1">
                <span v-if="col.format === 'date'">{{ formatDate(row[col.key]) }}</span>
                <span v-else-if="col.format === 'number'" class="font-medium text-slate-900">{{ formatNumber(row[col.key]) }}</span>
                <span v-else-if="col.format === 'currency'" class="font-semibold text-slate-900">{{ formatCurrency(row[col.key]) }}</span>
                <span v-else>{{ row[col.key] ?? '-' }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="data.length > 0" class="flex items-center justify-between px-3 py-2 bg-white rounded border border-slate-200 text-xs">
      <div class="flex items-center gap-2">
        <p class="text-slate-500">
          {{ data.length }} résultats - Page {{ currentPage }}/{{ totalPages }}
        </p>
      </div>
      <div class="flex items-center gap-1">
        <select v-model="internalItemsPerPage" class="text-xs border border-slate-300 rounded px-1 py-0.5">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="20">20</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
          <option :value="-1">100%</option>
        </select>
        <button @click="prevPage" :disabled="currentPage === 1 || internalItemsPerPage === -1" class="px-2 py-0.5 rounded border text-xs" :class="currentPage === 1 || internalItemsPerPage === -1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100'">
          Préc.
        </button>
        <button @click="nextPage" :disabled="currentPage === totalPages || internalItemsPerPage === -1" class="px-2 py-0.5 rounded border text-xs" :class="currentPage === totalPages || internalItemsPerPage === -1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100'">
          Suiv.
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";

const emit = defineEmits(['dataLoaded']);

const props = defineProps({
    title: String,
    subtitle: String,
    endpoint: Function,
    columns: Array,
    showDatePicker: {
        type: Boolean,
        default: false,
    },
    enableFilters: {
        type: Boolean,
        default: false,
    },
    filterColumn: {
        type: String,
        default: null,
    },
    itemsPerPage: {
        type: Number,
        default: 5,
    },
});

const formatDate = (val) => {
    if (!val) return "-";
    const str = String(val);
    if (str.length === 8)
        return `${str.slice(0, 2)}/${str.slice(2, 4)}/${str.slice(4, 8)}`;
    return val;
};

const formatNumber = (val) => {
    if (!val && val !== 0) return "-";
    return new Intl.NumberFormat("fr-FR").format(parseFloat(val));
};

const formatCurrency = (val) => {
    if (!val && val !== 0) return "-";
    return new Intl.NumberFormat("fr-FR", {
        style: "currency",
        currency: "EUR",
    }).format(parseFloat(val));
};

const data = ref([]);
const filterDate = ref("");
const loading = ref(false);
const error = ref(null);
const currentPage = ref(1);
const selectedRows = ref([]);
const sortKey = ref("");
const sortOrder = ref("asc");
const internalItemsPerPage = ref(props.itemsPerPage);

const getColumnLabel = (key) => {
    const col = props.columns?.find((c) => c.key === key);
    return col?.label || key;
};

const fetchData = async () => {
    loading.value = true;
    error.value = null;
    selectedRows.value = [];
    sortKey.value = "";
    sortOrder.value = "asc";
    try {
        const url = props.endpoint ? props.endpoint(filterDate.value) : (filterDate.value ? `/api/cdr_pp/${filterDate.value}` : "/api/cdr_pp");
        const response = await axios.get(url);
        const responseData = response.data || [];
        const hasError =
            responseData.length > 0 && responseData[0].type === "Erreur";
        if (hasError) {
            error.value =
                responseData[0].Description ||
                "Erreur lors du chargement des données";
            data.value = [];
        } else {
            data.value = responseData;
        }
        emit('dataLoaded', data.value);
        currentPage.value = 1;
    } catch (err) {
        error.value = "Erreur lors du chargement des données";
        data.value = [];
    } finally {
        loading.value = false;
    }
};

const clearFilter = () => {
    filterDate.value = "";
    fetchData();
};

const showAll = () => {
    filterDate.value = "";
    fetchData();
};

const exportToExcel = () => {
    if (!data.value.length) return;
    const XLSX = window.XLSX;
    const exportData = sortedData.value.map((row, idx) => {
        const obj = { "#": (currentPage.value - 1) * internalItemsPerPage.value + idx + 1 };
        columns.value.forEach((col) => {
            obj[col.label] = row[col.key] ?? "-";
        });
        return obj;
    });
    const worksheet = XLSX.utils.json_to_sheet(exportData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Données");
    const wbout = XLSX.write(workbook, { bookType: "xlsx", type: "array" });
    const blob = new Blob([wbout], { type: "application/octet-stream" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `${title.replace(/\s+/g, "_")}_${new Date().toISOString().slice(0, 10)}.xlsx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};

const isAllSelected = computed(() => {
    return data.value.length > 0 && selectedRows.value.length === data.value.length;
});

const toggleAll = (event) => {
    if (event.target.checked) {
        selectedRows.value = [...data.value];
    } else {
        selectedRows.value = [];
    }
};

const sortedData = computed(() => {
    if (!sortKey.value) return data.value;
    return [...data.value].sort((a, b) => {
        const valA = a[sortKey.value] ?? "";
        const valB = b[sortKey.value] ?? "";
        if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1;
        if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const totalPages = computed(() => {
    if (internalItemsPerPage.value === -1) return 1;
    return Math.max(1, Math.ceil(sortedData.value.length / internalItemsPerPage.value));
});

const paginatedData = computed(() => {
    if (internalItemsPerPage.value === -1) return sortedData.value;
    const start = (currentPage.value - 1) * internalItemsPerPage.value;
    return sortedData.value.slice(start, start + internalItemsPerPage.value);
});

const prevPage = () => {
    if (currentPage.value > 1 && internalItemsPerPage.value !== -1) {
        currentPage.value--;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value && internalItemsPerPage.value !== -1) {
        currentPage.value++;
    }
};

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortOrder.value = 'asc';
    }
    currentPage.value = 1;
};

watch(internalItemsPerPage, (newVal) => {
    currentPage.value = 1;
});

onMounted(() => {
    fetchData();
});
</script>

<style scoped>
.date-input {
    @apply pl-6 pr-2 py-1 border border-slate-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none text-xs bg-white;
}

.table-header {
    @apply text-xs font-semibold text-slate-600 uppercase tracking-wider;
}

.table-cell {
    @apply text-xs text-slate-800;
}

.table-row:hover {
    @apply bg-slate-50;
}
</style>
