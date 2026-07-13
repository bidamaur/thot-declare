/**
 * MODULE DE VALIDATION CDR : ENCOURS & ENGAGEMENTS (CEMAC)
 * Respecte strictement le Kit d'interfaçage Contribution des Données (CDR) v03/10/2023
 */

// --- RÉFÉRENTIELS ET CONSTANTES (Annexes du Kit) ---
export const REFERENTIELS = {
    StatutsContrat: ["00", "01", "02"], // 00: Actif, 01: Consolidé, 02: Clôturé [cite: 52, 53]
    MotifsCloture: ["01", "02", "03", "04", "05", "06", "07"], // Fin à terme, RA total, Consolidation... [cite: 59, 60, 61]
    TypesEngagement: ["01", "02", "03"], // 01: Avec échéancier, 02: Sans échéancier, 03: Cartes [cite: 62, 63]
    ModesRembEpargne: ["1", "2"], // 1: Déblocage, 2: Échéance [cite: 71]
    TypesTauxInt: ["00", "01"], // 00: Fixe, 01: Variable [cite: 78]
    IndicesReference: ["01", "02", "03", "04"], // TBB, LIBOR, EURIBOR, TIAO BEAC [cite: 79, 80, 81]
    Periodicites: ["00", "01", "02", "03", "04", "05", "06", "07"], // Irrégulier, Mensuel... [cite: 86, 87, 88]
    UnitesDuree: ["01", "02", "03"], // Jours, Mois, Années [cite: 91, 92]
    MaturitesCobac: ["01", "02", "03"], // Court, Moyen, Long terme [cite: 95, 96]
    MoyensRemb: ["01", "02", "03", "04", "05", "06", "07"], // Débit, Virement, Espèces... [cite: 102, 103, 104]
    TypesEcheance: ["01", "02"], // 01: Constante, 02: Variable [cite: 105]
    TypesAmortissement: ["01", "02", "03", "04"], // Constant, Dégressif, Progressif, In Fine [cite: 111, 112, 113]
    StatutsGarantie: ["01", "02", "03"], // Valide, Main levée, Mis en jeu [cite: 134, 135]
    RolesTitulaireCompte: ["01", "02"], // 01: Titulaire, 02: Mandataire [cite: 172]
};

// --- FONCTIONS UTILITAIRES DE VALIDATION SYNTAXIQUE ---
const MONTHS = {
    jan: 1,
    january: 1,
    janv: 1,
    janvier: 1,
    feb: 2,
    february: 2,
    fev: 2,
    fevrier: 2,
    février: 2,
    mar: 3,
    march: 3,
    mars: 3,
    apr: 4,
    april: 4,
    avr: 4,
    avril: 4,
    may: 5,
    mai: 5,
    jun: 6,
    june: 6,
    juin: 6,
    jul: 7,
    july: 7,
    jui: 7,
    juillet: 7,
    aug: 8,
    august: 8,
    aout: 8,
    août: 8,
    sep: 9,
    sept: 9,
    september: 9,
    septembre: 9,
    oct: 10,
    october: 10,
    octo: 10,
    octobre: 10,
    nov: 11,
    november: 11,
    novembre: 11,
    dec: 12,
    december: 12,
    decembre: 12,
};

