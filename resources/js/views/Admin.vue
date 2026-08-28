<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-1">
            <p
                class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600"
            >
                Administration
            </p>
            <h1 class="text-2xl font-semibold text-slate-900">Configuration</h1>
            <p class="text-sm text-slate-500">
                Paramètres de la source de données, de l'application et des flux
                CDR.
            </p>
        </div>

        <div class="flex gap-2 border-b border-slate-200">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                class="px-3 py-2 text-sm font-medium border-b-2"
                :class="
                    activeTab === tab.key
                        ? 'border-blue-600 text-blue-700'
                        : 'border-transparent text-slate-500 hover:text-slate-800'
                "
            >
                {{ tab.label }}
            </button>
        </div>

        <section v-if="activeTab === 'database'" class="max-w-3xl space-y-4">
            <div
                class="premium-card premium-card-header"
            >
                <h2 class="text-base font-semibold text-slate-900">
                    Base de données cible
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Oracle utilise OCI8 et son service/TNS. Les autres pilotes
                    utilisent les paramètres PDO Laravel.
                </p>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="field"
                        >Pilote<select v-model="database.driver">
                            <option value="oci8">Oracle (OCI8)</option>
                            <option value="mysql">MySQL</option>
                            <option value="sqlsrv">SQL Server</option>
                            <option value="pgsql">PostgreSQL</option>
                            <option value="sqlite">SQLite</option>
                        </select></label
                    >
                    <label class="field"
                        >Hôte<input v-model="database.host" type="text"
                    /></label>
                    <label class="field"
                        >Port<input
                            v-model.number="database.port"
                            type="number"
                    /></label>
                    <label class="field"
                        >Base / SID<input
                            v-model="database.database"
                            type="text"
                    /></label>
                    <label class="field"
                        >Utilisateur<input
                            v-model="database.username"
                            type="text"
                    /></label>
                    <label class="field"
                        >Mot de passe<input
                            v-model="database.password"
                            type="password"
                            :placeholder="
                                database.has_password
                                    ? 'Mot de passe enregistré'
                                    : ''
                            "
                    /></label>
                    <label
                        v-if="database.driver === 'oci8'"
                        class="field sm:col-span-2"
                        >Service / TNS (optionnel)<input
                            v-model="database.service"
                            type="text"
                            placeholder="host:1521/service ou chaîne TNS"
                    /></label>
                </div>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <button
                        @click="testDatabase"
                        :disabled="busy"
                        class="button secondary"
                    >
                        Tester la connexion
                    </button>
                    <button
                        @click="saveDatabase"
                        :disabled="busy"
                        class="button primary"
                    >
                        Enregistrer la base
                    </button>
                </div>
                <p
                    v-if="databaseMessage"
                    class="mt-3 text-sm"
                    :class="databaseOk ? 'text-emerald-700' : 'text-rose-700'"
                >
                    {{ databaseMessage }}
                </p>
            </div>
        </section>

        <section v-else-if="activeTab === 'application'" class="max-w-3xl">
            <div
                class="premium-card premium-card-header"
            >
                <h2 class="text-base font-semibold text-slate-900">
                    Paramètres de l'application
                </h2>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="field sm:col-span-2"
                        >Nom de l'application<input
                            v-model="application.name"
                            type="text"
                    /></label>
                    <label class="field"
                        >Langue<select v-model="application.language">
                            <option value="fr">Français</option>
                            <option value="en">English</option>
                        </select></label
                    >
                    <label class="field"
                        >Thème<select v-model="application.theme">
                            <option value="light">Clair</option>
                            <option value="dark">Sombre</option>
                            <option value="system">Système</option>
                        </select></label
                    >
                </div>
                <button
                    @click="saveApplication"
                    :disabled="busy"
                    class="button primary mt-5"
                >
                    Enregistrer les paramètres
                </button>
                <p
                    v-if="applicationMessage"
                    class="mt-3 text-sm"
                    :class="
                        applicationOk ? 'text-emerald-700' : 'text-rose-700'
                    "
                >
                    {{ applicationMessage }}
                </p>
            </div>
         </section>

          <section v-else-if="activeTab === 'themes'" class="space-y-6">
              <div
                  class="premium-card premium-card-header"
              >
                  <h2 class="text-base font-semibold">
                      Thèmes d'application
                  </h2>
                  <p class="mt-1 text-xs text-slate-500">
                      Sélectionnez un thème premium et personnalisez son nom.
                  </p>
              </div>

              <div
                  v-for="(theme, key) in themes"
                  :key="key"
                  class="theme-card group cursor-pointer border-2 transition-all"
                  :class="
                      selectedTheme === key
                          ? 'border-blue-600 shadow-lg shadow-blue-200/50'
                          : 'border-slate-200 hover:border-slate-300'
                  "
                  @click="selectedTheme = key"
              >
                  <div
                      class="h-24 rounded-lg"
                      :style="{ background: theme.preview }"
                  ></div>
                  <div class="p-4">
                      <h3 class="text-lg font-semibold text-slate-900">
                          {{ customThemeName && selectedTheme === key ? customThemeName : theme.name }}
                      </h3>
                      <p class="mt-1 text-sm text-slate-500">
                          {{ theme.description }}
                      </p>
                  </div>
              </div>

              <div
                  v-if="selectedTheme"
                  class="premium-card premium-card-header"
              >
                  <h3 class="text-sm font-semibold text-slate-700">
                      Nom personnalisé (optionnel)
                  </h3>
                  <p class="mt-1 text-xs text-slate-500">
                      Donnez un nom à ce thème pour l'identifier rapidement.
                  </p>
                  <div class="mt-3">
                      <input
                          v-model="customThemeName"
                          type="text"
                          maxlength="100"
                          placeholder="Ex: Thème direction, Thème conformité..."
                          class="form-input w-full max-w-xs"
                      />
                  </div>
              </div>

              <div class="flex items-center justify-between">
                  <button
                      @click="selectTheme"
                      :disabled="busy"
                      class="btn btn-primary"
                  >
                      Appliquer le thème
                  </button>
                  <span
                      v-if="themeMessage"
                      class="text-sm"
                      :class="themeOk ? 'text-emerald-700' : 'text-rose-700'"
                  >
                      {{ themeMessage }}
                 </span>
             </div>
         </section>

         <section v-else class="space-y-4">
            <div
                class="premium-card premium-card-header"
            >
                <h2 class="text-base font-semibold text-slate-900">
                    Requêtes CDR
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Catalogue des routes et contrôleurs. Les surcharges sont
                    limitées aux requêtes SELECT.
                </p>
            </div>
            <div
                v-for="query in queries"
                :key="query.key"
                class="premium-card premium-card-header"
            >
                <div
                    class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <h3 class="font-semibold text-slate-900">
                            {{ query.label }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            {{ query.source }} · {{ query.route }}
                        </p>
                    </div>
                    <span class="text-xs text-slate-400">{{
                        query.sql
                            ? "Surcharge enregistrée"
                            : "Requête du contrôleur"
                    }}</span>
                </div>
                 <textarea
                     v-model="query.sql"
                     rows="16"
                     spellcheck="false"
                     class="code-editor mt-3 w-full rounded border border-slate-300 bg-slate-950 p-3 font-mono text-xs text-emerald-100"
                     placeholder="La requête SQL du contrôleur sera chargée ici"
                 ></textarea>
                  <div
                      v-if="queryVariables[query.key]"
                      class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"
                  >
                      <div
                          v-for="(label, name) in variableLabels"
                          :key="name"
                          class="field"
                      >
                          <label>{{ label }}</label>
                          <input
                              v-model="queryVariables[query.key][name]"
                              type="text"
                              spellcheck="false"
                              class="w-full text-xs border border-slate-300 rounded px-2 py-1"
                          />
                      </div>
                  </div>
                  <div
                      v-if="queryVariables[query.key] && sqlExpressionVariables(query).length"
                      class="mt-3 space-y-2 border-t border-slate-200 pt-3"
                  >
                      <p class="text-xs font-semibold text-slate-500">
                          Variables PHP (expressions SQL)
                      </p>
                      <div
                          v-for="name in sqlExpressionVariables(query)"
                          :key="name"
                      >
                          <label class="block text-xs font-medium text-slate-600 mb-1">{{ name }}</label>
                          <textarea
                              v-model="queryVariables[query.key][name]"
                              rows="3"
                              spellcheck="false"
                              class="code-editor w-full rounded border border-slate-300 bg-slate-950 p-2 font-mono text-xs text-emerald-100"
                          ></textarea>
                      </div>
                  </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button
                        @click="testQuery(query)"
                        :disabled="busy"
                        class="button secondary"
                    >
                        Tester la requête
                    </button>
                    <button
                        @click="saveQuery(query)"
                        :disabled="busy"
                        class="button primary"
                    >
                        Enregistrer la requête
                    </button>
                    <span
                        v-if="query.status"
                        class="text-sm"
                        :class="
                            query.status.ok
                                ? 'text-emerald-700'
                                : 'text-rose-700'
                        "
                        >{{ query.status.message
                        }}<span v-if="query.status.ok">
                            ({{ query.status.rows }} ligne(s))</span
                        ></span
                    >
                </div>
            </div>
            <p
                v-if="queryMessage"
                class="text-sm"
                :class="queryOk ? 'text-emerald-700' : 'text-rose-700'"
            >
                {{ queryMessage }}
            </p>
        </section>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import axios from "axios";

const tabs = [
    { key: "database", label: "Base de données" },
    { key: "application", label: "Application" },
    { key: "themes", label: "Thèmes" },
    { key: "queries", label: "Requêtes CDR" },
];
const activeTab = ref("database");
const busy = ref(false);
const database = ref({
    driver: "oci8",
    host: "localhost",
    port: 1521,
    database: "XEPDB1",
    username: "dbprod",
    password: "",
    service: "",
    has_password: false,
});
const application = ref({
    name: "THOT Declare",
    language: "fr",
    theme: "light",
});
const queries = ref([]);
const queryVariables = ref({});
const variableLabels = {
    DateArr: "DateArr (dd/mm/yy)",
    DateDeb: "DateDeb (dd/mm/yyyy)",
    DateDebMois: "DateDebMois (dd/mm/yyyy)",
    DateArrYear: "DateArrYear (yyyy)",
    DateArrMonth: "DateArrMonth (mm)",
    DateArrDay: "DateArrDay (dd)",
    DateMonthYear: "DateMonthYear (/mm/yyyy)",
    MoisAnneeStr: "MoisAnneeStr (mm/yyyy)",
};
const defaultDateVariables = {
    DateArr: "31/12/2025",
    DateDeb: "01/01/2025",
    DateDebMois: "01/12/2025",
    DateArrYear: "2025",
    DateArrMonth: "12",
    DateArrDay: "31",
    DateMonthYear: "/12/2025",
    MoisAnneeStr: "12/2025",
};
const databaseMessage = ref("");
const databaseOk = ref(false);
const applicationMessage = ref("");
const applicationOk = ref(false);
const queryMessage = ref("");
const queryOk = ref(false);
const themeMessage = ref("");
const themeOk = ref(false);

const themes = ref({});
const selectedTheme = ref("thot_light");
const customThemeName = ref("");
const themeLoaded = ref(false);

const load = async () => {
    const [config, queryResponse, themeResponse] = await Promise.all([
        axios.get("/api/admin/config"),
        axios.get("/api/admin/queries"),
        axios.get("/api/admin/themes"),
    ]);
    database.value = {
        ...database.value,
        ...config.data.database,
        password: "",
    };
    application.value = { ...application.value, ...config.data.application };
    queries.value = queryResponse.data;
    queries.value.forEach((q) => {
        const base = { ...defaultDateVariables };
        if (q.variables && typeof q.variables === "object") {
            Object.assign(base, q.variables);
        }
        queryVariables.value[q.key] = base;
    });
    themes.value = themeResponse.data.themes || {};
    selectedTheme.value = themeResponse.data.current || "thot_light";
    customThemeName.value = themeResponse.data.customThemeName || "";
    applyTheme(selectedTheme.value);
    themeLoaded.value = true;
};
const save = async (payload) => {
    busy.value = true;
    try {
        await axios.put("/api/admin/config", payload);
        return true;
    } finally {
        busy.value = false;
    }
};
const testDatabase = async () => {
    busy.value = true;
    databaseMessage.value = "";
    try {
        const response = await axios.post("/api/admin/database/test", {
            ...database.value,
        });
        databaseOk.value = true;
        databaseMessage.value = response.data.message;
    } catch (error) {
        databaseOk.value = false;
        databaseMessage.value =
            error.response?.data?.message || "Test de connexion impossible.";
    } finally {
        busy.value = false;
    }
};
const saveDatabase = async () => {
    databaseMessage.value = "";
    try {
        await save({ database: database.value });
        databaseOk.value = true;
        databaseMessage.value = "Paramètres de base enregistrés.";
    } catch (error) {
        databaseOk.value = false;
        databaseMessage.value =
            error.response?.data?.message || "Enregistrement impossible.";
    }
};
const saveApplication = async () => {
    applicationMessage.value = "";
    try {
        await save({ application: application.value });
        applicationOk.value = true;
        applicationMessage.value = "Paramètres enregistrés.";
    } catch (error) {
        applicationOk.value = false;
        applicationMessage.value =
            error.response?.data?.message || "Enregistrement impossible.";
    }
};
const saveQuery = async (query) => {
    busy.value = true;
    queryMessage.value = "";
    try {
        const response = await axios.put(`/api/admin/queries/${query.key}`, {
            sql: query.sql,
        });
        queryOk.value = true;
        queryMessage.value = response.data.message;
    } catch (error) {
        queryOk.value = false;
        queryMessage.value =
            error.response?.data?.message || "Enregistrement impossible.";
    } finally {
        busy.value = false;
    }
};
const testQuery = async (query) => {
    busy.value = true;
    query.status = null;
    try {
        const response = await axios.post(
            `/api/admin/queries/${query.key}/test`,
            {
                sql: query.sql,
                variables: queryVariables.value[query.key] || {},
            },
        );
        query.status = {
            ok: true,
            message: response.data.message,
            rows: response.data.rows,
        };
    } catch (error) {
        query.status = {
            ok: false,
            message:
                error.response?.data?.message || "Test de requête impossible.",
        };
    } finally {
        busy.value = false;
    }
};
const sqlExpressionVariables = (query) => {
    const vars = queryVariables.value[query.key] || {};
    return Object.keys(vars).filter((name) => !(name in variableLabels));
};

const applyTheme = (themeKey) => {
    const root = document.documentElement;
    root.classList.remove("theme-light", "theme-dark");
    if (themes.value[themeKey]?.class) {
        root.classList.add(themes.value[themeKey].class);
        localStorage.setItem("thot_theme", themes.value[themeKey].class);
        window.dispatchEvent(new CustomEvent("thot-theme-change", { detail: { theme: themes.value[themeKey].class } }));
    } else {
        root.classList.add("theme-light");
        localStorage.setItem("thot_theme", "theme-light");
        window.dispatchEvent(new CustomEvent("thot-theme-change", { detail: { theme: "theme-light" } }));
    }
};

const selectTheme = async () => {
    busy.value = true;
    themeMessage.value = "";
    try {
        const response = await axios.post("/api/admin/themes/select", {
            theme: selectedTheme.value,
            custom_name: customThemeName.value || null,
        });
        themeOk.value = true;
        themeMessage.value = response.data.message;
        application.value.theme = selectedTheme.value;
        application.value.custom_theme_name = customThemeName.value || undefined;
        applyTheme(selectedTheme.value);
    } catch (error) {
        themeOk.value = false;
        themeMessage.value =
            error.response?.data?.message || "Sélection de thème impossible.";
    } finally {
        busy.value = false;
    }
};
const handleExternalThemeChange = (event) => {
    const { theme } = event.detail || {};
    if (theme) {
        selectedTheme.value = theme;
    }
};
onMounted(() => {
    load().catch(() => {
        databaseMessage.value = "Configuration indisponible.";
    });
    window.addEventListener("thot-theme-change", handleExternalThemeChange);
});
</script>

<style scoped>
.field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: rgb(var(--muted));
}
.field input,
.field select,
.field textarea {
    border: 1px solid rgb(var(--border));
    border-radius: 0.375rem;
    padding: 0.45rem 0.55rem;
    font-size: 0.75rem;
    font-weight: 400;
    color: rgb(var(--foreground));
    background: rgb(var(--surface));
}
.button {
    border-radius: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
}
.button:disabled {
    opacity: 0.5;
}
.primary {
    color: #ffffff;
    background: linear-gradient(135deg, rgb(var(--primary)) 0%, rgb(var(--primary-hover)) 100%);
}
.secondary {
    color: rgb(var(--primary-hover));
    background: rgba(var(--primary), 0.10);
}
.theme-card {
    border-radius: 1rem;
    border: 2px solid rgb(var(--border));
    background: rgb(var(--surface));
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    transition: all 0.3s ease;
    overflow: hidden;
}
.theme-card:hover {
    transform: translateY(-4px);
    border-color: rgb(var(--primary));
}
</style>
