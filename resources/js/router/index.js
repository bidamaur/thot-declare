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
import Admin from "../views/Admin.vue";
import Login from "../views/Login.vue";
import UserProfile from "../views/UserProfile.vue";
import axios from "axios";

const checkAuth = async () => {
    try {
        const response = await axios.get("/auth/user");
        return !!response.data.user;
    } catch {
        return false;
    }
};

const authenticatedUser = async () => {
    try {
        const response = await axios.get("/auth/user");
        return response.data.user || null;
    } catch {
        return null;
    }
};

const routes = [
    {
        path: "/login",
        name: "Login",
        component: Login,
        beforeEnter: async () => {
            const authenticated = await checkAuth();
            return authenticated ? "/" : true;
        },
    },
    {
        path: "/",
        component: AppLayout,
        beforeEnter: async (to) => {
            const user = await authenticatedUser();
            if (!user) return "/login";
            return user.must_change_password && to.path !== "/profil"
                ? "/profil"
                : true;
        },
        children: [
            { path: "", component: Dashboard, name: "Dashboard" },
            {
                path: "personnes-physiques",
                component: CdrPp,
                name: "PersonnesPhysiques",
            },
            {
                path: "personnes-morales",
                component: CdrPm,
                name: "PersonnesMorales",
            },
            { path: "encours", component: Encours, name: "Encours" },
            {
                path: "encours-ajust",
                component: EncoursAjust,
                name: "EncoursAjust",
            },
            {
                path: "engagements",
                component: Engagements,
                name: "Engagements",
            },
            {
                path: "credit-consolidation",
                component: CreditConsolidation,
                name: "CreditConsolidation",
            },
            { path: "garanties", component: Garanties, name: "Garanties" },
            {
                path: "admin",
                component: Admin,
                name: "Admin",
                beforeEnter: async () => {
                    const authenticated = await checkAuth();
                    if (!authenticated) return "/login";
                    try {
                        const res = await axios.get("/auth/user");
                        return res.data.user.role === "admin" ? true : "/";
                    } catch {
                        return "/login";
                    }
                },
            },
            {
                path: "profil",
                component: UserProfile,
                name: "UserProfile",
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