function parseDateValue(dateStr) {
    if (!dateStr || dateStr === "") return null;

    const raw = String(dateStr).trim();
    if (!raw) return null;

    if (/^\d{8}$/.test(raw)) {
        const day = parseInt(raw.substring(0, 2), 10);
        const month = parseInt(raw.substring(2, 4), 10);
        const year = parseInt(raw.substring(4, 8), 10);
        if (month < 1 || month > 12 || day < 1 || day > 31) return null;
        const maxDay = new Date(year, month, 0).getDate();
        if (day > maxDay) return null;
        return { day, month, year };
    }

    const normalized = raw.replace(/\s+/g, " ").replace(/\./g, "-");
    const patterns = [
        /^(\d{1,2})[-/](\d{1,2}|[A-Za-zÀ-ÿ]+)[-/](\d{2,4})$/,
        /^(\d{4})[-/](\d{1,2})[-/](\d{1,2})$/,
        /^(\d{1,2})\s([A-Za-zÀ-ÿ]+)\s(\d{2,4})$/,
    ];

    for (const pattern of patterns) {
        const match = normalized.match(pattern);
        if (!match) continue;

        let day, month, year;
        if (pattern === patterns[1]) {
            year = parseInt(match[1], 10);
            month = parseInt(match[2], 10);
            day = parseInt(match[3], 10);
        } else {
            day = parseInt(match[1], 10);
            const monthToken = match[2];
            month = Number.isInteger(parseInt(monthToken, 10))
                ? parseInt(monthToken, 10)
                : MONTHS[
                      monthToken
                          .toLowerCase()
                          .normalize("NFD")
                          .replace(/[\u0300-\u036f]/g, "")
                  ];
            year = parseInt(match[3], 10);
        }

        if (!month || month < 1 || month > 12) return null;
        if (day < 1 || day > 31) return null;
        if (year < 100) year += year < 50 ? 2000 : 1900;
        const maxDay = new Date(year, month, 0).getDate();
        if (day > maxDay) return null;
        return { day, month, year };
    }

    return null;
}

export function validerDateFormat(dateStr) {
    if (!dateStr || dateStr === "") return true; // Si non obligatoire/vide [cite: 40]
    return parseDateValue(dateStr) !== null;
}

export function normaliserDateVersCdr(dateStr) {
    const parsed = parseDateValue(dateStr);
    if (!parsed) return "";
    return `${String(parsed.day).padStart(2, "0")}${String(parsed.month).padStart(2, "0")}${String(parsed.year)}`;
}

function validerTauxFormat(val) {
    const num = Number(val);
    if (isNaN(num)) return false;
    // Format attendu : ###.## (séparateur point, pas de %) [cite: 418]
    return /^\d+(\.\d{1,2})?$/.test(val.toString());
}

/**
 * Fonction Principale : Validation d'une ligne complète de déclaration <Ligne>
 * @param {Object} ligne - L'objet JSON représentant la ligne parsée du XML/Excel
 * @param {Object} contexte - Métadonnées de la ligne : { client, contrat }
 * @returns {Object} { valide: boolean, erreurs: Array, avertissements: Array }
 *   Chaque erreur/avertissement contient : { code, message, field, value, client, contrat }
 */
