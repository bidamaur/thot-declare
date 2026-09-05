<template>
    <header class="premium-header">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="breadcrumb" aria-label="breadcrumb">
                <span class="material-icons text-sm" style="color: rgb(var(--muted));">chevron_right</span>
                <span class="breadcrumb-item">{{ currentPageLabel }}</span>
            </nav>

            <!-- Right Actions -->
            <div class="flex items-center gap-3">
                <!-- Global Search -->
                <div class="relative hidden md:block">
                    <input
                        type="text"
                        placeholder="Recherche globale..."
                        class="form-input w-64"
                    />
                    <span
                        class="material-icons absolute left-3 top-1/2 -translate-y-1/2 text-sm"
                        style="color: rgb(var(--muted-foreground));"
                        >search</span
                    >
                </div>

                <!-- Notifications -->
                <button class="btn-ghost btn-sm">
                    <span class="material-icons text-lg">notifications</span>
                </button>

                <!-- Theme Toggle -->
                <div class="flex items-center gap-2">
                    <span class="material-icons text-sm" :style="{ color: currentThemeClass === 'theme-dark' ? 'rgb(var(--muted))' : 'rgb(var(--muted))' }">
                        {{ currentThemeClass === 'theme-dark' ? 'light_mode' : 'dark_mode' }}
                    </span>
                    <label class="relative inline-flex h-5 w-10 items-center rounded-full transition-colors cursor-pointer">
                        <input
                            type="checkbox"
                            class="sr-only"
                            :checked="currentThemeClass === 'theme-dark'"
                            @change="toggleTheme"
                        />
                        <div
                            class="theme-switch"
                            :class="{ 'checked': currentThemeClass === 'theme-dark' }"
                        ></div>
                    </label>
                </div>

                <!-- User Profile -->
                <router-link
                    to="/profil"
                    class="flex items-center gap-2 rounded-full border px-2 py-1.5 transition-opacity hover:opacity-80"
                    :style="{
                        borderColor: 'rgb(var(--border))',
                        color: 'rgb(var(--muted))'
                    }"
                    title="Profil"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold text-white"
                        style="background: linear-gradient(135deg, rgb(var(--primary)) 0%, rgb(var(--primary-hover)) 100%);"
                    >
                        {{ userInitials }}
                    </div>
                </router-link>

                <button
                    v-if="isAdmin"
                    @click="goToAdmin"
                    class="flex items-center gap-2 rounded-full border px-2 py-1.5 transition-opacity hover:opacity-80"
                    :style="{
                        borderColor: 'rgb(var(--border))',
                        color: 'rgb(var(--muted))'
                    }"
                    title="Administration"
                >
                    <span class="material-icons text-sm">admin_panel_settings</span>
                </button>

                <button
                    @click="logout"
                    class="flex items-center justify-center rounded-full border p-2 text-slate-600 shadow-sm transition-opacity hover:opacity-80"
                    :style="{
                        borderColor: 'rgb(var(--border))',
                        color: 'rgb(var(--muted))'
                    }"
                    title="Déconnexion"
                >
                    <span class="material-icons text-sm">logout</span>
                </button>
            </div>
        </div>
    </header>
</template>

<script setup>
import { useRoute } from "vue-router";
import { ref, computed } from "vue";
import axios from "axios";

const route = useRoute();
const currentThemeClass = ref("theme-light");

const routeLabels = {
    "/": "Tableau de bord",
    "/personnes-physiques": "Personnes Physiques",
    "/personnes-morales": "Clients Entreprises",
    "/encours": "Encours",
    "/encours-ajust": "Ajustements",
    "/engagements": "Engagements",
    "/credit-consolidation": "Consolidation Crédit",
    "/garanties": "Garanties",
    "/admin": "Administration",
    "/profil": "Mon Profil",
};

const currentUser = ref(null);
const isAdmin = ref(false);

const loadUser = async () => {
    try {
        const response = await axios.get("/auth/user");
        currentUser.value = response.data.user;
        isAdmin.value = currentUser.value.role === "admin";
    } catch (error) {
        currentUser.value = null;
        isAdmin.value = false;
    }
};

const userInitials = computed(() => {
    if (!currentUser.value) return "";
    const parts = currentUser.value.name.split(" ");
    return parts.map((p) => p[0]).join("").toUpperCase().slice(0, 2);
});

const goToAdmin = () => {
    window.location.href = "/admin";
};

const logout = async () => {
    await axios.post("/auth/logout");
    window.location.href = "/login";
};

loadUser();

const currentPageLabel = computed(() => {
    return routeLabels[route.path] || "THOT Declare";
});

const toggleTheme = () => {
    const root = document.documentElement;
    if (root.classList.contains("theme-dark")) {
        currentThemeClass.value = "theme-light";
        root.classList.remove("theme-dark");
        root.classList.add("theme-light");
    } else {
        currentThemeClass.value = "theme-dark";
        root.classList.remove("theme-light");
        root.classList.add("theme-dark");
    }
    localStorage.setItem("thot_theme", currentThemeClass.value);

    axios.post("/api/admin/themes/select", {
        theme: currentThemeClass.value === "theme-dark" ? "thot_dark" : "thot_light",
    }).catch(() => {});

    window.dispatchEvent(new CustomEvent("thot-theme-change", {
        detail: { theme: currentThemeClass.value }
    }));
};
</script>
