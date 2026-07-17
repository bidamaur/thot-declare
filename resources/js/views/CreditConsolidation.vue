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
                <button
                    @click="runControleComplexe"
                    :disabled="complexLoading"
                    class="px-3 py-1.5 text-xs bg-emerald-600 text-white rounded hover:bg-emerald-700 disabled:opacity-50"
                >
                    {{ complexLoading ? "Contrôle en cours..." : "Contrôle Complexe" }}
                </button>
                <button
                    @click="clearCorrections"
                    class="px-3 py-1.5 text-xs bg-amber-600 text-white rounded hover:bg-amber-700"
                >
                    Vider les corrections ({{ totalCorrections }})
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
            <div class="px-4 py-3 border-t border-slate-200 bg-white flex flex-wrap gap-x-6 gap-y-2">
                <span class="text-xs font-semibold text-slate-600 self-center"
                    >Sections incluses :</span
                >
                <label class="inline-flex items-center gap-1 text-xs text-slate-600">
                    <input type="checkbox" v-model="includeOptions.engagements" />
                    Engagements
                </label>
                <label class="inline-flex items-center gap-1 text-xs text-slate-600">
                    <input type="checkbox" v-model="includeOptions.encours" />
                    Encours
                </label>
                <label class="inline-flex items-center gap-1 text-xs text-slate-600">
                    <input type="checkbox" v-model="includeOptions.encoursAjust" />
                    Encours ajustés
                </label>
                <label class="inline-flex items-center gap-1 text-xs text-slate-600">
                    <input type="checkbox" v-model="includeOptions.garanties" />
                    Garanties (GarantieAffectee)
                </label>
                <label class="inline-flex items-center gap-1 text-xs text-slate-600">
                    <input type="checkbox" v-model="includeOptions.compteDebiteur" />
                    Compte Débiteur
                </label>
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
            :data="effectiveData('engagements')"
            :loading="zones.engagements.loading"
            :error="zones.engagements.error"
            :items-per-page="10"
            :editable="true"
            @cell-edit="(p) => onCellEdit('engagements', p)"
            exportable
            export-name="engagements"
        />

        <!-- Zone Encours -->
        <TableZone
            title="Encours"
            subtitle="Suivi des encours de crédit par date d'arrêté"
            :columns="encoursColumns"
            :data="effectiveData('encours')"
            :loading="zones.encours.loading"
            :error="zones.encours.error"
            :items-per-page="10"
            :editable="true"
            @cell-edit="(p) => onCellEdit('encours', p)"
            exportable
            export-name="encours"
        />

        <!-- Zone Encours ajustés -->
        <TableZone
            title="Encours ajustés"
            subtitle="Encours créés pour ajustement (échéanciers flexibles)"
            :columns="encoursAjustColumns"
            :data="effectiveData('encoursAjust')"
            :loading="zones.encoursAjust.loading"
            :error="zones.encoursAjust.error"
            :items-per-page="10"
            :editable="true"
            @cell-edit="(p) => onCellEdit('encoursAjust', p)"
            exportable
            export-name="encours_ajustes"
        />

        <!-- Anomalies de contrôle complexe (Encours vs Engagements initiaux) -->
        <TableZone
            v-if="complexAnomalies.length || complexExecuted"
            title="Contrôle Complexe (Encours vs Engagements initiaux)"
            :subtitle="complexExecuted ? `${complexAnomalies.length} anomalie(s) détectée(s)` : 'Non exécuté'"
            :columns="complexAnomaliesColumns"
            :data="complexAnomalies"
            :items-per-page="10"
            exportable
            export-name="controle_complexe"
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
import { runComplexValidationFromApi } from "../validators/cdr_encours_engagement_ctrComplexe.js";
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
    NumDec: "0001",
    CodPay: "CM",
    CodDec: "10030",
    TypDec: "51",
    NatDec: "01",
    comment: "",
});

