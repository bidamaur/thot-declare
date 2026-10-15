import test from "node:test";
import assert from "node:assert/strict";
import { runComplexValidation } from "../resources/js/validators/cdr_encours_engagement_ctrComplexe.js";

const makeEncours = (counts) => ({
    CLI: "000123",
    EVE: "000456",
    AVE: "1",
    MNTCRD: "100",
    MNTCRESOUF: "10",
    CLADEPREC: "02",
    ...counts,
});

const makeEngagement = (total) => ({
    CLI: "000123",
    EVE: "000456",
    AVE: "1",
    MNTENG: "1000",
    DUREE: String(total),
});

test("accepts paid, unpaid and remaining installments equal to engagement total", () => {
    const anomalies = runComplexValidation(
        [
            makeEncours({
                NBRECHPAY: "11",
                NBRECHIMP: "1",
                NBRECHRES: "0",
            }),
        ],
        [makeEngagement(12)],
    );

    assert.equal(
        anomalies.some((anomaly) => anomaly.code === "CX_DUREE_001"),
        false,
    );
});

test("reports an installment count that differs from engagement total", () => {
    const anomalies = runComplexValidation(
        [
            makeEncours({
                NBRECHPAY: "11",
                NBRECHIMP: "1",
                NBRECHRES: "0",
            }),
        ],
        [makeEngagement(13)],
    );

    const installmentAnomaly = anomalies.find(
        (anomaly) => anomaly.code === "CX_DUREE_001",
    );
    assert.ok(installmentAnomaly);
    assert.equal(installmentAnomaly.value, "12");
});
