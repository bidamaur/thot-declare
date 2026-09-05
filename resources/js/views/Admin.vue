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

        <section v-if="activeTab === 'users'" class="max-w-3xl space-y-4">
            <div class="premium-card premium-card-header">
                <h2 class="text-base font-semibold text-slate-900">
                    Gestion des utilisateurs
                </h2>
                <p class="mt-1 text-xs text-slate-500">
                    Créez et gérez les comptes utilisateurs de l'application.
                </p>
                <div class="mt-4" v-if="users.length === 0">
                    <p class="text-sm text-slate-500">Aucun utilisateur.</p>
                </div>
                <div v-else class="mt-4 overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>E-mail</th>
                                <th>Rôle</th>
                                <th>Créé le</th>
                                <th class="actions-header">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in users" :key="u.id">
                                <td>{{ u.name }}</td>
                                <td>{{ u.email }}</td>
                                <td>
                                    <span
                                        class="role-badge"
                                        :class="`role-${u.role}`"
                                        >{{ roleLabels[u.role] }}</span
                                    >
                                </td>
                                <td>{{ u.created_at }}</td>
                                <td class="actions-cell">
                                    <button
                                        v-if="u.role !== 'admin'"
                                        @click="resetUserPassword(u)"
                                        class="action-btn action-reset"
                                        title="Réinitialiser le mot de passe"
                                    >
                                        <span class="material-icons text-sm"
                                            >key</span
                                        >
                                    </button>
                                    <button
                                        v-if="u.role !== 'admin'"
                                        @click="deleteUser(u)"
                                        class="action-btn action-delete"
                                        title="Supprimer"
                                    >
                                        <span class="material-icons text-sm"
                                            >delete</span
                                        >
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-6 border-t border-slate-200 pt-4">
                    <h3 class="text-sm font-semibold text-slate-800 mb-3">
                        Créer un nouvel utilisateur
                    </h3>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="field"
                            >Nom<input v-model="newUser.name" type="text"
                        /></label>
                        <label class="field"
                            >E-mail<input v-model="newUser.email" type="email"
                        /></label>
                        <label class="field"
                            >Mot de passe<input
                                v-model="newUser.password"
                                type="password"
                                autocomplete="new-password"
                        /></label>
                        <label class="field"
                            >Confirmer le mot de passe<input
                                v-model="newUser.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                        /></label>
                        <label class="field"
                            >Rôle<select v-model="newUser.role">
                                <option value="admin">Administrateur</option>
                                <option value="full">Accès complet</option>
                                <option value="read_only">Lecture seule</option>
                            </select></label
                        >
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <button
                            @click="createUser"
                            :disabled="busy"
                            class="button primary"
                        >
                            Créer l'utilisateur
                        </button>
                        <p
                            v-if="userMessage"
                            class="text-sm"
                            :class="
                                userOk ? 'text-emerald-700' : 'text-rose-700'
                            "
                        >
                            {{ userMessage }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section
            v-else-if="activeTab === 'database'"
            class="max-w-3xl space-y-4"
        >
            <div class="premium-card premium-card-header">
                <h2 class="text-base font-semibold text-slate-900">
                    Base de données cible
                    <span
                        v-if="isTestMode"
                        class="ml-2 inline-flex items-center rounded-md bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800"
                    >
                        Configuration de test
                    </span>
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
                        v-if="!isTestMode"
                        @click="switchToTestMode"
                        :disabled="busy"
                        class="button secondary"
                    >
                        Passer à la config de test
                    </button>
                    <button
                        v-else
                        @click="restoreProductionConfig"
                        :disabled="busy"
                        class="button secondary"
                    >
                        Restaurer config de production
                    </button>
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
            <div class="premium-card premium-card-header">
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
            <div class="premium-card premium-card-header">
                <h2 class="text-base font-semibold">Thèmes d'application</h2>
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
                        {{
                            customThemeName && selectedTheme === key
                                ? customThemeName
                                : theme.name
                        }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ theme.description }}
                    </p>
                </div>
            </div>

            <div v-if="selectedTheme" class="premium-card premium-card-header">
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
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import axios from "axios";

const TEST_CONFIG = {
    driver: "oci8",
    host: "localhost",
    port: 1521,
    database: "XEPDB1",
    username: "dbprod",
    password: "le secret a generer",
    service: "localhost:1521/XEPDB1",
    has_password: true,
};

const tabs = [
    { key: "users", label: "Utilisateurs" },
    { key: "database", label: "Base de données" },
    { key: "application", label: "Application" },
    { key: "themes", label: "Thèmes" },
];
const activeTab = ref("users");
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
const productionConfig = ref(null);
const isTestMode = ref(false);
const application = ref({
    name: "THOT Declare",
    language: "fr",
    theme: "light",
});
const databaseMessage = ref("");
const databaseOk = ref(false);
const applicationMessage = ref("");
const applicationOk = ref(false);
const themeMessage = ref("");
const themeOk = ref(false);
const users = ref([]);
const userMessage = ref("");
const userOk = ref(false);
const newUser = ref({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "read_only",
});
const roleLabels = {
    admin: "Administrateur",
    full: "Accès complet",
    read_only: "Lecture seule",
};

