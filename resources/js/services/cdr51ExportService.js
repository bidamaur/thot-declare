import { normaliserDateVersCdr } from "../validators/cdr_encours_engagement.js";

// --- Utilitaires de formatage XML ---
const toXmlAttrValue = (value) => {
    if (value === undefined || value === null) return "";
    const str = String(value).trim();
    return str
        .replace(/&/g, "&amp;")
        .replace(/"/g, "&quot;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/'/g, "&apos;");
};

const attr = (name, value) => ` ${name}="${toXmlAttrValue(value)}"`;
const selfClosing = (tag, attrs) => `<${tag}${attrs} />`;

// Toute date enfant doit être strictement au format JJMMAAAA (8 chiffres, sans séparateur)
const normalizeDate = (val) => {
    if (!val) return "";
    return normaliserDateVersCdr(val);
};

// Récupère une valeur brute (chaîne non null/undefined, trimmée)
const getVal = (row, field) => {
    const v = row ? row[field] : undefined;
    if (v === undefined || v === null) return "";
    const s = String(v).trim();
    return s;
};

// Construit les attributs dans l'ordre strict imposé par le kit CDR.
// pairs = [ [xmlAttr, jsonField, isDate?], ... ]
const buildAttrsInOrder = (pairs, row) =>
    pairs
        .map(([xml, field, isDate]) => {
            let v = getVal(row, field);
            if (isDate) v = normalizeDate(v);
            return attr(xml, v);
        })
        .join("");

// --- ORDRE STRICT DES ATTRIBUTS (balise <Declaration>) ---
// NumDec, CodPay, CodDec, TypDec, NatDec, NbrDec, DatDec, DatArr, comment
const buildDeclarationAttrs = (cfg) =>
    attr("NumDec", cfg.NumDec) +
    attr("CodPay", cfg.CodPay) +
    attr("CodDec", cfg.CodDec) +
    attr("TypDec", "51") +
    attr("NatDec", cfg.NatDec) +
    attr("NbrDec", cfg.NbrDec) +
    attr("DatDec", cfg.DatDec) +
    attr("DatArr", cfg.DatArr) +
    attr("comment", cfg.comment);

// --- ORDRE STRICT DES ATTRIBUTS (balise <Engagement>) ---
const ENGAGEMENT_FIELDS = [
    ["RefContCmpt", "REFCONTCMPT"],
    ["CodAge", "CODAGE"],
    ["Statut", "STATUT"],
    ["NatConso", "NATCONSO"],
    ["TypConso", "TYPCONSO"],
    ["Motif", "MOTIF"],
    ["TypEng", "TYPENG"],
    ["NatEng", "NATENG"],
    ["CodDev", "CODDEV"],
    ["MntEng", "MNTENG"],
    ["MntCrCedee", "MNTCRCEDEE"],
    ["MntEpargne", "MNTEPARGNE"],
    ["ModRembEpargne", "MODREMBEPARGNE"],
    ["TauxRenum", "TAUXRENUM"],
    ["DatMep", "DATMEP", true],
    ["TxInt", "TXINT"],
    ["TxComm", "TXCOMM"],
    ["TxBonifie", "TXBONIFIE"],
    ["TxEffGlob", "TXEFFGLOB"],
    ["TypTxInt", "TYPTXINT"],
    ["IndRef", "INDREF"],
    ["Sprd", "SPRD"],
    ["DatDeb", "DATDEB", true],
    ["DatFin", "DATFIN", true],
    ["Periodicite", "PERIODICITE"],
    ["UnitDur", "UNITDUR"],
    ["Duree", "DUREE"],
    ["Maturite", "MATURITE"],
    ["DatPreEchCap", "DATPREECHCAP", true],
    ["NbrEch", "NBRECH"],
    ["MoyRem", "MOYREM"],
    ["TypEch", "TYECH"],
    ["MntEch", "MNTECH"],
    ["TypAmo", "TYAMO"],
    ["TotInt", "TOTINT"],
    ["fraDos", "FRADOS"],
    ["fraAnnexe", "FRANNEXE"],
    ["MntPrm", "MNTPRM"],
    ["MntTax", "MNTTAX"],
    ["DatEve", "DATEVE", true],
];

// --- ORDRE STRICT DES ATTRIBUTS (balise <Encours>) ---
const ENCOURS_FIELDS = [
    ["RefContCmpt", "REFCONTCMPT"],
    ["DatPai", "DATPAI", true],
    ["DatEch", "DATECH", true],
    ["MntPay", "MNTPAY"],
    ["MntAgi", "MNTAGI"],
    ["MntCrd", "MNTCRD"],
    ["estSensible", "ESTSENSIBLE"],
    ["MntTotUtil", "MNTTOTUTIL"],
    ["nbrEchPay", "NBRECHPAY"],
    ["nbrEchImp", "NBRECHIMP"],
    ["nbrEchRes", "NBRECHRES"],
    ["MntCreSouf", "MNTCRESOUF"],
    ["MntCapSouf", "MNTCAPSOUF"],
    ["MntIntSouf", "MNTINTSOUF"],
    ["MntTaxSouf", "MNTTAXSOUF"],
    ["MntAgiosSouf", "MNTAGIOSSOUF"],
    ["MntCreRat", "MNTERAT"],
    ["MntPro", "MNTPRO"],
    ["nbrJrsImp", "NBRJRSIMP"],
    ["ClaDeprec", "CLADEPREC"],
];

// --- ORDRE STRICT DES ATTRIBUTS (balise <CompteDebiteur>) ---
const CPTDEB_FIELDS = [
    ["RefContCmpt", "RefContCmpt"],
    ["CodAge", "CodAge"],
    ["SolDeb", "SolDeb"],
    ["NbrJrsDebNonAut", "NbrJrsDebNonAut"],
    ["MntAgi", "MntAgi"],
    ["MntProv", "MntProv"],
    ["ClassDeprec", "ClassDeprec"],
];

// --- ORDRE STRICT DES ATTRIBUTS (balise <GarantieAffectee>) ---
const GARANTIE_FIELDS = [
    ["RefIntGar", "RefIntGar"],
    ["NatGar", "NatGar"],
    ["CodDevGar", "CodDevGar"],
    ["MntGar", "MntGar"],
    ["MntAffGar", "MntAffGar"],
    ["RefExtGar", "RefExtGar"],
    ["TypRefGar", "TypRefGar"],
    ["IdIntGarant", "IdIntGarant"],
    ["NomNaiGarant", "NomNaiGarant"],
    ["StatutGar", "StatutGar"],
];

// Date d'arrêté = DERNIER JOUR du mois/année sélectionné (format JJMMAAAA)
const computeDatArr = (yyyymm) => {
    if (!yyyymm) return "";
    const parts = String(yyyymm).split("-");
    if (parts.length !== 2) return "";
    const y = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10);
    if (isNaN(y) || isNaN(m) || m < 1 || m > 12) return "";
    const lastDay = new Date(y, m, 0).getDate();
    return `${String(lastDay).padStart(2, "0")}${String(m).padStart(2, "0")}${y}`;
};

