# Test Plan — ksf_FA_Rep_SalesGroups

## Scope

This module produces two read only reports. There is no business logic that
mutates data, so the test effort is split between:

1. **Unit tests** for everything that can be isolated from FA — the SQL the
   builder generates, the sections the presenter produces, the geometry of
   the table.
2. **UAT** for the report page scripts themselves, whose output is a PDF or
   an Excel file and which can only be exercised inside FA.

## Test approach

| Level | Tool | Scope | Gate |
|-------|------|-------|------|
| Unit | PHPUnit 9.6 | `src/ReportSqlBuilder.php`, `src/ReportPresenter.php`, `src/ReportLayout.php` | 100% line coverage, no skipped tests |
| Static | `php -l` | every PHP file | no syntax errors |
| UAT | manual, in FA | the two reports end to end | the UAT plan in `UAT.md` |

The unit tests run against a stubbed FA: `tests/bootstrap.php` defines
`db_query()`, `db_fetch()` and `db_escape()` so the tests can decide what the
database reports. `db_escape()` is defined to match real FA, which returns
the value already wrapped in single quotes — getting this wrong would make
the SQL assertions pass against the wrong SQL.

The SQL builder probes the schema with `SHOW TABLES LIKE` and
`SHOW COLUMNS FROM`, so the stub has to answer those. `FaSchema::describe()`
sets up the schema a test wants; `FaSchema::failQueries()` simulates an
unreachable database.

## Running the suite

```bash
composer install
./vendor/bin/phpunit
./vendor/bin/phpunit --coverage-text
```

Composer is a development dependency only. The module itself has no runtime
dependency, so `composer install --no-dev` is enough to produce a usable
`vendor/` for the deployment copy, and the module works without one.

## Test cases

### ReportSqlBuilder

| Test | What it pins down |
|------|-------------------|
| `testNoGroupFilterMeansNoGroupPredicate` | An unselected group adds no predicate |
| `testEmptyFiltersAreTreatedAsNoFilter` | `''`, `'0'` and `ALL_TEXT` all mean "no filter" |
| `testSelectedGroupIsFiltered` | A selected group is filtered on |
| `testGroupFilterDrivesTheCustomerQueryFromGroups` | The group filter is applied to the driving table, with inner joins |
| `testWithoutGroupFilterTheCustomerQueryListsEverybody` | Without a group filter the customer query keeps its outer joins |
| `testCustomerFilterIsApplied` | The customer filter is applied |
| `testEmptyCustomerFilterIsNotApplied` | An empty customer filter is not applied |
| `testCrmContactsAreJoinedWhenTheTablesExist` | The CRM tables are joined when present |
| `testContactsAreNotRestrictedToTheGeneralAction` | Every action is reported, not only *general* |
| `testContactActionFilterIsAppliedInsideTheJoin` | The action filter belongs to the join, not the `WHERE` clause |
| `testNoContactActionFilterByDefault` | No action filter by default |
| `testFallsBackToBranchContactColumns` | An installation without the CRM tables uses the `cust_branch` columns |
| `testNoContactColumnsWhenTheSchemaHasNone` | An installation with neither has no contact columns, and the report still runs |
| `testTheWhereClauseListReportsTheActiveFilters` | The active filters are reported |
| `testTheWhereClauseListIsEmptyWithoutFilters` | ... and empty when there are none |
| `testCrmContactColumnsAreReported` | The contact columns are reported |
| `testBranchContactColumnsAreReported` | ... and so are the branch contact columns |
| `testAnUnreachableDatabaseDegradesToNoContactDetails` | A database that rejects every query does not take the report down |

### ReportPresenter

| Test | What it pins down |
|------|-------------------|
| `testGroupsBecomeOneSectionEach` | One section per sales group |
| `testGroupRowsCarryDebtorAndBranch` | A group section carries the debtor and the branch |
| `testEmptyGroupHasASectionButNoRows` | A group with no customers still has a section |
| `testEmptyGroupsCanBeSuppressed` | ... and can be suppressed |
| `testNoRowsMeansNoSections` | No rows, no sections |
| `testAGroupWithoutANameGetsAPlaceholderTitle` | A row without a group name still gets a section |
| `testCustomersBecomeOneSectionEach` | One section per customer |
| `testContactWithSeveralActionsIsOneRow` | A person with several actions is one row with all of them |
| `testDifferentPeopleOnTheSameBranchAreSeparateRows` | Two people on one branch are two rows |
| `testContactTypesAreOrderedByUse` | The types are ordered general, delivery, invoice, order |
| `testUnknownContactTypesSortAlphabetically` | Unknown types sort after the known ones, alphabetically |
| `testBranchLabelFallsBackToTheBranchName` | `branch_ref`, falling back to `br_name` |
| `testBranchLabelIsEmptyWhenThereIsNoBranch` | Neither, so the label is empty |
| `testACustomerWithoutANameGetsAPlaceholderTitle` | A row without a customer name still gets a section |
| `testCountsOnlyTheEntriesThatCarryContactDetails` | The contact coverage counter |

### ReportLayout

| Test | What it pins down |
|------|-------------------|
| `testBranchAndGroupAreAlwaysTheFirstTwoColumns` | The first two columns are always Branch and Sales Group |
| `testContactColumnsAreAppended` | The contact columns follow, with their own widths |
| `testTypeColumnIndexIsReported` | The report knows which column holds the types |
| `testThereIsNoTypeColumnWithoutOne` | ... and knows when there is none |
| `testTheContactColumnsAreKept` | The contact columns are available to the caller |
| `testTableFitsThePageWidth` | The table fits the printable width of an A4 portrait page |

## Regression history

| Symptom | Root cause | Test that now pins it down |
|---------|------------|------------------------------|
| "Sales Groups by Group" spun forever, no report | `FrontReport` has no `row()` method; the call was a fatal error, and the query returned rows because the query is driven from `groups` | Fixed in the report script; the rendering is now `TextCol()` |
| "Sales Group by Customer" produced no results | The group filter was applied to `g.id` on the right of a `LEFT JOIN`, which discarded every customer that had no branch in that group | `testGroupFilterDrivesTheCustomerQueryFromGroups` |
| The contact columns were empty | The module looked for the CRM tables without checking they existed, and a query against a missing table is fatal in FA | `testFallsBackToBranchContactColumns`, `testNoContactColumnsWhenTheSchemaHasNone` |
| The same contact appeared three times, once per action | One row was produced per contact record rather than per person | `testContactWithSeveralActionsIsOneRow` |
| The Email column was clipped by the page margin | The column widths added up to 535pt against a 525pt printable width | `testTableFitsThePageWidth` |

## Out of scope

* Automated testing of the PDF content. The report page scripts are FA page
  scripts; their output is verified by the UAT plan.
* Performance testing. Both queries are simple joins over native FA tables.
* Testing against a live FA instance from the unit suite — that is what UAT
  is for.
