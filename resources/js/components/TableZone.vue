<template>
    <div
        class="border border-slate-200 rounded-lg bg-white shadow-sm overflow-hidden"
    >
        <div
            class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between"
        >
            <div>
                <h2 class="text-sm font-semibold text-slate-800">
                    {{ title }}
                </h2>
                <p v-if="subtitle" class="text-xs text-slate-500 mt-0.5">
                    {{ subtitle }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Recherche rapide"
                        class="w-44 text-xs border border-slate-300 rounded pl-7 pr-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    <span
                        class="material-icons absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-sm"
                        >search</span
                    >
                </div>
                <span class="text-xs text-slate-500"
                    >{{ filteredData.length }} ligne(s)</span
                >
                <button
                    v-if="exportable"
                    @click="exportToExcel"
                    class="px-2 py-0.5 text-xs bg-emerald-100 rounded hover:bg-emerald-200"
                >
                    Excel
                </button>
            </div>
        </div>

        <div v-if="loading" class="flex justify-center items-center py-8">
            <div class="flex flex-col items-center gap-2">
                <div
                    class="animate-spin rounded-full h-6 w-6 border-2 border-blue-600 border-t-transparent"
                ></div>
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
            <span class="material-icons text-2xl text-slate-300 mb-1"
                >inbox</span
            >
            <p class="text-slate-500 text-xs">Aucune donnée.</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th
                            class="px-2 py-1 text-left font-semibold text-slate-600 w-8"
                        >
                            #
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
                <tbody class="divide-y divide-slate-100">
                    <tr
                        v-for="(row, index) in paginatedData"
                        :key="index"
                        class="table-row"
                    >
                        <td class="px-2 py-1 text-slate-500">
                            {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                        </td>
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="table-cell px-2 py-1"
                        >
                            <span v-if="col.format === 'date'">{{
                                formatDate(row[col.key])
                            }}</span>
                            <span
                                v-else-if="col.format === 'number'"
                                class="font-medium text-slate-900"
                                >{{ formatNumber(row[col.key]) }}</span
                            >
                            <span v-else>{{ row[col.key] ?? "-" }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="data.length > 0"
            class="flex items-center justify-between px-3 py-2 bg-white border-t border-slate-200 text-xs"
        >
            <p class="text-slate-500">
                {{ data.length }} résultats - Page {{ currentPage }}/{{
                    totalPages
                }}
            </p>
            <div class="flex items-center gap-1">
                <select
                    v-model="internalItemsPerPage"
                    class="text-xs border border-slate-300 rounded px-1 py-0.5"
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
                    class="px-2 py-0.5 rounded border text-xs"
                    :class="
                        currentPage === 1 || internalItemsPerPage === -1
                            ? 'opacity-50 cursor-not-allowed'
                            : 'hover:bg-slate-100'
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
                    class="px-2 py-0.5 rounded border text-xs"
                    :class="
                        currentPage === totalPages ||
                        internalItemsPerPage === -1
                            ? 'opacity-50 cursor-not-allowed'
                            : 'hover:bg-slate-100'
                    "
                >
                    Suiv.
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: "" },
    columns: { type: Array, required: true },
    data: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
    itemsPerPage: { type: Number, default: 5 },
    exportable: { type: Boolean, default: false },
    exportName: { type: String, default: "" },
});

const exportToExcel = () => {
    if (!props.data.length) return;
    const XLSX = window.XLSX;
    if (!XLSX) return;
    const exportData = props.data.map((row) => {
        const obj = {};
        props.columns.forEach((col) => {
            obj[col.label] = row[col.key] ?? "";
        });
        return obj;
    });
    const worksheet = XLSX.utils.json_to_sheet(exportData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Donnees");
    const wbout = XLSX.write(workbook, { bookType: "xlsx", type: "array" });
    const blob = new Blob([wbout], { type: "application/octet-stream" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `${props.exportName || props.title || "export"}.xlsx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
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

const currentPage = ref(1);
const internalItemsPerPage = ref(props.itemsPerPage);
const sortKey = ref("");
const sortOrder = ref("asc");
const searchQuery = ref("");

const filteredData = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return props.data;

    return props.data.filter((row) =>
        props.columns.some((col) => {
            const value = row[col.key];
            if (value === null || value === undefined) return false;
            return String(value).toLowerCase().includes(query);
        }),
    );
});

const sortedData = computed(() => {
    if (!sortKey.value) return filteredData.value;
    return [...filteredData.value].sort((a, b) => {
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
    if (currentPage.value > 1 && internalItemsPerPage.value !== -1)
        currentPage.value--;
};
const nextPage = () => {
    if (
        currentPage.value < totalPages.value &&
        internalItemsPerPage.value !== -1
    )
        currentPage.value++;
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

watch(internalItemsPerPage, () => {
    currentPage.value = 1;
});
watch(searchQuery, () => {
    currentPage.value = 1;
});
watch(
    () => props.data,
    () => {
        currentPage.value = 1;
        sortKey.value = "";
        sortOrder.value = "asc";
        searchQuery.value = "";
    },
);
</script>

<style scoped>
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