export function validerLigneCdr(ligne, contexte = {}) {
    let erreurs = [];
    let avertissements = [];

    const addErr = (code, msg, field = null, value = null) =>
        erreurs.push({
            code,
            message: msg,
            field,
            value: value === null || value === undefined ? "" : String(value),
            client: contexte.client ?? "",
            contrat: contexte.contrat ?? "",
        });
    const addWarn = (code, msg, field = null, value = null) =>
        avertissements.push({
            code,
            message: msg,
            field,
            value: value === null || value === undefined ? "" : String(value),
            client: contexte.client ?? "",
            contrat: contexte.contrat ?? "",
        });

    // ==========================================
    // 1. VALIDATION DE LA BALISE <Engagement>
    // ==========================================
    if (ligne.Engagement) {
        const eng = ligne.Engagement;

        // --- Contrôles Obligatoires (OBL) & Syntaxiques (SYN) ---
        if (!eng.RefContCmpt || eng.RefContCmpt === "")
            addErr(
                "ENG_OBL_001",
                "RefContCmpt est obligatoire.",
                "RefContCmpt",
                eng.RefContCmpt,
            ); // [cite: 50]
        if (!eng.CodAge || !/^\d{5}$/.test(eng.CodAge))
            addErr(
                "ENG_SYN_002",
                "CodAge doit comporter exactement 5 chiffres.",
                "CodAge",
                eng.CodAge,
            ); // [cite: 51, 442]

        if (!REFERENTIELS.StatutsContrat.includes(eng.Statut)) {
            addErr(
                "ENG_SYN_003",
                `Statut du contrat invalide ou absent (${eng.Statut}).`,
                "Statut",
                eng.Statut,
            ); // [cite: 52]
        }

        // -- Conditionnels liés au Statut --
        if (eng.Statut === "01") {
            // Consolidé [cite: 52]
            if (!eng.NatConso || !["0", "1"].includes(eng.NatConso))
                addErr(
                    "ENG_LOG_004",
                    "NatConso obligatoire (0 ou 1) si le contrat est Consolidé.",
                    "NatConso",
                    eng.NatConso,
                ); // [cite: 53, 54]
            if (
                eng.NatConso === "0" &&
                !REFERENTIELS.TypesConso?.includes(eng.TypConso)
            ) {
                if (!["0", "1", "2"].includes(eng.TypConso))
                    addErr(
                        "ENG_LOG_005",
                        "TypConso obligatoire (0, 1, 2) si NatConso = 0.",
                        "TypConso",
                        eng.TypConso,
                    ); // [cite: 55, 56]
            }
        }
        if (eng.Statut === "02") {
            // Clôturé [cite: 53]
            if (!REFERENTIELS.MotifsCloture.includes(eng.Motif))
                addErr(
                    "ENG_LOG_006",
                    "Motif de clôture obligatoire et doit être valide si le contrat est clôturé.",
                    "Motif",
                    eng.Motif,
                ); // [cite: 61]
        }

        if (!REFERENTIELS.TypesEngagement.includes(eng.TypEng))
            addErr(
                "ENG_SYN_007",
                "TypEng invalide (01, 02 ou 03 requis).",
                "TypEng",
                eng.TypEng,
            ); // [cite: 62, 63]
        if (!eng.NatEng || eng.NatEng.length !== 2)
            addErr(
                "ENG_SYN_008",
                "NatEng (Nature engagement COBAC) obligatoire sur 2 caractères.",
                "NatEng",
                eng.NatEng,
            ); // [cite: 64, 461]
        if (!eng.CodDev || eng.CodDev.length !== 3)
            addErr(
                "ENG_SYN_009",
                "CodDev (Devise d'origine) obligatoire sur 3 caractères.",
                "CodDev",
                eng.CodDev,
            ); // [cite: 65, 462]

        // -- Montants --
        if (isNaN(Number(eng.MntEng)) || Number(eng.MntEng) < 0)
            addErr(
                "ENG_SYN_010",
                "MntEng doit être un numérique positif (exprimé en XAF).",
                "MntEng",
                eng.MntEng,
            ); // [cite: 66, 68]
        if (
            eng.NatEng === "Affacturage" &&
            (!eng.MntCrCedee || Number(eng.MntCrCedee) <= 0)
        ) {
            // Exemple de règle métier textuelle [cite: 70]
            addErr(
                "ENG_LOG_011",
                "MntCrCedee obligatoire pour l'Affacturage.",
                "MntCrCedee",
                eng.MntCrCedee,
            ); // [cite: 70]
        }

        // -- Épargne Préalable --
        const mntEpargne = Number(eng.MntEpargne) || 0; // [cite: 37, 418]
        if (mntEpargne > 0) {
            if (!REFERENTIELS.ModesRembEpargne.includes(eng.ModRembEpargne))
                addErr(
                    "ENG_LOG_012",
                    "ModRembEpargne obligatoire si MntEpargne > 0.",
                    "ModRembEpargne",
                    eng.ModRembEpargne,
                ); // [cite: 71]
            if (
                eng.ModRembEpargne === "2" &&
                (!eng.TauxRenum || !validerTauxFormat(eng.TauxRenum))
            ) {
                addErr(
                    "ENG_LOG_013",
                    "TauxRenum obligatoire au format valide si remboursement à l'échéance (Mode 2).",
                    "TauxRenum",
                    eng.TauxRenum,
                ); // [cite: 72]
            }
        }

        // -- Dates --
        if (!validerDateFormat(eng.DatMep))
            addErr(
                "ENG_SYN_014",
                "DatMep doit être au format JJMMAAAA.",
                "DatMep",
                eng.DatMep,
            ); // [cite: 73, 413]
        if (!validerDateFormat(eng.DatDeb))
            addErr(
                "ENG_SYN_015",
                "DatDeb doit être au format JJMMAAAA.",
                "DatDeb",
                eng.DatDeb,
            ); // [cite: 83, 413]
        if (!validerDateFormat(eng.DatFin))
            addErr(
                "ENG_SYN_016",
                "DatFin doit être au format JJMMAAAA.",
                "DatFin",
                eng.DatFin,
            ); // [cite: 84, 413]
        if (
            eng.DatFin &&
            eng.DatDeb &&
            parseInt(eng.DatFin.substring(4, 8)) <
                parseInt(eng.DatDeb.substring(4, 8))
        ) {
            addErr(
                "ENG_LOG_017",
                "La Date de fin ne peut pas être antérieure à la Date de début.",
                "DatFin",
                eng.DatFin,
            );
        }

        // -- Taux d'intérêt --
        if (!eng.NatEng?.startsWith("EPS")) {
            // Si hors engagements par signature [cite: 75, 76]
            if (!eng.TxInt || !validerTauxFormat(eng.TxInt))
                addErr(
                    "ENG_LOG_018",
                    "TxInt (Taux nominal) obligatoire pour cette nature d'engagement.",
                    "TxInt",
                    eng.TxInt,
                ); // [cite: 74, 75]
        } else {
            if (!eng.TxComm || !validerTauxFormat(eng.TxComm))
                addErr(
                    "ENG_LOG_019",
                    "TxComm (Taux commission) obligatoire pour les Engagements Par Signature (EPS).",
                    "TxComm",
                    eng.TxComm,
                ); // [cite: 76]
        }
        if (!eng.TxEffGlob || !validerTauxFormat(eng.TxEffGlob))
            addErr(
                "ENG_SYN_020",
                "TxEffGlob (TEG) obligatoire et doit être un nombre valide.",
                "TxEffGlob",
                eng.TxEffGlob,
            ); // [cite: 77]

        // -- Règles spécifiques aux Crédits avec Échéancier (TypEng == '01') --
        if (eng.TypEng === "01") {
            // [cite: 62]
            if (!REFERENTIELS.TypesTxInt.includes(eng.TypTxInt))
                addErr(
                    "ENG_LOG_021",
                    "TypTxInt (00 ou 01) obligatoire pour les crédits avec échéancier.",
                    "TypTxInt",
                    eng.TypTxInt,
                ); // [cite: 78]
            if (eng.TypTxInt === "01") {
                // Variable [cite: 78]
                if (!REFERENTIELS.IndicesReference.includes(eng.IndRef))
                    addErr(
                        "ENG_LOG_022",
                        "IndRef obligatoire si le taux est Variable.",
                        "IndRef",
                        eng.IndRef,
                    ); // [cite: 81]
                if (!eng.Sprd || isNaN(Number(eng.Sprd)))
                    addErr(
                        "ENG_LOG_023",
                        "Spread obligatoire si le taux est Variable.",
                        "Sprd",
                        eng.Sprd,
                    ); // [cite: 82]
            }
            if (!REFERENTIELS.Periodicites.includes(eng.Periodicite))
                addErr(
                    "ENG_LOG_024",
                    "Periodicite obligatoire pour les crédits avec échéancier.",
                    "Periodicite",
                    eng.Periodicite,
                ); // [cite: 89]
            if (!validerDateFormat(eng.DatPreEchCap))
                addErr(
                    "ENG_LOG_025",
                    "DatPreEchCap obligatoire au format JJMMAAAA pour les crédits avec échéancier.",
                    "DatPreEchCap",
                    eng.DatPreEchCap,
                ); // [cite: 98]
            if (!eng.NbrEch || Number(eng.NbrEch) <= 0)
                addErr(
                    "ENG_LOG_026",
                    "NbrEch (Nombre initial d'échéances) obligatoire pour TypEng 01.",
                    "NbrEch",
                    eng.NbrEch,
                ); // [cite: 100]
            if (!REFERENTIELS.TypesEcheance.includes(eng.TypEch))
                addErr(
                    "ENG_LOG_027",
                    "TypEch obligatoire pour les crédits avec échéancier.",
                    "TypEch",
                    eng.TypEch,
                ); // [cite: 106]
            if (!eng.MntEch || Number(eng.MntEch) <= 0)
                addErr(
                    "ENG_LOG_028",
                    "MntEch obligatoire pour les crédits avec échéancier.",
                    "MntEch",
                    eng.MntEch,
                ); // [cite: 109]
            if (!REFERENTIELS.TypesAmortissement.includes(eng.TypAmo))
                addErr(
                    "ENG_LOG_029",
                    "TypAmo obligatoire pour les crédits avec échéancier.",
                    "TypAmo",
                    eng.TypAmo,
                ); // [cite: 113]
            if (!eng.TotInt || isNaN(Number(eng.TotInt)))
                addErr(
                    "ENG_LOG_030",
                    "TotInt obligatoire pour les crédits avec échéancier.",
                    "TotInt",
                    eng.TotInt,
                ); // [cite: 115]
        }

        if (!REFERENTIELS.UnitesDuree.includes(eng.UnitDur))
            addErr(
                "ENG_SYN_031",
                "UnitDur obligatoire (01, 02, 03).",
                "UnitDur",
                eng.UnitDur,
            ); // [cite: 90, 91, 92]
        if (!eng.Duree || isNaN(Number(eng.Duree)))
            addErr(
                "ENG_SYN_032",
                "Duree du crédit obligatoire.",
                "Duree",
                eng.Duree,
            ); // [cite: 93]
        if (!REFERENTIELS.MaturitesCobac.includes(eng.Maturite))
            addErr(
                "ENG_SYN_033",
                "Maturite COBAC obligatoire (01, 02, 03).",
                "Maturite",
                eng.Maturite,
            ); // [cite: 94, 95, 96]

        if (eng.DatEve && !validerDateFormat(eng.DatEve))
            addErr(
                "ENG_SYN_034",
                "DatEve doit être au format JJMMAAAA.",
                "DatEve",
                eng.DatEve,
            ); // [cite: 118, 413]

        // --- Validation des Blocs Répétitifs Embarqués ---

        // Balise <Consolidation> (Optionnelle/Répétitive)
        if (eng.Consolidation && Array.isArray(eng.Consolidation)) {
            eng.Consolidation.forEach((c, idx) => {
                if (!c.RefInt || c.RefInt === "")
                    addErr(
                        "CONSO_OBL",
                        `RefInt manquant dans le bloc de consolidation index ${idx}.`,
                        `Consolidation[${idx}].RefInt`,
                        c.RefInt,
                    ); // [cite: 121]
            });
        }

        // Balise <TitulaireEngagement> (Obligatoire/Répétitive)
        if (!eng.TitulaireEngagement || eng.TitulaireEngagement.length === 0) {
            addErr(
                "TIT_OBL_001",
                "Au moins un bloc <TitulaireEngagement> est obligatoire.",
                "TitulaireEngagement",
                "",
            ); // [cite: 48]
        } else {
            eng.TitulaireEngagement.forEach((t, idx) => {
                if (!t.IdInt || t.IdInt === "")
                    addErr(
                        "TIT_SYN_002",
                        `IdInt du titulaire index ${idx} est obligatoire.`,
                        `TitulaireEngagement[${idx}].IdInt`,
                        t.IdInt,
                    ); // [cite: 122]
            });
        }

        // Balise <GarantieAffectee> (Optionnelle/Répétitive)
        if (eng.GarantieAffectee && Array.isArray(eng.GarantieAffectee)) {
            eng.GarantieAffectee.forEach((g, idx) => {
                if (!g.RefIntGar)
                    addErr(
                        "GAR_SYN_001",
                        `RefIntGar absent à l'index ${idx}.`,
                        `GarantieAffectee[${idx}].RefIntGar`,
                        g.RefIntGar,
                    ); // [cite:123]
                if (!g.NatGar || g.NatGar.length !== 3)
                    addErr(
                        "GAR_SYN_002",
                        `NatGar à l'index ${idx} doit comporter 3 caractères.`,
                        `GarantieAffectee[${idx}].NatGar`,
                        g.NatGar,
                    ); // [cite: 124]
                if (isNaN(Number(g.MntGar)) || isNaN(Number(g.MntAffGar)))
                    addErr(
                        "GAR_SYN_003",
                        `MntGar et MntAffGar doivent être numériques.`,
                        `GarantieAffectee[${idx}].MntGar`,
                        g.MntGar,
                    ); // [cite: 126, 127]
                if (!REFERENTIELS.StatutsGarantie.includes(g.StatutGar))
                    addErr(
                        "GAR_SYN_004",
                        `StatutGar invalide à l'index ${idx}.`,
                        `GarantieAffectee[${idx}].StatutGar`,
                        g.StatutGar,
                    ); // [cite: 134]

                // Si garant tiers [cite: 132]
                if (
                    g.IdIntGarant &&
                    (!g.NomNaiGarant || g.NomNaiGarant === "")
                ) {
                    addWarn(
                        "GAR_LOG_005",
                        `Le Nom/Raison sociale du Garant est recommandé si IdIntGarant est fourni.`,
                        `GarantieAffectee[${idx}].NomNaiGarant`,
                        g.NomNaiGarant,
                    ); // [cite: 133]
                }
            });
        }
    }

    // ==========================================
    // 2. VALIDATION DE LA BALISE <Encours>
    // ==========================================
    if (ligne.Encours) {
        const enc = ligne.Encours;
        const typEng = ligne.Engagement ? ligne.Engagement.TypEng : null; // [cite: 62]

        if (!enc.RefContCmpt || enc.RefContCmpt === "")
            addErr(
                "ENC_OBL_001",
                "RefContCmpt est obligatoire dans l'Encours.",
                "RefContCmpt",
                enc.RefContCmpt,
            ); // [cite: 136]
        if (enc.DatPai && !validerDateFormat(enc.DatPai))
            addErr(
                "ENC_SYN_002",
                "DatPai doit respecter le format JJMMAAAA.",
                "DatPai",
                enc.DatPai,
            ); // [cite: 137, 413]
        if (isNaN(Number(enc.MntTotUtil)))
            addErr(
                "ENC_SYN_003",
                "MntTotUtil doit être un montant numérique valide.",
                "MntTotUtil",
                enc.MntTotUtil,
            ); // [cite: 145]

        // -- Règles Métier spécifiques basées sur le Type d'engagement (TypEng == '01') --
        if (typEng === "01") {
            // [cite: 62]
            if (!enc.DatEch || !validerDateFormat(enc.DatEch))
                addErr(
                    "ENC_LOG_004",
                    "DatEch obligatoire au format JJMMAAAA si crédit avec échéancier.",
                    "DatEch",
                    enc.DatEch,
                ); // [cite: 138, 139]
            if (!enc.MntCrd || isNaN(Number(enc.MntCrd)))
                addErr(
                    "ENC_LOG_005",
                    "MntCrd (Capital restant dû) obligatoire si crédit avec échéancier.",
                    "MntCrd",
                    enc.MntCrd,
                ); // [cite: 143, 144]
            if (enc.nbrEchPay === undefined || isNaN(Number(enc.nbrEchPay)))
                addErr(
                    "ENC_LOG_006",
                    "nbrEchPay obligatoire si crédit avec échéancier.",
                    "nbrEchPay",
                    enc.nbrEchPay,
                ); // [cite: 147, 148]
            if (enc.nbrEchImp === undefined || isNaN(Number(enc.nbrEchImp)))
                addErr(
                    "ENC_LOG_007",
                    "nbrEchImp obligatoire si crédit avec échéancier.",
                    "nbrEchImp",
                    enc.nbrEchImp,
                ); // [cite: 149, 150]
            if (enc.nbrEchRes === undefined || isNaN(Number(enc.nbrEchRes)))
                addErr(
                    "ENC_LOG_008",
                    "nbrEchRes obligatoire si crédit avec échéancier.",
                    "nbrEchRes",
                    enc.nbrEchRes,
                ); // [cite: 151, 152]
            if (!enc.MntCreRat || isNaN(Number(enc.MntCreRat)))
                addErr(
                    "ENC_LOG_009",
                    "MntCreRat (Créances rattachées) obligatoire si crédit avec échéancier.",
                    "MntCreRat",
                    enc.MntCreRat,
                ); // [cite: 157, 158]
        }

        // -- Découverts (NatEng == '26') --
        if (ligne.Engagement && ligne.Engagement.NatEng === "26") {
            if (!enc.MntAgi || isNaN(Number(enc.MntAgi)))
                addErr(
                    "ENC_LOG_010",
                    "MntAgi (Montant des agios) obligatoire pour les découverts.",
                    "MntAgi",
                    enc.MntAgi,
                ); // [cite: 142]
        }

        // -- Souffrance & Dépréciation --
        const mntSouf = Number(enc.MntCreSouf) || 0; // [cite: 37, 418]
        if (mntSouf > 0) {
            if (enc.nbrJrsImp === undefined || Number(enc.nbrJrsImp) <= 0) {
                addErr(
                    "ENC_LOG_011",
                    "nbrJrsImp (Nombre de jours en souffrance) obligatoire et supérieur à 0 si MntCreSouf > 0.",
                    "nbrJrsImp",
                    enc.nbrJrsImp,
                ); // [cite: 160, 161]
            }
        }
        if (!enc.ClaDeprec || enc.ClaDeprec.length !== 2) {
            addErr(
                "ENC_SYN_012",
                "ClaDeprec (Classe de dépréciation réglementaire) obligatoire sur 2 caractères.",
                "ClaDeprec",
                enc.ClaDeprec,
            ); // [cite: 162]
        }
    }

    // ==========================================
    // 3. VALIDATION DE LA BALISE <CompteDebiteur>
    // ==========================================
    if (ligne.CompteDebiteur) {
        const cpt = ligne.CompteDebiteur;

        if (!cpt.RefContCmpt || cpt.RefContCmpt === "")
            addErr(
                "CPT_OBL_001",
                "RefContCmpt (ou Numéro de compte) obligatoire.",
                "RefContCmpt",
                cpt.RefContCmpt,
            ); // [cite: 164]
        if (!cpt.CodAge || !/^\d{5}$/.test(cpt.CodAge))
            addErr(
                "CPT_SYN_002",
                "CodAge agence obligatoire (5 chiffres).",
                "CodAge",
                cpt.CodAge,
            ); // [cite: 165]
        if (!cpt.SolDeb || isNaN(Number(cpt.SolDeb)))
            addErr(
                "CPT_SYN_003",
                "SolDeb doit être un montant numérique valide.",
                "SolDeb",
                cpt.SolDeb,
            ); // [cite: 166]
        if (
            cpt.NbrJrsDebNonAut === undefined ||
            isNaN(Number(cpt.NbrJrsDebNonAut))
        )
            addErr(
                "CPT_SYN_004",
                "NbrJrsDebNonAut obligatoire.",
                "NbrJrsDebNonAut",
                cpt.NbrJrsDebNonAut,
            ); // [cite: 167]
        if (!cpt.ClassDeprec || cpt.ClassDeprec.length !== 2)
            addErr(
                "CPT_SYN_005",
                "ClassDeprec obligatoire sur 2 caractères.",
                "ClassDeprec",
                cpt.ClassDeprec,
            ); // [cite: 170]

        // --- Validation du Bloc Embarqué <TitulaireCompte> ---
        if (!cpt.TitulaireCompte || cpt.TitulaireCompte.length === 0) {
            addErr(
                "CPT_LOG_006",
                "Au moins un <TitulaireCompte> est requis pour un compte débiteur.",
                "TitulaireCompte",
                "",
            ); // [cite: 48]
        } else {
            cpt.TitulaireCompte.forEach((tc, idx) => {
                if (!tc.IdInt)
                    addErr(
                        "CPT_TIT_OBL",
                        `IdInt manquant pour le titulaire compte index ${idx}.`,
                        `TitulaireCompte[${idx}].IdInt`,
                        tc.IdInt,
                    ); // [cite: 171]
                if (!REFERENTIELS.RolesTitulaireCompte.includes(tc.Role)) {
                    addErr(
                        "CPT_TIT_SYN",
                        `Rôle invalide ou absent (${tc.Role}) à l'index ${idx} (01=Titulaire, 02=Mandataire).`,
                        `TitulaireCompte[${idx}].Role`,
                        tc.Role,
                    ); // [cite: 172]
                }
            });
        }
    }

    // Si aucune erreur bloquante n'a été poussée dans l'array [cite: 178]
    return {
        valide: erreurs.length === 0, // [cite: 178]
        erreurs: erreurs,
        avertissements: avertissements,
    };
}
