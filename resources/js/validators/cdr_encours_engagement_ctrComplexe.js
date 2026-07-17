/**
 * MODULE DE CONTRÔLE COMPLEXE CDR : ENCOURS vs ENGAGEMENTS INITIAUX (CEMAC)
 * Respecte le Kit d'interfaçage Contribution des Données (CDR) v03/10/2023.
 *
 * Ce module compare le flux des "Encours" (route cdr_encours) avec le flux de
 * contrôle des engagements initiaux (route cdr_ctrEngagements) afin de détecter
 * les incohérences logiques entre l'état d'un crédit à l'arrêté et son contrat
 * d'engagement d'origine.
 *
 * Clé de jointure unique entre un encours et un engagement : (CLI, EVE, AVE).
 */

// --- CONSTANTES ---
const CTR_ENGAGEMENTS_URL = "http://127.0.0.1:8000/api/cdr_ctrEngagements";

// --- UTILITAIRES ---

/**
 * Convertit une valeur (chaîne, nombre, null) en Number.
 * Retourne NaN si la valeur n'est pas convertible.
 */
function toNumber(val) {
    if (val === null || val === undefined || val === "") return NaN;
    // Certaines sources renvoient des montants avec séparateurs (espaces, virgules)
    const cleaned = String(val).replace(/[\s]/g, "").replace(/,/g, ".");
    return Number(cleaned);
}

/**
 * Normalise une clé de jointure pour tolérer les écarts de casse / espaces.
 */
function normKey(val) {
    return String(val ?? "").trim().toUpperCase();
}

/**
 * Construit la clé composite (CLI|EVE|AVE) utilisée pour la jointure.
 */
function buildJoinKey(cli, eve, ave) {
    return `${normKey(cli)}|${normKey(eve)}|${normKey(ave)}`;
}

/**
 * Extrait les 3 champs de jointure d'un enregistrement, quelle que soit la casse
 * de ses propriétés (CLI / cli, EVE / eve, AVE / ave).
 */
function extractJoinFields(record) {
    const getField = (record, candidates) => {
        for (const c of candidates) {
            if (record[c] !== undefined && record[c] !== null) return record[c];
        }
        return undefined;
    };
    return {
        cli: getField(record, ["CLI", "cli"]),
        eve: getField(record, ["EVE", "eve"]),
        ave: getField(record, ["AVE", "ave"]),
    };
}

/**
 * Construit un index des engagements de contrôle par clé composite (CLI|EVE|AVE).
 */
function indexEngagements(engagementsCtrData) {
    const index = new Map();
    (engagementsCtrData || []).forEach((eng) => {
        const { cli, eve, ave } = extractJoinFields(eng);
        const key = buildJoinKey(cli, eve, ave);
        // En cas de doublon, on conserve le premier (ou le plus récent si besoin)
        if (!index.has(key)) index.set(key, eng);
    });
    return index;
}

/**
 * Crée et empile un objet anomalie dans le tableau `erreurs`.
 */
function pushAnomalie(
    erreurs,
    { ligne, cli, eve, type, field, code, message, value },
) {
    erreurs.push({
        ligne: ligne ?? "",
        client: cli ?? "",
        contrat: eve ?? "",
        type: type || "erreur", // "erreur" | "avertissement"
        field: field ?? "",
        code: code ?? "",
        message: message ?? "",
        value: value === null || value === undefined ? "" : String(value),
    });
}

// --- CONTRÔLES LOGIQUES ---

/**
 * 1. Cohérence Durée : somme des échéances (payées+impayées+restantes) <= DUREE.
 */
function controleDuree(enc, eng, ctx, erreurs) {
    const nbrPay = toNumber(enc.NBRECHPAY);
    const nbrImp = toNumber(enc.NBRECHIMP);
    const nbrRes = toNumber(enc.NBRECHRES);
    const duree = toNumber(eng.DUREE);

    if ([nbrPay, nbrImp, nbrRes, duree].some((v) => Number.isNaN(v))) return;

    const somme = nbrPay + nbrImp + nbrRes;
    if (somme > duree) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "NBRECHPAY+NBRECHIMP+NBRECHRES",
            code: "CX_DUREE_001",
            message: `La somme des échéances (${somme}) dépasse la durée initiale du contrat (${duree}).`,
            value: somme,
        });
    }
}

/**
 * 2. Capital Restant Dû (MNTCRD) <= Montant initial (MNTENG).
 */
function controleCrdu(enc, eng, ctx, erreurs) {
    const mntCrd = toNumber(enc.MNTCRD);
    const mntEng = toNumber(eng.MNTENG);

    if (Number.isNaN(mntCrd) || Number.isNaN(mntEng)) return;

    if (mntCrd > mntEng) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "MNTCRD",
            code: "CX_CRDU_002",
            message: `Le capital restant dû (${mntCrd}) est supérieur au montant initial de l'engagement (${mntEng}).`,
            value: mntCrd,
        });
    }
}

/**
 * 3. Fin de crédit : échéances payées = DUREE mais MNTCRD > 0.
 */
function controleFinCredit(enc, eng, ctx, erreurs) {
    const nbrPay = toNumber(enc.NBRECHPAY);
    const duree = toNumber(eng.DUREE);
    const mntCrd = toNumber(enc.MNTCRD);

    if ([nbrPay, duree, mntCrd].some((v) => Number.isNaN(v))) return;

    if (nbrPay === duree && mntCrd > 0) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "MNTCRD",
            code: "CX_FIN_003",
            message:
                "Le crédit est arrivé à terme (échéances payées = durée), mais le capital restant dû n'est pas nul.",
            value: mntCrd,
        });
    }
}