const computeDatDec = () => {
    const t = new Date();
    return `${String(t.getDate()).padStart(2, "0")}${String(t.getMonth() + 1).padStart(2, "0")}${t.getFullYear()}`;
};

// Regroupe les encours par compte (RefContCmpt) pour générer un <CompteDebiteur> par contrat
const buildComptesDebiteurs = (encoursRows, engagementsByRef) => {
    const map = new Map();
    encoursRows.forEach((row) => {
        const ref = getVal(row, "REFCONTCMPT");
        if (!ref) return;
        if (!map.has(ref)) {
            map.set(ref, {
                RefContCmpt: ref,
                CodAge: "",
                SolDeb: "",
                NbrJrsDebNonAut: "",
                MntAgi: "",
                MntProv: "",
                ClassDeprec: getVal(row, "CLADEPREC"),
                IdInt: getVal(row, "CLI"),
                Role: "01",
            });
        }
        const entry = map.get(ref);
        if (!entry.ClassDeprec && getVal(row, "CLADEPREC")) {
            entry.ClassDeprec = getVal(row, "CLADEPREC");
        }
    });

    // Récupère le code agence et l'identifiant depuis l'engagement correspondant
    map.forEach((entry) => {
        const eng = engagementsByRef.get(entry.RefContCmpt);
        if (eng) {
            if (!entry.CodAge) entry.CodAge = getVal(eng, "CODAGE");
            if (!entry.IdInt) entry.IdInt = getVal(eng, "IDINT");
        }
    });

    return Array.from(map.values());
};

