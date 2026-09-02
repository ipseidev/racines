# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

A Laravel + Inertia (React) application for **Racines** (working name, the brand is a runtime setting), a French voice-first family-memoir service (the "Remento clone" of the folder name). Block 00 of the roadmap is done: the app runs under Sail, the quality gate is green, CI is wired. No product feature exists yet; the next block to execute is 01.

The repo root is the Laravel project. Alongside it: five French-language product documents at v2.3 in `docs/dossier/`, dated Remento screenshots in `docs/reference/` (git-ignored), and the execution roadmap in `docs/roadmap/`.

The dossier gates the MVP build on the Gate Phase 1 decision (March 2027). The user decided on 2026-09-02 to build the whole product block by block anyway, with a testable checkpoint per block and TDD on front and back; the pilot tooling (blocks 00-17 of the roadmap) is what gets built first. When the dossier and the roadmap disagree, the dossier wins and the roadmap is corrected.

## Execution roadmap (read this before writing any code)

Everything about _how_ to build lives in `docs/roadmap/`. Read, in order and once: `00_INDEX.md` (block order, definition of done), `01_CONVENTIONS.md` (repo layout, naming, TDD protocol, canonical commands, env vars, security rules), `02_GLOSSAIRE_TECH.md` (French domain terms to English code identifiers, story states, token types), `03_DECISIONS.md` (stack and why). Then open the first block in `docs/roadmap/blocs/` whose status is not « terminé » and follow it: install the listed packages, write the §5 tests first, implement §6 in order, tick the boxes, run the §7 checkpoint, meet the §8 exit criteria, tag `bloc-XX-done`. Annexes hold the question corpus, the data model and the completion-engine rules.

Stack, decided 2026-09-02: Laravel + Inertia (React, TypeScript) + Postgres, Sail locally, Forge on a DigitalOcean droplet in an EU region in production, Cloudflare R2 with EU jurisdiction for media, Filament for the internal admin only, Resend for email, Twilio for SMS, Stripe via Cashier, Claude (`anthropic-ai/sdk`, model `claude-opus-5`) for the Fluide rendering, Gladia then Deepgram for transcription, PostHog EU for analytics. Brand name, links domain, colors and fonts are settings editable in the admin: never hard-code them.

All documents are written in French. Keep edits in French and keep the canonical French terms verbatim (`Initiateur·rice`, `Narrateur·rice`, `Proches`, `VALIDÉE`, `book-ready`, `BAT`, etc.).

## Document map and precedence

| File                                                         | Role                                                                                                                                                                                                                                                                         |
| ------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `docs/dossier/01_EXECUTIVE_MEMO.md`                          | Decision memo for the committee; carries the per-version changelog line under the title                                                                                                                                                                                      |
| `docs/dossier/02_OPPORTUNITY_ASSESSMENT.md`                  | Market, competitors, substitutes, what Phase 0 must validate                                                                                                                                                                                                                 |
| `docs/dossier/03_PRD_MVP.md`                                 | MVP scope (P0-1…P0-18), story state machine, completion engine spec (§5.3), acceptance criteria, pricing scenarios                                                                                                                                                           |
| `docs/dossier/04_DOSSIER_CONFIANCE_CONFORMITE_OPERATIONS.md` | Consent, GDPR/AI Act, content governance, hosting/QR/export commitments, SLOs, security (§12)                                                                                                                                                                                |
| `docs/dossier/05_REFERENTIEL_GLOSSAIRE_SOURCES.md`           | **Canonical reference.** Roles, offer, prices, state machine, hypotheses/thresholds, calendar, commitments, forbidden vocabulary, open-decision register, glossary, source register                                                                                          |
| `docs/reference/remento-screenshot/`                         | 24 dated captures of remento.co as seen from France on 2026-09-01/02, registered as source S16. **Finish reference, never a spec.** The Review-page capture holds personal test data: never commit or share this folder                                                      |
| `docs/roadmap/`                                              | Technical roadmap v1.0: index, conventions, glossary, decisions, versions, 18 block files (`blocs/B00_…` to `B17_…`), annexes (question corpus, data model, engine rules). Block 00 moves the five dossier files to `docs/dossier/` and the screenshots to `docs/reference/` |

**Doc 05 wins on any numeric or definitional conflict.** Every other doc states this in its header. When changing a threshold, price, date, role name, state name or commitment: edit doc 05 first, then propagate to the docs that cite it. Never introduce a figure in docs 01–04 that doc 05 does not carry.

## Cross-reference system

Docs cite each other through stable identifiers. Preserve them and reuse them rather than paraphrasing:

- `R-1` … `R-12` (plus `R-8b`, `R-10.1`…): canonical sections of doc 05 (roles, offer, prices, states, hypotheses, book-ready, KPIs, calendar, channel, durability commitments, forbidden vocabulary, open-decision register).
- `H0` … `H3`: the four hypotheses with kill/go thresholds (R-5). `H0` = gift acceptance, `H1` = repetition at J70 measured ITT, `H2` = family loop, `H3` = contribution after CAC.
- `P0-1` … `P0-18`: MVP scope items in doc 03 §3.
- `D-7`, `D-8`, `D-9`: open decisions, all listed in R-12 (149 € tier economics; QR commitment duration; phone-recording demand test with pre-committed attach-rate thresholds). Add new ones as `D-10`, `D-11`… and register them in R-12.
- `[S01]` … `[S16]`: source register in doc 05. New sources get the next number and an entry there; sources relayed by a competitor are marked as such and never carry a market decision alone.
- "doc 0X §Y" references point at the section numbering of the target file; keep section numbers stable or update every citation.

