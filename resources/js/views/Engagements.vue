<template>
  <DataTable
    title="Engagements"
    subtitle="Liste des engagements de crédit déclarés"
    :endpoint="fetchEngagements"
    :show-date-picker="true"
    :columns="columns"
  />
</template>

<script setup>
import DataTable from '../components/DataTable.vue';

const columns = [
  { key: 'CLI', label: 'Client' },
  { key: 'EVE', label: 'Événement' },
  { key: 'REFINT', label: 'Référence Interne' },
  { key: 'MNTTE', label: 'Montant Engagement', format: 'number' },
  { key: 'DATEP', label: 'Date Mise en Place', format: 'date' },
  { key: 'TXINT', label: 'Taux Intérêt' },
  { key: 'TXEFFGLOB', label: 'Taux Effectif Global' },
  { key: 'DATDEB', label: 'Date Début', format: 'date' },
  { key: 'DATFIN', label: 'Date Fin', format: 'date' },
  { key: 'DUREE', label: 'Durée' },
  { key: 'PERIODICITE', label: 'Périodicité' },
  { key: 'NBRECH', label: 'Nombre Échéances' },
  { key: 'MNTECH', label: 'Montant Échéance', format: 'number' },
];

const fetchEngagements = (date) =>
    `/api/cdr_engagements/${date ? date.split("-").reverse().join("-") : ""}`;
</script>
