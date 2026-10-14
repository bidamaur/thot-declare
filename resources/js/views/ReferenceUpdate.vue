<template>
    <div class="space-y-4">
        <section
            class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
        >
            <div
                class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-4 py-3"
            >
                <div>
                    <h1 class="text-sm font-semibold text-slate-800">
                        MAJ reference
                    </h1>
                    <p class="mt-1 text-xs text-slate-500">
                        Mise à jour des identifiants clients uniquement; cette
                        déclaration ne concerne pas les engagements.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="!canGenerate"
                    @click="downloadXml"
                >
                    <span class="material-icons text-base">download</span>
                    Générer le fichier XML
                </button>
            </div>
            <div
                class="grid grid-cols-2 gap-x-6 gap-y-3 p-4 text-xs sm:grid-cols-4 lg:grid-cols-8"
            >
                <div
                    v-for="field in headerFields"
                    :key="field.label"
                    class="min-w-0"
                >
                    <p class="text-slate-500">{{ field.label }}</p>
                    <p
                        class="mt-1 truncate font-semibold text-slate-800"
                        :title="field.value"
                    >
                        {{ field.value || "vide" }}
                    </p>
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3"
            >
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">
                        Paramètres des identifiants
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ activeRows.length }} ligne(s) prête(s) pour l'export
                    </p>
                </div>
                <div
                    class="inline-flex rounded border border-slate-200 bg-slate-50 p-1"
                    role="tablist"
                    aria-label="Mode de saisie"
                >
                    <button
                        v-for="mode in inputModes"
                        :key="mode.value"
                        type="button"
                        role="tab"
                        :aria-selected="inputMode === mode.value"
                        class="rounded px-3 py-1.5 text-xs font-medium transition"
                        :class="
                            inputMode === mode.value
                                ? 'bg-white text-slate-900 shadow-sm'
                                : 'text-slate-500 hover:text-slate-800'
                        "
                        @click="inputMode = mode.value"
                    >
                        {{ mode.label }}
                    </button>
                </div>
            </div>

            <div v-if="inputMode === 'table'" class="p-4">
                <div class="overflow-x-auto">
                    <table
                        class="w-full min-w-[36rem] border-collapse text-left text-xs"
                    >
                        <thead>
                            <tr
                                class="border-b border-slate-200 text-slate-500"
                            >
                                <th class="px-3 py-2 font-medium">
                                    AncienIdIntCli
                                </th>
                                <th class="px-3 py-2 font-medium">
                                    NouveauIdIntCli
                                </th>
                                <th
                                    class="w-14 px-3 py-2 text-right font-medium"
                                >
                                    &nbsp;
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, index) in mappings"
                                :key="row.id"
                                class="border-b border-slate-100 last:border-0"
                            >
                                <td class="px-3 py-2">
                                    <input
                                        v-model="row.oldId"
                                        type="text"
                                        autocomplete="off"
                                        class="w-full rounded border border-slate-300 px-2 py-2 text-xs text-slate-800 outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600"
                                        :aria-label="`Ancien identifiant, ligne ${index + 1}`"
                                        placeholder="0238450"
                                        @input="clearSourceError"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        v-model="row.newId"
                                        type="text"
                                        autocomplete="off"
                                        class="w-full rounded border border-slate-300 px-2 py-2 text-xs text-slate-800 outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600"
                                        :aria-label="`Nouvel identifiant, ligne ${index + 1}`"
                                        placeholder="C00000238450"
                                        @input="clearSourceError"
                                    />
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button
                                        type="button"
                                        class="rounded p-1.5 text-slate-500 hover:bg-rose-50 hover:text-rose-700"
                                        :aria-label="`Supprimer la ligne ${index + 1}`"
                                        title="Supprimer la ligne"
                                        @click="removeRow(index)"
                                    >
                                        <span class="material-icons text-base"
                                            >delete_outline</span
                                        >
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button
                    type="button"
                    class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-sky-800 hover:text-sky-950"
                    @click="addRow"
                >
                    <span class="material-icons text-base">add</span>
                    Ajouter une ligne
                </button>
            </div>

            <div v-else-if="inputMode === 'file'" class="p-4">
                <label
                    for="reference-file"
                    class="block text-xs font-medium text-slate-700"
                >
                    Fichier source
                </label>
                <input
                    id="reference-file"
                    type="file"
                    accept=".csv,.tsv,.txt,.xls,.xlsx"
                    class="mt-2 block w-full max-w-xl text-xs text-slate-600 file:mr-3 file:rounded file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
                    @change="loadFile"
                />
                <p class="mt-2 text-xs text-slate-500">
                    Les deux premières colonnes sont utilisées; les colonnes
                    intitulées ancien et nouveau sont également reconnues.
                </p>
            </div>

            <div v-else class="p-4">
                <label
                    for="reference-bulk"
                    class="block text-xs font-medium text-slate-700"
                >
                    Couples ancien;nouveau, un par ligne
                </label>
                <textarea
                    id="reference-bulk"
                    v-model="bulkText"
                    rows="8"
                    class="mt-2 block w-full rounded border border-slate-300 px-3 py-2 font-mono text-xs text-slate-800 outline-none focus:border-sky-600 focus:ring-1 focus:ring-sky-600"
                    placeholder="AncienIdIntCli;NouveauIdIntCli&#10;0238450;C00000238450"
                    @input="parseBulkText"
                ></textarea>
                <p class="mt-2 text-xs text-slate-500">
                    Le séparateur tabulation est aussi accepté pour un collage
                    direct depuis Excel.
                </p>
            </div>

            <div
                v-if="sourceError"
                class="border-t border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800"
                role="alert"
            >
                {{ sourceError }}
            </div>
            <div
                v-else-if="incompleteRows"
                class="border-t border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900"
                role="status"
            >
                {{ incompleteRows }} ligne(s) incomplète(s). Renseignez les deux
                identifiants avant de générer le fichier.
            </div>
        </section>

        <p
            v-if="generatedMessage"
            class="text-xs font-medium text-emerald-700"
            role="status"
        >
            {{ generatedMessage }}
        </p>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import * as XLSX from "xlsx";
