# Requirements — ksf_FA_Rep_SalesGroups

FrontAccounting module providing two sales group reports:

* **Sales Groups by Group** — for each sales group, the debtor and branch
  pairs assigned to it.
* **Sales Group by Customer** — for each customer, their branches, the sales
  group each branch sits in, and the contacts associated with the branch.

## Glossary

| Term | Meaning |
|------|---------|
| Sales group | A native FA record in `groups`, assigned to a customer *branch* through `cust_branch.group_no` |
| Branch | A customer branch in `cust_branch`; the branch, not the customer, is what carries the sales group |
| Contact | A person associated with a branch (or with a customer) in the native FA CRM tables, for a given action: general, delivery, invoice or order |
| Report class | The grouping of reports in the FA Report Center (Reporting → Report Center → *Sales Group*) |

## Data sources

All data comes from native FA tables. The module creates no tables of its own
and needs no schema change.

| Table | Used for |
|-------|----------|
| `groups` | Sales groups (id, description) |
| `cust_branch` | Branches, their `group_no` and their `branch_ref` / `br_name` |
| `debtors_master` | Customer names |
| `crm_contacts` | Which people are associated with a branch, and for which action |
| `crm_persons` | The contact's name, phone and email |

## Functional requirements

### FR-01 Report queries

| ID | Requirement |
|----|-------------|
| **FR-01.01** | The module provides two reports, registered in the FA Report Center under a *Sales Group* report class |
| **FR-01.02** | An unselected filter means "no filter". The dropdowns submit `''` (FA's `ALL_TEXT`) or `'0'` for the *All* option; both must be treated as no filter, and no filter may add a `WHERE` predicate |
| **FR-01.03** | When a sales group is selected, the customer oriented query must be driven from the `groups` table. A filter applied to a table on the right of a `LEFT JOIN` silently discards every customer that has no branch in that group |
| **FR-01.04** | With no sales group selected, the customer oriented query lists every customer, whether or not they are in a sales group |
| **FR-01.05** | A selected customer filters the customer oriented query on `debtors_master.debtor_no` |

### FR-02 Report layout

| ID | Requirement |
|----|-------------|
| **FR-02.01** | The by group report produces one section per sales group, in the order the query returned them, and one section per customer for the by customer report |
| **FR-02.02** | A by group section lists the debtor and the branch of every customer assigned to that group |
| **FR-02.03** | A group with no customers assigned may be included or suppressed. When included it shows `--NO CUSTOMERS IN THIS GROUP--`; when suppressed, and no group has customers, the report shows `--NO GROUPS ASSIGNED TO CUSTOMERS--` |
| **FR-02.04** | The by customer report produces one section per customer |
| **FR-02.05** | A person associated with a branch for several actions is a single row. The actions are collected into the Type cell, ordered general, delivery, invoice, order, then anything else alphabetically |
| **FR-02.06** | The branch is identified by `branch_ref`, falling back to `br_name` when there is no reference |
| **FR-02.07** | The report header states how many customers and how many contacts were listed, and how many of those carry contact details |
| **FR-02.08** | The by customer table columns are Branch, Sales Group, then the available contact columns. The Type cell is narrower than the others |
| **FR-02.09** | The table must fit the printable width of an A4 portrait page |

### FR-03 Branch contact details

| ID | Requirement |
|----|-------------|
| **FR-03.01** | Branch contact details come from the native FA CRM tables (`crm_contacts` joined to `crm_persons`) |
| **FR-03.02** | Every contact associated with a branch is listed, for every action, not only the *general* action |
| **FR-03.03** | The report offers a contact action filter. The filter is applied inside the join, so a branch with no contact of that action still appears, with empty contact columns |
| **FR-03.04** | An installation without the CRM tables falls back to the contact columns on `cust_branch` |
| **FR-03.05** | An installation with neither produces a report with no contact columns. The report still runs, and an unreachable database must not take the report page down |

### FR-04 Access and installation

| ID | Requirement |
|----|-------------|
| **FR-04.01** | The module reuses the native FA access area `SA_CUSTBULKREP` and declares no security areas of its own |
| **FR-04.02** | The module requires no schema change and no Composer runtime dependency |
| **FR-04.03** | The module's version must keep the FA compatibility prefix (`2.4.x`); the module's own release is carried in a separate `Build:` field |

## Non-functional requirements

| ID | Requirement |
|----|-------------|
| **NFR-01.01** | The module runs on PHP 7.3 or later (the cross-module compatibility floor) |
| **NFR-01.02** | The report logic that can be isolated is unit tested, with 100% line coverage of `src/` |
| **NFR-01.03** | The module follows the project namespace rule: `ksf_FA_<Module>` becomes `ksfraser\FrontAccounting\<Module>\`, with the vendor segment lowercase |
| **NFR-01.04** | A failed query shows the SQL instead of failing silently, because FA's `check_db_error()` calls `end_page(); exit;` and takes the report page down with it |

## Out of scope

* Writing to the database — both reports are read only.
* Editing sales groups or branch assignments.
* Contact maintenance. The module reports the contacts that exist; it does not
  create or change them.
