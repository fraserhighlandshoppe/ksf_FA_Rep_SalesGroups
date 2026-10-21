# BABOK Alignment — ksf_FA_Rep_SalesGroups

This document maps the ksf_FA_Rep_SalesGroups project to the **Business
Analysis Body of Knowledge (BABOK 3.0)** knowledge areas, tasks and
techniques. Each requirement and design decision is traced to BABOK tasks.

---

## Knowledge Area 1: Business Analysis Planning and Monitoring

### Task: Plan Business Analysis Approach

| Element | Application |
|---------|-------------|
| Approach | Agile BA — working software as the primary deliverable, with the report centre as the feedback loop |
| Techniques | Stakeholder List, Document Analysis, Interface Analysis |
| Outputs | This document, `Requirements.md`, `RTM.md`, `UseCases.md`, `TestPlan.md`, `UAT.md` |

### Task: Plan Stakeholder Engagement

| Stakeholder | BABOK Role | Engagement Strategy |
|-------------|------------|---------------------|
| Finance / Sales manager | Business Sponsor | Requirements sign-off, report layout reviews |
| FA Administrator | Domain SME | Install and activation checks, access level review |
| Developer (ksf) | Implementation | `AGENTS.md` conventions, peer reviews |

### Task: Plan BA Information Management

| Artifact | Repository | Access |
|----------|------------|--------|
| Requirements | `ProjectDocs/Requirements.md` | All team members |
| RTM | `ProjectDocs/RTM.md` | All team members |
| Use cases | `ProjectDocs/UseCases.md` | All team members |
| Code | GitHub | Developers |
| Test cases | `tests/` | QA team |
| UAT evidence | `ProjectDocs/UAT.md` | Business sponsor |

---

## Knowledge Area 2: Elicitation and Collaboration

### Task: Prepare for Elicitation

| Source | Technique | Outcome |
|--------|-----------|---------|
| Native FA report classes (`reporting/includes/pdf_report.inc`) | Interface Analysis | `FrontReport` has no `row()` method; the API is `NewLine()` + `TextCol()` |
| FA's `get_branch_contacts()` | Interface Analysis | The branch contact convention: `crm_contacts.entity_id` = `cust_branch.branch_code`, action `general` |
| FA's `customer_branches_list` query | Interface Analysis | The branch contact join the module mirrors |
| `ksf_Infrastructure/NAMESPACE_COMPLIANCE_TODO.md` | Document Analysis | The namespace rule the module follows |
| Report centre review with the business sponsor | Stakeholder Interview | One table per group and one table per customer, group name as the section title |

### Task: Conduct Elicitation

| Session | Technique | Result |
|---------|-----------|--------|
| Report layout review | Stakeholder Interview | The by group report is a table per group; the by customer report is a table per company |
| Contact review | Stakeholder Interview | Every action a person is associated with for a branch is listed, not only *general* |
| Contact type presentation | Stakeholder Interview | A person with several actions is one row, the types stacked in the cell |

### Task: Confirm Elicitation Results

| Item | Status |
|------|--------|
| Requirements | Confirmed in `Requirements.md` |
| Use cases | Confirmed in `UseCases.md` |
| Low level requirements | Confirmed in `RTM.md` |

---

## Knowledge Area 3: Requirements Life Cycle Management

### Task: Trace Requirements

See **RTM.md**. Every functional requirement maps to a use case, a code file
and a test case.

### Task: Maintain Requirements

| Change driver | Impact | Process |
|---------------|--------|---------|
| A new contact action is used | None | The action dropdown is built from the data, so it appears on its own |
| FA changes where contacts live | Contact lookup | The builder detects the schema; extend `detectContactMode()` if a third source appears |
| A new report is added | Registration | Add it to `reports_custom.php` in the *Sales Group* class |
| The access model changes | Security | The module declares no areas; move to its own area only if it needs one |

### Task: Prioritize Requirements

Using MoSCoW:

**Must Have**

- FR-01.01 through FR-01.05: the report queries
- FR-02.01 through FR-02.05: the report layout
- FR-03.01 through FR-03.03: the branch contact details
- FR-04.01 through FR-04.03: access and installation
- NFR-01.01 through NFR-01.04: quality attributes

