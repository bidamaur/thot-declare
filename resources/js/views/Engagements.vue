<template>
  <div class="space-y-4">
    <div
      class="border border-slate-200 rounded-lg bg-white shadow-sm overflow-hidden"
    >
      <div
        class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between"
      >
        <h2 class="text-sm font-semibold text-slate-800">
          Engagements échus
        </h2>
        <span class="text-xs text-slate-500"
          >Période : {{ selectedDateDeb || "non définie" }} - {{ selectedDateArr || "non définie" }}</span
        >
      </div>
      <div class="p-4 flex flex-wrap items-end gap-3">
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1"
            >Date d'arrêté (MM-YYYY)</label
          >
          <input
            type="month"
            v-model="selectedDateArr"
            class="text-xs border border-slate-300 rounded px-2 py-1"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 mb-1"
            >Date de début (MM-YYYY)</label
          >
          <input
            type="month"
            v-model="selectedDateDeb"
            class="text-xs border border-slate-300 rounded px-2 py-1"
          />
        </div>
        <button
          @click="fetchEngagements"
          :disabled="loading"
          class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
        >
          Charger les engagements
        </button>
        <button
          @click="runSimpleControls"
          :disabled="!data.length || controlsLoading"
          class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50"
        >
          {{ controlsLoading ? "Contrôle en cours..." : "Contrôles simples" }}
        </button>
        <button
          @click="runComplexControl"
          :disabled="!data.length || complexLoading"
          class="px-3 py-1.5 text-xs bg-emerald-600 text-white rounded hover:bg-emerald-700 disabled:opacity-50"
        >
          {{ complexLoading ? "Contrôle en cours..." : "Contrôle complexe" }}
        </button>
        <button
          @click="clearCorrections"
          :disabled="!data.length"
          class="px-3 py-1.5 text-xs bg-amber-600 text-white rounded hover:bg-amber-700 disabled:opacity-50"
        >
Vider les corrections ({{ totalCorrections }})
         </button>
       </div>
     </div>

     <!-- Export XML CDR -->
     <div class="border border-slate-200 rounded-lg bg-white shadow-sm overflow-hidden">
       <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
         <h2 class="text-sm font-semibold text-slate-800">Export CDR - Type 51</h2>
         <span class="text-xs text-slate-500">NatDec : {{ xmlConfig.NatDec }} | Fichier : {{ expectedFilename }}</span>
       </div>
       <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
         <div>
           <label class="block text-xs font-medium text-slate-600 mb-1">NumDec</label>
           <input v-model="xmlConfig.NumDec" type="text" maxlength="10" class="w-full text-xs border border-slate-300 rounded px-2 py-1" />
         </div>
         <div>
           <label class="block text-xs font-medium text-slate-600 mb-1">CodPay</label>
           <select v-model="xmlConfig.CodPay" class="w-full text-xs border border-slate-300 rounded px-2 py-1">
             <option value="CM">CM</option><option value="CF">CF</option><option value="TD">TD</option><option value="CG">CG</option><option value="GA">GA</option><option value="GQ">GQ</option>
           </select>
         </div>
         <div>
           <label class="block text-xs font-medium text-slate-600 mb-1">CodDec</label>
           <input v-model="xmlConfig.CodDec" type="text" maxlength="10" class="w-full text-xs border border-slate-300 rounded px-2 py-1" />
         </div>
         <div>
           <label class="block text-xs font-medium text-slate-600 mb-1">NatDec</label>
           <select v-model="xmlConfig.NatDec" class="w-full text-xs border border-slate-300 rounded px-2 py-1">
             <option value="00">00 - Reprise</option><option value="01">01 - Création</option><option value="02">02 - Mise à jour</option><option value="03">03 - Clôture</option>
           </select>
         </div>
         <div>
           <label class="block text-xs font-medium text-slate-600 mb-1">DatArr (JJMMAAAA)</label>
           <input v-model="xmlConfig.DatArr" type="text" maxlength="8" class="w-full text-xs border border-slate-300 rounded px-2 py-1" />
         </div>
         <div>
           <label class="block text-xs font-medium text-slate-600 mb-1">Commentaire</label>
           <input v-model="xmlConfig.comment" type="text" class="w-full text-xs border border-slate-300 rounded px-2 py-1" />
         </div>
         <div class="flex items-end">
           <button @click="exportXml" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">Générer XML</button>
         </div>
       </div>
     </div>

     <!-- Progress bar for complex control -->
     <div class="p-4 border border-slate-200 rounded-lg bg-white" v-if="complexLoading">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-medium text-slate-600"
          >Progression du contrôle complexe</span
        >
        <span class="text-xs text-slate-500">{{ progress }}%</span>
      </div>
      <div class="w-full bg-slate-200 rounded-full h-2.5">
        <div
          class="bg-emerald-600 h-2.5 rounded-full transition-all duration-300"
          :style="{ width: progress + '%' }"
        ></div>
      </div>
      <p class="text-xs text-slate-500 mt-2">
        Comparaison des engagements de contrôle (CLI-EVE-AVE) en cours...
      </p>
    </div>

