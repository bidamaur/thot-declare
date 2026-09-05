<template>
    <div :class="themeClass" class="flex min-h-screen">
        <Sidebar />
        <div class="flex flex-col flex-1 overflow-hidden">
            <AppHeader />
            <div v-if="showInfoBanner" class="info-banner">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-start gap-3 py-3">
                        <span
                            class="material-icons text-lg mt-0.5 flex-shrink-0"
                            style="color: rgb(var(--primary))"
                            >info</span
                        >
                        <div
                            class="flex-1 text-xs leading-relaxed space-y-1"
                            style="color: rgb(var(--muted-foreground))"
                        >
                            <p>
                                <span
                                    class="font-semibold"
                                    style="color: rgb(var(--foreground))"
                                    >Filtres :</span
                                >
                                Si vous décochez une section, elle ne ressortira
                                pas dans le fichier final. Si vous décochez une
                                ligne, elle ne ressortira pas non plus.
                            </p>
                            <p class="info-important">
                                <span class="font-bold">Information :</span>
                                Les contrôles de conformité et les résultats
                                d’anomalies peuvent être consultés et exportés
                                au format Excel depuis cette application.
                                Vérifiez les lignes signalées et comparez-les
                                avec les informations du portail de la Centrale
                                de Risque COBAC.
                            </p>
                        </div>
                        <button
                            @click="showInfoBanner = false"
                            class="flex-shrink-0 opacity-70 hover:opacity-100 transition-opacity"
                        >
                            <span class="material-icons text-sm">close</span>
                        </button>
                    </div>
                </div>
            </div>
            <main class="flex-1 overflow-auto px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <router-view />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from "vue";
import AppHeader from "./AppHeader.vue";
import Sidebar from "./Sidebar.vue";
import { useRoute } from "vue-router";

const route = useRoute();
const themeClass = ref("theme-light");
const showInfoBanner = ref(false);

const infoRoutes = [
    "/",
    "/personnes-physiques",
    "/personnes-morales",
    "/encours",
    "/encours-ajust",
    "/engagements",
    "/credit-consolidation",
    "/garanties",
    "/admin",
];

watch(
    () => route.path,
    (path) => {
        showInfoBanner.value = infoRoutes.includes(path);
    },
);

const applyTheme = (theme) => {
    const root = document.documentElement;
    root.classList.remove("theme-light", "theme-dark");
    root.classList.add(theme);
    themeClass.value = theme;
};

const loadTheme = async () => {
    const stored = localStorage.getItem("thot_theme");
    if (stored) {
        applyTheme(stored);
    } else {
        try {
            const axios = await import("axios");
            const response = await axios.get("/api/admin/config");
            const savedTheme = response.data.application?.theme || "thot_light";
            applyTheme(
                savedTheme === "thot_dark" ? "theme-dark" : "theme-light",
            );
        } catch (e) {
            applyTheme("theme-light");
        }
    }
};

const handleExternalThemeChange = (event) => {
    const { theme } = event.detail || {};
    if (theme) {
        applyTheme(theme);
    }
};

onMounted(() => {
    loadTheme();
    window.addEventListener("thot-theme-change", handleExternalThemeChange);
});
</script>
