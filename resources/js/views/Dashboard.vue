<template>
    <div class="space-y-6">
        <!-- Welcome Section -->
        <section class="premium-card overflow-hidden">
            <div class="grid gap-0 lg:grid-cols-[1.5fr_1fr]">
                <div class="p-6 sm:p-8 lg:p-10">
                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700" style="background: rgba(var(--primary-light), 0.5); border-color: rgba(var(--primary), 0.2); color: rgb(var(--primary));">
                        <span class="material-icons text-base">dashboard</span>
                        Tableau de bord
                    </div>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">
                        Pilotage réglementaire, suivi crédit et reporting en un seul espace.
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                        Centralisez vos déclarations, contrôlez vos encours et surveillez vos engagements avec une expérience claire et professionnelle.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <button class="btn btn-primary" @click="$router.push('/encours')">
                            Voir les encours
                        </button>
                        <button class="btn btn-secondary" @click="$router.push('/engagements')">
                            Consulter les engagements
                        </button>
                    </div>
                </div>

                <div class="border-t border-slate-200 bg-slate-50 p-6 sm:p-8 lg:border-l lg:border-t-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.33em] text-slate-500">
                                État du système
                            </p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">
                                Opérationnel
                            </p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white shadow">
                            <span class="material-icons text-slate-700">shield</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- KPI Cards Grid -->
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="kpi-card">
                <div class="kpi-title">Total clients</div>
                <div class="kpi-value">12 540</div>
                <div class="kpi-trend kpi-trend-positive">
                    <span class="material-icons text-xs">trending_up</span>
                    <span>+8.4%</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Dossiers CDR</div>
                <div class="kpi-value">4 892</div>
                <div class="kpi-trend kpi-trend-positive">
                    <span class="material-icons text-xs">trending_up</span>
                    <span>+3.2%</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Encours total (€)</div>
                <div class="kpi-value">1.42M</div>
                <div class="kpi-trend kpi-trend-negative">
                    <span class="material-icons text-xs">trending_down</span>
                    <span>-1.8%</span>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Alertes actives</div>
                <div class="kpi-value">14</div>
                <div class="kpi-trend kpi-trend-positive">
                    <span class="material-icons text-xs">trending_up</span>
                    <span>+5% depuis hier</span>
                </div>
            </div>
        </section>

        <!-- Module Cards Grid -->
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="premium-card-card group cursor-pointer" @click="$router.push('/personnes-physiques')">
                <div class="premium-card-body">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl text-blue-600 group-hover:scale-110 transition-transform" style="background: rgba(var(--primary-light), 0.5); color: rgb(var(--primary));">
                        <span class="material-icons text-2xl">person</span>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Personnes Physiques</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Dossiers clients et déclarations individuelles.
                    </p>
                </div>
            </div>

            <div class="premium-card-card group cursor-pointer" @click="$router.push('/personnes-morales')">
                <div class="premium-card-body">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 group-hover:scale-110 transition-transform">
                        <span class="material-icons text-2xl">business</span>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Clients Entreprises</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Portefeuilles institutionnels et entités.
                    </p>
                </div>
            </div>

            <div class="premium-card-card group cursor-pointer" @click="$router.push('/encours')">
                <div class="premium-card-body">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl text-green-600 group-hover:scale-110 transition-transform" style="background: rgba(var(--success), 0.05); color: rgb(var(--success));">
                        <span class="material-icons text-2xl">account_balance_wallet</span>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Encours</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Suivi des positions et exposition risque.
                    </p>
                </div>
            </div>
        </section>

        <!-- Secondary Module Cards -->
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="premium-card-card group cursor-pointer" @click="$router.push('/engagements')">
                <div class="premium-card-body">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl text-amber-600 group-hover:scale-110 transition-transform" style="background: rgba(var(--warning), 0.05); color: rgb(var(--warning));">
                        <span class="material-icons text-2xl">verified_user</span>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Engagements</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Analyse des engagements et limites crédit.
                    </p>
                </div>
            </div>

            <div class="premium-card-card group cursor-pointer" @click="$router.push('/encours-ajust')">
                <div class="premium-card-body">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl text-rose-600 group-hover:scale-110 transition-transform" style="background: rgba(var(--danger), 0.05); color: rgb(var(--danger));">
                        <span class="material-icons text-2xl">tune</span>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Ajustements</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Vue de conformité et recalibrage.
                    </p>
                </div>
            </div>

            <div class="premium-card-card group cursor-pointer" @click="$router.push('/garanties')">
                <div class="premium-card-body">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl text-cyan-600 group-hover:scale-110 transition-transform" style="background: rgba(var(--info), 0.05); color: rgb(var(--info));">
                        <span class="material-icons text-2xl">shield</span>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-slate-900">Garanties</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Suivi des sûretés mobilisées.
                    </p>
                </div>
            </div>
        </section>

        <!-- Regulatory Info Section -->
        <section class="premium-card">
            <div class="premium-card-body">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">
                            Gestion réglementaire
                        </h2>
                        <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                            Plateforme dédiée aux exigences BCE/CRR pour contrôle et reporting crédit.
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="font-semibold text-slate-900">Système CDR</p>
                        <p class="mt-1 text-xs text-slate-500">v1.0 — prêt pour intégration</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
</script>
