<template>
    <div class="min-h-screen bg-transparent">
        <AppHeader />
        <main class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <router-view />
            </div>
        </main>
    </div>
</template>

<script setup>
import AppHeader from "./AppHeader.vue";
</script>
