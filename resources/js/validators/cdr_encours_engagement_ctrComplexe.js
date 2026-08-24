/**
 * MODULE DE CONTRÔLE COMPLEXE CDR : ENCOURS vs ENGAGEMENTS INITIAUX (CEMAC)
 * Respecte le Kit d'interfaçage Contribution des Données (CDR) v03/10/2023.
 *
 * Ce module intègre tous les contrôles logiques du Kit CDR de la Centrale des Risques CEMAC :
 * - Alignement et règles COBAC/CEMAC sur la Classe de Dépréciation (CLADEPREC)
 * - Incohérences LOG0041 / LOG0042 (Souffrance, Impayés, Jours de retard)
 * - Règles de répartition du montant en souffrance (Capitl / Intérêts / Taxes / Agios)
 * - Contrôles de cohérence Encours vs Engagement initial
 * - Règles spécifiques Établissements de Crédit (Banques) vs EMF
 */

// --- CONSTANTES ---
const CTR_ENGAGEMENTS_URL = "http://127.0.0.1:8000/api/cdr_ctrEngagements";

/**
 * Codes de dépréciation réglementaires CEMAC :
 * 01: Sain / Normal
 * 02: À surveiller / Sensible (impayé < 90j Banques ou < 45j EMF)
 * 03: Inquiétant / Souffrance initiale (90j-180j Banques ou 45j-90j EMF)
 * 04: Compromis / Impayés avérés (181j-360j Banques ou 91j-180j EMF)
 * 05: Irrécouvrable / Pertes (> 360j Banques ou > 180j EMF)
 * 07: Douteux / Souffrance au plus 1 an (utilisé par certaines déclinaisons CBS)
 */
const CLASSES_SAINES = ["01"];
const CLASSES_SENSIBLES = ["02"];
const CLASSES_SOUFFRANCE = ["03", "04", "05", "07"];

// --- UTILITAIRES ---

function toNumber(val) {
    if (val === null || val === undefined || val === "") return NaN;
    const cleaned = String(val).replace(/[\s]/g, "").replace(/,/g, ".");
    return Number(cleaned);
}

function normKey(val) {
    return String(val ?? "")
        .trim()
        .toUpperCase();
}

function buildJoinKey(cli, eve, ave) {
    return `${normKey(cli)}|${normKey(eve)}|${normKey(ave)}`;
}

function extractJoinFields(record) {
    const getField = (rec, candidates) => {
        for (const c of candidates) {
            if (rec[c] !== undefined && rec[c] !== null) return rec[c];
        }
        return undefined;
    };
    return {
        cli: getField(record, ["CLI", "cli"]),
        eve: getField(record, ["EVE", "eve"]),
        ave: getField(record, ["AVE", "ave"]),
    };
}

function indexEngagements(engagementsCtrData) {
    const index = new Map();
    (engagementsCtrData || []).forEach((eng) => {
        const { cli, eve, ave } = extractJoinFields(eng);
        const key = buildJoinKey(cli, eve, ave);
        if (!index.has(key)) index.set(key, eng);
    });
    return index;
}

function pushAnomalie(
    erreurs,
    { ligne, cli, eve, type, field, code, message, value },
) {
    erreurs.push({
        ligne: ligne ?? "",
        client: cli ?? "",
        contrat: eve ?? "",
        type: type || "erreur",
        field: field ?? "",
        code: code ?? "",
        message: message ?? "",
        value: value === null || value === undefined ? "" : String(value),
    });
}

// --- BLOC DE CONTRÔLES : CLASSE DE DÉPRÉCIATION ET SOUS-SOUPAPE IMPAYÉS (LOG0041) ---

/**
 * C01. Contrôle complet de la classe de dépréciation (Règle LOG0041 CDR)
 * Incohérences entre CLADEPREC, MNTCRESOUF, NBRECHIMP, NBRJRSIMP et ESTSENSIBLE.
 */
