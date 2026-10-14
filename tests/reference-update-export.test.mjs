import test from "node:test";
import assert from "node:assert/strict";
import { generateReferenceUpdateXml } from "../resources/js/services/referenceUpdateExportService.js";

test("generates reference update XML with automatic count and escaped identifiers", () => {
    const result = generateReferenceUpdateXml(
        [{ oldId: "0238450", newId: 'C00000238450&<"' }],
        { DatDec: "25092026" },
    );

    assert.equal(
        result.xml,
        '<Declaration NumDec="0001" CodPay="CM" CodDec="10030" TypDec="11" NbrDec="1" NatDec="01" DatDec="25092026" comment="">\n' +
            '    <MiseAJourIdentifiant AncienIdIntCli="0238450" NouveauIdIntCli="C00000238450&amp;&lt;&quot;"></MiseAJourIdentifiant>\n' +
            "</Declaration>",
    );
    assert.equal(result.filename, "CM-10030-0001-25092026-11-DEC.xml");
});

test("rejects incomplete reference mappings", () => {
    assert.throws(
        () => generateReferenceUpdateXml([{ oldId: "0238450", newId: "" }]),
        /Chaque ligne doit contenir les deux identifiants/,
    );
});
