<template>
  <aside class="w-72 bg-white border-r border-gray-100 flex flex-col h-full shadow-premium-lg z-30">
    <div class="p-6 border-b border-gray-50">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg">
          <span class="material-icons text-white text-xl">account_balance</span>
        </div>
        <div>
          <h1 class="text-lg font-bold text-gray-900 tracking-tight">THOT Declare</h1>
          <p class="text-xs text-gray-400 font-medium">Credit Risk Manager</p>
        </div>
      </div>
    </div>
    
    <nav class="flex-1 overflow-y-auto py-4 scrollbar-thin">
      <div class="px-4 space-y-6">
        <div>
          <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Main Menu</p>
          <div class="space-y-1">
            <router-link 
              to="/" 
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="$route.path === '/' ? 'bg-gradient-to-r from-primary-50 to-primary-100/50 text-primary-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <span class="material-icons text-lg" :class="$route.path === '/' ? 'text-primary-600' : 'text-gray-400'">dashboard</span>
              Dashboard
            </router-link>
          </div>
        </div>
        
        <div>
          <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Clients</p>
          <div class="space-y-1">
            <router-link 
              to="/personnes-physiques" 
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="$route.path === '/personnes-physiques' ? 'bg-gradient-to-r from-primary-50 to-primary-100/50 text-primary-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <span class="material-icons text-lg" :class="$route.path === '/personnes-physiques' ? 'text-primary-600' : 'text-gray-400'">person</span>
              Personnes Physiques
            </router-link>
            <router-link 
              to="/personnes-morales" 
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="$route.path === '/personnes-morales' ? 'bg-gradient-to-r from-primary-50 to-primary-100/50 text-primary-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <span class="material-icons text-lg" :class="$route.path === '/personnes-morales' ? 'text-primary-600' : 'text-gray-400'">business</span>
              Personnes Morales
            </router-link>
          </div>
        </div>
        
        <div>
          <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Credit Portfolio</p>
          <div class="space-y-1">
            <router-link 
              to="/encours" 
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="$route.path === '/encours' ? 'bg-gradient-to-r from-primary-50 to-primary-100/50 text-primary-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <span class="material-icons text-lg" :class="$route.path === '/encours' ? 'text-primary-600' : 'text-gray-400'">credit_score</span>
              Encours
            </router-link>
            <router-link 
              to="/encours-ajust" 
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="$route.path === '/encours-ajust' ? 'bg-gradient-to-r from-primary-50 to-primary-100/50 text-primary-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <span class="material-icons text-lg" :class="$route.path === '/encours-ajust' ? 'text-primary-600' : 'text-gray-400'">tune</span>
              Ajustements Encours
            </router-link>
            <router-link 
              to="/engagements" 
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="$route.path === '/engagements' ? 'bg-gradient-to-r from-primary-50 to-primary-100/50 text-primary-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <span class="material-icons text-lg" :class="$route.path === '/engagements' ? 'text-primary-600' : 'text-gray-400'">verified_user</span>
              Engagements
            </router-link>
            <router-link 
              to="/garanties" 
              class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
              :class="$route.path === '/garanties' ? 'bg-gradient-to-r from-primary-50 to-primary-100/50 text-primary-700 shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
            >
              <span class="material-icons text-lg" :class="$route.path === '/garanties' ? 'text-primary-600' : 'text-gray-400'">shield</span>
              Garanties
            </router-link>
          </div>
        </div>
      </div>
    </nav>
    
    <div class="p-4 border-t border-gray-50">
      <div class="material-card p-4">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center">
            <span class="material-icons text-primary-700 text-sm">info</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-gray-700 truncate">CDR Reporting</p>
            <p class="text-xs text-gray-400">v1.0.0</p>
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { computed } from 'vue';

const route = useRoute();
const isActive = (path) => route.path === path;
</script>
