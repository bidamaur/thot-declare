<template>
    <div class="space-y-4">
        <!-- Sélecteur de date d'arrêté commun -->
        <div class="border border-slate-200 rounded-lg bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800">Reporting consolidé Crédit</h2>
                <span class="text-xs text-slate-500">Date d'arrêté : {{ selectedDate || 'non définie' }}</span>
            </div>
            <div class="p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Date d'arrêté (mois / année)</label>
                    <input type="month" v-model="selectedDate" class="text-xs border border-slate-300 rounded px-2 py-1" />
                </div>
                <button @click="fetchAll" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                    Lancer le reporting
                </button>
                <div class="flex-1"></div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="border border-slate-200 rounded-lg px-3 py-2">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Engagements</p>
                        <p class="text-lg font-semibold text-slate-900">{{ zones.engagements.data.length }}</p>
                    </div>
                    <div class="border border-slate-200 rounded-lg px-3 py-2">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Encours</p>
                        <p class="text-lg font-semibold text-slate-900">{{ zones.encours.data.length }}</p>
                    </div>
                    <div class="border border-slate-200 rounded-lg px-3 py-2">
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Encours ajustés</p>
                        <p class="text-lg font-semibold text-slate-900">{{ zones.encoursAjust.data.length }}</p>
                    </div>
                </div>
            </div>
        </div>

        <p v-if="globalError" class="text-xs text-red-600">{{ globalError }}</p>

        <!-- Zone Engagements -->
        <TableZone
            title="Engagements"
            subtitle="Liste des engagements de crédit déclarés"
            :columns="engagementsColumns"
            :data="zones.engagements.data"
            :loading="zones.engagements.loading"
            :error="zones.engagements.error"
            :items-per-page="10"
        />

        <!-- Zone Encours -->
        <TableZone
            title="Encours"
            subtitle="Suivi des encours de crédit par date d'arrêté"
            :columns="encoursColumns"
            :data="zones.encours.data"
            :loading="zones.encours.loading"
            :error="zones.encours.error"
            :items-per-page="10"
        />

        <!-- Zone Encours ajustés -->
        <TableZone
            title="Encours ajustés"
            subtitle="Encours créés pour ajustement (échéanciers flexibles)"
            :columns="encoursAjustColumns"
            :data="zones.encoursAjust.data"
            :loading="zones.encoursAjust.loading"
            :error="zones.encoursAjust.error"
            :items-per-page="10"
        />

        <!-- Statistiques de contrôle -->
        <div class="border border-slate-200 rounded-lg bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800">Statistiques de contrôle (CDR Encours / Engagements)</h2>
                <span class="text-xs text-slate-500">
                    {{ totalControlErrors }} anomalie(s) sur {{ totalControlRows }} ligne(s) contrôlée(s)
                </span>
            </div>
            <div v-if="controlStats.length === 0" class="p-6 text-center text-xs text-slate-500">
                Aucune anomalie détectée par le référentiel de contrôle.
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-600">Code</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-600">Libellé</th>
                            <th class="px-3 py-2 text-right font-semibold text-slate-600 w-24">Occurrences</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(s, idx) in controlStats" :key="s.code + '-' + idx" class="table-row">
                            <td class="px-3 py-2 font-mono text-red-700">{{ s.code }}</td>
                            <td class="px-3 py-2">{{ s.message }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-slate-900">{{ s.count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import axios from "axios";
import TableZone from "../components/TableZone.vue";
import { validerLigneCdr } from "../validators/cdr_encours_engagement.js";

const now = new Date();
const selectedDate = ref(`${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`);
const globalError = ref(null);

const zones = reactive({
    engagements: { data: [], loading: false, error: null },
    encours: { data: [], loading: false, error: null },
    encoursAjust: { data: [], loading: false, error: null },
});

const toBackendDate = (yyyymm) => {
    if (!yyyymm) return "";
    const [y, m] = yyyymm.split("-");
    return `${m}-${y}`;
};

const normalize = (arr) => {
    if (!Array.isArray(arr)) return [];
    if (arr.length && arr[0] && arr[0].Erreur) return [];
    return arr;
};

const fetchAll = async () => {
    if (!selectedDate.value) {
        globalError.value = "Veuillez choisir une date d'arrêté.";
        return;
    }
    globalError.value = null;
    const bd = toBackendDate(selectedDate.value);
    const calls = [
        { key: "engagements", url: `/api/cdr_engagements/${bd}` },
        { key: "encours", url: `/api/cdr_encours/${bd}` },
        { key: "encoursAjust", url: `/api/cdr_encours_ajust/${bd}` },
    ];
    await Promise.all(
        calls.map(async (c) => {
            zones[c.key].loading = true;
            try {
                const res = await axios.get(c.url);
                zones[c.key].data = normalize(res.data);
                zones[c.key].error = null;
            } catch (e) {
                zones[c.key].data = [];
                zones[c.key].error = "Erreur lors du chargement des données.";
            } finally {
                zones[c.key].loading = false;
            }
        })
    );
};

onMounted(fetchAll);

const engagementsColumns = [
    { key: "CLI", label: "Client" },
    { key: "EVE", label: "Événement" },
    { key: "REFINT", label: "Réf. Interne" },
    { key: "CODAGE", label: "Code Agence" },
    { key: "STATUT", label: "Statut" },
    { key: "TYPENG", label: "Type Eng." },
    { key: "NATENG", label: "Nature Eng." },
    { key: "MNTENG", label: "Montant Eng.", format: "number" },
    { key: "DATMEP", label: "Mise en place", format: "date" },
    { key: "TXINT", label: "Taux Intérêt" },
    { key: "TXEFFGLOB", label: "Taux Eff. Global" },
    { key: "DATDEB", label: "Date Début", format: "date" },
    { key: "DATFIN", label: "Date Fin", format: "date" },
    { key: "DUREE", label: "Durée" },
    { key: "PERIODICITE", label: "Périodicité" },
    { key: "NBRECH", label: "Nb Échéances" },
    { key: "MNTECH", label: "Montant Échéance", format: "number" },
    { key: "MATURITE", label: "Maturité" },
    { key: "DATEVE", label: "Date Evénement", format: "date" },
];

const encoursColumns = [
    { key: "EVE", label: "Événement" },
    { key: "CLI", label: "Client" },
    { key: "DVA", label: "Date Échéance", format: "date" },
    { key: "REFCONTCMPT", label: "Réf Contrat" },
    { key: "DATPAI", label: "Date Paiement", format: "date" },
    { key: "MNTCRD", label: "Montant Crédit", format: "number" },
    { key: "MNTTOTUTIL", label: "Montant Tot. Utilisé", format: "number" },
    { key: "NBRECHPAY", label: "Échéances Payées" },
    { key: "NBRECHIMP", label: "Échéances Impayées" },
    { key: "NBRECHRES", label: "Échéances Restantes" },
    { key: "CLADEPREC", label: "Classe Dépréciation" },
];

const encoursAjustColumns = [
    { key: "EVE", label: "Événement" },
    { key: "CLI", label: "Client" },
    { key: "DVA", label: "Date Valeur", format: "date" },
    { key: "REFCONTCMPT", label: "Réf Contrat" },
    { key: "MNTCRD", label: "Montant Crédit", format: "number" },
    { key: "MNTPAY", label: "Montant Payé", format: "number" },
    { key: "NBRECHPAY", label: "Échéances Payées" },
    { key: "NBRECHIMP", label: "Échéances Impayées" },
    { key: "NBRECHRES", label: "Échéances Restantes" },
    { key: "MNTTOTUTIL", label: "Montant Tot. Utilisé", format: "number" },
    { key: "CLADEPREC", label: "Classe Dépréciation" },
];

// --- Mapping des lignes plates vers la structure attendue par le validateur CDR ---
const rowToControlLine = (row, type) => {
    const get = (k) =>
        row[k] === undefined || row[k] === null ? "" : String(row[k]).trim();
    if (type === "engagement") {
        return {
            Engagement: {
                RefContCmpt: get("REFCONTCMPT"),
                CodAge: get("CODAGE"),
                Statut: get("STATUT"),
                NatConso: get("NATCONSO"),
                TypConso: get("TYPCONSO"),
                Motif: get("MOTIF"),
                TypEng: get("TYPENG"),
                NatEng: get("NATENG"),
                CodDev: get("CODDEV"),
                MntEng: get("MNTENG"),
                MntCrCedee: get("MNTCRCEDEE"),
                MntEpargne: get("MNTEPARGNE"),
                ModRembEpargne: get("MODREMBEPARGNE"),
                TauxRenum: get("TAUXRENUM"),
                DatMep: get("DATMEP"),
                TxInt: get("TXINT"),
                TxComm: get("TXCOMM"),
                TxEffGlob: get("TXEFFGLOB"),
                TypTxInt: get("TYPTXINT"),
                IndRef: get("INDREF"),
                Sprd: get("SPRD"),
                DatDeb: get("DATDEB"),
                DatFin: get("DATFIN"),
                Periodicite: get("PERIODICITE"),
                UnitDur: get("UNITDUR"),
                Duree: get("DUREE"),
                Maturite: get("MATURITE"),
                DatPreEchCap: get("DATPREECHCAP"),
                NbrEch: get("NBRECH"),
                MntEch: get("MNTECH"),
                TypEch: get("TYECH"),
                TypAmo: get("TYAMO"),
                TotInt: get("TOTINT"),
                DatEve: get("DATEVE"),
            },
        };
    }
    return {
        Encours: {
            RefContCmpt: get("REFCONTCMPT"),
            DatEch: get("DVA"),
            DatPai: get("DATPAI"),
            MntTotUtil: get("MNTTOTUTIL"),
            MntCrd: get("MNTCRD"),
            nbrEchPay: get("NBRECHPAY"),
            nbrEchImp: get("NBRECHIMP"),
            nbrEchRes: get("NBRECHRES"),
            MntCreRat: get("MNTCRERAT"),
            MntCreSouf: get("MNTCRESOUF"),
            nbrJrsImp: get("NBRJRSIMP"),
            ClaDeprec: get("CLADEPREC"),
        },
    };
};

const totalControlRows = computed(
    () =>
        zones.engagements.data.length +
        zones.encours.data.length +
        zones.encoursAjust.data.length
);

const controlStats = computed(() => {
    const tally = {};
    const addRow = (row, type) => {
        const line = rowToControlLine(row, type);
        let res;
        try {
            res = validerLigneCdr(line);
        } catch (e) {
            return;
        }
        (res.erreurs || []).forEach((err) => {
            if (!tally[err.code]) {
                tally[err.code] = { code: err.code, message: err.message, count: 0 };
            }
            tally[err.code].count++;
        });
    };
    zones.engagements.data.forEach((r) => addRow(r, "engagement"));
    zones.encours.data.forEach((r) => addRow(r, "encours"));
    zones.encoursAjust.data.forEach((r) => addRow(r, "encours"));
    return Object.values(tally).sort((a, b) => b.count - a.count);
});

const totalControlErrors = computed(() =>
    controlStats.value.reduce((s, x) => s + x.count, 0)
);
</script>

<style scoped>
.table-row:hover {
    background-color: #f8fafc;
}
</style>
