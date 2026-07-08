/**
 * Validateur de conformité officiel Kit FRCB / CDR BEAC - Personnes Physiques
 * Intègre l'ensemble des contrôles obligatoires, syntaxiques et logiques métier.
 * Configuration par défaut : Cameroun ('CM').
 */

// Référentiels de base du kit BEAC
const VALID_SEXE = ["M", "F"];
const VALID_RESIDENT = ["01", "02", "03"]; // 01=Résident, 02=Non rés. CEMAC, 03=Non rés. Hors CEMAC
const VALID_PRENAI = ["01", "02", "03"]; // 01=Date complète, 02=Mois/Année, 03=Année seule
const VALID_STATUT = ["00", "01"]; // 00=Actif, 01=Décédé
const VALID_SITJUD = ["0", "1"]; // 0=Pas d'interdiction, 1=Interdit judiciaire
const VALID_TYPLOG = ["01", "02", "03", "04"]; // Propriétaire, Locataire, Copropriétaire, Usufruit
const VALID_SITMAT = ["01", "02", "03", "04", "05"];

// SYN008 / SYN0008 : Liste exhaustive des chaînes et mots interdits absolus
const FORBIDDEN_WORDS = new Set([
    "NSP",
    "NE SAIS PAS",
    "NA",
    "ND",
    "INCO",
    "INCONU",
    "INCONNU",
    "PND",
    "M",
    "MME",
    "MLLE",
    "MR",
    "SENOR",
    "SENIOR",
    "SENORA",
    "SENIORA",
    "DR",
    "HN",
    "MAITRE",
    "DOCTEUR",
    "MISTER",
    "MS",
    "EXCELLENCE",
    "HE",
    "SE",
    "MISS",
    "MRS",
    "MX",
    "VVE",
    "VEUF",
    "VEUVE",
    "FEU",
    "FEUE",
    "EP",
    "EPS",
    "EPOUS",
    "EPOUSE",
    "EPES",
    "NEE",
    "DIT",
    "ALIAS",
    "ET",
    "OU",
    "PC",
    "P/C",
    "REP",
    "REPRESENTANT",
    "REPRESENTE",
    "COMPANY",
    "CO",
    "COM",
    "COMP",
    "ASSOCIES",
    "ET FILS",
    "2000",
    "3000",
    "ABACERIA",
]);

// Matrice FRCB des longueurs de pièces d'identité utiles par pays (Annexe 9.2)
const PIECE_LENGTHS_BY_COUNTRY = {
    CM: {
        "01": [9, 17, 19],
        "02": [7, 8, 9],
        "03": [17, 18],
        "04": [20],
        "05": [7],
        "06": [20],
        NIF: [14],
        RCCM: [17],
    }, // Cameroun
    GA: {
        "01": [15],
        "02": [8, 9],
        "03": [9],
        "04": [14, 17],
        "05": [11],
        "06": [9],
        NIF: [7, 13],
        RCCM: [10, 12, 15, 19],
    }, // Gabon
    CF: {
        "01": [16],
        "02": [8, 9],
        "03": [10],
        "04": [9],
        "05": [9],
        "06": [9],
        NIF: [11],
        RCCM: [12],
    }, // RCA
    CG: {
        "01": [13, 14, 15],
        "02": [9],
        "03": [9],
        "04": [9],
        "05": [11],
        "06": [9],
        NIF: [16, 17],
        RCCM: [11],
    }, // Congo
    TD: {
        "01": [10],
        "02": [9],
        "03": [9],
        "04": [9],
        "05": [8],
        "06": [9],
        NIF: [10],
        RCCM: [11],
    }, // Tchad
    GQ: {
        "01": [7],
        "02": [9],
        "03": [9],
        "04": [9],
        "05": [9],
        "06": [9],
        NIF: [9],
        RCCM: [8],
    }, // Guinée Éq.
};

