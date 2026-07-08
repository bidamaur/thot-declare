<template>
    <header
        class="border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur sm:px-6 lg:px-8"
    >
        <div class="flex items-center justify-between gap-3">
            <div>
                <p
                    class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400"
                >
                    Déclaration de crédit
                </p>
                <h2 class="text-lg font-semibold text-slate-800">
                    {{ pageTitle }}
                </h2>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <label
                    class="hidden items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500 md:flex"
                >
                    <span class="material-icons text-base">search</span>
                    <input
                        type="text"
                        placeholder="Rechercher"
                        class="w-40 bg-transparent outline-none"
                    />
                </label>

                <button
                    class="relative rounded-full border border-slate-200 bg-white p-2 text-slate-600 shadow-sm"
                >
                    <span class="material-icons text-base">notifications</span>
                    <span
                        class="absolute right-1 top-1 h-2 w-2 rounded-full bg-rose-500"
                    ></span>
                </button>

                <div
                    class="flex items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-3 py-2"
                >
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-slate-800">
                            Admin
                        </p>
                    </div>
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-700 text-sm font-semibold text-white"
                    >
                        A
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();

const pageTitle = computed(() => {
    const titles = {
        "/": "Tableau de bord",
        "/personnes-physiques": "Personnes physiques",
        "/personnes-morales": "Personnes morales",
        "/encours": "Encours",
        "/encours-ajust": "Encours ajusté",
        "/engagements": "Engagements",
        "/garanties": "Garanties",
    };
    return titles[route.path] || "Tableau de bord";
});
</script>
