import test from "node:test";
import assert from "node:assert/strict";
import { validerDateFormat } from "../resources/js/validators/cdr_encours_engagement.js";

test("accepts human-readable dates and rejects invalid ones", () => {
    assert.equal(validerDateFormat("12-JUN-26"), true);
    assert.equal(validerDateFormat("11-may-2026"), true);
    assert.equal(validerDateFormat("31/02/2023"), false);
    assert.equal(validerDateFormat("99-ABC-2026"), false);
});