**Should Have**

- FR-02.06 through FR-02.09: label fallbacks, the header counters, the column geometry

**Could Have**

- A contact action filter per report class
- Exporting the by customer report to a spreadsheet template
- Grouping the by customer report by sales group as well as by customer

**Won't Have (v1.x)**

- Editing sales groups or branch assignments from the report
- Maintaining contacts from the report
- Persisting a report definition

---

## Knowledge Area 4: Strategy Analysis

### Task: Analyze Current State

| Element | Current state |
|---------|---------------|
| Business process | Sales groups are assigned to customer branches in FA; there is no view of who is in which group |
| Technology | FA's Report Center, with reports added by an extension module |
| Pain points | The group membership could only be seen by opening the branch records one at a time |
| Production data | The reports are read only, so no data is at risk |

### Task: Define Future State

| Element | Future state |
|----------|--------------|
| Visibility | One table per group and one table per customer, with the branch contacts alongside |
| Delivery | The native FA Report Center, using the native access area |
| Risk | None to the data: both reports are queries and a print |

---

## Knowledge Area 5: Requirements and Design Definition

### Task: Specify Requirements

Functional and non-functional requirements are specified in
`Requirements.md`, with the data sources stated explicitly so that the
report's behaviour is traceable to the FA schema it reads.

### Task: Define Business Requirements

| Requirement | Business value |
|-------------|----------------|
| FR-02.01 — one section per group | A sales manager can see the membership of a group at a glance |
| FR-03.02 — every contact action | The person to contact for a branch is visible without opening the branch |
| FR-02.05 — one row per contact | A branch is not repeated once per contact, so the output is readable |

### Task: Define System Requirements

| Requirement | System behaviour |
|-------------|------------------|
| FR-01.03 — group driven query | The report returns the customers of the selected group |
| FR-03.03 — action filter in the join | A branch is never silently dropped from the report |
| FR-03.04 / FR-03.05 — schema detection | The module runs on installations with different contact storage |
| NFR-01.04 — failed query prints the SQL | A failure is diagnosable instead of silent |

---

## Knowledge Area 6: Solution Delivery and Transition

### Task: Build and Test

| Artefact | Where | Gate |
|----------|-------|------|
| SQL generation | `src/ReportSqlBuilder.php` | Unit tests, 100% coverage |
| Row presentation | `src/ReportPresenter.php` | Unit tests, 100% coverage |
| Table geometry | `src/ReportLayout.php` | Unit tests, 100% coverage |
| The report scripts | `reporting/rep_salesgroups_*.php` | UAT plan |

### Task: Deploy and Release

| Artefact | Where |
|----------|-------|
| The module | `ksf_Infrastructure/fa_modules/ksf_FA_Rep_SalesGroups/`, without the git repository |
| The version | `_init/config`: `Version` carries the FA compatibility prefix, `Build` carries the module release |
| The releases | git tags `v1.0.0` (the reports), `v1.1.0` (the reports with the unit test suite) |

---

## Knowledge Area 7: Evaluation

### Task: Monitor and Control the Delivered Work

| Measure | Target |
|---------|--------|
| Unit tests passing | 41 tests, 89 assertions, no skips |
| Line coverage of `src/` | 100% |
| Report activation | Succeeds, no schema change |
| Access level screen | Renders, the module adds no areas |

### Task: Evaluate the Deliverable

| Question | Answer |
|----------|--------|
| Did the module find the defect the report layout had? | Yes — the table was 535pt wide against a 525pt printable width, and the Email column was clipped. `testTableFitsThePageWidth` now pins it down |
| Is the report useful to the business sponsor? | To be confirmed at UAT |

### Task: Manage Changes

| Change | Impact analysis |
|--------|-----------------|
| A new report is added to the module | New class, new report file, new control type if needed, requirements and RTM updated, unit tests for the new builder behaviour |
| The contact storage moves in a future FA | `detectContactMode()` gains a branch; no change to the report scripts |
