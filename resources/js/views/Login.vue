<template>
    <div class="login-page">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">
                        <div class="logo-icon">
                            <span class="material-icons">account_balance</span>
                        </div>
                        <h1 class="login-title">THOT Déclare</h1>
                    </div>
                    <p class="login-subtitle">
                        Connectez-vous pour accéder à votre espace
                    </p>
                </div>

                <form @submit.prevent="onLogin" class="login-form">
                    <div class="form-group">
                        <label for="email" class="form-label"
                            >Adresse e-mail</label
                        >
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="form-input"
                            placeholder="vous@entreprise.com"
                            autocomplete="username"
                            required
                            @input="clearError('email')"
                        />
                        <p v-if="errors.email" class="form-error">
                            {{ errors.email }}
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label"
                            >Mot de passe</label
                        >
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="form-input"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                            @input="clearError('password')"
                        />
                        <p v-if="errors.password" class="form-error">
                            {{ errors.password }}
                        </p>
                    </div>

                    <div class="form-row">
                        <label class="checkbox-row">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="checkbox"
                            />
                            <span class="checkbox-label"
                                >Se souvenir de moi</span
                            >
                        </label>
                    </div>

                    <div v-if="errors.general" class="form-error general-error">
                        {{ errors.general }}
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="login-button"
                    >
                        <span v-if="!loading">Se connecter</span>
                        <span v-else class="spinner"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

const router = useRouter();

const form = reactive({
    email: "",
    password: "",
    remember: false,
});

const errors = ref({});
const loading = ref(false);

const clearError = (field) => {
    if (errors.value[field]) {
        errors.value[field] = null;
    }
};

const onLogin = async () => {
    errors.value = {};
    loading.value = true;

    try {
        await axios.get("/sanctum/csrf-cookie");

        const response = await axios.post("/auth/login", {
            email: form.email,
            password: form.password,
            remember: form.remember,
        });

        if (response.data.must_change_password) {
            router.push("/profil");
        } else if (response.data.redirect) {
            router.push(response.data.redirect);
        }
    } catch (error) {
        if (error.response?.data?.message) {
            errors.value.general = error.response.data.message;
        }
        if (error.response?.data?.errors) {
            const fieldErrors = error.response.data.errors;
            Object.keys(fieldErrors).forEach((key) => {
                errors.value[key] = fieldErrors[key][0];
            });
        }
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(ellipse at top, #f0f4ff 0%, #ffffff 100%);
    padding: 1.5rem;
}

.login-container {
    width: 100%;
    max-width: 380px;
}

.login-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 1rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.login-header {
    padding: 2rem 1.5rem 1.5rem;
    text-align: center;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.login-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.logo-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
    border-radius: 0.5rem;
    color: white;
}

.logo-icon .material-icons {
    font-size: 1.5rem;
}

.login-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.login-subtitle {
    font-size: 0.85rem;
    color: #94a3b8;
    margin: 0.5rem 0 0;
}

.login-form {
    padding: 1.75rem;
}

.form-group {
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
    padding: 0.65rem 0.85rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 0.5rem;
    font-size: 0.9rem;
    transition:
        border-color 0.2s,
        box-shadow 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.form-row {
    margin-bottom: 1.5rem;
}

.checkbox-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.85rem;
    color: #64748b;
    cursor: pointer;
}

.checkbox {
    width: 1rem;
    height: 1rem;
    border-radius: 0.25rem;
    border: 1.5px solid #cbd5e1;
    accent-color: #2563eb;
    cursor: pointer;
}

.form-error {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.4rem;
}

.general-error {
    margin-bottom: 1rem;
}

.login-button {
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

.login-button:hover:not(:disabled) {
    opacity: 0.95;
}

.login-button:disabled {
    opacity: 0.6;
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
