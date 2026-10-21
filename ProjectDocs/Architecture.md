# Architecture — ksf_FA_Rep_SalesGroups

## Overview

The module is a read only reporting add-on for FrontAccounting. It adds two
reports to the native FA Report Center and creates nothing in the database.

```
Browser
  |
  |  Reporting -> Report Center -> *Sales Group* -> Display
  v
reporting/prn_redirect.php                (FA core)
  |  requires the report file
  v
reporting/rep_salesgroups_v1.php   ----.
  reporting/rep_salesgroups_v2.php      |  page scripts: filters, PDF/Excel
  |                                   |
  v                                   v
src/ReportSqlBuilder.php  ----->  src/ReportPresenter.php
  |                                   |
  |  builds the SQL                   |  buckets the rows into sections
  v                                   v
src/ReportLayout.php  ------------>  FrontReport (PDF) / Excel report class
```

## Components

### `reporting/rep_salesgroups_v1.php` — Sales Groups by Group

Reads the filter parameters, runs the query, and prints one table per sales
group. The report class is `FrontReport` from
`reporting/includes/pdf_report.inc`, or the Excel variant when the
destination is Excel.

### `reporting/rep_salesgroups_v2.php` — Sales Group by Customer

Reads the filter parameters, runs the query, and prints one table per
customer. Same report classes.

### `reporting/reports_custom.php` — registration

Registers the *Sales Group* report class and the two reports, and installs the
custom control handlers:

| Control type | Control |
|--------------|---------|
| `SALES_GROUPS` | A dropdown of the native FA sales groups |
| `CONTACT_ACTIONS` | A dropdown of the contact actions present in `crm_contacts` |
| `YES_NO_DEFAULT_YES` | A Yes/No selector that defaults to Yes |

The report class id is the value `addReportClass()` returns, not
`RC_CUSTOMER`: `RC_CUSTOMER` is `0`, and `addReportClass()` treats a falsy id
as "give me a new one", so passing it would create an empty class and leave
the reports in the standard *Customer* class.

### `src/ReportSqlBuilder.php` — the SQL

Builds the query for both reports.

* **By group** — `groups` LEFT JOIN `cust_branch` LEFT JOIN `debtors_master`.
  The group filter is applied to the driving table.
* **By customer** — `debtors_master` LEFT JOIN `cust_branch` LEFT JOIN
  `groups`, so every customer is listed whether or not they are in a group.
  When a sales group *is* selected the query is driven from `groups` with
  inner joins instead, because a predicate on the right of a `LEFT JOIN`
  turns it into an inner join and drops every customer that has no branch in
  that group.
* **Contacts** — `crm_contacts` joined to `crm_persons`, one row per contact.
  The action filter is applied inside the join so that a branch without a
  contact of that action still appears.

The builder also detects what the installation provides:

| Detected | Contact source |
|----------|----------------|
| `crm_contacts` and `crm_persons` exist | The CRM tables |
| Otherwise, `cust_branch` has contact-ish columns | Those columns |
| Otherwise | No contact details |

Detection uses `SHOW TABLES LIKE` and `SHOW COLUMNS FROM`, because a query
against a missing table is a fatal error in FA, while `SHOW` is not.

### `src/ReportPresenter.php` — the rows

Turns the result set into printable sections. This is where the behaviour
that is easy to get wrong lives:

* a group with no customers is included or suppressed;
* a person associated with a branch for several actions is a single row, and
  the actions are collected and ordered.

### `src/ReportLayout.php` — the columns

Computes the x positions, headings and alignments of the table, and reports
which column holds the contact types. The widths are chosen so the table
fits the printable width of an A4 portrait page (595pt wide, less the 40pt
left and 30pt right margins).

## Design decisions

| Decision | Rationale |
|----------|-----------|
| Reuse the native `SA_CUSTBULKREP` access area | The module declares no security areas, so there is nothing to register and nothing to get out of step with FA's own access level editor. Declaring an area the module does not own also breaks the FA access level screen, because `install_access()` must return the areas and the sections in that order |
| No schema of its own | Everything comes from native FA tables. `sql/install.sql` documents the mapping and installs nothing |
| No Composer runtime dependency | The module loads its classes with `require_once`. The Composer autoloader is a development dependency only, so the deployed tree does not need `vendor/` |
| Read only | Both reports are queries and a print. Nothing to migrate, nothing to roll back |
| A failed query prints the SQL | FA's `check_db_error()` calls `end_page(); exit;`, so a query with an error message takes the whole report page down with no explanation |

## Namespace

`ksfraser\FrontAccounting\RepSalesGroups\`, per the project rule that
`ksf_FA_<Module>` becomes `ksfraser\FrontAccounting\<Module>\` with the
vendor segment lowercase. The classes are loaded by path, so the namespace
carries no runtime weight, but it keeps the module consistent with the rest
of the estate.

## Testing

| Piece | How it is tested |
|-------|------------------|
| `ReportSqlBuilder` | Unit tests assert on the SQL it generates, against a stubbed database that reports a chosen schema |
| `ReportPresenter` | Unit tests assert on the sections it produces |
| `ReportLayout` | Unit tests assert on the geometry, including that the table fits the page |
| The report page scripts | Not unit tested. They are FA page scripts whose output is a PDF; they are covered by the UAT plan |

`tests/bootstrap.php` stubs the FA `db_*` functions so the tests can control
what the database reports. `db_escape()` is defined to match real FA, which
returns the value already wrapped in single quotes.
