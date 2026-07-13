<template>
    <div class="space-y-4">
        <!-- Sélecteur de date d'arrêté commun -->
        <div
            class="border border-slate-200 rounded-lg bg-white shadow-sm overflow-hidden"
        >
            <div
                class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between"
            >
                <h2 class="text-sm font-semibold text-slate-800">
                    Reporting consolidé Crédit
                </h2>
                <span class="text-xs text-slate-500"
                    >Date d'arrêté : {{ selectedDate || "non définie" }}</span
                >
            </div>
            <div class="p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1"
                        >Date d'arrêté (mois / année)</label
                    >
                    <input
                        type="month"
                        v-model="selectedDate"
                        class="text-xs border border-slate-300 rounded px-2 py-1"
                    />
                </div>
                <button
                    @click="fetchAll"
                    class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    Lancer le reporting
                </button>
                <div class="flex-1"></div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="border border-slate-200 rounded-lg px-3 py-2">
                        <p
                            class="text-xs font-medium text-slate-500 uppercase tracking-wider"
                        >
                            Engagements
                        </p>
                        <p class="text-lg font-semibold text-slate-900">
                            {{ zones.engagements.data.length }}
                        </p>
                    </div>
                    <div class="border border-slate-200 rounded-lg px-3 py-2">
                        <p
                            class="text-xs font-medium text-slate-500 uppercase tracking-wider"
                        >
                            Encours
                        </p>
                        <p class="text-lg font-semibold text-slate-900">
                            {{ zones.encours.data.length }}
                        </p>
                    </div>
                    <div class="border border-slate-200 rounded-lg px-3 py-2">
                        <p
                            class="text-xs font-medium text-slate-500 uppercase tracking-wider"
                        >
                            Encours ajustés
                        </p>
                        <p class="text-lg font-semibold text-slate-900">
                            {{ zones.encoursAjust.data.length }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone de configuration de l'entête + Export XML CDR (Type 51) -->
        <div
            class="border border-slate-200 rounded-lg bg-white shadow-sm overflow-hidden"
        >
            <div
                class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between"
            >
                <h2 class="text-sm font-semibold text-slate-800">
                    Export déclaration CDR - Crédits (Type 51)
                </h2>
                <span class="text-xs text-slate-500"
                    >Date d'arrêté calculée : {{ datArr }}</span
                >
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1"
                        >Numéro déclaration (NumDec)</label
                    >
                    <input
                        v-model="xmlConfig.NumDec"
                        type="text"
                        maxlength="10"
                        class="w-full text-xs border border-slate-300 rounded px-2 py-1"
                        placeholder="1111"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1"
                        >Code pays (CodPay)</label
                    >
                    <select
                        v-model="xmlConfig.CodPay"
                        class="w-full text-xs border border-slate-300 rounded px-2 py-1"
                    >
                        <option value="CM">CM - Cameroun</option>
                        <option value="CF">CF - Rép. Centrafricaine</option>
                        <option value="TD">TD - Tchad</option>
                        <option value="CG">CG - Congo</option>
                        <option value="GA">GA - Gabon</option>
                        <option value="GQ">GQ - Guinée Équatoriale</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1"
                        >Code déclarant (CodDec)</label
                    >
                    <input
                        v-model="xmlConfig.CodDec"
                        type="text"
                        maxlength="10"
                        class="w-full text-xs border border-slate-300 rounded px-2 py-1"
                        placeholder="20009"
                    />
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1"
                        >Type déclaration (TypDec)</label
                    >
                    <select
                        v-model="xmlConfig.TypDec"
                        disabled
                        class="w-full text-xs border border-slate-300 rounded px-2 py-1 bg-slate-100 text-slate-500 cursor-not-allowed"
                    >
                        <option value="51">51 - Crédits (Type 51)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1"
                        >Nature déclaration (NatDec)</label
                    >
                    <select
                        v-model="xmlConfig.NatDec"
                        class="w-full text-xs border border-slate-300 rounded px-2 py-1"
                    >
                        <option value="00">00 - Reprise d'historique</option>
                        <option value="01">01 - Création</option>
                        <option value="02">02 - Modification</option>
                        <option value="03">03 - Clôture</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1"
                        >Commentaire (optionnel)</label
                    >
                    <input
                        v-model="xmlConfig.comment"
                        type="text"
                        class="w-full text-xs border border-slate-300 rounded px-2 py-1"
                        placeholder=""
                    />
                </div>
            </div>
            <div
                class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between"
            >
                <p class="text-xs text-slate-500">
                    Nom fichier :
                    <span class="font-mono font-medium">{{ expectedFilename }}</span>
                    <span class="ml-2 text-slate-400"
                        >({{ totalLignes }} ligne(s) déclarée(s))</span
                    >
                </p>
                <button
                    @click="exportXml"
                    class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                    Générer XML
                </button>
            </div>
        </div>

        <p v-if="globalError" class="text-xs text-red-600">{{ globalError }}</p>

        <div
            v-if="isLoadingRoutes"
            class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 shadow-sm"
        >
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="text-xs font-semibold text-blue-700">
                        Chargement des données
                    </p>
                    <p class="text-[11px] text-blue-600">
                        {{ completedRoutes }}/{{ totalRoutes }} routes terminées
                    </p>
                </div>
                <span class="text-xs font-semibold text-blue-700"
                    >{{ progressPercent }}%</span
                >
            </div>
            <div class="w-full h-2 rounded-full bg-blue-100 overflow-hidden">
                <div
                    class="h-2 rounded-full bg-blue-600 transition-all duration-300"
                    :style="{ width: `${progressPercent}%` }"
                ></div>
            </div>
        </div>

        <!-- Zone Engagements -->
        <TableZone
            title="Engagements"
            subtitle="Liste des engagements de crédit déclarés"
            :columns="engagementsColumns"
            :data="zones.engagements.data"
            :loading="zones.engagements.loading"
            :error="zones.engagements.error"
            :items-per-page="10"
            exportable
            export-name="engagements"
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
            exportable
            export-name="encours"
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
            exportable
            export-name="encours_ajustes"
        />

        <!-- Anomalies de contrôle (une ligne par anomalie) -->
        <TableZone
            title="Anomalies de contrôle (CDR Encours / Engagements)"
            :subtitle="`${totalControlErrors} anomalie(s) sur ${totalControlRows} ligne(s) contrôlée(s)`"
            :columns="anomaliesColumns"
            :data="anomalies"
            :items-per-page="10"
            exportable
            export-name="anomalies_controle"
        />
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import axios from "axios";
import TableZone from "../components/TableZone.vue";
import {
    validerLigneCdr,
    normaliserDateVersCdr,
} from "../validators/cdr_encours_engagement.js";
import { generateCdr51Xml, downloadCdr51Xml } from "../services/cdr51ExportService.js";

const now = new Date();
const selectedDate = ref(
    `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`,
);
const globalError = ref(null);
const loadingProgress = ref(0);
const completedRoutes = ref(0);
const totalRoutes = ref(3);

const isLoadingRoutes = computed(
    () =>
        completedRoutes.value > 0 && completedRoutes.value < totalRoutes.value,
);
const progressPercent = computed(() =>
    Math.round((completedRoutes.value / totalRoutes.value) * 100),
);

// --- Configuration de l'entête CDR (Type 51) & export XML ---
const xmlConfig = ref({
    NumDec: "1111",
    CodPay: "CF",
    CodDec: "20009",
    TypDec: "51",
    NatDec: "00",
    comment: "",
});

// Date d'arrêté : dernier jour du mois/année sélectionné (JJMMAAAA)
const datArr = computed(() => {
    const yyyymm = selectedDate.value;
    if (!yyyymm) return "";
    const [y, m] = yyyymm.split("-").map(Number);
    const lastDay = new Date(y, m, 0).getDate();
    return `${String(lastDay).padStart(2, "0")}${String(m).padStart(2, "0")}${y}`;
});

// Nomenclature : CodePays-CodeDéclarant-NumDéclaration-DateArrêté-TypeDéclaration-TypeFichier.xml
const expectedFilename = computed(() => {
    const codPay = String(xmlConfig.value.CodPay || "CF").trim() || "CF";
    const codDec = String(xmlConfig.value.CodDec || "00000").trim() || "00000";
    const numDec = String(xmlConfig.value.NumDec || "0001").trim() || "0001";
    const typDec = "51";
    return `${codPay}-${codDec}-${numDec}-${datArr.value}-${typDec}-DEC.xml`;
});

const totalLignes = computed(
    () => zones.encours.data.length + zones.encoursAjust.data.length,
);

const exportXml = () => {
    const result = generateCdr51Xml({
        engagements: zones.engagements.data,
        encours: zones.encours.data,
        encoursAjust: zones.encoursAjust.data,
        xmlConfig: xmlConfig.value,
        selectedDate: selectedDate.value,
    });
    downloadCdr51Xml(result.xml, result.filename);
};

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
    completedRoutes.value = 0;
    loadingProgress.value = 0;
    const bd = toBackendDate(selectedDate.value);
    const calls = [
        { key: "engagements", url: `/api/cdr_engagements/${bd}` },
        { key: "encours", url: `/api/cdr_encours/${bd}` },
        { key: "encoursAjust", url: `/api/cdr_encours_ajust/${bd}` },
    ];

    const runCall = async (c) => {
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
            completedRoutes.value += 1;
            loadingProgress.value = Math.round(
                (completedRoutes.value / totalRoutes.value) * 100,
            );
        }
    };

    await Promise.all(calls.map(runCall));
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

