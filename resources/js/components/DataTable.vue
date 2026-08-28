<template>
    <div class="space-y-2">
         <!-- Header Section -->
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
            >
                <div>
                    <h1 class="text-lg font-semibold" style="color: rgb(var(--foreground));">
                        {{ title }}
                    </h1>
                    <p class="text-xs" style="color: rgb(var(--muted));">{{ subtitle }}</p>
                </div>

            <div
                v-if="enableFilters && filterColumn"
                class="flex items-center gap-2"
            >
                <label class="text-xs font-medium text-slate-600"
                    >Filtrer {{ getColumnLabel(filterColumn) }} :</label
                >
                <div class="relative">
                    <input
                        type="month"
                        v-model="filterDate"
                        class="date-input"
                    />
                    <span
                        class="material-icons text-slate-400 absolute left-2 top-1/2 -translate-y-1/2 text-xs pointer-events-none"
                        >filter_list</span
                    >
                </div>
                <div class="relative">
                    <input
                        type="text"
                        v-model="filterQuery"
                        class="date-input"
                        placeholder="Recherche (nom, prénom, sexe, id)"
                    />
                </div>
                <button
                     v-if="filterDate || filterQuery"
                     @click="clearFilter"
                     class="action-btn"
                     >
                     Effacer
                 </button>
                 <button
                     @click="fetchData"
                     class="action-btn-primary"
                     >
                     Rechercher
                 </button>
                 <button
                     @click="showAll"
                     class="action-btn-primary"
                     >
                     Afficher tout
                 </button>
                 <button
                     @click="exportToExcel"
                     :disabled="data.length === 0"
                     class="action-btn-success"
                     :class="
                         data.length === 0 ? 'opacity-50 cursor-not-allowed' : ''
                     "
                     >
                     Excel
                 </button>
            </div>
        </div>

         <!-- Table Card -->
            <div class="premium-card" style="overflow: visible;">
                <div v-if="loading" class="flex justify-center items-center py-8">
                    <div class="flex flex-col items-center gap-2">
                        <div
                            class="animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"
                        ></div>
                        <p class="text-xs" style="color: rgb(var(--muted));">Chargement...</p>
                    </div>
                </div>

                <div v-else-if="error" class="p-4">
                    <div class="flex items-center gap-2 p-2 bg-red-50 rounded">
                        <span class="material-icons text-red-600 text-sm"
                            >error</span>
                        >
                        <p class="text-red-700 text-xs">{{ error }}</p>
                    </div>
                </div>

                <div v-else-if="data.length === 0" class="p-8 text-center">
                    <span class="material-icons text-2xl mb-1"
                        >inbox</span>
                    >
                    <p class="text-xs" style="color: rgb(var(--muted));">Aucune donnée.</p>
                </div>

                <div v-show="data.length > 0" class="overflow-x-auto">
                    <table class="premium-table">
                        <thead class="table-header">
                            <tr>
                                <th
                                    class="px-2 py-1 text-left font-semibold w-8"
                                    style="color: rgb(var(--muted));"
                                >
                                    #
                                </th>
                                <th class="px-2 py-1 text-left">
                                    <input
                                        type="checkbox"
                                        @change="toggleAll"
                                        :checked="isAllSelected"
                                        class="w-3 h-3"
                                    />
                                </th>
                                <th
                                    v-for="col in columns"
                                    :key="col.key"
                                    class="table-header px-2 py-1 cursor-pointer select-none"
                                    @click="sortBy(col.key)"
                                >
                                    <div class="flex items-center gap-1">
                                        <span>{{ col.label }}</span>
                                        <span
                                            v-if="sortKey === col.key"
                                            class="material-icons text-xs"
                                        >
                                            {{
                                                sortOrder === "asc"
                                                    ? "arrow_upward"
                                                    : "arrow_downward"
                                            }}
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                    <tbody class="divide-y" style="border-color: rgb(var(--border));">
                        <tr
                            v-for="(row, index) in paginatedData"
                            :key="index"
                            class="table-row"
                        >
                            <td class="px-2 py-1" style="color: rgb(var(--muted));">
                                {{
                                    (currentPage - 1) * itemsPerPage + index + 1
                                }}
                            </td>
                            <td class="px-2 py-1">
                                <input
                                    type="checkbox"
                                    :value="row"
                                    v-model="selectedRows"
                                    class="w-3 h-3"
                                />
                            </td>
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="table-cell px-2 py-1"
                            >
                                <input
                                    v-if="editable && !col.readonly"
                                    class="w-full text-xs rounded px-1 py-0.5 focus:outline-none focus:ring-1"
                                    :class="[
                                        isModified(row.__idx, col.key)
                                            ? 'border border-orange-300 bg-orange-50 focus:ring-orange-500'
                                            : 'border border-transparent',
                                    ]"
                                    :value="row[col.key] ?? ''"
                                    @input="
                                        emitEdit(
                                            row.__idx,
                                            col.key,
                                            $event.target.value,
                                        )
                                    "
                                />
                                <span v-else-if="col.format === 'date'">{{
                                    formatDate(row[col.key])
                                }}</span>
                                <span
                                    v-else-if="col.format === 'number'"
                                    class="font-medium"
                                    style="color: rgb(var(--foreground));"
                                    >{{ formatNumber(row[col.key]) }}</span
                                >
                                <span
                                    v-else-if="col.format === 'currency'"
                                    class="font-semibold"
                                    style="color: rgb(var(--foreground));"
                                    >{{ formatCurrency(row[col.key]) }}</span
                                >
                                <span v-else style="color: rgb(var(--foreground));">{{ row[col.key] ?? "-" }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
            <div
                v-if="data.length > 0"
                class="flex items-center justify-between px-3 py-2 rounded border text-xs"
                style="background: rgb(var(--surface-secondary)), border-color: rgb(var(--border));"
            >
                <div class="flex items-center gap-2">
                    <p style="color: rgb(var(--muted));">
                        {{ data.length }} résultats - Page {{ currentPage }}/{{
                            totalPages
                        }}
                    </p>
                </div>
                <div class="flex items-center gap-1">
                    <select
                        v-model="internalItemsPerPage"
                        class="text-xs border rounded px-1 py-0.5"
                        style="background: rgb(var(--surface)), border-color: rgb(var(--border)), color: rgb(var(--foreground));"
                    >
                    <option :value="5">5</option>
                    <option :value="10">10</option>
                    <option :value="20">20</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                    <option :value="-1">100%</option>
                </select>
                <button
                     @click="prevPage"
                     :disabled="currentPage === 1 || internalItemsPerPage === -1"
                     class="action-btn"
                     :class="
                         currentPage === 1 || internalItemsPerPage === -1
                             ? 'opacity-50 cursor-not-allowed'
                             : ''
                     "
                     >
                     Préc.
                 </button>
                 <button
                     @click="nextPage"
                     :disabled="
                         currentPage === totalPages ||
                         internalItemsPerPage === -1
                     "
                     class="action-btn"
                     :class="
                         currentPage === totalPages ||
                         internalItemsPerPage === -1
                             ? 'opacity-50 cursor-not-allowed'
                             : ''
                     "
                     >
                     Suiv.
                 </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";

const emit = defineEmits(["dataLoaded", "cell-edit"]);

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
    editable: {
        type: Boolean,
        default: false,
    },
    corrections: {
        type: Object,
        default: () => ({}),
    },
});