/**
 * SYN003 / LOG003 : Validation calendrier stricte de la date (JJMMAAAA)
 */
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
    if (day < 1 || day > daysInMonth) {
        return { syntaxValid: true, logicalValid: false };
    }

    return { syntaxValid: true, logicalValid: true };
}

/**
 * Convertisseur de date BEAC (JJMMAAAA) vers Objet Date JS
 */
function parseBEACDate(dateStr) {
    if (!dateStr || dateStr === "PND" || !/^\d{8}$/.test(dateStr)) return null;
    return new Date(
        parseInt(dateStr.substring(4, 8), 10),
        parseInt(dateStr.substring(2, 4), 10) - 1,
        parseInt(dateStr.substring(0, 2), 10),
    );
}

/**
 * Calcule la taille d'une chaîne alphanumérique en ignorant les caractères spéciaux (Annexe 2)
 */
function getCleanAlphanumericLength(value) {
    if (!value) return 0;
    return value.toString().replace(/[\.,;\s\-]/g, "").length;
}

/**
 * Fonction principale de validation d'un tiers PP
 * @param {Object} data - Objet JSON représentant le tiers
 * @param {String} currentCountry - Code ISO du pays déclarant ('CM', 'GA', etc.)
 * @returns {Object} { isValid: boolean, errors: Array }
 */
