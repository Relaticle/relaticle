# Tour Catalogue

8 Whittaker-style exploration tours, mapped to Relaticle workflows. Tours are *what to do*; oracles are *whether what happened is wrong*.

## Tour selection

The skill picks tours by surface. Always-include rules trump risk tier:

| Surface in matrix | Tours added |
|---|---|
| Filament UI | Garbage Collector, Landmark, Supermodel |
| Livewire | Interrupt, Supermodel |
| REST API | Data Flow, Stress |
| MCP server | Data Flow |
| Migration in diff | Stress (record-count boundary) |
| Auth/policy in diff | Sensitive Data (always-include) |
| Custom-fields-bearing model in diff | Sensitive Data (always-include) |
| deep tier (any diff) | Back Alley (always-include at deep tier) |

Risk tier caps the *count* of tours run per surface (smoke: 1, light: 2, medium: 3, deep: all relevant).

---

## Garbage Collector

Visit every menu, dropdown, error message, and empty state on the changed surface, **once**. Don't pursue depth — touch breadth.

**Relaticle steps:**
1. Open the changed Filament resource list view.
2. Click every header column to sort.
3. Open the filter panel; toggle each filter once.
4. Open the action menu; click each action (cancel modals immediately).
5. Trigger the empty state (filter to nothing) — does it render correctly?
6. Trigger an error state (e.g., visit `/<resource>/0` for a non-existent ID).

**Bug class hunted:** Forgotten error handling, broken filters, missing empty-state copy.

---

## Landmark

Companies → People → Opportunities → Tasks → Notes in random order. Catches state-leak between resources.

**Relaticle steps:**
1. Open Companies list. Note the URL.
2. Click into a Company detail. Note URL.
3. Click "Tasks" in the sidebar. Note URL.
4. Click into a Task. Click "Back to Companies" or use breadcrumb.
5. Confirm Companies list state (filters, page) is preserved or sensibly reset.

**Bug class hunted:** Stale URL state, breadcrumb bugs, sidebar state desync, search-context leakage.

---

## Data Flow (FedEx)

Follow data through every front door. Same data must round-trip identical.

**Relaticle steps:**
1. Create a record via Filament UI (capture all custom-field values).
2. `GET /v1/<resource>/<id>` via REST — assert payload matches.
3. Call `Show<Resource>Tool` via MCP — assert payload matches.
4. Export to CSV (if the resource supports it) — assert columns and values.
5. (Optional) Re-import the CSV — assert no duplication, no truncation, no encoding loss.

**Bug class hunted:** API/UI/MCP shape drift; encoding loss (Unicode/RTL); validation drift across surfaces.

---

## Sensitive Data (Money Tour, repurposed)

Touch every place that handles PII or encrypted custom fields. Verify encryption + tenant scoping at each touchpoint.

**Relaticle steps:**
1. List every encrypted-flagged custom field on the affected models (`SELECT * FROM custom_fields WHERE encrypted = true`).
2. For each, verify: stored as ciphertext (DB query), decrypted only for owner, returned as plaintext in API only for owner.
3. Cross-tenant probe each: as Team A, attempt to read Team B's encrypted field via API/MCP — expected: 403 or zero data.
4. PII fields (email, phone): same probes.

**Bug class hunted:** Encryption bypass, plaintext leak in logs, cross-tenant PII leak.

---

## Interrupt (Rained-Out Tour)

Start an action, abandon midway. Various interruption modes.

**Relaticle steps:**
1. Open a multi-step form. Start filling. Hit browser back. Return — is the form draft preserved or cleanly cleared?
2. During a save request, refresh the page. After refresh: did the save complete? Is the UI consistent with DB state?
3. Drag-and-drop a Task on the Tasks board. Release outside the drop zone. Where does the Task end up?
4. Double-click Submit on a form. How many records are created (expected: 1)?

**Bug class hunted:** Idempotency, partial state, transaction integrity, drag-drop edge cases.

---

## Back Alley

Least-used features. Where bugs hide because nobody looks.

**Relaticle steps:**
1. Custom field admin (create/edit/delete a custom field on the affected model).
2. API tokens page (create, revoke, regenerate).
3. Scribe API docs page (does it render after the diff?).
4. System administrator panel (if accessible — `/sysadmin/*` routes).
5. Profile / EditTeam pages.

**Bug class hunted:** Forgotten admin features, broken doc generation, sysadmin surface regressions.

---

## Stress (Intellectual Tour)

Push the limits.

**Relaticle steps:**
1. Max-length strings in every text input on the diff (use `references/data-nasties.md` payloads).
2. 10K records pagination: navigate to page 50, page 100, page 500. Time the response.
3. Deeply nested custom fields: a record with values for all 22 custom-field types simultaneously.
4. JSON / CustomFieldValueData bombs: payloads with deeply nested arrays, max-int values.

**Bug class hunted:** Memory blowups, query timeouts, JSON serialization failures, off-by-one pagination bugs.

---

## Supermodel

Visual / layout-only.

**Relaticle steps:**
1. Mobile viewport (`agent-browser set viewport 375 812`). Re-walk the changed pages — is anything broken?
2. If dark mode exists, toggle and re-walk.
3. Empty state: render the changed page with zero data. Look right?
4. Error state: trigger a 500 / validation error. Is the page graceful?
5. Long content overflow: a Company name 200 chars long, a Note 5000 chars long. Does layout hold?

**Bug class hunted:** Visual inconsistency, mobile layout breakage, content overflow, AI-slop patterns.
