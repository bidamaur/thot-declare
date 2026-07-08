<template>
  <DataTable
    title="Clients Entreprises"
    subtitle="Liste des clients personnes morales enregistrés"
    :endpoint="fetchPm"
    :show-date-picker="true"
    :columns="columns"
  />
</template>

<script setup>
import DataTable from '../components/DataTable.vue';

const columns = [
  { key: 'IDINTCLI', label: 'ID Client' },
  { key: 'NIF_NIU', label: 'NIF/NIU' },
  { key: 'RAISOC', label: 'Raison Sociale' },
  { key: 'SIGLE', label: 'Sigle' },
  { key: 'DATCRE', label: 'Date Création', format: 'date' },
  { key: 'FORJURID', label: 'Forme Juridique' },
  { key: 'SECACT', label: 'Secteur Activité' },
  { key: 'AGEECO', label: 'Code Activité' },
  { key: 'RCCM', label: 'RCCM' },
  { key: 'STALEG', label: 'Statut Légal' },
  { key: 'EMAIL', label: 'Email' },
  { key: 'TEL', label: 'Téléphone' },
  { key: 'DATEVE', label: 'Date Entrée Parc' },
  { key: 'REGION', label: 'Région' },
  { key: 'VILLE', label: 'Ville' },
  { key: 'ADRESSE', label: 'Adresse' },
];

const fetchPm = (date) => `/api/cdr_pm/${date}`;
</script>