export function validatePersonnePhysique(data, currentCountry = "CM") {
    const errors = [];
    const dateDeclaration = new Date(); // Équivalent à la date de session courante

    const pushErr = (code, type, field, msg) =>
        errors.push({ code, type, field, message: msg });
    const countryConfig =
        PIECE_LENGTHS_BY_COUNTRY[currentCountry] ||
        PIECE_LENGTHS_BY_COUNTRY["CM"];

    // ==========================================
    // 1. CONTRÔLES OBLIGATOIRES (OBL002)
    // ==========================================
    const mandatoryFields = [
        "IDINTCLI",
        "SEXE",
        "DATNAI",
        "PRENAI",
        "PAYSNAI",
        "STATUT",
        "RESIDENT",
        "PAYSRES",
        "NATCLI",
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

    // Filiation Obligatoire (OBL002 / LOG005)
    ["PREPERE", "PREMERE"].forEach((f) => {
        if (
            data[f] === null ||
            data[f] === undefined ||
            data[f].toString().trim() === ""
        ) {
            pushErr("OBL002", "Erreur", f, "Champ obligatoire non fourni");
        }
    });

    // LOG069 / OBL002 : Exclusion mutuelle et conditionnalité du Nom complet
    if (data.NOMCOMPLET) {
        if (data.NOM || data.PRENOM) {
            pushErr(
                "LOG069",
                "Erreur",
                "NOMCOMPLET",
                "Le champ NOMCOMPLET ne doit être fourni que si et seulement si les champs disjoints NOM et PRENOM ne sont pas déclarés.",
            );
        }
    } else {
        if (!data.NOM || !data.PRENOM) {
            pushErr(
                "OBL002",
                "Erreur",
                "NOM/PRENOM",
                "Champs obligatoires conditionnés non fournis (Saisir soit Nom et Prénom, soit Nom Complet)",
            );
        }
    }

    // OBL002 / LOG063 : Conditionnalité du nom marital pour les femmes mariées
    if (data.SEXE === "F" && data.SITMAT === "02") {
        if (!data.NOMMAR || data.NOMMAR.trim() === "") {
            pushErr(
                "OBL002",
                "Erreur",
                "NOMMAR",
                "Champ obligatoire non fourni (Requis pour femme mariée)",
            );
        }
    }
    if (data.SEXE === "M" && data.NOMMAR) {
        pushErr(
            "LOG038",
            "Erreur",
            "NOMMAR",
            "Le champ NomMar ne doit jamais être renseigné si le sexe est Masculin (M)",
        );
    }

    // NIF_NIU : Obligatoire pour les créations (Type 01) au Cameroun et au Congo
    if (
        (currentCountry === "CM" || currentCountry === "CG") &&
        (!data.NIF_NIU || data.NIF_NIU === "PND")
    ) {
        pushErr(
            "OBL002",
            "Erreur",
            "NIF_NIU",
            "Champ obligatoire non fourni (Obligatoire pour le Cameroun et le Congo)",
        );
    }

    // ==========================================
    // 2. CONTRÔLES SYNTAXIQUES (SYN)
    // ==========================================

    // SYN002 : Champs numériques stricts (Entiers ou Décimaux)
    const numericFields = [
        "IDINTCLI",
        "REGION",
        "VILLE",
        "AGEECO",
        "NOMBEMP",
        "CA",
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

    // SYN004 : Séparateur décimal point unique pour le CA
    if (data.CA && data.CA.toString().includes(",")) {
        pushErr(
            "SYN004",
            "Erreur",
            "CA",
            "Le champ de type décimal ne doit pas contenir de virgule",
        );
    }

    // SYN011 : Validation des formats, tailles et structures des identifiants par Pays
    if (data.NIF_NIU && data.NIF_NIU !== "PND") {
        const nifLen = getCleanAlphanumericLength(data.NIF_NIU);
        const allowedNifLens = countryConfig.NIF;
        if (allowedNifLens && !allowedNifLens.includes(nifLen)) {
            pushErr(
                "SYN011",
                "Erreur",
                "NIF_NIU",
                `Format invalide (Taille attendue pour ${currentCountry}: ${allowedNifLens.join("/")} caractères utiles)`,
            );
        }
    }

    if (data.TYPPIECE && data.NUMPIECE && data.NUMPIECE !== "PND") {
        const pieceType = data.TYPPIECE.toString().padStart(2, "0");
        const cleanLength = getCleanAlphanumericLength(data.NUMPIECE);
        const allowedLengths = countryConfig[pieceType];
        if (allowedLengths && !allowedLengths.includes(cleanLength)) {
            pushErr(
                "SYN011",
                "Erreur",
                "NUMPIECE",
                `Format invalide (Longueur pour type ${pieceType} au ${currentCountry} attendue: ${allowedLengths.join("/")})`,
            );
        }
    }

    // SYN012 : Téléphone Mobile strict (9 à 16 chiffres, début par 00)
    if (data.MOBILE) {
        const phone = data.MOBILE.toString();
        const hasForbiddenChars =
            /[\s\.,\-\/\/\\_+\#]/.test(phone) ||
            phone.includes("N°") ||
            phone.toUpperCase().includes("NUM");
        if (
            hasForbiddenChars ||
            phone.length < 9 ||
            phone.length > 16 ||
            !phone.startsWith("00")
        ) {
            pushErr(
                "SYN012",
                "Avertissement",
                "MOBILE",
                "Numéro de téléphone invalide",
            );
        }
    }

    // SYN013 : Format Email Standard (Séparateur '//' admis si multiple)
    if (data.EMAIL) {
        const emails = data.EMAIL.toString().split("//");
        const emailPattern = /^[A-Za-z0-9\._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
        emails.forEach((email) => {
            if (!emailPattern.test(email.trim())) {
                pushErr(
                    "SYN013",
                    "Avertissement",
                    "EMAIL",
                    "Adresse mail non valide",
                );
            }
        });
    }

    // Codes de Région et Ville révisés (Contrôle strict selon les règles géographiques du kit)
    if (data.REGION && !/^\d{3,4}$/.test(data.REGION.toString())) {
        pushErr(
            "SYN002",
            "Erreur",
            "REGION",
            "Le champ de type numérique doit contenir uniquement des chiffres (Format attendu : 3 à 4 chiffres)",
        );
    }

    if (data.VILLE) {
        const villeStr = data.VILLE.toString();
        if (currentCountry === "CM") {
            // Pour le Cameroun, la ville doit obligatoirement faire exactement 4 chiffres stricts
            if (!/^\d{4}$/.test(villeStr)) {
                pushErr(
                    "SYN002",
                    "Erreur",
                    "VILLE",
                    "Le champ VILLE doit contenir obligatoirement et strictement 4 chiffres pour le Cameroun (Ex: 1528)",
                );
            }
        } else {
            // Pour les autres pays de la CEMAC, format générique de 3 à 4 chiffres
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

    // ==========================================
    // 3. CHAÎNES ET MOTS INTERDITS (SYN006 -> SYN010)
    // ==========================================
    const identityFields = [
        "NOM",
        "PRENOM",
        "NOMMAR",
        "NOMCOMPLET",
        "NOMPERE",
        "PREPERE",
        "NOMMERE",
        "PREMERE",
        "NOMREL",
        "PRENOMREL",
        "LIEU",
        "ADRESSE",
    ];

    identityFields.forEach((f) => {
        if (data[f]) {
            const valStr = data[f].toString().trim();
            const upperStr = valStr.toUpperCase();
            const isRel = f === "PRENOMREL" || f === "NOMREL";

            // SYN008 / SYN0008 : Contient uniquement une valeur exclue
            if (FORBIDDEN_WORDS.has(upperStr)) {
                pushErr(
                    isRel ? "SYN0008" : "SYN008",
                    isRel ? "Avertissement" : "Erreur",
                    f,
                    "Valeur du champ non conforme",
                );
            }

            // SYN006 / SYN0006 : Caractères spéciaux interdits (Sauf ' ', '-', ''')
            if (/[^a-zA-Z0-9\s\-\(\)\']/.test(valStr)) {
                pushErr(
                    isRel ? "SYN0006" : "SYN0006",
                    isRel ? "Avertissement" : "Erreur",
                    f,
                    "Champ ne doit pas contenir de caractères spéciaux",
                );
            }

            // SYN007 / SYN0007 : Pas plus de 2 chiffres dans l'identité
            const digitCount = (valStr.match(/\d/g) || []).length;
            if (digitCount > 2) {
                pushErr(
                    isRel ? "SYN0007" : "SYN0007",
                    isRel ? "Avertissement" : "Erreur",
                    f,
                    "Le champ ne doit pas contenir plus que 2 chiffres",
                );
            }

            // SYN010 / SYN0010 : Pas de répétition en série d'un même caractère (Ex: XXXXX)
            if (/(.)\1{4,}/.test(valStr)) {
                pushErr(
                    isRel ? "SYN0010" : "SYN0010",
                    isRel ? "Avertissement" : "Erreur",
                    f,
                    "Le champ ne doit pas contenir un caractère en série",
                );
            }
        }
    });

    // OBL003 / OBL004 : Vérification des tables de codes référentiels BEAC
    if (data.SEXE && !VALID_SEXE.includes(data.SEXE))
        pushErr("OBL003", "Erreur", "SEXE", "Valeur référentielle invalide");
    if (
        data.STATUT &&
        !VALID_STATUT.includes(data.STATUT.toString().padStart(2, "0"))
    )
        pushErr("OBL003", "Erreur", "STATUT", "Valeur référentielle invalide");
    if (
        data.RESIDENT &&
        !VALID_RESIDENT.includes(data.RESIDENT.toString().padStart(2, "0"))
    )
        pushErr(
            "OBL003",
            "Erreur",
            "RESIDENT",
            "Valeur référentielle invalide",
        );
    if (
        data.PRENAI &&
        !VALID_PRENAI.includes(data.PRENAI.toString().padStart(2, "0"))
    )
        pushErr("OBL003", "Erreur", "PRENAI", "Valeur référentielle invalide");
    if (
        data.SITMAT &&
        !VALID_SITMAT.includes(data.SITMAT.toString().padStart(2, "0"))
    )
        pushErr("OBL003", "Erreur", "SITMAT", "Valeur référentielle invalide");
    if (
        data.TYPLOG &&
        !VALID_TYPLOG.includes(data.TYPLOG.toString().padStart(2, "0"))
    )
        pushErr(
            "OBL004",
            "Avertissement",
            "TYPLOG",
            "Valeur référentielle invalide",
        );

    // ==========================================
    // 4. CONTRÔLES LOGIQUES ET MÉTIER (LOG)
    // ==========================================

    // Contrôles de validité et formats chronologiques de toutes les dates
    const dateFields = [
        { name: "DATNAI", required: true },
        { name: "DATDEBINT", required: false },
        { name: "DATEFININT", required: false },
        { name: "DATENTRELPAR", required: false },
        { name: "DATEVE", required: false },
        { name: "DATEMPIECE", required: false },
        { name: "DATFINPIECE", required: false },
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
                    "Le format de date doit être JJMMAAAA",
                );
            } else if (!check.logicalValid) {
                pushErr("LOG003", "Erreur", d.name, "Date invalide");
            }
        }
    });

    // LOG001 : Date d'événement <= Date de session/déclaration
    if (data.DATEVE) {
        const dEve = parseBEACDate(data.DATEVE.toString());
        if (dEve && dEve > dateDeclaration) {
            pushErr(
                "LOG001",
                "Erreur",
                "DATEVE",
                "La date d'événement doit être inférieure ou égale à la date de déclaration",
            );
        }
    }

    // LOG007 : Date de naissance < Date de session/déclaration
    if (data.DATNAI) {
        const dNai = parseBEACDate(data.DATNAI.toString());
        if (dNai && dNai >= dateDeclaration) {
            pushErr(
                "LOG007",
                "Erreur",
                "DATNAI",
                "La date de naissance doit être inférieure à la date de déclaration.",
            );
        }
    }

    // LOG002 : Date fin d'activité / pièce > Date début émission
    if (
        data.DATEMPIECE &&
        data.DATFINPIECE &&
        data.DATEMPIECE !== "PND" &&
        data.DATFINPIECE !== "PND"
    ) {
        const emi = parseBEACDate(data.DATEMPIECE.toString());
        const fin = parseBEACDate(data.DATFINPIECE.toString());
        if (emi && fin && fin <= emi) {
            pushErr(
                "LOG002",
                "Erreur",
                "DATFINPIECE",
                "Date fine doit être strictement supérieure à la date de début",
            );
        }
    }

    // LOG014 / LOG015 : Dates de début relation et pièce <= Date de session
    if (data.DATENTRELPAR) {
        const entRel = parseBEACDate(data.DATENTRELPAR.toString());
        if (entRel && entRel > dateDeclaration) {
            pushErr(
                "LOG014",
                "Erreur",
                "DATENTRELPAR",
                "La date d'entrée en relation avec le déclarant doit être inférieure ou égale à la date de session.",
            );
        }
    }
    if (data.DATEMPIECE && data.DATEMPIECE !== "PND") {
        const emiPiece = parseBEACDate(data.DATEMPIECE.toString());
        if (emiPiece && emiPiece > dateDeclaration) {
            pushErr(
                "LOG015",
                "Erreur",
                "DATEMPIECE",
                "La date début ne doit pas être supérieure à la date de session",
            );
        }
    }

    // LOG008 : Incohérence données relatives à la résidence
    if (
        data.PAYSRES === "CM" &&
        data.RESIDENT &&
        data.RESIDENT.toString() !== "01" &&
        data.RESIDENT.toString() !== "1"
    ) {
        pushErr(
            "LOG008",
            "Erreur",
            "RESIDENT",
            "Incohérence dans les données relatives à la résidence",
        );
    }

    // LOG019 : Pays de résidence principal vs Pays fourni dans l'Adresse
    if (data.PAYSRES && data.PAYS && data.PAYSRES !== data.PAYS) {
        pushErr(
            "LOG019",
            "Erreur",
            "PAYS",
            "Le pays de résidence et le pays de l'adresse de résidence principale ne sont pas concordants",
        );
    }

    // LOG018 : Cohérence de zone géographique Région / Pays
    if (data.REGION && data.PAYS) {
        const regStr = data.REGION.toString().padStart(3, "0");
        if (
            data.PAYS.toString().toUpperCase() === "CM" &&
            (regStr.startsWith("9") || regStr.startsWith("8"))
        ) {
            pushErr(
                "LOG018",
                "Avertissement",
                "REGION",
                "La région et le pays ne sont pas concordants",
            );
        }
    }

    // Gestion des Interdictions Judiciaires (SitJud, DatDebInt, DateFinInt)
    if (data.SITJUD && data.SITJUD.toString() === "0") {
        if (data.DATDEBINT || data.DATEFININT) {
            pushErr(
                "LOG031",
                "Erreur",
                "SITJUD",
                "Les champs de date d'interdiction ne doivent pas être renseignés si SitJud = 0",
            );
        }
    }
    if (data.SITJUD && data.SITJUD.toString() === "1") {
        if (!data.DATDEBINT) {
            pushErr(
                "OBL002",
                "Erreur",
                "DATDEBINT",
                "Champ obligatoire non fourni (Requis car SitJud = 1)",
            );
        }
        if (data.DATEFININT) {
            const finInt = parseBEACDate(data.DATEFININT.toString());
            if (finInt && finInt <= dateDeclaration) {
                pushErr(
                    "LOG002",
                    "Erreur",
                    "DATEFININT",
                    "La date de fin d'interdiction doit être supérieure à la date courante de session",
                );
            }
        }
    }

    // Exclusion Mutuelle et Règles Agent Économique (Professions libérales/Entrepreneurs '1080' ou '1090')
    if (data.AGEECO) {
        const ageEcoStr = data.AGEECO.toString();
        const isProfessional = ageEcoStr === "1080" || ageEcoStr === "1090";

        if (isProfessional) {
            if (!data.RCCM || data.RCCM === "PND") {
                pushErr(
                    "OBL002",
                    "Erreur",
                    "RCCM",
                    "Champ obligatoire non fourni (Requis pour l'agent économique professionnel 1080/1090)",
                );
            } else {
                const rccmLen = getCleanAlphanumericLength(data.RCCM);
                const allowedRccmLens = countryConfig.RCCM;
                if (allowedRccmLens && !allowedRccmLens.includes(rccmLen)) {
                    pushErr(
                        "SYN011",
                        "Erreur",
                        "RCCM",
                        `Format invalide (Longueur RCCM attendue pour ${currentCountry}: ${allowedRccmLens.join("/")} caractères utiles)`,
                    );
                }
            }
        } else {
            // LOG011, LOG012, LOG013 : Interdiction si ce n'est pas un professionnel indépendant
            if (data.RCCM)
                pushErr(
                    "LOG011",
                    "Avertissement",
                    "RCCM",
                    "Le champ RCCM ne doit pas être renseigné",
                );
            if (data.CA)
                pushErr(
                    "LOG012",
                    "Avertissement",
                    "CA",
                    "Le champ chiffre d'affaires ne doit pas être renseigné",
                );
            if (data.NOMBEMP)
                pushErr(
                    "LOG013",
                    "Avertissement",
                    "NOMBEMP",
                    "Le champ nombre d'employé ne doit pas être renseigné",
                );
        }
    }

    // LOG023 / LOG026 / LOG029 / LOG034 : Vérification d'unicité des identifiants relationnels liés
    const linkedIdentifiers = [
        { field: "IDINTMAND", label: "mandataire" },
        { field: "IDINTREL", label: "relation" },
        { field: "IDINTACT", label: "actionnaire" },
        { field: "IDINTEMP", label: "employeur" },
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
                `L'identifiant interne du ${id.label} doit être différent de l'identifiant interne du déclaré principal (IDINTCLI).`,
            );
        }
    });

    return {
        isValid: !errors.some((e) => e.type === "Erreur"),
        errors: errors,
    };
}

/**
 * Traitement par lots de déclarations (Array de Personnes Physiques)
 */
export function validateAllPersonnesPhysiques(
    dataArray,
    currentCountry = "CM",
) {
    return dataArray.map((item) => ({
        data: item,
        ...validatePersonnePhysique(item, currentCountry),
    }));
}
