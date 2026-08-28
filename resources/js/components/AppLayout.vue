<template>
    <div :class="themeClass" class="flex min-h-screen">
        <Sidebar />
        <div class="flex flex-col flex-1 overflow-hidden">
            <AppHeader />
            <main class="flex-1 overflow-auto px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-7xl">
                    <router-view />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import AppHeader from "./AppHeader.vue";
import Sidebar from "./Sidebar.vue";

const themeClass = ref("theme-light");
const sidebarOpenClass = ref("");

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
            applyTheme(savedTheme === "thot_dark" ? "theme-dark" : "theme-light");
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

<style scoped>
.theme-light {
    background: #F8FAFC;
    color: #0F172A;
}

.theme-dark {
    background: #0B1120;
    color: #F8FAFC;
}
</style>
