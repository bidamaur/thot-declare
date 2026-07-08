<template>
  <DataTable
    title="Encours"
    subtitle="Suivi des encours de crédit par date d'arrêté"
    :endpoint="fetchEncours"
    :show-date-picker="true"
    :columns="columns"
  />
</template>

<script setup>
import DataTable from '../components/DataTable.vue';

const columns = [
  { key: 'EVE', label: 'Événement' },
  { key: 'CLI', label: 'Client' },
  { key: 'DVA', label: 'Date Échéance' },
  { key: 'REFCONTCMPT', label: 'Réf Contrat' },
  { key: 'MNTPAY', label: 'Montant Payé', format: 'number' },
  { key: 'MNTCRD', label: 'Montant Crédit', format: 'number' },
  { key: 'MNTTOTUTIL', label: 'Montant Tot Utilisé', format: 'number' },
  { key: 'NBRECHPAY', label: 'Nb Échéances Payées' },
  { key: 'NBRECHIMP', label: 'Nb Échéances Impayées' },
  { key: 'NBRECHRES', label: 'Nb Échéances Restantes' },
  { key: 'CLADEPREC', label: 'Classe Dépréciation' },
];

const fetchEncours = (date) => `/api/cdr_encours/${date}`;
</script>