// Sections incluses dans le XML généré (par défaut : engagements + encours + ajustements)
const includeOptions = ref({
    engagements: true,
    encours: true,
    encoursAjust: true,
    garanties: false,
    compteDebiteur: false,
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
    () =>
        zones.engagements.data.length +
        zones.encours.data.length +
        zones.encoursAjust.data.length,
);

const exportXml = () => {
    const opts = includeOptions.value;
    const result = generateCdr51Xml({
        engagements: opts.engagements ? zones.engagements.data : [],
        encours: opts.encours ? zones.encours.data : [],
        encoursAjust: opts.encoursAjust ? zones.encoursAjust.data : [],
        xmlConfig: xmlConfig.value,
        selectedDate: selectedDate.value,
        includeGaranties: opts.garanties,
        includeCompteDebiteur: opts.compteDebiteur,
    });
    downloadCdr51Xml(result.xml, result.filename);
};

const zones = reactive({
    engagements: { data: [], raw: [], loading: false, error: null },
    encours: { data: [], raw: [], loading: false, error: null },
    encoursAjust: { data: [], raw: [], loading: false, error: null },
});

// --- Corrections manuelles (édition en place) persistées en localStorage ---
const CORRECTIONS_KEY = "cdr51_corrections_v1";
const corrections = reactive({ engagements: {}, encours: {}, encoursAjust: {} });

const loadCorrections = () => {
    try {
        const stored = localStorage.getItem(CORRECTIONS_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            (["engagements", "encours", "encoursAjust"]).forEach((z) => {
                corrections[z] = parsed[z] || {};
            });
        }
    } catch (e) {
        /* ignore */
    }
};
const saveCorrections = () => {
    try {
        localStorage.setItem(CORRECTIONS_KEY, JSON.stringify(corrections));
    } catch (e) {
        /* ignore */
    }
};
const applyCorrections = (raw, zone) => {
    const map = corrections[zone] || {};
    return raw.map((row, i) => {
        const c = map[i];
        const merged = c ? { ...row, ...c } : { ...row };
        merged.__idx = i;
        return merged;
    });
};
// Données effectives (brutes + corrections appliquées + index stable) pour l'affichage
const effectiveData = (zone) => applyCorrections(zones[zone].raw, zone);

const onCellEdit = (zone, { idx, colKey, value }) => {
    if (idx === undefined || idx === null) return;
    // Mise à jour immédiate des données utilisées pour l'export et les contrôles
    if (zones[zone].data[idx]) zones[zone].data[idx][colKey] = value;
    if (zones[zone].raw[idx]) zones[zone].raw[idx][colKey] = value;
    corrections[zone][idx] = corrections[zone][idx] || {};
    corrections[zone][idx][colKey] = value;
    saveCorrections();
};
const clearCorrections = () => {
    (["engagements", "encours", "encoursAjust"]).forEach((z) => {
        corrections[z] = {};
        zones[z].raw = zones[z].raw.map((r) => ({ ...r }));
        zones[z].data = applyCorrections(zones[z].raw, z);
    });
    try {
        localStorage.removeItem(CORRECTIONS_KEY);
    } catch (e) {
        /* ignore */
    }
};
const totalCorrections = computed(() => {
    let n = 0;
    (["engagements", "encours", "encoursAjust"]).forEach((z) => {
        n += Object.keys(corrections[z] || {}).length;
    });
    return n;
});
loadCorrections();

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
            const raw = normalize(res.data);
            zones[c.key].raw = raw.map((r) => ({ ...r }));
            zones[c.key].data = applyCorrections(raw, c.key);
            zones[c.key].error = null;
        } catch (e) {
            zones[c.key].data = [];
            zones[c.key].raw = [];
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
                fraDos: get("FRADOS"),
                fraAnnexe: get("FRAANNEXE"),
                DatEve: getD("DATEVE"),
            },
        };
    }
    return {
        Encours: {
            RefContCmpt: get("REFCONTCMPT"),
            DatEch: getD("DVA"),
            DatPai: getD("DATPAI"),
            MntPay: get("MNTPAY"),
            MntAgi: get("MNTAGI"),
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

// --- Contrôle complexe (Encours vs Engagements initiaux) ---
const complexLoading = ref(false);
const complexExecuted = ref(false);
const complexAnomalies = ref([]);

const complexAnomaliesColumns = [
    { key: "ligne", label: "Ligne" },
    { key: "client", label: "N° Client" },
    { key: "contrat", label: "Réf. Contrat" },
    { key: "type", label: "Type" },
    { key: "field", label: "Champ" },
    { key: "code", label: "Code" },
    { key: "message", label: "Message" },
    { key: "value", label: "Valeur actuelle" },
];

const runControleComplexe = async () => {
    if (!zones.encours.data.length) {
        globalError.value =
            "Veuillez d'abord charger les encours (Lancer le reporting).";
        return;
    }
    complexLoading.value = true;
    complexExecuted.value = true;
    globalError.value = null;
    try {
        complexAnomalies.value = await runComplexValidationFromApi(
            zones.encours.data,
        );
    } catch (e) {
        complexAnomalies.value = [];
        globalError.value = `Contrôle complexe impossible : ${e.message}`;
    } finally {
        complexLoading.value = false;
    }
};

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