// NbrDec = nombre de balises ouvrantes <Encours + nombre de balises ouvrantes <Engagement
const computeNbrDec = (engagementCount, encoursCount) =>
    String(engagementCount + encoursCount).padStart(2, "0");

export function generateCdr51Xml({
    engagements = [],
    encours = [],
    encoursAjust = [],
    xmlConfig = {},
    selectedDate = "",
}) {
    const numDec = String(xmlConfig.NumDec || "").trim() || "0001";
    const codPay = String(xmlConfig.CodPay || "CF").trim() || "CF";
    const codDec = String(xmlConfig.CodDec || "").trim() || "00000";
    const natDec = String(xmlConfig.NatDec || "00").trim() || "00";
    const comment = String(xmlConfig.comment || "").trim();

    const datArr = computeDatArr(selectedDate);
    const datDec = computeDatDec();

    // Fusion : les encours_ajust sont concaténés à la fin de la collection des encours
    const mergedEncours = [...encours, ...encoursAjust];

    const nbrDec = computeNbrDec(engagements.length, mergedEncours.length);

    // Index des engagements par RefContCmpt (pour le CompteDebiteur)
    const engagementsByRef = new Map();
    engagements.forEach((e) => {
        const ref = getVal(e, "REFCONTCMPT");
        if (ref && !engagementsByRef.has(ref)) engagementsByRef.set(ref, e);
    });

    // --- Bloc <Engagement> : tabulation après chaque balise fermante </Engagement> ---
    const engagementLines = engagements.map((e) => {
        const attrs = buildAttrsInOrder(ENGAGEMENT_FIELDS, e);
        const consolidation = selfClosing(
            "Consolidation",
            attr("RefInt", getVal(e, "REFINT")),
        );
        const titulaire = selfClosing(
            "TitulaireEngagement",
            attr("IdInt", getVal(e, "IDINT")),
        );
        const garantie = selfClosing(
            "GarantieAffectee",
            buildAttrsInOrder(GARANTIE_FIELDS, {}),
        );
        return `<Engagement${attrs}> ${consolidation} ${titulaire}${garantie}</Engagement>\t`;
    });

    // --- Bloc <Encours> : tabulation à la fin de chaque encours (après la fermeture) ---
    const encoursLines = mergedEncours.map((en) => {
        const attrs = buildAttrsInOrder(ENCOURS_FIELDS, en);
        return `<Encours${attrs}/>\t`;
    });

    // --- Bloc <CompteDebiteur> ---
    const comptes = buildComptesDebiteurs(mergedEncours, engagementsByRef);
    const compteLines = comptes.map((c) => {
        const attrs = buildAttrsInOrder(CPTDEB_FIELDS, c);
        const titulaire = selfClosing(
            "TitulaireCompte",
            attr("IdInt", c.IdInt) + attr("Role", c.Role),
        );
        return `<CompteDebiteur${attrs}>${titulaire}</CompteDebiteur>\t`;
    });

    const bodyParts = [engagementLines, encoursLines, compteLines]
        .filter((arr) => arr.length)
        .map((arr) => arr.join("\n"));
    const body = bodyParts.join("\n");

    const declAttrs = buildDeclarationAttrs({
        NumDec: numDec,
        CodPay: codPay,
        CodDec: codDec,
        NatDec: natDec,
        NbrDec: nbrDec,
        DatDec: datDec,
        DatArr: datArr,
        comment,
    });

    const xml = `<?xml version="1.0" encoding="UTF-8"?>
<Declaration${declAttrs}>
${body}
</Declaration>`;

    // Nomenclature : CodePays-CodeDéclarant-NumDéclaration-DateArrêté-TypeDéclaration-TypeFichier.xml
    const filename = `${codPay}-${codDec}-${numDec}-${datArr}-51-DEC.xml`;

    return {
        xml,
        filename,
        config: {
            NumDec: numDec,
            CodPay: codPay,
            CodDec: codDec,
            TypDec: "51",
            NatDec: natDec,
            DatArr: datArr,
            DatDec: datDec,
            NbrDec: nbrDec,
            comment,
        },
    };
}

export function downloadCdr51Xml(xmlString, filename) {
    const blob = new Blob([xmlString], { type: "application/xml" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

export default { generateCdr51Xml, downloadCdr51Xml };
