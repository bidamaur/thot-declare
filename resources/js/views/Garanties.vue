<template>
  <DataTable
    title="Garanties"
    subtitle="Liste des garanties associées aux engagements"
    :endpoint="fetchGaranties"
    :show-date-picker="true"
    :columns="columns"
  />
</template>

<script setup>
import DataTable from '../components/DataTable.vue';

const columns = [
  { key: 'CLI', label: 'Client' },
  { key: 'REFINTGAR', label: 'Référence Garantie' },
  { key: 'NATGAR', label: 'Nature Garantie' },
  { key: 'MNTGAR', label: 'Montant Garantie', format: 'number' },
  { key: 'MNTAFFGAR', label: 'Montant Affecté', format: 'number' },
  { key: 'POURA', label: 'Pourcentage Couverture' },
  { key: 'REFTGAR', label: 'Référence Titre' },
  { key: 'NOMNAIGARANT', label: 'Nom Garant' },
  { key: 'STATUTGAR', label: 'Statut' },
];

const fetchGaranties = (date) => `/api/cdr_garanties/${date}`;
</script>