/**
 * 4a. Si MNTCRD == 0 alors NBRECHRES doit être égal à 0.
 */
function controleCrdZeroRes(enc, ctx, erreurs) {
    const mntCrd = toNumber(enc.MNTCRD);
    const nbrRes = toNumber(enc.NBRECHRES);

    if (Number.isNaN(mntCrd) || Number.isNaN(nbrRes)) return;

    if (mntCrd === 0 && nbrRes !== 0) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "NBRECHRES",
            code: "CX_COH_004",
            message: `Le capital restant dû est nul, mais des échéances restantes (${nbrRes}) sont déclarées.`,
            value: nbrRes,
        });
    }
}

/**
 * 4b. Si NBRECHIMP > 0 alors le montant en souffrance (MNTCRESOUF) > 0.
 */
function controleImpayeSouffrance(enc, ctx, erreurs) {
    const nbrImp = toNumber(enc.NBRECHIMP);
    const mntSouf = toNumber(enc.MNTCRESOUF);

    if (Number.isNaN(nbrImp)) return;

    if (nbrImp > 0) {
        // Souffrance absente ou non convertible : on lève quand même une erreur
        if (Number.isNaN(mntSouf) || mntSouf <= 0) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "erreur",
                field: "MNTCRESOUF",
                code: "CX_COH_005",
                message:
                    "Des échéances impayées sont déclarées, mais le montant en souffrance est nul ou absent.",
                value: Number.isNaN(mntSouf) ? "" : mntSouf,
            });
        }
    }
}

/**
 * 4c. Si crédit sain (CLADEPREC == '01') alors MNTCRESOUF == 0 et NBRECHIMP == 0.
 * (Cohérence avec les règles LOG0041 / LOG0042 du Kit CDR)
 */
function controleSain(enc, ctx, erreurs) {
    const classe = normKey(enc.CLADEPREC);
    if (classe !== "01") return;

    const nbrImp = toNumber(enc.NBRECHIMP);
    const mntSouf = toNumber(enc.MNTCRESOUF);

    if (!Number.isNaN(nbrImp) && nbrImp !== 0) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "NBRECHIMP",
            code: "CX_COH_006",
            message:
                "Crédit classé sain (CLADEPREC=01) mais des échéances impayées sont déclarées.",
            value: nbrImp,
        });
    }
    if (!Number.isNaN(mntSouf) && mntSouf !== 0) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "MNTCRESOUF",
            code: "CX_COH_007",
            message:
                "Crédit classé sain (CLADEPREC=01) mais un montant en souffrance est déclaré.",
            value: mntSouf,
        });
    }
}

// --- FONCTION PRINCIPALE ---

/**
 * Exécute l'ensemble des contrôles complexes entre les encours et les engagements
 * initiaux de contrôle.
 *
 * @param {Array} encoursData - Flux des encours (cdr_encours). Champs attendus :
 *   CLI, EVE, AVE, MNTCRD, NBRECHPAY, NBRECHIMP, NBRECHRES, MNTCRESOUF, CLADEPREC.
 * @param {Array} engagementsCtrData - Flux cdr_ctrEngagements. Champs attendus :
 *   CLI, EVE, AVE, MNTENG, DUREE, CTR.
 * @returns {Array} Liste d'anomalies { ligne, client, contrat, type, field, code, message, value }
 */
export function runComplexValidation(encoursData, engagementsCtrData) {
    const erreurs = [];
    const engagementIndex = indexEngagements(engagementsCtrData);

    (encoursData || []).forEach((enc, idx) => {
        const { cli, eve, ave } = extractJoinFields(enc);
        const key = buildJoinKey(cli, eve, ave);
        const eng = engagementIndex.get(key);

        const ctx = {
            ligne: idx + 1,
            cli: normKey(cli),
            eve: normKey(eve),
        };

        // Si aucun engagement correspondant n'est trouvé, on ne peut pas croiser.
        if (!eng) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "avertissement",
                field: "CLI/EVE/AVE",
                code: "CX_JOIN_000",
                message: `Aucun engagement initial correspondant trouvé pour la clé (CLI=${cli}, EVE=${eve}, AVE=${ave}).`,
                value: key,
            });
            return;
        }

        controleDuree(enc, eng, ctx, erreurs);
        controleCrdu(enc, eng, ctx, erreurs);
        controleFinCredit(enc, eng, ctx, erreurs);
        controleCrdZeroRes(enc, ctx, erreurs);
        controleImpayeSouffrance(enc, ctx, erreurs);
        controleSain(enc, ctx, erreurs);
    });

    return erreurs;
}

/**
 * Récupère automatiquement le flux cdr_ctrEngagements puis lance le contrôle.
 *
 * @param {Array} encoursData - Flux des encours déjà chargé.
 * @param {string} [url] - URL de la route cdr_ctrEngagements (optionnel).
 * @returns {Promise<Array>} Liste d'anomalies.
 */
export async function runComplexValidationFromApi(
    encoursData,
    url = CTR_ENGAGEMENTS_URL,
) {
    const res = await fetch(url, { headers: { Accept: "application/json" } });
    if (!res.ok) {
        throw new Error(
            `Échec de récupération du flux cdr_ctrEngagements (HTTP ${res.status})`,
        );
    }
    const engagementsCtrData = await res.json();
    return runComplexValidation(encoursData, engagementsCtrData);
}

export default runComplexValidation;
