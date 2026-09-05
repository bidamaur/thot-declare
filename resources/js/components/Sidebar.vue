<template>
    <aside class="premium-sidebar">
             <div class="premium-sidebar-brand">
            <div class="premium-sidebar-brand-icon">
                <span class="material-icons text-xl">account_balance</span>
            </div>
            <div>
                <h1 class="text-sm font-bold tracking-tight" style="color: #ffffff;">
                    THOT Declare
                </h1>
                <p class="text-xs font-medium" style="color: rgb(var(--muted));">
                    Credit Risk Manager
                </p>
            </div>
        </div>

        <nav class="py-4">
            <div class="px-4 space-y-1">
                <router-link
                    to="/"
                    class="premium-sidebar-item"
                    :class="{ 'premium-sidebar-item-active': $route.path === '/' }"
                >
                    <span class="material-icons premium-sidebar-icon">dashboard</span>
                    Tableau de bord
                </router-link>
                <router-link
                    to="/personnes-physiques"
                    class="premium-sidebar-item"
                    :class="{ 'premium-sidebar-item-active': $route.path === '/personnes-physiques' }"
                >
                    <span class="material-icons premium-sidebar-icon">person</span>
                    Personnes Physiques
                </router-link>
                <router-link
                    to="/personnes-morales"
                    class="premium-sidebar-item"
                    :class="{ 'premium-sidebar-item-active': $route.path === '/personnes-morales' }"
                >
                    <span class="material-icons premium-sidebar-icon">business</span>
                    Clients Entreprises
                </router-link>
                <router-link
                    to="/encours"
                    class="premium-sidebar-item"
                    :class="{ 'premium-sidebar-item-active': $route.path === '/encours' }"
                >
                    <span class="material-icons premium-sidebar-icon">account_balance_wallet</span>
                    Encours
                </router-link>
                <router-link
                    to="/engagements"
                    class="premium-sidebar-item"
                    :class="{ 'premium-sidebar-item-active': $route.path === '/engagements' }"
                >
                    <span class="material-icons premium-sidebar-icon">verified_user</span>
                    Engagements
                </router-link>
                <router-link
                    to="/credit-consolidation"
                    class="premium-sidebar-item"
                    :class="{ 'premium-sidebar-item-active': $route.path === '/credit-consolidation' }"
                >
                    <span class="material-icons premium-sidebar-icon">hub</span>
                    Consolidation
                </router-link>
                <router-link
                    to="/garanties"
                    class="premium-sidebar-item"
                    :class="{ 'premium-sidebar-item-active': $route.path === '/garanties' }"
                >
                    <span class="material-icons premium-sidebar-icon">shield</span>
                    Garanties
                </router-link>
            </div>
        </nav>

        <div class="px-6 py-4 border-t" style="border-color: rgb(var(--border));">
            <router-link
                to="/profil"
                class="premium-sidebar-item flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-white/5 transition-colors"
            >
                <span class="material-icons text-sm" style="color: rgb(var(--muted));">person</span>
                <span class="text-sm" style="color: rgb(var(--muted-foreground));">Mon profil</span>
            </router-link>
        </div>

        <div class="px-6 py-4 border-t" style="border-color: rgb(var(--border));">
            <button
                @click="logout"
                class="premium-sidebar-item w-full flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-white/5 transition-colors text-left"
            >
                <span class="material-icons text-sm" style="color: rgb(var(--muted));">logout</span>
                <span class="text-sm" style="color: rgb(var(--muted-foreground));">Déconnexion</span>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { useRoute } from "vue-router";
import axios from "axios";

const route = useRoute();

const logout = async () => {
    await axios.post("/auth/logout");
    window.location.href = "/login";
};
</script>

<style scoped>
.premium-sidebar-item {
    color: rgb(203 211 225);
}
@media (min-width: 768px) {
    .premium-sidebar {
        width: 248px;
        transform: translateX(0);
        transition: transform 0.3s ease;
    }
}
@media (max-width: 767px) {
    .premium-sidebar {
        position: fixed;
        z-index: 50;
        height: 100vh;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    .premium-sidebar.open {
        transform: translateX(0);
    }
}
</style>