<TableZone
      v-if="data.length"
      title="Engagements"
      subtitle="Liste des engagements échus"
      :columns="columns"
      :data="data"
      :loading="loading"
      :error="error"
      :items-per-page="itemsPerPage"
      :editable="true"
      :selectable="true"
      :corrections="corrections"
      @selection-change="onSelectionChange"
      exportable
      export-name="engagements_echus"
      @cell-edit="onCellEdit"
    />

    <div
      v-else-if="!loading && !error && searched"
      class="p-8 text-center border border-dashed border-slate-300 rounded-lg bg-white"
    >
      <p class="text-xs text-slate-500">
        Aucune donnée trouvée pour cette période.
      </p>
    </div>

    <div
      v-else-if="!loading && !error && !searched"
      class="p-8 text-center border border-dashed border-slate-300 rounded-lg bg-white"
    >
      <p class="text-xs text-slate-500">
        Sélectionnez une période puis lancez le chargement pour afficher les engagements.
      </p>
    </div>

    <div
      v-if="simpleErrors.length || complexAnomalies.length"
      class="border border-slate-200 rounded-lg bg-white shadow-sm overflow-hidden"
    >
      <div
        class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between"
      >
        <h2 class="text-sm font-semibold text-slate-800">Anomalies</h2>
        <span class="text-xs text-slate-500"
          >{{ totalAnomalies }} anomalie(s) détectée(s)</span
        >
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-2 py-1 text-left font-semibold text-slate-600">Type</th>
              <th class="px-2 py-1 text-left font-semibold text-slate-600">Client</th>
              <th class="px-2 py-1 text-left font-semibold text-slate-600">Contrat</th>
              <th class="px-2 py-1 text-left font-semibold text-slate-600">Champ</th>
              <th class="px-2 py-1 text-left font-semibold text-slate-600">Code</th>
              <th class="px-2 py-1 text-left font-semibold text-slate-600">Message</th>
              <th class="px-2 py-1 text-left font-semibold text-slate-600">Valeur</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="(row, index) in paginatedAnomalies"
              :key="index"
              class="table-row"
            >
              <td class="px-2 py-1">
                <span
                  :class="[
                    row.type === 'erreur'
                      ? 'text-red-700 bg-red-50'
                      : 'text-amber-700 bg-amber-50',
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                  ]"
                >
                  {{ row.type }}
                </span>
              </td>
              <td class="px-2 py-1">{{ row.client }}</td>
              <td class="px-2 py-1">{{ row.contrat }}</td>
              <td class="px-2 py-1">{{ row.field }}</td>
              <td class="px-2 py-1 font-mono">{{ row.code }}</td>
              <td class="px-2 py-1">{{ row.message }}</td>
              <td class="px-2 py-1">{{ row.value }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        v-if="totalAnomalies > 0"
        class="flex items-center justify-between px-3 py-2 bg-white border-t border-slate-200 text-xs"
      >
        <p class="text-slate-500">
          {{ totalAnomalies }} résultat(s) - Page {{ anomalyCurrentPage }}/{{
            anomalyTotalPages
          }}
        </p>
        <div class="flex items-center gap-1">
          <select
            v-model="anomalyItemsPerPage"
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
            @click="anomalyPrevPage"
            :disabled="anomalyCurrentPage === 1 || anomalyItemsPerPage === -1"
            class="px-2 py-0.5 rounded border text-xs"
            :class="
              anomalyCurrentPage === 1 || anomalyItemsPerPage === -1
                ? 'opacity-50 cursor-not-allowed'
                : 'hover:bg-slate-100'
            "
          >
            Préc.
          </button>
          <button
            @click="anomalyNextPage"
            :disabled="
              anomalyCurrentPage === anomalyTotalPages ||
              anomalyItemsPerPage === -1
            "
            class="px-2 py-0.5 rounded border text-xs"
            :class="
              anomalyCurrentPage === anomalyTotalPages ||
              anomalyItemsPerPage === -1
                ? 'opacity-50 cursor-not-allowed'
                : 'hover:bg-slate-100'
            "
          >
            Suiv.
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="controlsRun && !simpleErrors.length && !complexAnomalies.length"
      class="p-4 text-center border border-slate-200 rounded-lg bg-white text-xs text-slate-600"
    >
      Contrôle terminé : aucune anomalie détectée.
    </div>

    <!-- Modal d'erreur -->
    <div
      v-if="showErrorModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click="showErrorModal = false"
    >
      <div
        class="bg-white rounded-lg shadow-xl max-w-sm w-full mx-4 p-4"
        @click.stop
      >
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-red-700">Erreur</h3>
          <button
            @click="showErrorModal = false"
            class="text-slate-400 hover:text-slate-600 text-lg leading-none"
          >
            &times;
          </button>
        </div>
        <p class="text-xs text-slate-600">{{ errorModalMessage }}</p>
        <div class="mt-4 flex justify-end">
          <button
            @click="showErrorModal = false"
            class="px-3 py-1.5 text-xs bg-slate-200 text-slate-700 rounded hover:bg-slate-300"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import axios from "axios";
import TableZone from "../components/TableZone.vue";
import { validerLigneEngagement, normaliserDateVersCdr } from "../validators/cdr_Engagement.js";
import { generateCdr51Xml, downloadCdr51Xml } from "../services/cdr51ExportService.js";

const selectedRows = ref(new Set());

const loading = ref(false);
const controlsLoading = ref(false);
const complexLoading = ref(false);
const progress = ref(0);
const error = ref(null);
const searched = ref(false);
const showErrorModal = ref(false);
const errorModalMessage = ref("");
const selectedDateArr = ref("");
const selectedDateDeb = ref("");
const itemsPerPage = ref(5);
const rawData = ref([]);
const corrections = ref({});

// --- Configuration de l'entête CDR (Type 51) & export XML ---
const xmlConfig = ref({
  NumDec: "0001",
  CodPay: "CM",
  CodDec: "10030",
  TypDec: "51",
  NatDec: "02",
  NbrDec: "",
  DatDec: "",
  DatArr: "",
  comment: "",
});

// Date d'arrêté calculée : dernier jour du mois/année sélectionné (JJMMAAAA)
const datArr = computed(() => {
  const yyyymm = selectedDateArr.value;
  if (!yyyymm) return "";
  const [y, m] = yyyymm.split("-").map(Number);
  if (!y || !m || m < 1 || m > 12) return "";
  const lastDay = new Date(y, m, 0).getDate();
  return `${String(lastDay).padStart(2, "0")}${String(m).padStart(2, "0")}${y}`;
});

const expectedFilename = computed(() => {
  const codPay = String(xmlConfig.value.CodPay || "CM").trim() || "CM";
  const codDec = String(xmlConfig.value.CodDec || "00000").trim() || "00000";
  const numDec = String(xmlConfig.value.NumDec || "0001").trim() || "0001";
  const typDec = "51";
  return `${codPay}-${codDec}-${numDec}-${datArr.value}-${typDec}-DEC.xml`;
});

const totalLignes = computed(() => rawData.value.length);

watch(
  () => selectedDateArr.value,
  () => {
    xmlConfig.value.DatArr = datArr.value;
  }
);

const onSelectionChange = (ids) => {
  selectedRows.value = new Set(ids);
};

const exportXml = () => {
  if (!rawData.value.length) {
    errorModalMessage.value = "Aucune donnée disponible. Veuillez d'abord charger les engagements.";
    showErrorModal.value = true;
    return;
  }
  const rowsToExport = selectedRows.value.size > 0
    ? rawData.value.filter((r) => selectedRows.value.has(r.__idx))
    : rawData.value;
  const result = generateCdr51Xml({
    engagements: rowsToExport.map((r) => ({ ...r })),
    encours: [],
    encoursAjust: [],
    xmlConfig: xmlConfig.value,
    selectedDate: selectedDateArr.value,
    includeGaranties: false,
    includeCompteDebiteur: false,
  });
  downloadCdr51Xml(result.xml, result.filename);
};

const simpleErrors = ref([]);
const complexAnomalies = ref([]);
const controlsRun = ref(false);

const columns = [
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

const data = computed(() => {
  return rawData.value.map((row, idx) => ({
    ...row,
    __idx: idx,
  }));
});

const totalCorrections = computed(() => {
  return Object.keys(corrections.value).reduce((acc, key) => {
    return acc + Object.keys(corrections.value[key] || {}).length;
  }, 0);
});

const loadCorrections = () => {
  try {
    const stored = localStorage.getItem("cdr51_corrections_v1");
    if (stored) {
      const parsed = JSON.parse(stored);
      corrections.value = parsed.engagements || {};
    }
  } catch (e) {
    /* ignore */
  }
};
const saveCorrections = () => {
  try {
    const payload = {
      engagements: corrections.value,
      encours: {},
      encoursAjust: {},
    };
    localStorage.setItem("cdr51_corrections_v1", JSON.stringify(payload));
  } catch (e) {
    /* ignore */
  }
};
const applyCorrections = (rows) => {
  return rows.map((row, i) => {
    const c = corrections.value[i];
    const merged = c ? { ...row, ...c } : { ...row };
    merged.__idx = i;
    return merged;
  });
};

const onCellEdit = ({ idx, colKey, value }) => {
  if (idx === undefined || idx === null) return;
  if (rawData.value[idx]) rawData.value[idx][colKey] = value;
  corrections.value[idx] = corrections.value[idx] || {};
  corrections.value[idx][colKey] = value;
  saveCorrections();
};
const clearCorrections = () => {
  corrections.value = {};
  rawData.value = rawData.value.map((r) => ({ ...r }));
  progress.value = 0;
  try {
    localStorage.removeItem("cdr51_corrections_v1");
  } catch (e) {
    /* ignore */
  }
};

const normalizeDate = (val) => {
  if (!val) return "";
  return normaliserDateVersCdr(val);
};

const rowToControlLine = (row) => {
  const get = (k) =>
    row[k] === undefined || row[k] === null ? "" : String(row[k]).trim();
  const getD = (k) => normalizeDate(get(k));
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
};

const runSimpleControls = () => {
  if (!data.value.length) return;
  controlsLoading.value = true;
  progress.value = 0;
  controlsRun.value = false;
  simpleErrors.value = [];
  error.value = null;
  try {
    data.value.forEach((row, idx) => {
      try {
        const line = rowToControlLine(row);
        const client = row.CLI ?? "";
        const contrat = row.REFINT ?? row.REFCONTCMPT ?? "";
        const res = validerLigneEngagement(line, { client, contrat });
        (res.erreurs || []).forEach((err) => {
          simpleErrors.value.push({
            type: "erreur",
            client: err.client || client,
            contrat: err.contrat || contrat,
            field: err.field || "",
            code: err.code,
            message: err.message,
            value: err.value ?? "",
            ligne: idx + 1,
          });
        });
        (res.avertissements || []).forEach((warn) => {
          simpleErrors.value.push({
            type: "avertissement",
            client: warn.client || client,
            contrat: warn.contrat || contrat,
            field: warn.field || "",
            code: warn.code,
            message: warn.message,
            value: warn.value ?? "",
            ligne: idx + 1,
          });
        });
      } catch (e) {
        simpleErrors.value.push({
          type: "erreur",
          client: row.CLI ?? "",
          contrat: row.REFINT ?? row.REFCONTCMPT ?? "",
          field: "ligne",
          code: "SYS_ERR",
          message: `Erreur interne lors de la validation de la ligne ${idx + 1}: ${e.message}`,
          value: "",
          ligne: idx + 1,
        });
      }
    });
  } catch (e) {
    simpleErrors.value = [];
    error.value = `Contrôle simple impossible : ${e.message}`;
  } finally {
    controlsLoading.value = false;
    controlsRun.value = true;
    progress.value = 0;
  }
};

const runComplexControl = async () => {
  if (!data.value.length) return;
  complexLoading.value = true;
  progress.value = 0;
  controlsRun.value = false;
  error.value = null;
  try {
    const dateArr = formatMonthForApi(selectedDateArr.value);
    const dateDeb = formatMonthForApi(selectedDateDeb.value);
    if (!dateArr || !dateDeb) {
      throw new Error('Veuillez choisir les deux dates.');
    }
    const res = await axios.get(
      `/api/cdr_ctrEngagements/compare/${dateArr}/${dateDeb}`
    );
    const payload = res.data;
    const anomalies = Array.isArray(payload.anomalies) ? payload.anomalies : [];
    complexAnomalies.value = anomalies;
    progress.value = 100;
  } catch (e) {
    complexAnomalies.value = [];
    progress.value = 0;
    error.value = `Contrôle complexe impossible : ${e.message}`;
  } finally {
    complexLoading.value = false;
    controlsRun.value = true;
  }
};

const allAnomalies = computed(() => {
  return [...simpleErrors.value, ...complexAnomalies.value];
});

const totalAnomalies = computed(() => allAnomalies.value.length);

const anomalyItemsPerPage = ref(5);
const anomalyCurrentPage = ref(1);

watch(
  () => [simpleErrors.value.length, complexAnomalies.value.length],
  () => {
    anomalyCurrentPage.value = 1;
  }
);

const anomalyTotalPages = computed(() => {
  if (anomalyItemsPerPage.value === -1) return 1;
  return Math.max(
    1,
    Math.ceil(allAnomalies.value.length / anomalyItemsPerPage.value)
  );
});

const paginatedAnomalies = computed(() => {
  if (anomalyItemsPerPage.value === -1) return allAnomalies.value;
  const start = (anomalyCurrentPage.value - 1) * anomalyItemsPerPage.value;
  return allAnomalies.value.slice(start, start + anomalyItemsPerPage.value);
});

const anomalyPrevPage = () => {
  if (anomalyCurrentPage.value > 1 && anomalyItemsPerPage.value !== -1)
    anomalyCurrentPage.value--;
};
const anomalyNextPage = () => {
  if (
    anomalyCurrentPage.value < anomalyTotalPages.value &&
    anomalyItemsPerPage.value !== -1
  )
    anomalyCurrentPage.value++;
};

const formatMonthForApi = (value) => {
  if (!value) return "";
  const [year, month] = value.split("-");
  return `${month}-${year}`;
};

const fetchEngagements = async () => {
  const dateArr = formatMonthForApi(selectedDateArr.value);
  const dateDeb = formatMonthForApi(selectedDateDeb.value);
  if (!dateArr || !dateDeb) {
    error.value = "Veuillez choisir les deux dates.";
    return;
  }
  error.value = null;
  loading.value = true;
  searched.value = true;
  simpleErrors.value = [];
  complexAnomalies.value = [];
  progress.value = 0;
  try {
    const res = await axios.get(
      `/api/cdr_engagements_echus/${dateArr}/${dateDeb}`
    );
    const payload = Array.isArray(res.data) ? res.data : [];
    const first = payload[0] || {};
    if (first.Erreur) {
      rawData.value = [];
      error.value =
        first.Erreur.Description || "Aucune donnée trouvée pour cette période.";
    } else {
      rawData.value = applyCorrections(payload);
      error.value = null;
    }
  } catch (e) {
    rawData.value = [];
    error.value = "Erreur lors du chargement des engagements.";
  } finally {
    loading.value = false;
  }
};

loadCorrections();
</script>
