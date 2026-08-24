import test from "node:test";
import assert from "node:assert/strict";
import { validerDateFormat } from "../resources/js/validators/cdr_encours_engagement.js";
import { runComplexValidation } from "../resources/js/validators/cdr_encours_engagement_ctrComplexe.js";

test("accepts human-readable dates and rejects invalid ones", () => {
    assert.equal(validerDateFormat("12-JUN-26"), true);
    assert.equal(validerDateFormat("11-may-2026"), true);
    assert.equal(validerDateFormat("31/02/2023"), false);
    assert.equal(validerDateFormat("99-ABC-2026"), false);
});

test("rejects inconsistent remaining capital and remaining installments", () => {
    const encours = [
        {
            CLI: "1",
            EVE: "2",
            AVE: "3",
            MNTCRD: 500,
            NBRECHRES: 0,
            NBRECHPAY: 12,
            NBRECHIMP: 0,
            MNTCRESOUF: 0,
            CLADEPREC: "01",
        },
        {
            CLI: "4",
            EVE: "5",
            AVE: "6",
            MNTCRD: 0,
            NBRECHRES: 1,
            NBRECHPAY: 12,
            NBRECHIMP: 0,
            MNTCRESOUF: 0,
            CLADEPREC: "01",
        },
    ];
    const engagements = [
        { CLI: "1", EVE: "2", AVE: "3", MNTENG: 500, DUREE: 12, CTR: "X" },
        { CLI: "4", EVE: "5", AVE: "6", MNTENG: 500, DUREE: 12, CTR: "X" },
    ];

    const erreurs = runComplexValidation(encours, engagements);

    assert.equal(
        erreurs.some((e) => e.code === "CX_COH_004" && e.field === "NBRECHRES"),
        true,
    );
    assert.equal(
        erreurs.some((e) => e.code === "CX_COH_008" && e.field === "MNTCRD"),
        true,
    );
    assert.equal(
        erreurs.some((e) => e.code === "CX_COH_009" && e.field === "NBRECHRES"),
        true,
    );
});