import {
    downloadReferenceUpdateXml,
    generateReferenceUpdateXml,
    getCurrentDeclarationDate,
} from "../services/referenceUpdateExportService.js";

const inputModes = [
    { value: "table", label: "Tableau" },
    { value: "file", label: "Fichier" },
    { value: "text", label: "Coller" },
];
const inputMode = ref("table");
let rowSequence = 0;
const createRow = (oldId = "", newId = "") => ({
    id: `reference-${++rowSequence}`,
    oldId: String(oldId ?? "").trim(),
    newId: String(newId ?? "").trim(),
});
const mappings = ref([createRow()]);
const bulkText = ref("");
const sourceError = ref("");
const generatedMessage = ref("");

const activeRows = computed(() =>
    mappings.value.filter((row) => row.oldId.trim() || row.newId.trim()),
);
const incompleteRows = computed(
    () =>
        activeRows.value.filter((row) => !row.oldId.trim() || !row.newId.trim())
            .length,
);
const canGenerate = computed(
    () =>
        activeRows.value.length > 0 &&
        incompleteRows.value === 0 &&
        !sourceError.value,
);
const declarationDate = ref(getCurrentDeclarationDate());
const declarationConfig = ref({ CodPay: "CM", CodDec: "10030" });

onMounted(async () => {
    try {
        const response = await axios.get("/api/admin/config");
        const application = response.data.application || {};
        declarationConfig.value = {
            CodPay: application.codPay ?? "CM",
            CodDec: application.codDec ?? "10030",
        };
    } catch (error) {
        console.error("Chargement des codes de déclaration impossible:", error);
    }
});

const headerFields = computed(() => [
    { label: "NumDec", value: "0001" },
    { label: "CodPay", value: declarationConfig.value.CodPay },
    { label: "CodDec", value: declarationConfig.value.CodDec },
    { label: "TypDec", value: "11" },
    { label: "NbrDec", value: String(activeRows.value.length) },
    { label: "NatDec", value: "01" },
    { label: "DatDec", value: declarationDate.value },
    { label: "comment", value: "" },
]);

