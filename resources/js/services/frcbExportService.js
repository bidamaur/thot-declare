const toXmlAttrValue = (value) => {
    if (value === undefined || value === null) return "";
    const str = String(value).trim();
    return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/'/g, '&apos;');
};

const formatDateBEAC = (dateStr) => {
    if (!dateStr) return "";
    const s = String(dateStr).trim();
    if (/^\d{8}$/.test(s)) return s;
    if (s.length === 10 && s.includes('-')) {
        const [y, m, d] = s.split('-');
        return `${d}${m}${y}`;
    }
    return s;
};

const attr = (name, value) => ` ${name}="${toXmlAttrValue(value)}"`;
const selfClosing = (tag, attrs) => `<${tag}${attrs} />`;

const DATA_FIELD_MAP = {
    NIF_NIU: "NIF_NIU",
    Sexe: "SEXE",
    Nom: "NOM",
    NomMar: "NOMMAR",
    Prenom: "PRENOM",
    NomComplet: "NOMCOMPLET",
    PreNai: "PRENAI",
    DatNai: "DATNAI",
    VilleNai: "VILLENAI",
    PaysNai: "PAYSNAI",
    Statut: "STATUT",
    Resident: "RESIDENT",
    PaysRes: "PAYSRES",
    NatCli: "NATCLI",
    NomPere: "NOMPERE",
    PrePere: "PREPERE",
    NomMere: "NOMMERE",
    PreMere: "PREMERE",
    SitMat: "SITMAT",
    AgeEco: "AGEECO",
    RCCM: "RCCM",
    SectAct: "SECTACT",
    CA: "CA",
    NombEmp: "NOMBEMP",
    SitJud: "SITJUD",
    DatDebInt: "DATDEBINT",
    DatFinInt: "DATEFININT",
    DatEntRelPar: "DATENTRELPAR",
    Email: "EMAIL",
    Mobile: "MOBILE",
    DatEve: "DATEVE",
};

const PIECE_IDENTITE_MAP = {
    TypPiece: "TYPPIECE",
    NumPiece: "NUMPIECE",
    DatEmPiece: "DATEMPIECE",
    Lieu: "LIEU",
    Pays: "PAYS_",
    DatFinPiece: "DATFINPIECE",
};

const ADRESSES_MAP = {
    TypAdr: "TYPADR",
    Adresse: "ADRESSE",
    Pays: "PAYS",
    Region: "REGION",
    Ville: "VILLE",
    CodPost: "CODPOST",
};

const MANDATAIRE_MAP = {
    IdIntMand: "IDINTREL",
    QualiteMand: "TYPREL",
};

const EMPLOYEUR_MAP = {
    RaiSocEmp: "RAISOC",
    RccmEmp: "RCCM",
    StaEmp: "STALEG",
    DatEntEmp: "DATENTRELPAR",
    FonEmp: "FONEMP",
};

const AUTRES_RELATIONS_MAP = {
    IdIntRel: "IDINTREL",
    NomRel: "NOMREL",
    PrenomRel: "PRENOMREL",
    TypRel: "TYPREL",
};

const PERSONNE_MORALE_MAP = {
    NIF_NIU: "NIF_NIU",
    RaiSoc: "RAISOC",
    DatCre: "DATCRE",
    Sigle: "SIGLE",
    Resident: "RESIDENT",
    PaysSiege: "PAYSSIEGE",
    Ville: "VILLE",
    RCCM: "RCCM",
    ForJurid: "FORJURID",
    SecAct: "SECACT",
    AgeEco: "AGEECO",
    StaLeg: "STALEG",
    DatEntRelPar: "DATENTRELPAR",
    ChiAffaire: "CHIAFFAIRE",
    TotBilan: "TOTBILAN",
    Effectif: "EFFECTIF",
    Email: "EMAIL",
    Tel: "TEL",
    SitJud: "SITJUD",
    DatDebInt: "DATDEBINT",
    DatFinInt: "DATEFININT",
    DatEve: "DATEVE",
};

const PM_ADRESSES_MAP = {
    TypAdr: "TYPADR",
    Adresse: "ADRESSE",
    Pays: "PAYS",
    Region: "REGION",
    Ville: "VILLE",
    CodPost: "CODPOST",
};

const PM_MANDATAIRE_MAP = {
    IdIntMand: "IDINTMAND",
    TypMand: "TYPMAND",
    DatDebMand: "DATDEBMAND",
    DatFinMand: "DATFINMAND",
};

const PM_ACTIONNAIRES_MAP = {
    IdIntAct: "IDINTACT",
    NomAct: "NOMACT",
    PctAct: "PCTACT",
    DatDebAct: "DATDEBACT",
    DatFinAct: "DATEFINACT",
    DatMajAct: "DATMAJACT",
    TelAct: "TELACT",
};