function controleClasseDepreciation(enc, ctx, erreurs, isEMF = false) {
    const classRaw = normKey(enc.CLADEPREC);
    // Tolérance sur le formatage à deux chiffres (ex: "1" -> "01")
    const classe = classRaw.length === 1 ? `0${classRaw}` : classRaw;

    const mntSouf = toNumber(enc.MNTCRESOUF ?? enc.MntCreSouf);
    const nbrImp = toNumber(enc.NBRECHIMP ?? enc.nbrEchImp);
    const nbrJrs = toNumber(enc.NBRJRSIMP ?? enc.nbrJrsImp);
    const estSensible = toNumber(enc.ESTSENSIBLE ?? enc.estSensible);

    // Seuil de reclassification automatique en souffrance selon le type d'établissement
    const seuilJoursSouffrance = isEMF ? 45 : 90;

    // --- 1. CAS CRÉDIT SAIN (01) ---
    if (CLASSES_SAINES.includes(classe)) {
        if (!Number.isNaN(mntSouf) && mntSouf > 0) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "erreur",
                field: "CLADEPREC / MNTCRESOUF",
                code: "LOG0041_SAIN_MNT",
                message: `Crédit classé Sain (${classe}) mais présente un montant en souffrance (${mntSouf} XAF).`,
                value: mntSouf,
            });
        }
        if (!Number.isNaN(nbrImp) && nbrImp > 0) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "erreur",
                field: "CLADEPREC / NBRECHIMP",
                code: "LOG0041_SAIN_ECH",
                message: `Crédit classé Sain (${classe}) mais déclare ${nbrImp} échéance(s) impayée(s).`,
                value: nbrImp,
            });
        }
        if (!Number.isNaN(nbrJrs) && nbrJrs > 0) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "erreur",
                field: "CLADEPREC / NBRJRSIMP",
                code: "LOG0041_SAIN_JRS",
                message: `Crédit classé Sain (${classe}) mais déclare ${nbrJrs} jour(s) de retard.`,
                value: nbrJrs,
            });
        }
    }

    // --- 2. CAS CRÉDIT À SURVEILLER / SENSIBLE (02) ---
    if (CLASSES_SENSIBLES.includes(classe)) {
        // Un crédit sensible peut avoir des impayés récents, mais pas au-delà des seuils
        if (!Number.isNaN(nbrJrs) && nbrJrs >= seuilJoursSouffrance) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "erreur",
                field: "CLADEPREC / NBRJRSIMP",
                code: "LOG0041_SENSIBLE_SEUIL",
                message: `Crédit classé Sensible (${classe}) alors que le nombre de jours d'impayés (${nbrJrs}j) atteint/dépasse le seuil de bascule en souffrance (${seuilJoursSouffrance}j). Reclassement obligatoire.`,
                value: nbrJrs,
            });
        }
    }

    // --- 3. CAS CRÉDIT EN SOUFFRANCE / DÉPRÉCIÉ (03, 04, 05, 07) ---
    if (CLASSES_SOUFFRANCE.includes(classe)) {
        if (Number.isNaN(mntSouf) || mntSouf <= 0) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "erreur",
                field: "CLADEPREC / MNTCRESOUF",
                code: "LOG0041_SOUF_MNT_ZERO",
                message: `Crédit classé en Souffrance / Déprécié (${classe}) mais le montant en souffrance (MNTCRESOUF) est nul ou non renseigné.`,
                value: mntSouf,
            });
        }
        if (
            (Number.isNaN(nbrImp) || nbrImp === 0) &&
            (Number.isNaN(nbrJrs) || nbrJrs === 0)
        ) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "erreur",
                field: "CLADEPREC / IMPAYES",
                code: "LOG0041_SOUF_SANS_IMP",
                message: `Crédit classé en Souffrance (${classe}) sans déclaration d'échéance impayée ni jours de retard.`,
                value: `nbrImp:${nbrImp}, nbrJrs:${nbrJrs}`,
            });
        }
    }

    // --- 4. COHÉRENCE DU FANION estSensible ---
    if (!Number.isNaN(estSensible)) {
        if (CLASSES_SENSIBLES.includes(classe) && estSensible !== 1) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "avertissement",
                field: "ESTSENSIBLE",
                code: "CX_SENS_001",
                message: `Le crédit est classé '02' (Sensible) mais le champ 'estSensible' vaut ${estSensible} au lieu de 1.`,
                value: estSensible,
            });
        }
    }
}

/**
 * C02. Contrôle de la sommation de la ventilation du montant en souffrance
 * MNTCRESOUF = MNTCAPSOUF + MNTINTSOUF + MNTTAXSOUF + MNTAGIOSSOUF
 */
