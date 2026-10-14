const toXmlAttribute = (value) =>
    String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/"/g, "&quot;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/'/g, "&apos;");

const attr = (name, value) => ` ${name}="${toXmlAttribute(value)}"`;

export const getCurrentDeclarationDate = () => {
    const now = new Date();
    return `${String(now.getDate()).padStart(2, "0")}${String(now.getMonth() + 1).padStart(2, "0")}${now.getFullYear()}`;
};

export function generateReferenceUpdateXml(rows, configuration = {}) {
    const mappings = rows.map((row) => ({
        oldId: String(row.oldId ?? "").trim(),
        newId: String(row.newId ?? "").trim(),
    }));

    if (!mappings.length || mappings.some((row) => !row.oldId || !row.newId)) {
        throw new Error("Chaque ligne doit contenir les deux identifiants.");
    }

    const header = {
        NumDec: "0001",
        CodPay: "CM",
        CodDec: "10030",
        TypDec: "11",
        NbrDec: String(mappings.length),
        NatDec: "01",
        DatDec: getCurrentDeclarationDate(),
        comment: "",
        ...configuration,
        NbrDec: String(mappings.length),
    };

    const attributes =
        attr("NumDec", header.NumDec) +
        attr("CodPay", header.CodPay) +
        attr("CodDec", header.CodDec) +
        attr("TypDec", header.TypDec) +
        attr("NbrDec", header.NbrDec) +
        attr("NatDec", header.NatDec) +
        attr("DatDec", header.DatDec) +
        attr("comment", header.comment);
    const elements = mappings
        .map(
            (row) =>
                `    <MiseAJourIdentifiant${attr("AncienIdIntCli", row.oldId)}${attr("NouveauIdIntCli", row.newId)}></MiseAJourIdentifiant>`,
        )
        .join("\n");
    const xml = `<Declaration${attributes}>\n${elements}\n</Declaration>`;
    const filename = `${header.CodPay}-${header.CodDec}-${header.NumDec}-${header.DatDec}-${header.TypDec}-DEC.xml`;

    return { xml, filename, header };
}

export function downloadReferenceUpdateXml(xml, filename) {
    const blob = new Blob([xml], { type: "application/xml;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}
