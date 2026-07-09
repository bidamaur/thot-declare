<template>
    <div class="space-y-4">
        <DataTable
            ref="dataTable"
            title="Clients Entreprises"
            subtitle="Liste des clients personnes morales enregistrés"
            :endpoint="fetchPm"
            :columns="columns"
            :enable-filters="true"
            filter-column="DATENTRELPAR"
            @data-loaded="onDataLoaded"
        />

        <div v-if="totalClients > 0" class="border border-slate-200 rounded-lg bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800">Export déclaration FRCB / CDR BEAC</h2>
                <span class="text-xs text-slate-500">{{ totalClients }} tiers à déclarer</span>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Numéro déclaration</label>
                    <input v-model="xmlConfig.NumDec" type="text" maxlength="10" class="w-full text-xs border border-slate-300 rounded px-2 py-1" placeholder="0001" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Code établissement</label>
                    <input v-model="xmlConfig.CodDec" type="text" maxlength="10" class="w-full text-xs border border-slate-300 rounded px-2 py-1" placeholder="10030" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nature déclaration</label>
                    <select v-model="xmlConfig.NatDec" class="w-full text-xs border border-slate-300 rounded px-2 py-1">
                        <option value="01">01 - Déclaration normale</option>
                        <option value="02">02 - Modification</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Commentaire</label>
                    <input v-model="xmlConfig.comment" type="text" class="w-full text-xs border border-slate-300 rounded px-2 py-1" placeholder="" />
                </div>
            </div>
            <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <p class="text-xs text-slate-500">Nom fichier : <span class="font-mono font-medium">{{ expectedFilename }}</span></p>
                <button @click="exportXml" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                    Générer XML
                </button>
            </div>
        </div>

        <div v-if="errorClients > 0" class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1">
                    <div class="border border-slate-200 rounded-lg bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Clients vérifiés</p>
                        <p class="text-2xl font-semibold text-slate-900 mt-1">{{ totalClients }}</p>
                    </div>
                    <div class="border border-emerald-200 rounded-lg bg-emerald-50 p-4 shadow-sm">
                        <p class="text-xs font-medium text-emerald-700 uppercase tracking-wider">Conformes</p>
                        <p class="text-2xl font-semibold text-emerald-900 mt-1">{{ validClients }}</p>
                    </div>
                    <div class="border border-red-200 rounded-lg bg-red-50 p-4 shadow-sm">
                        <p class="text-xs font-medium text-red-700 uppercase tracking-wider">Erreurs</p>
                        <p class="text-2xl font-semibold text-red-900 mt-1">{{ errorClients }}</p>
                    </div>
                </div>
                <button @click="exportAnomaliesToExcel" class="px-2 py-0.5 text-xs bg-emerald-100 rounded hover:bg-emerald-200 ml-3">
                  Excel
                </button>
            </div>

            <div class="border border-slate-200 rounded-lg bg-white shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-800">Anomalies détectées</h2>
                    <span class="text-xs text-slate-500">{{ errorClients }} résultat(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-600 w-8">#</th>
                                <th class="px-3 py-2 text-left cursor-pointer select-none" @click="errorSortBy('data.RAISOC')">
                                    <div class="flex items-center gap-1">
                                        <span>Raison Sociale</span>
                                        <span v-if="errorSortKey === 'data.RAISOC'" class="material-icons text-xs">
                                            {{ errorSortOrder === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
                                        </span>
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-left cursor-pointer select-none" @click="errorSortBy('data.IDINTCLI')">
                                    <div class="flex items-center gap-1">
                                        <span>N° Client</span>
                                        <span v-if="errorSortKey === 'data.IDINTCLI'" class="material-icons text-xs">
                                            {{ errorSortOrder === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
                                        </span>
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-left cursor-pointer select-none" @click="errorSortBy('errors[0].type')">
                                    <div class="flex items-center gap-1">
                                        <span>Type</span>
                                        <span v-if="errorSortKey === 'errors[0].type'" class="material-icons text-xs">
                                            {{ errorSortOrder === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
                                        </span>
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-left cursor-pointer select-none" @click="errorSortBy('errors[0].field')">
                                    <div class="flex items-center gap-1">
                                        <span>Champ</span>
                                        <span v-if="errorSortKey === 'errors[0].field'" class="material-icons text-xs">
                                            {{ errorSortOrder === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
                                        </span>
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-left cursor-pointer select-none" @click="errorSortBy('errors[0].currentValue')">
                                    <div class="flex items-center gap-1">
                                        <span>Valeur actuelle</span>
                                        <span v-if="errorSortKey === 'errors[0].currentValue'" class="material-icons text-xs">
                                            {{ errorSortOrder === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
                                        </span>
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-left cursor-pointer select-none" @click="errorSortBy('errors[0].message')">
                                    <div class="flex items-center gap-1">
                                        <span>Message d'erreur</span>
                                        <span v-if="errorSortKey === 'errors[0].message'" class="material-icons text-xs">
                                            {{ errorSortOrder === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
                                        </span>
                                    </div>
                                </th>
                                <th class="px-3 py-2 text-left cursor-pointer select-none" @click="errorSortBy('errors[0].code')">
                                    <div class="flex items-center gap-1">
                                        <span>Code</span>
                                        <span v-if="errorSortKey === 'errors[0].code'" class="material-icons text-xs">
                                            {{ errorSortOrder === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(item, idx) in paginatedInvalidClients" :key="item.data.IDINTCLI + '-' + idx" class="table-row">
                                <td class="px-3 py-2 text-slate-500">{{ (errorPage - 1) * errorPerPage + idx + 1 }}</td>
                                <td class="px-3 py-2">{{ item.data.RAISOC }}</td>
                                <td class="px-3 py-2">{{ item.data.IDINTCLI }}</td>
                                <td class="px-3 py-2">
                                    <span :class="item.errors[0].type === 'Erreur' ? 'text-red-700 font-medium' : 'text-amber-700 font-medium'">
                                        {{ item.errors[0].type }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 font-medium text-red-700">{{ item.errors[0].field }}</td>
                                <td class="px-3 py-2 font-mono text-red-700">{{ item.errors[0].currentValue }}</td>
                                <td class="px-3 py-2">{{ item.errors[0].message }}</td>
                                <td class="px-3 py-2 font-mono">{{ item.errors[0].code }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between px-3 py-2 bg-white border-t border-slate-200 text-xs">
                    <div class="flex items-center gap-2">
                        <p class="text-slate-500">
                            {{ errorClients }} résultats - Page {{ errorPage }}/{{ errorTotalPages }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1">
                        <select v-model="errorPerPage" class="text-xs border border-slate-300 rounded px-1 py-0.5">
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                            <option :value="-1">100%</option>
                        </select>
                        <button @click="errorPrevPage" :disabled="errorPage === 1 || errorPerPage === -1" class="px-2 py-0.5 rounded border text-xs" :class="errorPage === 1 || errorPerPage === -1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100'">
                            Préc.
                        </button>
                        <button @click="errorNextPage" :disabled="errorPage === errorTotalPages || errorPerPage === -1" class="px-2 py-0.5 rounded border text-xs" :class="errorPage === errorTotalPages || errorPerPage === -1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100'">
                            Suiv.
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showSelectionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full">
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-icons text-red-600 text-sm">error</span>
                    <h3 class="text-sm font-semibold text-slate-800">Aucune sélection</h3>
                </div>
                <p class="text-xs text-slate-600 mb-4">Veuillez sélectionner au moins un client dans le tableau avant de générer le fichier XML.</p>
                <div class="flex justify-end">
                    <button @click="showSelectionModal = false" class="px-3 py-1.5 text-xs bg-slate-200 rounded hover:bg-slate-300">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import DataTable from "../components/DataTable.vue";
import { validateAllPersonnesMorales } from "../validators/cdrPm.js";
import { generateFRCBXml, downloadFRCBXml } from "../services/frcbExportService.js";

const dataTable = ref(null);

const columns = [
    { key: "IDINTCLI", label: "ID Client" },
    { key: "NIF_NIU", label: "NIF/NIU" },
    { key: "RAISOC", label: "Raison Sociale" },
    { key: "SIGLE", label: "Sigle" },
    { key: "DATCRE", label: "Date Création", format: "date" },
    { key: "FORJURID", label: "Forme Juridique" },
    { key: "SECACT", label: "Secteur Activité" },
    { key: "AGEECO", label: "Code Activité" },
    { key: "RCCM", label: "RCCM" },
    { key: "STALEG", label: "Statut Légal" },
    { key: "RESIDENT", label: "Résident" },
    { key: "PAYSSIEGE", label: "Pays Siège" },
    { key: "REGION", label: "Région" },
    { key: "VILLE", label: "Ville" },
    { key: "TEL", label: "Téléphone" },
    { key: "SITJUD", label: "Sit. Jud" },
    { key: "DATENTRELPAR", label: "Entrée Parc", format: "date" },
    { key: "DATEVE", label: "Date Vér.", format: "date" },
    { key: "EMAIL", label: "Email" },
    { key: "ADRESSE", label: "Adresse" },
];

const fetchPm = (date = "") => {
    if (!date) {
        return "/api/cdr_pm";
    }
    return `/api/cdr_pm/${date}`;
};

const validationResults = ref([]);

const totalClients = computed(() => validationResults.value.length);
const validClients = computed(() => validationResults.value.filter((r) => r.isValid).length);
const errorClients = computed(() => validationResults.value.filter((r) => !r.isValid).length);
const invalidClients = computed(() => validationResults.value.filter((r) => !r.isValid));

const xmlConfig = ref({
    NumDec: "0001",
    CodDec: "10030",
    NatDec: "01",
    comment: "",
});

const today = new Date();
const dd = String(today.getDate()).padStart(2, "0");
const mm = String(today.getMonth() + 1).padStart(2, "0");
const yyyy = today.getFullYear();
const datDec = `${dd}${mm}${yyyy}`;

const expectedFilename = computed(() => {
    const numDec = String(xmlConfig.value.NumDec || "0001").trim() || "0001";
    const codDec = String(xmlConfig.value.CodDec || "00000").trim() || "00000";
    const natDec = String(xmlConfig.value.NatDec || "01").trim() || "01";
    return `CM-${codDec}-${numDec}-${datDec}-${natDec}-DEC.xml`;
});

const showSelectionModal = ref(false);

const exportXml = () => {
    const selected = dataTable.value?.selectedRows ?? [];
    if (!selected.length) {
        showSelectionModal.value = true;
        return;
    }
    const result = generateFRCBXml(selected, xmlConfig.value);
    downloadFRCBXml(result.xml, result.filename);
};

const errorPerPage = ref(20);
const errorPage = ref(1);
const errorSortKey = ref("");
const errorSortOrder = ref("asc");

const sortedInvalidClients = computed(() => {
    if (!errorSortKey.value) return invalidClients.value;
    return [...invalidClients.value].sort((a, b) => {
        const valA = errorSortKey.value.split('.').reduce((obj, key) => obj?.[key], a) ?? "";
        const valB = errorSortKey.value.split('.').reduce((obj, key) => obj?.[key], b) ?? "";
        if (valA < valB) return errorSortOrder.value === 'asc' ? -1 : 1;
        if (valA > valB) return errorSortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const errorTotalPages = computed(() => {
    if (errorPerPage.value === -1) return 1;
    return Math.max(1, Math.ceil(sortedInvalidClients.value.length / errorPerPage.value));
});

const paginatedInvalidClients = computed(() => {
    if (errorPerPage.value === -1) return sortedInvalidClients.value;
    const start = (errorPage.value - 1) * errorPerPage.value;
    return sortedInvalidClients.value.slice(start, start + errorPerPage.value);
});

const errorPrevPage = () => {
    if (errorPage.value > 1 && errorPerPage.value !== -1) {
        errorPage.value--;
    }
};

const errorNextPage = () => {
    if (errorPage.value < errorTotalPages.value && errorPerPage.value !== -1) {
        errorPage.value++;
    }
};

const errorSortBy = (key) => {
    if (errorSortKey.value === key) {
        errorSortOrder.value = errorSortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        errorSortKey.value = key;
        errorSortOrder.value = 'asc';
    }
    errorPage.value = 1;
};

watch(errorPerPage, () => {
    errorPage.value = 1;
});

watch(invalidClients, () => {
    errorPage.value = 1;
    errorSortKey.value = "";
    errorSortOrder.value = "asc";
});

const onDataLoaded = (dataArray) => {
    try {
        const results = validateAllPersonnesMorales(dataArray).map((result) => ({
            ...result,
            errors: result.errors.map((err) => ({
                ...err,
                currentValue: result.data[err.field] ?? '',
            })),
        }));
        validationResults.value = results;
    } catch (err) {
        console.error('Validation error:', err);
        validationResults.value = [];
    }
};

const exportAnomaliesToExcel = () => {
    if (!invalidClients.value.length) return;
    const XLSX = window.XLSX;
    const exportData = invalidClients.value.map((item) => ({
        "Raison Sociale": item.data.RAISOC,
        "N° Client": item.data.IDINTCLI,
        "Type": item.errors[0].type,
        "Champ": item.errors[0].field,
        "Valeur actuelle": item.errors[0].currentValue,
        "Message d'erreur": item.errors[0].message,
        "Code": item.errors[0].code,
    }));
    const worksheet = XLSX.utils.json_to_sheet(exportData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Anomalies");
    const wbout = XLSX.write(workbook, { bookType: "xlsx", type: "array" });
    const blob = new Blob([wbout], { type: "application/octet-stream" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `Anomalies_PM_${new Date().toISOString().slice(0, 10)}.xlsx`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
};
</script>

<style scoped>
.table-row:hover {
    background-color: #f8fafc;
}
</style>