function controleVentilationSouffrance(enc, ctx, erreurs) {
    const mntSouf = toNumber(enc.MNTCRESOUF ?? enc.MntCreSouf);
    const capSouf = toNumber(enc.MNTCAPSOUF ?? enc.MntCapSouf) || 0;
    const intSouf = toNumber(enc.MNTINTSOUF ?? enc.MntIntSouf) || 0;
    const taxSouf = toNumber(enc.MNTTAXSOUF ?? enc.MntTaxSouf) || 0;
    const agiSouf = toNumber(enc.MNTAGIOSSOUF ?? enc.MntAgiosSouf) || 0;

    if (Number.isNaN(mntSouf) || mntSouf <= 0) return;

    const sommeVentilee = capSouf + intSouf + taxSouf + agiSouf;
    const ecart = Math.abs(mntSouf - sommeVentilee);

    // Tolérance d'arrondi de 1 XAF
    if (ecart > 1) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "MNTCRESOUF",
            code: "LOG0042_VENTILATION",
            message: `Le montant total en souffrance (${mntSouf}) ne correspond pas à la somme de sa ventilation (${sommeVentilee} = Cap:${capSouf} + Int:${intSouf} + Tax:${taxSouf} + Agi:${agiSouf}).`,
            value: `Total:${mntSouf} | Somme:${sommeVentilee}`,
        });
    }
}

/**
 * C03. Contrôle des provisions imposées si crédit déprécié (MNTPRO)
 */
function controleProvisions(enc, ctx, erreurs) {
    const classe = normKey(enc.CLADEPREC);
    const mntPro = toNumber(enc.MNTPRO ?? enc.MntPro);
    const mntSouf = toNumber(enc.MNTCRESOUF ?? enc.MntCreSouf);

    if (CLASSES_SOUFFRANCE.includes(classe)) {
        if (Number.isNaN(mntPro) || mntPro < 0) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "avertissement",
                field: "MNTPRO",
                code: "CX_PROV_001",
                message: `Créance dépréciée (${classe}) mais le montant de la provision (MNTPRO) n'est pas correctement complété.`,
                value: mntPro,
            });
        }
        if (
            !Number.isNaN(mntPro) &&
            !Number.isNaN(mntSouf) &&
            mntPro > mntSouf
        ) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "avertissement",
                field: "MNTPRO",
                code: "CX_PROV_002",
                message: `Le montant de la provision (${mntPro}) dépasse le montant total de la créance en souffrance (${mntSouf}).`,
                value: mntPro,
            });
        }
    }
}

// --- CONTRÔLES TRANSVERSAUX ET D'ENGAGEMENT ---

function controleDuree(enc, eng, ctx, erreurs) {
    const nbrPay = toNumber(enc.NBRECHPAY ?? enc.nbrEchPay);
    const nbrImp = toNumber(enc.NBRECHIMP ?? enc.nbrEchImp);
    const nbrRes = toNumber(enc.NBRECHRES ?? enc.nbrEchRes);
    const duree = toNumber(eng.DUREE ?? eng.Duree);

    if ([nbrPay, nbrImp, nbrRes, duree].some((v) => Number.isNaN(v))) return;

    const somme = nbrPay + nbrImp + nbrRes;
    if (somme > duree) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "NBRECHPAY+NBRECHIMP+NBRECHRES",
            code: "CX_DUREE_001",
            message: `La somme des échéances (${somme} = Pay:${nbrPay} + Imp:${nbrImp} + Res:${nbrRes}) dépasse la durée initiale du contrat (${duree}).`,
            value: somme,
        });
    }
}

function controleCrdu(enc, eng, ctx, erreurs) {
    const mntCrd = toNumber(enc.MNTCRD ?? enc.MntCrd);
    const mntEng = toNumber(eng.MNTENG ?? eng.MntEng);

    if (Number.isNaN(mntCrd) || Number.isNaN(mntEng)) return;

    if (mntCrd > mntEng) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "MNTCRD",
            code: "CX_CRDU_002",
            message: `Le capital restant dû (${mntCrd}) est supérieur au montant initial accordé (${mntEng}).`,
            value: mntCrd,
        });
    }
}

