/**
 * Validateur de conformité officiel Kit FRCB / CDR BEAC - Personnes Morales
 * Intègre le contrôle d'existence sur la table complète des codes NAEMA (Annexe 4).
 */

const VALID_RESIDENT = ["01", "02", "03"];
const VALID_SITJUD = ["0", "1"];
const VALID_STATUT_LEGAL = ["01", "02", "03", "04"];

const FORBIDDEN_WORDS = new Set([
    "NSP",
    "NE SAIS PAS",
    "NA",
    "ND",
    "INCO",
    "INCONU",
    "INCONNU",
    "PND",
    "COMPANY",
    "CO",
    "COM",
    "COMP",
    "ABACERIA",
]);

const PM_LENGTHS_BY_COUNTRY = {
    CM: { NIF: [14], RCCM: [17] },
    GA: { NIF: [7, 13], RCCM: [10, 12, 15, 19] },
    CF: { NIF: [11], RCCM: [12] },
    CG: { NIF: [16, 17], RCCM: [11] },
    TD: { NIF: [10], RCCM: [11] },
    GQ: { NIF: [9], RCCM: [8] },
};

/**
 * Annexe 4 : Référentiel des Secteurs d'activité NAEMA complet
 * Le champ SECACT teste son existence directement sur cette table de codes.
 */
