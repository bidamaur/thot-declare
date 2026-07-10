<template>
  <DataTable
    title="Encours Ajusté"
    subtitle="Encours ajustés selon les soldes et impayés"
    :endpoint="fetchEncoursAjust"
    :show-date-picker="true"
    :columns="columns"
  />
</template>

<script setup>
import DataTable from '../components/DataTable.vue';

const columns = [
  { key: 'EVE', label: 'Événement' },
  { key: 'CLI', label: 'Client' },
  { key: 'DVA', label: 'Date Valeur' },
  { key: 'REFCONTCMPT', label: 'Réf Contrat' },
  { key: 'MNTCRD', label: 'Montant Crédit', format: 'number' },
  { key: 'MNTPAY', label: 'Montant Payé', format: 'number' },
  { key: 'NBRECHPAY', label: 'Nb Échéances Payées' },
  { key: 'NBRECHIMP', label: 'Nb Échéances Impayées' },
  { key: 'NBRECHRES', label: 'Nb Échéances Restantes' },
  { key: 'CLADEPREC', label: 'Classe Dépréciation' },
  { key: 'MNTTOTUTIL', label: 'Montant Tot Utilisé', format: 'number' },
];

const fetchEncoursAjust = (date) =>
    `/api/cdr_encours_ajust/${date ? date.split("-").reverse().join("-") : ""}`;
</script>