function controleFinCredit(enc, eng, ctx, erreurs) {
    const nbrPay = toNumber(enc.NBRECHPAY ?? enc.nbrEchPay);
    const duree = toNumber(eng.DUREE ?? eng.Duree);
    const mntCrd = toNumber(enc.MNTCRD ?? enc.MntCrd);

    if ([nbrPay, duree, mntCrd].some((v) => Number.isNaN(v))) return;

    if (nbrPay === duree && mntCrd > 0) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "MNTCRD",
            code: "CX_FIN_003",
            message:
                "Toutes les échéances prévues ont été payées, mais le capital restant dû (MNTCRD) n'est pas égal à zéro.",
            value: mntCrd,
        });
    }
}

function controleCrdZeroRes(enc, ctx, erreurs) {
    const mntCrd = toNumber(enc.MNTCRD ?? enc.MntCrd);
    const nbrRes = toNumber(enc.NBRECHRES ?? enc.nbrEchRes);

    if ([mntCrd, nbrRes].some((v) => Number.isNaN(v))) return;

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

function controleCrdEcheances(enc, ctx, erreurs) {
    const mntCrd = toNumber(enc.MNTCRD ?? enc.MntCrd);
    const nbrRes = toNumber(enc.NBRECHRES ?? enc.nbrEchRes);

    if ([mntCrd, nbrRes].some((v) => Number.isNaN(v))) return;

    if (nbrRes === 0 && mntCrd !== 0) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "MNTCRD",
            code: "CX_COH_008",
            message: `Aucune échéance restante déclarée, mais le capital restant dû vaut ${mntCrd} XAF. Il doit être nul lorsque toutes les échéances sont payées.`,
            value: mntCrd,
        });
    }

    if (nbrRes === 1 && mntCrd === 0) {
        pushAnomalie(erreurs, {
            ...ctx,
            type: "erreur",
            field: "NBRECHRES",
            code: "CX_COH_009",
            message: `Il reste une échéance (${nbrRes}) mais le capital restant dû est nul. Vérifiez le montant restant et le nombre d'échéances restantes.`,
            value: nbrRes,
        });
    }
}

// --- FONCTION PRINCIPALE ---

/**
 * Exécute l'ensemble des contrôles complexes du Kit CDR.
 *
 * @param {Array} encoursData - Tableau du flux des encours.
 * @param {Array} engagementsCtrData - Tableau du flux des engagements initiaux.
 * @param {Object} options - Options de validation (ex: { isEMF: false }).
 * @returns {Array} Liste des anomalies générées.
 */
export function runComplexValidation(
    encoursData,
    engagementsCtrData,
    options = { isEMF: false },
) {
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

        // 1. Contrôle d'existence du contrat d'origine
        if (!eng) {
            pushAnomalie(erreurs, {
                ...ctx,
                type: "avertissement",
                field: "CLI/EVE/AVE",
                code: "CX_JOIN_000",
                message: `Aucun contrat d'engagement d'origine trouvé pour la clé composite (${cli} | ${eve} | ${ave}).`,
                value: key,
            });
        } else {
            // Contrôles croisés Encours vs Engagements
            controleDuree(enc, eng, ctx, erreurs);
            controleCrdu(enc, eng, ctx, erreurs);
            controleFinCredit(enc, eng, ctx, erreurs);
        }

        // 2. Contrôles propres au bloc Encours (Dépréciation, Ventilation, Impayés)
        controleClasseDepreciation(enc, ctx, erreurs, options.isEMF);
        controleVentilationSouffrance(enc, ctx, erreurs);
        controleProvisions(enc, ctx, erreurs);
        controleCrdZeroRes(enc, ctx, erreurs);
        controleCrdEcheances(enc, ctx, erreurs);
    });

    return erreurs;
}

/**
 * Récupère le flux distant d'engagements et exécute la validation.
 */
export async function runComplexValidationFromApi(
    encoursData,
    url = CTR_ENGAGEMENTS_URL,
    options = { isEMF: false },
) {
    const res = await fetch(url, { headers: { Accept: "application/json" } });
    if (!res.ok) {
        throw new Error(
            `Échec de récupération du flux cdr_ctrEngagements (HTTP ${res.status})`,
        );
    }
    const engagementsCtrData = await res.json();
    return runComplexValidation(encoursData, engagementsCtrData, options);
}

export default runComplexValidation;