Every quantitative or market claim carries one taxonomy tag (doc 02): `[FAIT SOURCÉ Sxx]`, `[DÉCLARATIF]`, `[OBSERVATION]`, `[ESTIMATION]`, `[HYPOTHÈSE]`, plus `[OBJECTIF à recalibrer]` for targets. Untagged numbers are a defect.

## Editing rules specific to this dossier

- **Version discipline**: all five docs share one version (`v2.3 — Septembre 2026`). A substantive change bumps the version everywhere and adds a "Changements vX.Y" sentence under the title of doc 01.
- **Strategy, decided 2026-09-02**: same product shape as Remento, different substance. Copy the proven UX patterns (link, one-button page, no account, verbatim + polished text, QR book, scheduled gift delivery, 60-second demo); differentiate on the completion engine, narrator sovereignty, French-native operations, honest promises and an own brand. Non-captivity is table stakes, not a selling point. On any conflict the dossier wins over the screenshots.
- **Forbidden vocabulary (R-11)** in product, marketing and contract wording: « pour toujours », « illimité », « QR autonomes », « les contenus appartiennent à la famille », « validation tacite/automatique », « garanti à vie ». Use the replacements doc 05 gives (fair use chiffré, R-10.1 licence wording, etc.).
- **Validation is explicit, never tacit.** The story state machine (R-4) is `PROPOSÉE → ENREGISTRÉE → TRANSCRITE → À RELIRE → VALIDÉE → PARTAGÉE → INCLUSE AU LIVRE`, with withdrawal states `MASQUÉE / ARCHIVÉE / CORBEILLE (30 j) / SUPPRIMÉE`. Do not invent states or imply that silence equals consent.
- **Roles are R-1 names.** "Claire" is a marketing persona, not a role. The narrator's veto always prevails over the initiator.
- **Book-ready is R-6 criteria** (words / audio minutes / pages / themes), never a story count. "~25 histoires" is a marketing landmark only.
- **Multi-narrators** stay out of P0 UI but in the data model, promoted only if ≥ 35 % of purchase intentions are conditioned on two narrators.
- **No voice cloning, AI always disclosed, no training on family content** (doc 04 §1). These are brand positions, not implementation details.
- Prices are hypotheses (R-3): 49 € pilot, 99 € vs 129 € core, 149–199 € assisted, 120 € modelled basket. Do not present any of them as fixed.

## Checks available today

Start the environment with `./vendor/bin/sail up -d`; the app answers on `http://localhost:8001` (ports are offset, decision T-34). The canonical commands are in `docs/roadmap/01_CONVENTIONS.md` §6: `sail composer check` for the whole PHP gate, `sail npm run check` and `sail npm run types:check` for the front, `sail npm run test` for Vitest, `sail npx playwright test` for end-to-end. PHPStan runs at level 8 and must stay at zero errors.

One check has no tooling and must be run by hand on the French text, scanning for the vocabulary R-11 forbids:

```bash
grep -rn -i -E "pour toujours|illimité|QR autonomes|appartiennent à la famille|validation tacite|validation automatique|garanti à vie" docs/dossier docs/roadmap
```

Expected hits are the R-11 list itself (doc 05 and `docs/roadmap/00_INDEX.md`), negated uses such as « Jamais de validation tacite » in doc 03, and the description of Remento's tacit-sharing model in doc 02. Anything else needs rewording.

## Product constraints any future code must honour

These are spread across docs 03, 04 and 05 and are declared non-negotiable for the eventual build:

- **Channel**: a browser recording link sent by SMS/email, one link per question, valid 30 days, reusable until `VALIDÉE`, revocable and re-issuable. No native app (PWA/responsive web), no native WhatsApp at MVP (R-9). Telephone is never a default channel: the « Enregistrement par téléphone » option is a paid demand test (D-9), human-operated and capped at 10 families during the pilot, automated only if the R-12 thresholds are met.
- **Recording links are bearer tokens** (doc 04 §12): ≥ 128 bits entropy, no personal data in the URL, strict scope (1 link = 1 question = 1 narrator = record-only), invalidated on validation, masked in logs/analytics. Family listening links are separate, read-only tokens. No account is needed to record; sensitive acts (withdrawal, deletion, post-mortem directives) require passwordless OTP auth.
- **Browser targets**: Safari iOS N-2, Chrome Android N-2, Samsung Internet; recording must survive incoming calls, sleep and Safari tab purge; iOS auto-resume is to be proven by spike, not promised (doc 03 US-01).
- **Data**: EU hosting, encryption at rest and in transit, per-project isolation, antivirus/format checks on uploads, resumable uploads, immutable logging of consents and of every back-office action including reads.
- **Provider abstraction** for ASR, LLM, SMS/email, print and payment, with documented exit strategies; no provider training on customer data.
- **Content invariants**: source audio is kept and never replaced; the Verbatim rendering is never deleted and sits alongside the Fluide rendering; nothing is visible to family before `VALIDÉE`.
- **Durability commitments (R-10)**: free complete export (PDF, MP3, ZIP + manifest) during the whole hosting period, delivered proactively at book finalisation; published hosting duration; QR pages read-only with published commitment duration and an offline pack.
- **SLOs (doc 04 §11)**: pre-confirmation capture failure < 2 % with draft resume; zero loss after "histoire enregistrée" (immediate replication of confirmed audio); quarterly restore drills with RTO ≤ 72 h; 99.5 % availability at MVP.
- **Accessibility**: WCAG 2.2 AA, ≥ 44 px touch targets, system zoom respected, no anxiety-inducing countdowns.
