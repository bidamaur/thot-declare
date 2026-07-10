import { createRouter, createWebHistory } from "vue-router";
import AppLayout from "../components/AppLayout.vue";
import Dashboard from "../views/Dashboard.vue";
import CdrPp from "../views/CdrPp.vue";
import CdrPm from "../views/CdrPm.vue";
import Encours from "../views/Encours.vue";
import EncoursAjust from "../views/EncoursAjust.vue";
import Engagements from "../views/Engagements.vue";
import Garanties from "../views/Garanties.vue";
import CreditConsolidation from "../views/CreditConsolidation.vue";

const routes = [
    {
        path: "/",
        component: AppLayout,
        children: [
            { path: "", component: Dashboard, name: "Dashboard" },
            { path: "personnes-physiques", component: CdrPp, name: "PersonnesPhysiques" },
            { path: "personnes-morales", component: CdrPm, name: "PersonnesMorales" },
            { path: "encours", component: Encours, name: "Encours" },
            { path: "encours-ajust", component: EncoursAjust, name: "EncoursAjust" },
            { path: "engagements", component: Engagements, name: "Engagements" },
            { path: "credit-consolidation", component: CreditConsolidation, name: "CreditConsolidation" },
            { path: "garanties", component: Garanties, name: "Garanties" },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
