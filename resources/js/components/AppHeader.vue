<template>
    <header class="premium-header">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <h1 class="text-lg font-bold text-slate-900">THOT Declare</h1>
                    <p class="text-xs text-slate-500 hidden sm:block">Credit Risk Manager</p>
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-2">
                <router-link
                    v-for="item in navItems"
                    :key="item.path"
                    :to="item.path"
                    class="nav-link"
                    :class="{ 'nav-link-active': $route.path === item.path }"
                >
                    <span class="material-icons text-sm mr-2">{{ item.icon }}</span>
                    {{ item.label }}
                </router-link>
            </nav>

            <div class="flex items-center gap-2">
                <button class="btn-icon">
                    <span class="material-icons text-lg">notifications</span>
                </button>
                <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-2 py-1.5">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-slate-800">Admin</p>
                        <p class="text-xs text-slate-500">Utilisateur sécurisé</p>
                    </div>
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 text-xs font-semibold text-white">
                        A
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { useRoute } from 'vue-router';

const route = useRoute();

const navItems = [
    { path: "/", label: "Tableau de bord", icon: "dashboard" },
    { path: "/personnes-physiques", label: "Personnes Physiques", icon: "person" },
    { path: "/personnes-morales", label: "Clients Entreprises", icon: "business" },
    { path: "/encours", label: "Encours", icon: "account_balance_wallet" },
    { path: "/encours-ajust", label: "Ajustements", icon: "tune" },
    { path: "/engagements", label: "Engagements", icon: "verified_user" },
    { path: "/credit-consolidation", label: "Consolidation", icon: "hub" },
    { path: "/garanties", label: "Garanties", icon: "shield" },
];
</script>
