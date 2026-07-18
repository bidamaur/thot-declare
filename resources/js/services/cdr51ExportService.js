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

// Récupère une valeur numérique : renvoie "0" si la valeur est vide, null ou non renseignée.
const getMontant = (row, field) => {
    const s = getVal(row, field);
    return s === "" ? "0" : s;
};

// Construit les attributs dans l'ordre strict imposé par le kit CDR.
// pairs = [ [xmlAttr, jsonField, isDate?, isMontant?], ... ]
const buildAttrsInOrder = (pairs, row) =>
    pairs
        .map(([xml, field, isDate, isMontant]) => {
            let v = isMontant ? getMontant(row, field) : getVal(row, field);
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
    ["MntEng", "MNTENG", false, true],
    ["MntCrCedee", "MNTCRCEDEE", false, true],
    ["MntEpargne", "MNTEPARGNE", false, true],
    ["ModRembEpargne", "MODREMBEPARGNE"],
    ["TauxRenum", "TAUXRENUM", false, true],
    ["DatMep", "DATMEP", true],
    ["TxInt", "TXINT", false, true],
    ["TxComm", "TXCOMM", false, true],
    ["TxBonifie", "TXBONIFIE", false, true],
    ["TxEffGlob", "TXEFFGLOB", false, true],
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
    ["MntEch", "MNTECH", false, true],
    ["TypAmo", "TYAMO"],
    ["TotInt", "TOTINT", false, true],
    ["fraDos", "FRADOS", false, true],
    ["fraAnnexe", "FRANNEXE", false, true],
    ["MntPrm", "MNTPRM", false, true],
    ["MntTax", "MNTTAX", false, true],
    ["DatEve", "DATEVE", true],
];

// --- ORDRE STRICT DES ATTRIBUTS (balise <Encours>) ---
const ENCOURS_FIELDS = [
    ["RefContCmpt", "REFCONTCMPT"],
    ["DatPai", "DATPAI", true],
    ["DatEch", "DATECH", true],
    ["MntPay", "MNTPAY", false, true],
    ["MntAgi", "MNTAGI", false, true],
    ["MntCrd", "MNTCRD", false, true],
    ["estSensible", "ESTSENSIBLE"],
    ["MntTotUtil", "MNTTOTUTIL", false, true],
    ["nbrEchPay", "NBRECHPAY"],
    ["nbrEchImp", "NBRECHIMP"],
    ["nbrEchRes", "NBRECHRES"],
    ["MntCreSouf", "MNTCRESOUF", false, true],
    ["MntCapSouf", "MNTCAPSOUF", false, true],
    ["MntIntSouf", "MNTINTSOUF", false, true],
    ["MntTaxSouf", "MNTTAXSOUF", false, true],
    ["MntAgiosSouf", "MNTAGIOSSOUF", false, true],
    ["MntCreRat", "MNTERAT", false, true],
    ["MntPro", "MNTPRO", false, true],
    ["nbrJrsImp", "NBRJRSIMP"],
    ["ClaDeprec", "CLADEPREC"],
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

// NbrDec = nombre de balises ouvrantes <Encours + nombre de balises ouvrantes <Engagement
const computeNbrDec = (engagementCount, encoursCount) =>
    String(engagementCount + encoursCount).padStart(2, "0");

export function generateCdr51Xml({
    engagements = [],
    encours = [],
    encoursAjust = [],
    xmlConfig = {},
    selectedDate = "",
    includeGaranties = false,
    includeCompteDebiteur = false,
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

    // --- Bloc <Engagement> : tabulation après chaque balise fermante </Engagement> ---
    const engagementLines = engagements.map((e) => {
        const attrs = buildAttrsInOrder(ENGAGEMENT_FIELDS, e);
        const consolidation = selfClosing("Consolidation", attr("RefInt", ""));
        const titulaire = selfClosing(
            "TitulaireEngagement",
            attr("IdInt", getVal(e, "IDINT")),
        );
        const garantie = includeGaranties
            ? selfClosing("GarantieAffectee", buildAttrsInOrder(GARANTIE_FIELDS, {}))
            : "";
        return `<Engagement${attrs}> ${consolidation} ${titulaire}${garantie}</Engagement>\t`;
    });

    // --- Bloc <Encours> : tabulation à la fin de chaque encours (après la fermeture) ---
    const encoursLines = mergedEncours.map((en) => {
        const attrs = buildAttrsInOrder(ENCOURS_FIELDS, en);
        return `<Encours${attrs}/>\t`;
    });

    // --- Bloc <CompteDebiteur> : optionnel (case à cocher) ---
    const compteDebiteurLine = includeCompteDebiteur
        ? `<CompteDebiteur RefContCmpt="" CodAge="" SolDeb="" NbrJrsDebNonAut="" MntAgi="" MntProv="" ClassDeprec="" >` +
          `<TitulaireCompte IdInt="" Role="" /></CompteDebiteur>\t`
        : "";

    const bodyParts = [engagementLines, encoursLines, [compteDebiteurLine]]
        .filter((arr) => (Array.isArray(arr) ? arr.join("").trim() : arr))
        .map((arr) => (Array.isArray(arr) ? arr.join("\n") : arr));
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