const themes = ref({});
const selectedTheme = ref("thot_light");
const customThemeName = ref("");
const themeLoaded = ref(false);

const switchToTestMode = () => {
    productionConfig.value = {
        ...database.value,
        has_password: database.value.has_password,
    };
    database.value = { ...TEST_CONFIG };
    isTestMode.value = true;
    databaseMessage.value = "";
    databaseOk.value = false;
};

const restoreProductionConfig = () => {
    if (productionConfig.value) {
        database.value = { ...productionConfig.value };
    }
    isTestMode.value = false;
    databaseMessage.value = "";
    databaseOk.value = false;
};

const loadUsers = async () => {
    try {
        const response = await axios.get("/auth/users");
        users.value = response.data.users || [];
    } catch (error) {
        userMessage.value = "Impossible de charger les utilisateurs.";
        userOk.value = false;
    }
};

const createUser = async () => {
    busy.value = true;
    userMessage.value = "";
    userOk.value = false;

    if (newUser.value.password !== newUser.value.password_confirmation) {
        userMessage.value = "Les deux mots de passe doivent être identiques.";
        busy.value = false;
        return;
    }

    try {
        await axios.post("/auth/register", {
            ...newUser.value,
        });
        userOk.value = true;
        userMessage.value = "Utilisateur créé.";
        newUser.value = {
            name: "",
            email: "",
            password: "",
            password_confirmation: "",
            role: "read_only",
        };
        await loadUsers();
    } catch (error) {
        userOk.value = false;
        const validationErrors = error.response?.data?.errors || {};
        const firstValidationError = Object.values(validationErrors).flat()[0];
        userMessage.value =
            firstValidationError ||
            error.response?.data?.message ||
            "Création impossible.";
    } finally {
        busy.value = false;
    }
};

const deleteUser = async (u) => {
    if (!confirm(`Supprimer ${u.name} ?`)) return;
    busy.value = true;
    userMessage.value = "";
    try {
        await axios.delete(`/auth/users/${u.id}`);
        userOk.value = true;
        userMessage.value = "Utilisateur supprimé.";
        await loadUsers();
    } catch (error) {
        userOk.value = false;
        userMessage.value =
            error.response?.data?.message || "Suppression impossible.";
    } finally {
        busy.value = false;
    }
};

const resetUserPassword = async (u) => {
    if (!confirm(`Réinitialiser le mot de passe de ${u.name} ?`)) return;
    busy.value = true;
    userMessage.value = "";
    try {
        const response = await axios.post(`/auth/users/${u.id}/reset-password`);
        userOk.value = true;
        userMessage.value = `${response.data.message} Mot de passe temporaire : ${response.data.temporary_password}`;
    } catch (error) {
        userOk.value = false;
        userMessage.value =
            error.response?.data?.message || "Réinitialisation impossible.";
    } finally {
        busy.value = false;
    }
};

const load = async () => {
    const [config, themeResponse] = await Promise.all([
        axios.get("/api/admin/config"),
        axios.get("/api/admin/themes"),
    ]);
    database.value = {
        ...database.value,
        ...config.data.database,
        password: "",
    };
    productionConfig.value = { ...config.data.database };
    isTestMode.value = false;
    application.value = { ...application.value, ...config.data.application };
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
const applyTheme = (themeKey) => {
    const root = document.documentElement;
    root.classList.remove("theme-light", "theme-dark");
    if (themes.value[themeKey]?.class) {
        root.classList.add(themes.value[themeKey].class);
        localStorage.setItem("thot_theme", themes.value[themeKey].class);
        window.dispatchEvent(
            new CustomEvent("thot-theme-change", {
                detail: { theme: themes.value[themeKey].class },
            }),
        );
    } else {
        root.classList.add("theme-light");
        localStorage.setItem("thot_theme", "theme-light");
        window.dispatchEvent(
            new CustomEvent("thot-theme-change", {
                detail: { theme: "theme-light" },
            }),
        );
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
        application.value.custom_theme_name =
            customThemeName.value || undefined;
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
    loadUsers();
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
    background: linear-gradient(
        135deg,
        rgb(var(--primary)) 0%,
        rgb(var(--primary-hover)) 100%
    );
}
.secondary {
    color: rgb(var(--primary-hover));
    background: rgba(var(--primary), 0.1);
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
.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}
.admin-table th,
.admin-table td {
    text-align: left;
    padding: 0.6rem 0.75rem;
    border-bottom: 1px solid rgb(var(--border));
    color: rgb(var(--foreground));
}
.admin-table th {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgb(var(--muted));
}
.admin-table tbody tr:hover {
    background: rgba(37, 99, 235, 0.03);
}
.actions-header {
    text-align: center;
}
.actions-cell {
    text-align: center;
}
.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background 0.2s;
}
.action-delete:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}
.role-badge {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.role-admin {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
}
.role-full {
    background: rgba(34, 197, 94, 0.12);
    color: #16a34a;
}
.role-read_only {
    background: rgba(148, 153, 158, 0.12);
    color: #6b7280;
}
</style>
