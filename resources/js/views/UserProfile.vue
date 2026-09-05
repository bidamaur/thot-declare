<template>
    <div class="profile-page">
        <div class="profile-card">
            <div class="profile-header">
                <div class="avatar">
                    <span class="material-icons">person</span>
                </div>
                <div class="profile-info">
                    <h2 class="profile-name">{{ user.name }}</h2>
                    <span class="profile-email">{{ user.email }}</span>
                    <span class="profile-role" :class="roleClass">{{
                        roleLabel
                    }}</span>
                </div>
            </div>

            <form @submit.prevent="onSave" class="profile-form">
                <h3 class="section-title">Informations personnelles</h3>

                <div class="form-row">
                    <label class="form-label">Nom complet</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="form-input"
                        :disabled="!canEdit"
                        @input="clearError('name')"
                    />
                    <p v-if="errors.name" class="form-error">
                        {{ errors.name }}
                    </p>
                </div>

                <div class="form-row">
                    <label class="form-label">Adresse e-mail</label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="form-input"
                        :disabled="!canEdit"
                        @input="clearError('email')"
                    />
                    <p v-if="errors.email" class="form-error">
                        {{ errors.email }}
                    </p>
                </div>

                <div v-if="isAdmin" class="form-row">
                    <label class="form-label">Rôle</label>
                    <p class="role-display">{{ roleLabel }}</p>
                </div>

                <div v-if="mustChangePassword" class="forced-password-notice">
                    Votre administrateur a réinitialisé votre mot de passe. Vous
                    devez en choisir un nouveau avant d'accéder à l'application.
                </div>
                <h3 class="section-title">Changement de mot de passe</h3>

                <div class="form-row">
                    <label class="form-label">Mot de passe actuel</label>
                    <input
                        v-model="form.current_password"
                        type="password"
                        class="form-input"
                        placeholder="• • • • • • • •"
                        autocomplete="current-password"
                        :disabled="!canEdit"
                    />
                    <p v-if="errors.current_password" class="form-error">
                        {{ errors.current_password }}
                    </p>
                </div>

                <div class="form-row">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input
                        v-model="form.password"
                        type="password"
                        class="form-input"
                        placeholder="• • • • • • • •"
                        autocomplete="new-password"
                        :disabled="!canEdit"
                    />
                    <p v-if="errors.password" class="form-error">
                        {{ errors.password }}
                    </p>
                </div>

                <div class="form-row">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        class="form-input"
                        placeholder="• • • • • • • •"
                        autocomplete="new-password"
                        :disabled="!canEdit"
                    />
                </div>

                <p v-if="errors.general" class="form-error general-error">
                    {{ errors.general }}
                </p>

                <button
                    type="submit"
                    :disabled="!canEdit || saving"
                    class="save-button"
                >
                    <span v-if="!saving">Enregistrer</span>
                    <span v-else class="spinner"></span>
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from "vue";
import axios from "axios";

const user = ref({
    name: "",
    email: "",
    role: "",
    must_change_password: false,
});

const form = reactive({
    name: "",
    email: "",
    current_password: "",
    password: "",
    password_confirmation: "",
});

const errors = ref({});
const saving = ref(false);

const isAdmin = computed(() => user.value.role === "admin");

const roleLabel = computed(() => {
    const labels = {
        admin: "Administrateur",
        full: "Accès complet",
        read_only: "Lecture seule",
    };
    return labels[user.value.role] || user.value.role;
});

const roleClass = computed(() => {
    const classes = {
        admin: "role-admin",
        full: "role-full",
        read_only: "role-readonly",
    };
    return classes[user.value.role] || "";
});

const canEdit = computed(() => {
    return user.value.role !== "read_only" || mustChangePassword.value;
});

const mustChangePassword = computed(
    () => user.value.must_change_password === true,
);

const clearError = (field) => {
    if (errors.value[field]) {
        errors.value[field] = null;
    }
};

const loadUser = async () => {
    try {
        const response = await axios.get("/auth/user");
        user.value = response.data.user;
        form.name = user.value.name;
        form.email = user.value.email;
    } catch (error) {
        console.error("Erreur lors du chargement:", error);
    }
};

const onSave = async () => {
    errors.value = {};
    saving.value = true;

    try {
        const response = await axios.post("/auth/user", {
            name: form.name,
            email: form.email,
            current_password: form.current_password,
            password: form.password,
            password_confirmation: form.password_confirmation,
        });
        user.value.name = response.data.user.name;
        user.value.email = response.data.user.email;
        user.value.must_change_password =
            response.data.user.must_change_password;
        form.current_password = "";
        form.password = "";
        form.password_confirmation = "";
        errors.value.success = response.data.message;
    } catch (error) {
        if (error.response?.data?.errors) {
            const fieldErrors = error.response.data.errors;
            Object.keys(fieldErrors).forEach((key) => {
                errors.value[key] = fieldErrors[key][0];
            });
        }
        if (error.response?.data?.message) {
            errors.value.general = error.response.data.message;
        }
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    loadUser();
});
</script>

<style scoped>
.profile-page {
    min-height: 100vh;
    background: radial-gradient(ellipse at top, #f0f4ff 0%, #ffffff 100%);
    padding: 2rem 1.5rem;
}

.profile-card {
    max-width: 560px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 1.25rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 2rem 1.75rem 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 4rem;
    height: 4rem;
    background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    border-radius: 1rem;
    color: white;
    font-size: 2rem;
}

.profile-info h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.profile-email {
    font-size: 0.85rem;
    color: #64748b;
}

.profile-role {
    display: inline-block;
    margin-top: 0.25rem;
    padding: 0.25rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.role-admin {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
}

.role-full {
    background: rgba(34, 197, 94, 0.12);
    color: #16a34a;
}

.role-readonly {
    background: rgba(148, 153, 158, 0.12);
    color: #6b7280;
}

.profile-form {
    padding: 1.75rem;
}

.section-title {
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    margin: 0 0 1rem 0;
}

.form-row {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
}

.form-input {
    width: 100%;
    padding: 0.6rem 0.85rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.form-input:focus:not(:disabled) {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.form-input:disabled {
    background: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
}

.form-error {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.4rem;
}

.general-error {
    margin-bottom: 1rem;
}

.role-display {
    padding: 0.5rem 0.8rem;
    font-size: 0.9rem;
    color: #1e293b;
    background: #f8fafc;
    border-radius: 0.5rem;
    border: 1.5px solid #e2e8f0;
}

.save-button {
    width: 100%;
    padding: 0.7rem;
    background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.save-button:hover:not(:disabled) {
    opacity: 0.95;
}

.save-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.spinner {
    display: inline-block;
    width: 1.2rem;
    height: 1.2rem;
    border: 2px solid rgba(255, 255, 255, 0.5);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
