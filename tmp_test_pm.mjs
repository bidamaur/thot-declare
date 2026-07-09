import { validateAllPersonnesMorales } from "./resources/js/validators/cdrPm.js";
import { generateFRCBXml as gen } from "./resources/js/services/frcbExportService.js";

const rec = {
    "IDINTCLI": "101016",
    "NIF_NIU": "P126800238157J",
    "RAISOC": "CABINET DENTAIRE DE LA GRACE",
    "DATCRE": "08052026",
    "SIGLE": null,
    "RESIDENT": "01",
    "PAYSSIEGE": "CM",
    "VILLE": "1528",
    "RCCM": "CM-DLA-01-2008-A-10-",
    "FORJURID": "05",
    "SECACT": "86.32",
    "AGEECO": "1061",
    "STALEG": "01",
    "DATENTRELPAR": "11052026",
    "EMAIL": null,
    "TEL": "00237696623676",
    "SITJUD": "0",
    "DATEVE": "11052026",
    "TYPADR": "03",
    "ADRESSE": "BONAPRISO",
    "PAYS": "CM",
    "REGION": "1500",
};

const res = validateAllPersonnesMorales([rec])[0];
console.log("SECACT errors:", res.errors.filter((e) => e.field === "SECACT").map((e) => e.code + " " + e.message));
console.log("ALL errors:", res.errors.map((e) => e.field + ":" + e.code));

console.log("rec.SECACT =", JSON.stringify(rec.SECACT), "typeof", typeof rec.SECACT);
const toXmlAttrValue = (value) => { if (value === undefined || value === null) return ""; return String(value).trim(); };
console.log("manual attr SectAct =", ` SectAct="${toXmlAttrValue(rec.SECACT)}"`);

const out = gen([rec], { NumDec: "0001", CodDec: "10030", NatDec: "01", comment: "" });
console.log("\n--- XML ---\n" + out.xml);