const replaceMappings = (rows) => {
    mappings.value = rows.map((row) => createRow(row.oldId, row.newId));
    if (!mappings.value.length) mappings.value = [createRow()];
};

const clearSourceError = () => {
    sourceError.value = "";
    generatedMessage.value = "";
};

const addRow = () => {
    mappings.value.push(createRow());
    sourceError.value = "";
    generatedMessage.value = "";
};

const removeRow = (index) => {
    mappings.value.splice(index, 1);
    if (!mappings.value.length) mappings.value.push(createRow());
    sourceError.value = "";
    generatedMessage.value = "";
};

const normalizeHeader = (value) =>
    String(value ?? "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .replace(/[^a-z0-9]/g, "");

const isOldHeader = (value) => {
    const header = normalizeHeader(value);
    return header.includes("ancien") || header.includes("old");
};
const isNewHeader = (value) => {
    const header = normalizeHeader(value);
    return header.includes("nouveau") || header.includes("new");
};

const rowsFromMatrix = (matrix) => {
    const headerIndex = matrix.findIndex(
        (row) => row.some(isOldHeader) && row.some(isNewHeader),
    );
    let oldColumn = 0;
    let newColumn = 1;
    let dataStart = 0;

    if (headerIndex >= 0) {
        oldColumn = matrix[headerIndex].findIndex(isOldHeader);
        newColumn = matrix[headerIndex].findIndex(isNewHeader);
        dataStart = headerIndex + 1;
    }

    return matrix
        .slice(dataStart)
        .filter((row) => row.some((cell) => String(cell ?? "").trim()))
        .map((row) => ({
            oldId: row[oldColumn] ?? "",
            newId: row[newColumn] ?? "",
        }));
};

const loadFile = async (event) => {
    const file = event.target.files?.[0];
    sourceError.value = "";
    generatedMessage.value = "";
    if (!file) return;

    try {
        const workbook = XLSX.read(await file.arrayBuffer(), { type: "array" });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        if (!firstSheet)
            throw new Error("Le fichier ne contient aucune feuille lisible.");
        const matrix = XLSX.utils.sheet_to_json(firstSheet, {
            header: 1,
            raw: false,
            defval: "",
        });
        const rows = rowsFromMatrix(matrix);
        if (!rows.length)
            throw new Error(
                "Aucun couple ancien/nouveau n'a été trouvé dans le fichier.",
            );
        replaceMappings(rows);
    } catch (error) {
        sourceError.value = error.message || "Impossible de lire ce fichier.";
    }
};

const parseBulkText = () => {
    sourceError.value = "";
    generatedMessage.value = "";
    const lines = bulkText.value.split(/\r?\n/).filter((line) => line.trim());
    if (!lines.length) {
        replaceMappings([]);
        return;
    }

    const matrix = lines.map((line) => {
        const delimiter = line.includes(";")
            ? ";"
            : line.includes("\t")
              ? "\t"
              : ",";
        return line.split(delimiter).map((cell) => cell.trim());
    });
    const rows = rowsFromMatrix(matrix);
    if (rows.some((row) => !row.oldId || !row.newId)) {
        sourceError.value =
            "Chaque ligne doit contenir un ancien identifiant et un nouveau, séparés par un point-virgule.";
    }
    replaceMappings(rows);
};

const downloadXml = () => {
    if (!canGenerate.value) return;
    try {
        const result = generateReferenceUpdateXml(activeRows.value, {
            DatDec: declarationDate.value,
            ...declarationConfig.value,
        });
        downloadReferenceUpdateXml(result.xml, result.filename);
        generatedMessage.value = `${result.filename} généré avec ${activeRows.value.length} mise(s) à jour.`;
    } catch (error) {
        sourceError.value =
            error.message || "Impossible de générer le fichier XML.";
    }
};
</script>