// --- Normalisation des dates JSON vers le format JJMMAAAA attendu par le kit CDR ---
const normalizeDate = (val) => {
    if (!val) return "";
    return normaliserDateVersCdr(val);
};

// --- Mapping des lignes plates vers la structure attendue par le validateur CDR ---
const rowToControlLine = (row, type) => {
    const get = (k) =>
        row[k] === undefined || row[k] === null ? "" : String(row[k]).trim();
    const getD = (k) => normalizeDate(get(k));
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
                DatMep: getD("DATMEP"),
                TxInt: get("TXINT"),
                TxComm: get("TXCOMM"),
                TxEffGlob: get("TXEFFGLOB"),
                TypTxInt: get("TYPTXINT"),
                IndRef: get("INDREF"),
                Sprd: get("SPRD"),
                DatDeb: getD("DATDEB"),
                DatFin: getD("DATFIN"),
                Periodicite: get("PERIODICITE"),
                UnitDur: get("UNITDUR"),
                Duree: get("DUREE"),
                Maturite: get("MATURITE"),
                DatPreEchCap: getD("DATPREECHCAP"),
                NbrEch: get("NBRECH"),
                MntEch: get("MNTECH"),
                TypEch: get("TYECH"),
                TypAmo: get("TYAMO"),
                TotInt: get("TOTINT"),
                DatEve: getD("DATEVE"),
            },
        };
    }
    return {
        Encours: {
            RefContCmpt: get("REFCONTCMPT"),
            DatEch: getD("DVA"),
            DatPai: getD("DATPAI"),
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
        zones.encoursAjust.data.length,
);

const anomaliesColumns = [
    { key: "client", label: "N° Client" },
    { key: "contrat", label: "Réf. Contrat" },
    { key: "field", label: "Champ" },
    { key: "code", label: "Code" },
    { key: "message", label: "Message" },
    { key: "value", label: "Valeur actuelle" },
];

const anomalies = computed(() => {
    const rows = [];
    const addRow = (row, type) => {
        const client = row.CLI ?? "";
        const contrat = row.REFINT ?? row.REFCONTCMPT ?? "";
        const line = rowToControlLine(row, type);
        let res;
        try {
            res = validerLigneCdr(line, { client, contrat });
        } catch (e) {
            return;
        }
        (res.erreurs || []).forEach((err) => {
            rows.push({
                client: err.client || client,
                contrat: err.contrat || contrat,
                field: err.field || "",
                code: err.code,
                message: err.message,
                value: err.value ?? "",
            });
        });
    };
    zones.engagements.data.forEach((r) => addRow(r, "engagement"));
    zones.encours.data.forEach((r) => addRow(r, "encours"));
    zones.encoursAjust.data.forEach((r) => addRow(r, "encours"));
    return rows;
});

const totalControlErrors = computed(() => anomalies.value.length);
</script>

<style scoped>
.table-row:hover {
    background-color: #f8fafc;
}
</style>