const VALID_NAEMA_SECTORS = new Set([
    // Partie 1
    "01",
    "01.1",
    "01.11",
    "01.12",
    "01.13",
    "01.14",
    "01.15",
    "01.16",
    "01.2",
    "01.21",
    "01.22",
    "01.3",
    "01.31",
    "01.32",
    "01.33",
    "01.34",
    "01.35",
    "01.36",
    "01.37",
    "01.4",
    "01.41",
    "01.42",
    "01.43",
    "01.44",
    "01.45",
    "01.46",
    "01.47",
    "01.48",
    "01.5",
    "01.50",
    "01.6",
    "01.60",
    "01.7",
    "01.70",
    "02",
    "02.1",
    "02.11",
    "02.12",
    "02.13",
    "02.2",
    "02.20",
    "02.3",
    "02.30",
    "03",
    "03.0",
    "03.01",
    "03.02",
    "03.03",
    "05",
    "05.0",
    "05.00",
    "06",
    "06.0",
    "06.01",
    "06.02",
    "07",
    "07.1",
    "07.10",
    "07.2",
    "07.21",
    "07.22",
    "07.23",
    "07.24",
    "07.25",
    "07.26",
    "08",
    "08.1",
    "08.10",
    "08.2",
    "08.21",
    "08.22",
    "08.23",
    "08.24",
    "08.25",
    "09",
    "09.0",
    "09.00",
    "10",
    "10.1",
    "10.10",
    "10.2",
    "10.21",
    "10.22",
    "10.23",
    "10.3",
    "10.30",
    "10.4",
    "10.40",
    "10.5",
    "10.50",
    "10.6",
    "10.61",
    "10.62",
    "10.7",
    "10.71",
    "10.72",
    "10.73",
    "10.8",
    "10.80",
    "10.9",
    "10.91",
    "10.92",
    "10.93",
    "10.94",
    "10.95",
    "11",
    "11.0",
    "11.01",
    "11.02",
    "12",
    "12.0",
    "12.00",
    "13",
    "13.1",
    "13.10",
    "13.2",
    "13.21",
    "13.22",
    "14",
    "14.0",
    "14.00",
    "15",
    "15.1",
    "15.11",
    "15.12",
    "15.2",
    "15.20",
    "16",
    "16.1",
    "16.10",
    "16.2",
    "16.21",
    "16.22",
    "16.23",
    "17",
    "17.0",
    "17.01",
    "17.02",
    "17.03",
    "18",
    "18.1",
    "18.10",
    "18.2",
    "18.20",
    "19",
    "19.1",
    "19.10",
    "19.2",
    "19.20",
    "20",
    "20.1",
    "20.11",
    "20.12",
    "20.2",
    "20.21",
    "20.22",
    "20.23",
    "20.24",
    "20.25",
    "20.26",
    "21",
    "21.0",
    "21.01",
    "21.02",
    "22",
    "22.1",
    "22.11",
    "22.12",
    "22.2",
    "22.20",
    "23",
    "23.1",
    "23.10",
    "23.2",
    "23.21",
    "23.22",
    "23.23",
    "23.3",
    "23.31",
    "23.32",
    "23.33",
    "24",
    "24.1",
    "24.10",
    "24.2",
    "24.20",
    "24.3",
    "24.30",
    "25",
    "25.1",
    "25.10",
    "25.2",
    "25.20",
    "26",
    "26.1",
    "26.11",
    "26.12",
    "26.2",
    "26.21",
    "26.22",
    "26.3",
    "26.30",
    "27",
    "27.1",
    "27.10",
    "27.2",
    "27.20",
    "28",
    "28.1",
    "28.10",
    "28.2",
    "28.20",
    "29",
    "29.0",
    "29.00",
    "30",
    "30.1",
    "30.11",
    "30.12",
    "30.13",
    "30.14",
    "30.2",
    "30.20",
    "31",
    "31.1",
    "31.10",
    "31.20",
    "32",
    "32.0",
    "32.10",
    "32.20",
    "32.30",
    "33",
    "33.1",
    "33.10",
    "33.2",
    "33.20",
    "35",
    "35.1",
    "35.10",
    "35.2",
    "35.20",
    "36",
    "36.0",
    "36.00",
    // Partie 2 (Suite du document)
    "37",
    "37.0",
    "37.00",
    "38",
    "38.0",
    "38.01",
    "38.02",
    "39",
    "39.0",
    "39.00",
    "41",
    "41.1",
    "41.11",
    "41.2",
    "41.20",
    "42",
    "42.0",
    "42.00",
    "43",
    "43.0",
    "43.01",
    "43.02",
    "43.03",
    "43.04",
    "45",
    "45.1",
    "45.10",
    "45.2",
    "45.20",
    "45.3",
    "45.30",
    "45.4",
    "45.40",
    "46",
    "46.1",
    "46.10",
    "46.2",
    "46.21",
    "46.22",
    "46.23",
    "46.3",
    "46.31",
    "46.32",
    "46.33",
    "46.4",
    "46.41",
    "46.42",
    "46.43",
    "46.44",
    "46.5",
    "46.51",
    "46.52",
    "46.6",
    "46.60",
    "47",
    "47.1",
    "47.10",
    "47.2",
    "47.21",
    "47.22",
    "47.23",
    "47.24",
    "47.25",
    "47.26",
    "47.27",
    "47.28",
    "47.29",
    "47.3",
    "47.31",
    "47.32",
    "47.33",
    "47.34",
    "47.35",
    "47.36",
    "49",
    "49.1",
    "49.10",
    "49.2",
    "49.21",
    "49.22",
    "49.3",
    "49.30",
    "50",
    "50.1",
    "50.10",
    "50.2",
    "50.20",
    "51",
    "51.0",
    "51.01",
    "51.02",
    "52",
    "52.1",
    "52.10",
    "52.2",
    "52.21",
    "52.22",
    "52.23",
    "53",
    "53.0",
    "53.01",
    "53.02",
    "55",
    "55.0",
    "55.00",
    "56",
    "56.1",
    "56.10",
    "56.2",
    "56.20",
    "58",
    "58.1",
    "58.10",
    "58.2",
    "58.20",
    "59",
    "59.1",
    "59.10",
    "59.2",
    "59.20",
    "60",
    "60.0",
    "60.01",
    "60.02",
    "61",
    "61.0",
    "61.00",
    "62",
    "62.0",
    "62.01",
    "62.02",
    "63",
    "63.0",
    "63.01",
    "63.02",
    "64",
    "64.1",
    "64.11",
    "64.12",
    "64.2",
    "64.20",
    "64.3",
    "64.31",
    "64.32",
    "65",
    "65.0",
    "65.01",
    "65.02",
    "66",
    "66.0",
    "66.01",
    "66.02",
    "66.03",
    "68",
    "68.1",
    "68.10",
    "68.2",
    "68.20",
    "69",
    "69.0",
    "69.01",
    "69.02",
    "70",
    "70.0",
    "70.01",
    "70.02",
    "71",
    "71.0",
    "71.01",
    "71.02",
    "72",
    "72.1",
    "72.10",
    "72.2",
    "72.20",
    "73",
    "73.0",
    "73.01",
    "73.02",
    "74",
    "74.0",
    "74.01",
    "74.02",
    "74.03",
    "75",
    "75.0",
    "75.00",
    "77",
    "77.0",
    "77.01",
    "77.02",
    "77.03",
    "77.04",
    "78",
    "78.0",
    "78.00",
    "79",
    "79.0",
    "79.00",
    "80",
    "80.0",
    "80.00",
    "81",
    "81.0",
    "81.01",
    "81.02",
    "81.03",
    "82",
    "82.0",
    "82.01",
    "82.02",
    "82.03",
    "84",
    "84.1",
    "84.10",
    "84.2",
    "84.20",
    "84.3",
    "84.30",
    "85",
    "85.1",
    "85.10",
    "85.2",
    "85.21",
    "85.22",
    "85.3",
    "85.30",
    "85.4",
    "85.40",
    "86",
    "86.1",
    "86.10",
    "86.2",
    "86.20",
    "86.3",
    "86.31",
    "86.32",
    "87",
    "87.0",
    "87.00",
    "88",
    "88.0",
    "88.00",
    "90",
    "90.0",
    "90.00",
    "91",
    "91.0",
    "91.00",
    "92",
    "92.0",
    "92.00",
    "93",
    "93.0",
    "93.00",
    "94",
    "94.1",
    "94.10",
    "94.2",
    "94.20",
    "94.3",
    "94.31",
    "94.32",
    "94.33",
    "95",
    "95.1",
    "95.11",
    "95.12",
    "95.2",
    "95.20",
    "96",
    "96.0",
    "96.01",
    "96.02",
    "96.03",
    "96.04",
    "97",
    "97.0",
    "97.00",
    "98",
    "98.0",
    "98.01",
    "98.02",
    "99",
    "99.0",
    "99.00",
]);