const isModified = (idx, colKey) =>
    props.corrections?.[idx]?.[colKey] !== undefined;

const emitEdit = (idx, colKey, value) => {
    if (idx === undefined || idx === null) return;
    const row = data.value.find((item) => item.__idx === idx);
    if (!row) return;
    row[colKey] = value;
    emit("cell-edit", { idx, colKey, value });
    emit("dataLoaded", data.value);
};

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
const filterQuery = ref("");
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
        const params = new URLSearchParams();
        if (filterDate.value) params.set("date", filterDate.value);
        if (filterQuery.value) {
            const normalizedQuery = String(filterQuery.value).trim();
            if (/^\d+$/.test(normalizedQuery)) {
                params.set("client_id", normalizedQuery);
            } else {
                params.set("q", normalizedQuery);
            }
        }

        const url = props.endpoint
            ? props.endpoint(filterDate.value, filterQuery.value)
            : params.toString()
              ? `/api/cdr_pp?${params.toString()}`
              : "/api/cdr_pp";
        console.debug("[DataTable] fetching", url);
        const response = await axios.get(url);
        const responseData = response.data || [];
        const rows = Array.isArray(responseData)
            ? responseData
            : [responseData];
        console.debug("[DataTable] fetched", rows.length, "rows for", url);
        const hasError =
            rows.length > 0 && rows[0] && rows[0].type === "Erreur";
        if (hasError) {
            error.value =
                rows[0].Description || "Erreur lors du chargement des données";
            data.value = [];
        } else {
            data.value = (Array.isArray(responseData) ? responseData : []).map(
                (row, index) => ({
                    ...row,
                    __idx: index,
                    ...(props.corrections?.[index] || {}),
                }),
            );
        }
        emit("dataLoaded", data.value);
        currentPage.value = 1;
    } catch (err) {
        console.error("DataTable fetch error", err);
        if (err.response && err.response.data) {
            // Try to show backend message
            error.value =
                typeof err.response.data === "string"
                    ? err.response.data
                    : err.response.data.message ||
                      "Erreur lors du chargement des données";
        } else {
            error.value =
                err.message || "Erreur lors du chargement des données";
        }
        data.value = [];
    } finally {
        loading.value = false;
    }
};

