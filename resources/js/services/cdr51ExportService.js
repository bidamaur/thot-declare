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

// --- ORDRE STRICT DES ATTRIBUTS (balise <Engagement>) ---
const ENGAGEMENT_FIELDS = [
    ["CodAge", "CODAGE"],
    ["CodDev", "CODDEV"],
    ["DatDeb", "DATDEB", true],
    ["DatEve", "DATEVE", true],
    ["DatFin", "DATFIN", true],
    ["DatMep", "DATMEP", true],
    ["DatPreEchCap", "DATPREECHCAP", true],
    ["Duree", "DUREE"],
    ["IndRef", "INDREF"],
    ["Maturite", "MATURITE"],
    ["MntCrCedee", "MNTCRCEDEE"],
    ["MntEch", "MNTECH"],
    ["MntEng", "MNTENG"],
    ["MntEpargne", "MNTEPARGNE"],
    ["MntPrm", "MNTPRM"],
    ["MntTax", "MNTTAX"],
    ["ModRembEpargne", "MODREMBEPARGNE"],
    ["Motif", "MOTIF"],
    ["MoyRem", "MOYREM"],
    ["NatConso", "NATCONSO"],
    ["NatEng", "NATENG"],
    ["NbrEch", "NBRECH"],
    ["Periodicite", "PERIODICITE"],
    ["RefContCmpt", "REFCONTCMPT"],
    ["Sprd", "SPRD"],
    ["Statut", "STATUT"],
    ["TauxRenum", "TAUXRENUM"],
    ["TotInt", "TOTINT"],
    ["TxBonifie", "TXBONIFIE"],
    ["TxComm", "TXCOMM"],
    ["TxEffGlob", "TXEFFGLOB"],
    ["TxInt", "TXINT"],
    ["TypAmo", "TYAMO"],
    ["TypConso", "TYPCONSO"],
    ["TypEch", "TYECH"],
    ["TypEng", "TYPENG"],
    ["TypTxInt", "TYPTXINT"],
    ["UnitDur", "UNITDUR"],
    ["fraAnnexe", "FRANNEXE"],
    ["fraDos", "FRADOS"],
];

// --- ORDRE STRICT DES ATTRIBUTS (balise <Encours>) ---
const ENCOURS_FIELDS = [
    ["ClaDeprec", "CLADEPREC"],
    ["DatEch", "DATECH", true],
    ["DatPai", "DATPAI", true],
    ["MntAgi", "MNTAGI"],
    ["MntAgiosSouf", "MNTAGIOSSOUF"],
    ["MntCapSouf", "MNTCAPSOUF"],
    ["MntCrd", "MNTCRD"],
    ["MntCreRat", "MNTERAT"],
    ["MntCreSouf", "MNTCRESOUF"],
    ["MntIntSouf", "MNTINTSOUF"],
    ["MntPay", "MNTPAY"],
    ["MntPro", "MNTPRO"],
    ["MntTaxSouf", "MNTTAXSOUF"],
    ["MntTotUtil", "MNTTOTUTIL"],
    ["RefContCmpt", "REFCONTCMPT"],
    ["estSensible", "ESTSENSIBLE"],
    ["nbrEchImp", "NBRECHIMP"],
    ["nbrEchPay", "NBRECHPAY"],
    ["nbrEchRes", "NBRECHRES"],
    ["nbrJrsImp", "NBRJRSIMP"],
];

// --- ORDRE STRICT DES ATTRIBUTS (balise <CompteDebiteur>) ---
const CPTDEB_FIELDS = [
    ["ClassDeprec", "ClassDeprec"],
    ["CodAge", "CodAge"],
    ["MntAgi", "MntAgi"],
    ["MntProv", "MntProv"],
    ["NbrJrsDebNonAut", "NbrJrsDebNonAut"],
    ["RefContCmpt", "RefContCmpt"],
    ["SolDeb", "SolDeb"],
];

// --- ORDRE STRICT DES ATTRIBUTS (balise <GarantieAffectee>) ---
const GARANTIE_FIELDS = [
    ["CodDevGar", "CodDevGar"],
    ["IdIntGarant", "IdIntGarant"],
    ["MntAffGar", "MntAffGar"],
    ["MntGar", "MntGar"],
    ["NatGar", "NatGar"],
    ["NomNaiGarant", "NomNaiGarant"],
    ["RefExtGar", "RefExtGar"],
    ["RefIntGar", "RefIntGar"],
    ["StatutGar", "StatutGar"],
    ["TypRefGar", "TypRefGar"],
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
                ClassDeprec: getVal(row, "CLADEPREC"),
                CodAge: "",
                MntAgi: "",
                MntProv: "",
                NbrJrsDebNonAut: "",
                SolDeb: "",
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
    const typDec = "51";
    const natDec = String(xmlConfig.NatDec || "00").trim() || "00";
    const comment = String(xmlConfig.comment || "").trim();

    const datArr = computeDatArr(selectedDate);
    const datDec = computeDatDec();

    // Fusion : les encours_ajust sont concaténés à la fin de la collection des encours
    const mergedEncours = [...encours, ...encoursAjust];

    const nbrDec = String(mergedEncours.length).padStart(2, "0");

    // Index des engagements par RefContCmpt (pour le CompteDebiteur)
    const engagementsByRef = new Map();
    engagements.forEach((e) => {
        const ref = getVal(e, "REFCONTCMPT");
        if (ref && !engagementsByRef.has(ref)) engagementsByRef.set(ref, e);
    });

    const engagementBlocks = engagements
        .map((e) => {
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
        })
        .join("");

    const encoursBlocks = mergedEncours
        .map((en) => {
            const attrs = buildAttrsInOrder(ENCOURS_FIELDS, en);
            return `<Encours${attrs}/>\t`;
        })
        .join("");

    const comptes = buildComptesDebiteurs(mergedEncours, engagementsByRef);
    const compteBlocks = comptes
        .map((c) => {
            const attrs = buildAttrsInOrder(CPTDEB_FIELDS, c);
            const titulaire = selfClosing(
                "TitulaireCompte",
                attr("IdInt", c.IdInt) + attr("Role", c.Role),
            );
            return `<CompteDebiteur${attrs}>${titulaire}</CompteDebiteur>\t`;
        })
        .join("");

    const declAttrs =
        attr("CodDec", codDec) +
        attr("CodPay", codPay) +
        attr("DatArr", datArr) +
        attr("DatDec", datDec) +
        attr("NatDec", natDec) +
        attr("NbrDec", nbrDec) +
        attr("NumDec", numDec) +
        attr("TypDec", typDec) +
        attr("comment", comment);

    const xml = `<?xml version="1.0" encoding="UTF-8"?>
<Declaration${declAttrs}>
${engagementBlocks}${encoursBlocks}${compteBlocks}</Declaration>`;

    // Nomenclature : CodePays-CodeDéclarant-NumDéclaration-DateArrêté-TypeDéclaration-TypeFichier.xml
    const filename = `${codPay}-${codDec}-${numDec}-${datArr}-${typDec}-DEC.xml`;

    return {
        xml,
        filename,
        config: {
            NumDec: numDec,
            CodPay: codPay,
            CodDec: codDec,
            TypDec: typDec,
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