/**
 * Normalise un code NAEMA en supprimant les zéros finaux après le séparateur décimal
 * (ex: "18.10" -> "18.1", "55.00" -> "55") pour fiabiliser la comparaison quel que
 * soit le format renvoyé par la source de données.
 */
const normalizeNaema = (code) => {
    const s = String(code).trim();
    if (!s.includes(".")) return s;
    const [intPart, decPart] = s.split(".");
    const trimmed = decPart.replace(/0+$/, "");
    return trimmed === "" ? intPart : `${intPart}.${trimmed}`;
};

const VALID_NAEMA_SECTORS_NORM = new Set(
    [...VALID_NAEMA_SECTORS].map(normalizeNaema),
);

function checkBEACDate(dateStr) {
    if (!dateStr || dateStr === "PND")
        return { syntaxValid: false, logicalValid: false };
    if (!/^\d{8}$/.test(dateStr))
        return { syntaxValid: false, logicalValid: false };
    const day = parseInt(dateStr.substring(0, 2), 10);
    const month = parseInt(dateStr.substring(2, 4), 10);
    const year = parseInt(dateStr.substring(4, 8), 10);
    if (month < 1 || month > 12)
        return { syntaxValid: true, logicalValid: false };
    const daysInMonth = new Date(year, month, 0).getDate();
    if (day < 1 || day > daysInMonth)
        return { syntaxValid: true, logicalValid: false };
    return { syntaxValid: true, logicalValid: true };
}

function parseBEACDate(dateStr) {
    if (!dateStr || dateStr === "PND" || !/^\d{8}$/.test(dateStr)) return null;
    return new Date(
        parseInt(dateStr.substring(4, 8), 10),
        parseInt(dateStr.substring(2, 4), 10) - 1,
        parseInt(dateStr.substring(0, 2), 10),
    );
}

function getCleanAlphanumericLength(value) {
    if (!value) return 0;
    return value.toString().replace(/[\.,;\s\-]/g, "").length;
}

