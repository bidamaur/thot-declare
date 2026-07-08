<template>
    <div class="space-y-6">
        <!-- Welcome Section -->
        <section class="premium-card overflow-hidden">
            <div class="grid gap-0 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="p-7 sm:p-8 lg:p-10">
                    <div class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
                        <span class="material-icons mr-2 text-base">dashboard</span>
                        Tableau de bord
                    </div>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                        Pilotage réglementaire, suivi crédit et reporting en un seul espace.
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                        Centralisez vos déclarations, contrôlez vos encours et surveillez vos engagements avec une expérience claire et professionnelle.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <button class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700" @click="$router.push('/encours')">
                            Voir les encours
                        </button>
                        <button class="rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:text-blue-700" @click="$router.push('/engagements')">
                            Consulter les engagements
                        </button>
                    </div>
                </div>

                <div class="border-t border-slate-200 bg-gradient-to-br from-slate-950 via-blue-950 to-indigo-950 p-7 text-white sm:p-8 lg:border-l lg:border-t-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-300">État du système</p>
                            <p class="mt-2 text-2xl font-semibold">Opérationnel</p>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10">
                            <span class="material-icons text-2xl">shield</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cards Grid -->
        <section class="grid gap-4 md:grid-cols-3">
            <div class="premium-card-card cursor-pointer transition-shadow hover:shadow-md" @click="$router.push('/personnes-physiques')">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                    <span class="material-icons text-2xl">person</span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Personnes Physiques</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">Dossiers clients et déclarations individuelles.</p>
            </div>

            <div class="premium-card-card cursor-pointer transition-shadow hover:shadow-md" @click="$router.push('/personnes-morales')">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-600">
                    <span class="material-icons text-2xl">business</span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Clients Entreprises</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">Portefeuilles institutionnels et entités.</p>
            </div>

            <div class="premium-card-card cursor-pointer transition-shadow hover:shadow-md" @click="$router.push('/encours')">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                    <span class="material-icons text-2xl">account_balance_wallet</span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Encours</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">Suivi des positions et exposition risque.</p>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <div class="premium-card-card cursor-pointer transition-shadow hover:shadow-md" @click="$router.push('/engagements')">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                    <span class="material-icons text-2xl">verified_user</span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Engagements</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">Analyse des engagements et limites crédit.</p>
            </div>

            <div class="premium-card-card cursor-pointer transition-shadow hover:shadow-md" @click="$router.push('/encours-ajust')">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-100 text-rose-600">
                    <span class="material-icons text-2xl">tune</span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Ajustements</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">Vue de conformité et recalibrage.</p>
            </div>

            <div class="premium-card-card cursor-pointer transition-shadow hover:shadow-md" @click="$router.push('/garanties')">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-100 text-cyan-600">
                    <span class="material-icons text-2xl">shield</span>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">Garanties</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">Suivi des sûretés mobilisées.</p>
            </div>
        </section>

        <!-- Info Section -->
        <section class="premium-card-dark">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-white">Gestion réglementaire</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-300">
                        Plateforme dédiée aux exigences BCE/CRR pour contrôle et reporting crédit.
                    </p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                    <p class="font-semibold text-white">Système CDR</p>
                    <p class="mt-1 text-xs text-slate-300">v1.0 • prêt pour intégration</p>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup></script>