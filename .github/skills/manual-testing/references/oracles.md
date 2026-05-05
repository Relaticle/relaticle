# Oracle Catalogue (FEW HICCUPPS)

Every reported bug must cite **one letter** from this catalogue as its detection oracle. No vibes-based findings.

If the skill cannot articulate which oracle was violated, the observation is not a bug — it gets logged as a "neutral note" instead and is not reported.

| Letter | Oracle | What it tests | Relaticle example of a violation |
|---|---|---|---|
| **F** | Familiar | Pattern we've seen break before | Loading state spinner not cleared after error response |
| **E** | Explainable | User can articulate what just happened | 500 page with no error message; `<x-error>` blank |
| **W** | World | Real-world expectation | "Save" button shows success notification but DB unchanged |
| **H** | History | Worked before this change | Filament action that worked yesterday now throws |
| **I** | Image | Relaticle quality bar | AI-slop layout, broken alignment, inconsistent typography |
| **C** | Comparable | Other CRMs handle differently | Bulk edit modal in HubSpot supports undo; this one doesn't acknowledge changes |
| **C** | Claims | README/docs/marketing claim violated | README says "5-layer authorization"; cross-tenant probe succeeds |
| **U** | Users | Real workflow need | Salesperson can't undo accidental delete of a Company |
| **P** | Product | Internal consistency | CompanyResource sorts by name asc by default; PeopleResource sorts by created_at desc — same table component, different default |
| **P** | Purpose | Feature's stated purpose / Definition of Done violation | Charter DoD says "validation errors identical across surfaces"; UI shows field-level, API returns plain string |
| **S** | Statutes | GDPR/PII/encryption/AGPL compliance | Encrypted custom field returns plaintext in API response |

## Disambiguating C / C and P / P

There are two C's and two P's. The skill must pick the more specific one:

- **Comparable** vs **Claims**: if a competing product has a feature, that's *Comparable*. If our own README/docs assert it, that's *Claims*. *Claims* wins when both apply.
- **Product** vs **Purpose**: if the inconsistency is across the codebase, that's *Product*. If it's a violation of *this charter's* DoD, that's *Purpose*. *Purpose* wins when both apply.

## How to use

When evaluating a matrix-cell observation, ask:

1. Did something fail or look wrong? → check oracle list.
2. Pick the **most specific** oracle violated.
3. If you can't pick one, the observation is not a reportable bug.
4. Record the chosen oracle in the finding (`**Oracle:** Claims`).

A finding without an oracle is rejected by the skill at the triage stage.