const clearFilter = () => {
    filterDate.value = "";
    filterQuery.value = "";
    fetchData();
};

const showAll = () => {
    filterDate.value = "";
    filterQuery.value = "";
    fetchData();
};

const exportToExcel = () => {
    if (!data.value.length) return;
    const XLSX = window.XLSX;
    const exportData = sortedData.value.map((row, idx) => {
        const obj = {
            "#": (currentPage.value - 1) * internalItemsPerPage.value + idx + 1,
        };
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
    return (
        data.value.length > 0 && selectedRows.value.length === data.value.length
    );
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
        if (valA < valB) return sortOrder.value === "asc" ? -1 : 1;
        if (valA > valB) return sortOrder.value === "asc" ? 1 : -1;
        return 0;
    });
});

const totalPages = computed(() => {
    if (internalItemsPerPage.value === -1) return 1;
    return Math.max(
        1,
        Math.ceil(sortedData.value.length / internalItemsPerPage.value),
    );
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
    if (
        currentPage.value < totalPages.value &&
        internalItemsPerPage.value !== -1
    ) {
        currentPage.value++;
    }
};

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = key;
        sortOrder.value = "asc";
    }
    currentPage.value = 1;
};

watch(internalItemsPerPage, (newVal) => {
    currentPage.value = 1;
});

onMounted(() => {
    fetchData();
});

defineExpose({
    selectedRows,
    fetchData,
});
</script>

<style scoped>
.date-input {
    @apply pl-6 pr-2 py-1 border border-slate-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none text-xs;
    background: rgb(var(--surface));
    color: rgb(var(--foreground));
}
.date-input::placeholder {
    color: rgb(var(--muted-foreground));
}
.table-header {
    @apply text-xs font-semibold uppercase tracking-wider;
    color: rgb(var(--muted));
}
.table-cell {
    @apply text-xs;
    color: rgb(var(--foreground));
}
.table-row:hover {
    background: rgb(var(--surface-secondary));
}
.action-btn {
    @apply px-2 py-0.5 rounded text-xs font-medium transition-all;
    border: 1px solid rgb(var(--border));
    color: rgb(var(--muted));
}
.action-btn:hover {
    background: rgb(var(--primary));
    color: #fff;
}
.action-btn-primary {
    @apply px-2 py-0.5 rounded text-xs font-medium text-white transition-all;
    background: rgb(var(--primary));
    border: none;
}
.action-btn-primary:hover {
    background: rgb(var(--primary-hover));
}
.action-btn-success {
    @apply px-2 py-0.5 rounded text-xs font-medium text-white transition-all;
    background: rgb(var(--success));
    border: none;
}
.action-btn-success:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