const PM_AUTRES_RELATIONS_MAP = {
    IdIntRel: "IDINTREL",
    NomRel: "NOMREL",
    TypRel: "TYPREL",
};

const INFOS_ADDMAP = {
    NbrPerCh: "NBRPERCH",
    TypLog: "TYPLOG",
    RevMenNet: "REVMENNET",
    CodDev: "CODDEV",
};

const buildAttrs = (map, data) => {
    return Object.entries(map)
        .map(([xmlAttr, jsonField]) => attr(xmlAttr, data[jsonField]))
        .join("");
};

const buildPersonnePhysique = (data) => {
    const attrs = buildAttrs(DATA_FIELD_MAP, data);
    const children = [
        selfClosing("PieceIdentite", buildAttrs(PIECE_IDENTITE_MAP, data)),
        selfClosing("Adresses", buildAttrs(ADRESSES_MAP, data)),
        selfClosing("Mandataire", buildAttrs(MANDATAIRE_MAP, data)),
        selfClosing("Employeur", buildAttrs(EMPLOYEUR_MAP, data)),
        selfClosing("AutresRelations", buildAttrs(AUTRES_RELATIONS_MAP, data)),
        selfClosing("InformationsAdditionnelles", buildAttrs(INFOS_ADDMAP, data)),
    ].join("");
    return `<PersonnePhysique${attrs}>${children}</PersonnePhysique>`;
};

const buildEntete = (data) => {
    return `<EntetePersonnePhysique${attr("IdIntCli", data.IDINTCLI)}>${buildPersonnePhysique(data)}</EntetePersonnePhysique>`;
};

const buildPersonneMorale = (data) => {
    const attrs = buildAttrs(PERSONNE_MORALE_MAP, data);
    const children = [
        selfClosing("Adresses", buildAttrs(PM_ADRESSES_MAP, data)),
        selfClosing("Mandataire", buildAttrs(PM_MANDATAIRE_MAP, data)),
        selfClosing("Actionnaires", buildAttrs(PM_ACTIONNAIRES_MAP, data)),
        selfClosing("AutresRelations", buildAttrs(PM_AUTRES_RELATIONS_MAP, data)),
    ].join("");
    return `<PersonneMorale${attrs}>${children}</PersonneMorale>`;
};

const buildEntetePersonneMorale = (data) => {
    return `<EntetePersonneMorale${attr("IdIntCli", data.IDINTCLI)}>${buildPersonneMorale(data)}</EntetePersonneMorale>`;
};

export function generateFRCBXml(dataArray, configurationInputs = {}) {
    const numDec = String(configurationInputs.NumDec || "").trim() || "0001";
    const codPay = String(configurationInputs.CodPay || "CM").trim();
    const codDec = String(configurationInputs.CodDec || "").trim() || "00000";
    const typDec = String(configurationInputs.TypDec || "01").trim();
    const natDec = String(configurationInputs.NatDec || "01").trim();
    const typPers = String(configurationInputs.TypPers || "01").trim();
    const comment = String(configurationInputs.comment || "").trim();

    const today = new Date();
    const dd = String(today.getDate()).padStart(2, "0");
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const yyyy = today.getFullYear();
    const datDec = `${dd}${mm}${yyyy}`;

    const nbrDec = String(dataArray.length).padStart(2, "0");

    const normalizedData = dataArray.map((item) => {
        const copy = { ...item };
        if (copy.SECACT === undefined && copy.SECTACT !== undefined) copy.SECACT = copy.SECTACT;
        if (copy.SECTACT === undefined && copy.SECACT !== undefined) copy.SECTACT = copy.SECACT;
        return copy;
    });

    const buildEnteteFn = typPers === "02" ? buildEntetePersonneMorale : buildEntete;
    const entetes = normalizedData.map((item) => buildEnteteFn(item)).join("\n");

    const xml = `<?xml version="1.0" encoding="UTF-8"?>
<Declaration${attr("NumDec", numDec)}${attr("CodPay", codPay)}${attr("CodDec", codDec)}${attr("TypDec", typDec)}${attr("NbrDec", nbrDec)}${attr("NatDec", natDec)}${attr("DatDec", datDec)}${attr("comment", comment)}>
${entetes}
</Declaration>`;

    const filename = `CM-${codDec}-${numDec}-${datDec}-${typPers}-DEC.xml`;

    return {
        xml,
        filename,
        config: {
            NumDec: numDec,
            CodPay: codPay,
            CodDec: codDec,
            TypDec: typDec,
            NatDec: natDec,
            TypPers: typPers,
            DatDec: datDec,
            NbrDec: nbrDec,
            comment,
        },
    };
}

export function downloadFRCBXml(xmlString, filename) {
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

export default { generateFRCBXml, downloadFRCBXml };
