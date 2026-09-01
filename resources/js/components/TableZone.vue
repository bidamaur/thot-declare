<template>
    <div
        class="premium-card overflow-hidden"
    >
        <div
            class="px-4 py-3 border-b flex items-center justify-between"
            style="border-color: rgb(var(--border));"
        >
            <div>
                <h2 class="text-sm font-semibold" style="color: rgb(var(--foreground));">
                    {{ title }}
                </h2>
                <p v-if="subtitle" class="text-xs mt-0.5" style="color: rgb(var(--muted));">
                    {{ subtitle }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Recherche rapide"
                        class="w-44 text-xs border rounded pl-7 pr-2 py-1 focus:outline-none focus:ring-1"
                        style="background: rgb(var(--surface)), border-color: rgb(var(--border)), color: rgb(var(--foreground));"
                    />
                    <span
                        class="absolute left-2 top-1/2 -translate-y-1/2 text-sm pointer-events-none"
                        style="color: rgb(var(--muted));"
                        >search</span
                    >
                </div>
                <span class="text-xs" style="color: rgb(var(--muted));">
                    {{ filteredData.length }} ligne(s)</span
                >
                <button
                    v-if="selectable"
                    @click="clearSelection"
                    class="action-btn"
                    title="Désélectionner toutes les lignes"
                >
                    Désélectionner tout
                </button>
                <button
                    v-if="exportable"
                    @click="exportToExcel"
                    class="action-btn-success"
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
                <p class="text-xs" style="color: rgb(var(--muted));">Chargement...</p>
            </div>
        </div>

        <div v-else-if="error" class="p-4">
            <div class="flex items-center gap-2 p-2 bg-red-50 rounded">
                <span class="material-icons text-red-600 text-sm">error</span>
                <p class="text-red-700 text-xs">{{ error }}</p>
            </div>
        </div>

        <div v-else-if="data.length === 0" class="p-8 text-center">
            <span class="material-icons text-2xl mb-1"
                >inbox</span
            >
            <p class="text-xs" style="color: rgb(var(--muted));">Aucune donnée.</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="table-header">
                    <tr style="border-bottom-color: rgb(var(--border));">
                        <th
                            v-if="selectable"
                            class="px-2 py-1 text-center font-semibold w-8"
                            style="color: rgb(var(--muted));"
                        >
                            <input
                                type="checkbox"
                                :checked="allPageSelected"
                                @change="toggleAllPage($event.target.checked)"
                                title="Tout sélectionner (page courante)"
                            />
                        </th>
                        <th
                            class="px-2 py-1 text-left font-semibold w-8"
                            style="color: rgb(var(--muted));"
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
                <tbody class="divide-y" style="division-color: rgb(var(--border));">
                    <tr
                        v-for="(row, index) in paginatedData"
                        :key="index"
                        class="table-row"
                    >
                        <td v-if="selectable" class="px-2 py-1 text-center">
                            <input
                                type="checkbox"
                                :checked="isSelected(row.__idx)"
                                @change="
                                    toggleRow(row.__idx, $event.target.checked)
                                "
                            />
                        </td>
                        <td class="px-2 py-1" style="color: rgb(var(--muted));">
                            {{ row.__idx !== undefined ? row.__idx + 1 : (props.startIndex + (currentPage - 1) * itemsPerPage + index + 1) }}
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
                                :value="row[col.key]"
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

        <div
            v-if="data.length > 0"
            class="flex items-center justify-between px-3 py-2 border-t text-xs"
            style="border-color: rgb(var(--border));"
        >
            <p style="color: rgb(var(--muted));">
                {{ data.length }} résultats - Page {{ currentPage }}/{{
                    totalPages
                }}
            </p>
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
    editable: { type: Boolean, default: false },
    selectable: { type: Boolean, default: false },
    corrections: { type: Object, default: () => ({}) },
    startIndex: { type: Number, default: 0 },
});

const emit = defineEmits(["cell-edit", "selection-change", "selection-clear"]);

const isModified = (idx, colKey) => {
    if (idx === undefined || idx === null || !colKey) return false;
    return !!(
        props.corrections &&
        props.corrections[idx] &&
        props.corrections[idx][colKey] !== undefined
    );
};

const emitEdit = (idx, colKey, value) => {
    if (idx === undefined || idx === null) return;
    emit("cell-edit", { idx, colKey, value });
};

const clearSelection = () => {
    selected.value = new Set();
    emit("selection-clear");
};

const searchQuery = ref("");
const normalizeText = (value) => {
    if (value === null || value === undefined) return "";
    return String(value)
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim();
};

const getSearchableText = (row) =>
    props.columns
        .map((col) => normalizeText(row[col.key]))
        .filter(Boolean)
        .join(" ");

const primaryKeyColumns = [
    "EVE",
    "CLI",
    "REFCONTCMPT",
    "REFINT",
    "AVE",
    "DVA",
    "DATEVE",
    "DATPAI",
];

const filteredData = computed(() => {
    const query = normalizeText(searchQuery.value);
    if (!query) return props.data;

    const terms = query.split(/\s+/).filter(Boolean);

    return props.data.filter((row) => {
        const primaryMatch = primaryKeyColumns.some((key) => {
            const value = normalizeText(row[key]);
            return value.includes(query);
        });

        const generalMatch = props.columns.some((col) => {
            const value = normalizeText(row[col.key]);
            if (!value) return false;
            return terms.every((term) => value.includes(term));
        });

        return primaryMatch || generalMatch;
    });
});

// --- Sélection de lignes (cochées par défaut) ---
// La sélection porte sur les lignes FILTRÉES par la recherche (filteredData),
// pas sur tout le dataset : ainsi, en recherchant un contrat, seules les lignes
// correspondantes restent sélectionnées pour l'export.
const selected = ref(new Set());

const isSelected = (idx) => selected.value.has(idx);
const emitSelection = () =>
    emit("selection-change", Array.from(selected.value));
const toggleRow = (idx, checked) => {
    if (checked) selected.value.add(idx);
    else selected.value.delete(idx);
    emitSelection();
};
const toggleAllPage = (checked) => {
    (props.data || []).forEach((row) => {
        if (checked) selected.value.add(row.__idx);
        else selected.value.delete(row.__idx);
    });
    emitSelection();
};
const allPageSelected = computed(() => {
    const rows = filteredData.value;
    if (!rows.length) return false;
    return rows.every((row) => selected.value.has(row.__idx));
});
// (Ré)initialise à "tout coché" uniquement quand le nombre de lignes du dataset
// change (nouveau chargement), afin de ne PAS écraser les décoches faites par
// l'utilisateur à chaque changement de recherche.
watch(
    () => (props.data || []).length,
    () => {
        selected.value = new Set();
        (props.data || []).forEach((r) => {
            if (r && r.__idx !== undefined) selected.value.add(r.__idx);
        });
        emitSelection();
    },
    { immediate: true },
);

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
        // La recherche (searchQuery) est conservée intentionally : elle ne doit
        // être effacée que par l'utilisateur lui-même (via l'input de recherche).
    },
);
</script>

<style scoped>
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