export function validatePersonneMorale(data, currentCountry = "CM") {
    const errors = [];
    const dateDeclaration = new Date();

    const pushErr = (code, type, field, msg) =>
        errors.push({ code, type, field, message: msg });

    const paysDeclarant =
        data["C##DBPROD."] || data["C##DBPROD"] || currentCountry;
    const countryConfig =
        PM_LENGTHS_BY_COUNTRY[paysDeclarant] || PM_LENGTHS_BY_COUNTRY["CM"];

    // 1. CONTRÔLES OBLIGATOIRES
    const mandatoryFields = [
        "IDINTCLI",
        "RAISOC",
        "DATCRE",
        "RESIDENT",
        "VILLE",
        "RCCM",
        "FORJURID",
        "SECACT",
        "AGEECO",
        "DATENTRELPAR",
        "TEL",
        "SITJUD",
        "DATEVE",
    ];

    mandatoryFields.forEach((f) => {
        if (
            data[f] === null ||
            data[f] === undefined ||
            data[f].toString().trim() === ""
        ) {
            pushErr("OBL002", "Erreur", f, "Champ obligatoire non fourni");
        }
    });

    if (
        !data.NIF_NIU ||
        data.NIF_NIU.toString().trim() === "" ||
        data.NIF_NIU === "PND"
    ) {
        pushErr(
            "OBL002",
            "Erreur",
            "NIF_NIU",
            "Le numéro d'identifiant fiscal/unique (NIF_NIU) est obligatoire.",
        );
    }

    // 2. CONTRÔLES SYNTAXIQUES
    const numericFields = [
        "IDINTCLI",
        "FORJURID",
        "AGEECO",
        "EFFECTIF",
        "CHIAFFAIRE",
        "TOTBILAN",
        "PCTACT",
    ];
    numericFields.forEach((field) => {
        if (data[field] && !/^\d+(\.\d+)?$/.test(data[field].toString())) {
            pushErr(
                "SYN002",
                "Erreur",
                field,
                "Le champ de type numérique doit contenir uniquement des chiffres",
            );
        }
    });

    // REVISE ET COMPLET - TEST D'EXISTENCE STRICT SUR LA TABLE NAEMA (SECACT)
    if (data.SECACT) {
        const secActStr = data.SECACT.toString().trim();
        if (!VALID_NAEMA_SECTORS_NORM.has(normalizeNaema(secActStr))) {
            pushErr(
                "OBL004",
                "Erreur",
                "SECACT",
                `Le code secteur d'activité '${secActStr}' est invalide. Il doit obligatoirement figurer dans la table officielle de la nomenclature NAEMA.`,
            );
        }
    }

    const decimalFields = ["CHIAFFAIRE", "TOTBILAN", "PCTACT"];
    decimalFields.forEach((field) => {
        if (data[field] && data[field].toString().includes(",")) {
            pushErr(
                "SYN004",
                "Erreur",
                field,
                "Le champ de type décimal ne doit pas contenir de virgule, utilisez le point '.'",
            );
        }
    });

    if (data.NIF_NIU && data.NIF_NIU !== "PND") {
        const nifLen = getCleanAlphanumericLength(data.NIF_NIU);
        const allowedNifLens = countryConfig.NIF;
        if (allowedNifLens && !allowedNifLens.includes(nifLen)) {
            pushErr(
                "SYN011",
                "Erreur",
                "NIF_NIU",
                `Format invalide (Taille attendue pour la PM au ${paysDeclarant}: ${allowedNifLens.join("/")} caractères utiles)`,
            );
        }
    }

    if (data.RCCM && data.RCCM !== "PND") {
        const rccmPattern =
            /^(RC\/[A-Z]{3}\/\d{4}\/[A-Z]\/\d+|CM-[A-Z]{3}-\d{2}-\d{4}-[A-Z]\d*-\d+)$/;
        if (!rccmPattern.test(data.RCCM.toString().trim())) {
            pushErr(
                "SYN011",
                "Erreur",
                "RCCM",
                "Format invalide (Le RCCM doit respecter l'un des formats: RC/XXX/AAAA/X/NNN ou CM-XXX-AA-AAAA-Xn-NNN)",
            );
        }
    }

    if (data.TEL) {
        const phone = data.TEL.toString();
        const hasForbiddenChars =
            /[\s\.,\-\/\/\\_+\#]/.test(phone) ||
            phone.includes("N°") ||
            phone.toUpperCase().includes("NUM");
        if (
            hasForbiddenChars ||
            phone.length < 9 ||
            phone.length > 16 ||
            !phone.startsWith("00") ||
            !/^\d+$/.test(phone)
        ) {
            pushErr(
                "SYN012",
                "Avertissement",
                "TEL",
                "Numéro de téléphone de la personne morale invalide (Doit débuter par '00' suivis des chiffres stricts)",
            );
        }
    }

    if (data.EMAIL) {
        const emails = data.EMAIL.toString().split("//");
        const emailPattern = /^[A-Za-z0-9\._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
        emails.forEach((email) => {
            if (!emailPattern.test(email.trim())) {
                pushErr(
                    "SYN013",
                    "Avertissement",
                    "EMAIL",
                    "Adresse mail de contact non valide",
                );
            }
        });
    }

    if (data.VILLE) {
        const villeStr = data.VILLE.toString();
        if (paysDeclarant === "CM") {
            if (!/^\d{4}$/.test(villeStr)) {
                pushErr(
                    "SYN002",
                    "Erreur",
                    "VILLE",
                    "Le champ VILLE doit contenir obligatoirement et strictement 4 chiffres pour le Cameroun (Ex: 1528)",
                );
            }
        } else {
            if (!/^\d{3,4}$/.test(villeStr)) {
                pushErr(
                    "SYN002",
                    "Erreur",
                    "VILLE",
                    "Le champ de type numérique doit contenir uniquement des chiffres (Format attendu : 3 à 4 chiffres)",
                );
            }
        }
    }

    // 3. CHAÎNES ET MOTS INTERDITS
    const identityFields = [
        "RAISOC",
        "SIGLE",
        "VILLE",
        "ADRESSE",
        "NOMACT",
        "NOMREL",
    ];
    identityFields.forEach((f) => {
        if (data[f]) {
            const valStr = data[f].toString().trim();
            const upperStr = valStr.toUpperCase();
            const isRel = f === "NOMREL" || f === "NOMACT";

            if (FORBIDDEN_WORDS.has(upperStr)) {
                pushErr(
                    isRel ? "SYN0008" : "SYN008",
                    isRel ? "Avertissement" : "Erreur",
                    f,
                    "Dénomination ou valeur du champ non conforme (Mot prohibé détecté)",
                );
            }
            if (/[^a-zA-Z0-9\s\-\(\)\']/.test(valStr) && f !== "ADRESSE") {
                pushErr(
                    "SYN0006",
                    isRel ? "Avertissement" : "Erreur",
                    f,
                    "Le champ ne doit pas contenir de caractères spéciaux non autorisés",
                );
            }
            if (/(.)\1{4,}/.test(valStr)) {
                pushErr(
                    "SYN0010",
                    isRel ? "Avertissement" : "Erreur",
                    f,
                    "Le champ ne doit pas contenir un caractère répétitif en série",
                );
            }
        }
    });

    if (
        data.RESIDENT &&
        !VALID_RESIDENT.includes(data.RESIDENT.toString().padStart(2, "0"))
    ) {
        pushErr(
            "OBL003",
            "Erreur",
            "RESIDENT",
            "Valeur référentielle de résidence invalide",
        );
    }

    if (
        data.STALEG &&
        !VALID_STATUT_LEGAL.includes(data.STALEG.toString().padStart(2, "0"))
    ) {
        pushErr(
            "OBL004",
            "Avertissement",
            "STALEG",
            "Code de statut légal entreprise invalide",
        );
    }

    // 4. CONTRÔLES LOGIQUES ET MÉTIER COHÉRENCE
    const dateFields = [
        { name: "DATCRE", required: true },
        { name: "DATENTRELPAR", required: true },
        { name: "DATEVE", required: true },
        { name: "DATDEBINT", required: false },
        { name: "DATFININT", required: false },
        { name: "DATDEBMAND", required: false },
        { name: "DATFINMAND", required: false },
        { name: "DATDEBACT", required: false },
        { name: "DATFINACT", required: false },
        { name: "DATMAJACT", required: false },
    ];

    dateFields.forEach((d) => {
        const val = data[d.name];
        if (val && val !== "PND") {
            const check = checkBEACDate(val.toString());
            if (!check.syntaxValid) {
                pushErr(
                    d.required ? "SYN003" : "SYN0003",
                    d.required ? "Erreur" : "Avertissement",
                    d.name,
                    "Le format de la date doit être strictement JJMMAAAA",
                );
            } else if (!check.logicalValid) {
                pushErr(
                    "LOG003",
                    "Erreur",
                    d.name,
                    "Date chronologiquement inexistante ou invalide",
                );
            }
        }
    });

    if (data.DATEVE) {
        const dEve = parseBEACDate(data.DATEVE.toString());
        if (dEve && dEve > dateDeclaration) {
            pushErr(
                "LOG001",
                "Erreur",
                "DATEVE",
                "La date d'événement de la PM doit être inférieure ou égale à la date courante de session",
            );
        }
    }
    if (data.DATCRE) {
        const dCre = parseBEACDate(data.DATCRE.toString());
        if (dCre && dCre >= dateDeclaration) {
            pushErr(
                "LOG007",
                "Erreur",
                "DATCRE",
                "La date de création légale de l'entreprise doit être inférieure à la date de déclaration.",
            );
        }
    }
    if (data.DATENTRELPAR) {
        const entRel = parseBEACDate(data.DATENTRELPAR.toString());
        if (entRel && entRel > dateDeclaration) {
            pushErr(
                "LOG014",
                "Erreur",
                "DATENTRELPAR",
                "La date d'entrée en relation d'affaires doit être inférieure ou égale à la date de session.",
            );
        }
    }

    if (data.SITJUD && data.SITJUD.toString() === "0") {
        if (data.DATDEBINT || data.DATFININT) {
            pushErr(
                "LOG031",
                "Erreur",
                "SITJUD",
                "Les champs de date d'interdiction ne doivent pas être alimentés si SitJud = 0",
            );
        }
    }
    if (data.SITJUD && data.SITJUD.toString() === "1") {
        if (!data.DATDEBINT) {
            pushErr(
                "OBL002",
                "Erreur",
                "DATDEBINT",
                "La date de début d'interdiction est obligatoire lorsque la situation judiciaire (SitJud) est égale à 1.",
            );
        }
        if (data.DATFININT) {
            const finInt = parseBEACDate(data.DATFININT.toString());
            if (finInt && finInt <= dateDeclaration) {
                pushErr(
                    "LOG002",
                    "Erreur",
                    "DATFININT",
                    "La date de fin d'interdiction de la PM doit être strictement supérieure à la date courante de session",
                );
            }
        }
    }

    if (data.DATDEBMAND && data.DATFINMAND) {
        const deb = parseBEACDate(data.DATDEBMAND.toString());
        const fin = parseBEACDate(data.DATFINMAND.toString());
        if (deb && fin && fin <= deb) {
            pushErr(
                "LOG002",
                "Erreur",
                "DATFINMAND",
                "La date de fin de mandat doit être supérieure à la date de début.",
            );
        }
    }
    if (data.DATDEBACT && data.DATFINACT) {
        const debAct = parseBEACDate(data.DATDEBACT.toString());
        const finAct = parseBEACDate(data.DATFINACT.toString());
        if (debAct && finAct && finAct <= debAct) {
            pushErr(
                "LOG002",
                "Erreur",
                "DATFINACT",
                "La date de fin d'actionnariat doit être supérieure à la date d'entrée au capital.",
            );
        }
    }

    if (paysDeclarant === "CM" && data.RESIDENT) {
        const resStr = data.RESIDENT.toString();
        if (resStr !== "01" && resStr !== "1") {
            pushErr(
                "LOG008",
                "Erreur",
                "RESIDENT",
                `Incohérence réglementaire : Si le siège social est au pays déclarant (${paysDeclarant}), le statut de résidence doit être 01.`,
            );
        }
    }

    const linkedIdentifiers = [
        { field: "IDINTMAND", label: "mandataire représentant" },
        { field: "IDINTREL", label: "tiers en relation" },
        { field: "IDINTACT", label: "actionnaire associé" },
    ];
    linkedIdentifiers.forEach((id) => {
        if (
            data[id.field] &&
            data.IDINTCLI &&
            data[id.field].toString().trim() === data.IDINTCLI.toString().trim()
        ) {
            pushErr(
                "LOG023",
                "Erreur",
                id.field,
                `L'identifiant interne du ${id.label} rattaché ne peut pas être identique à l'identifiant IDINTCLI.`,
            );
        }
    });

    if (data.PCTACT && data.PCTACT.toString().includes("%")) {
        pushErr(
            "SYN002",
            "Erreur",
            "PCTACT",
            "Le symbole '%' est strictement prohibé, renseignez uniquement la valeur décimale.",
        );
    }

    return {
        isValid: !errors.some((e) => e.type === "Erreur"),
        errors: errors,
    };
}

export function validateAllPersonnesMorales(dataArray, currentCountry = "CM") {
    return dataArray.map((item) => ({
        data: item,
        ...validatePersonneMorale(item, currentCountry),
    }));
}
