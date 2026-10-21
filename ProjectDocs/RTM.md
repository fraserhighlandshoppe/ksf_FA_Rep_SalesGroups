# Requirements Traceability Matrix (RTM) — ksf_FA_Rep_SalesGroups

## Matrix Legend

- **FR**: Functional Requirement
- **NFR**: Non-Functional Requirement
- **UC**: Use Case
- **TC**: Test Case (unit)

---

## RTM: Requirement -> Use Case -> Code -> Test

| Req ID | Requirement | Use Case | Code file(s) | Test |
|---------|-------------|----------|--------------|------|
| **FR-01.01** | Two reports in a *Sales Group* report class | UC-01, UC-02 | `reporting/reports_custom.php` | manual (UAT-01) |
| **FR-01.02** | Empty / zero filter means no filter | UC-01, UC-02 | `src/ReportSqlBuilder.php` | `ReportSqlBuilderTest::testEmptyFiltersAreTreatedAsNoFilter`, `::testNoGroupFilterMeansNoGroupPredicate`, `::testEmptyCustomerFilterIsNotApplied` |
| **FR-01.03** | Group filter drives the customer query from `groups` | UC-02 | `src/ReportSqlBuilder.php` | `ReportSqlBuilderTest::testGroupFilterDrivesTheCustomerQueryFromGroups` |
| **FR-01.04** | Without a group filter every customer is listed | UC-02 | `src/ReportSqlBuilder.php` | `ReportSqlBuilderTest::testWithoutGroupFilterTheCustomerQueryListsEverybody` |
| **FR-01.05** | Customer filter on `debtors_master.debtor_no` | UC-02 | `src/ReportSqlBuilder.php` | `ReportSqlBuilderTest::testCustomerFilterIsApplied` |
| **FR-02.01** | One section per group / per customer | UC-01, UC-02 | `src/ReportPresenter.php` | `ReportPresenterTest::testGroupsBecomeOneSectionEach`, `::testCustomersBecomeOneSectionEach` |
| **FR-02.02** | Group section lists debtor and branch | UC-01 | `src/ReportPresenter.php` | `ReportPresenterTest::testGroupRowsCarryDebtorAndBranch` |
| **FR-02.03** | Empty groups included or suppressed | UC-01 | `src/ReportPresenter.php`, `reporting/rep_salesgroups_v1.php` | `ReportPresenterTest::testEmptyGroupHasASectionButNoRows`, `::testEmptyGroupsCanBeSuppressed` |
| **FR-02.04** | One section per customer | UC-02 | `src/ReportPresenter.php` | `ReportPresenterTest::testCustomersBecomeOneSectionEach` |
| **FR-02.05** | A contact with several actions is one row, types collected | UC-02 | `src/ReportPresenter.php` | `ReportPresenterTest::testContactWithSeveralActionsIsOneRow`, `::testContactTypesAreOrderedByUse`, `::testUnknownContactTypesSortAlphabetically` |
| **FR-02.06** | Branch label falls back to the branch name | UC-02 | `src/ReportPresenter.php` | `ReportPresenterTest::testBranchLabelFallsBackToTheBranchName`, `::testBranchLabelIsEmptyWhenThereIsNoBranch` |
| **FR-02.07** | Header states the customer and contact counts | UC-02 | `reporting/rep_salesgroups_v2.php` | manual (UAT-03) |
| **FR-02.08** | Column layout, Type cell narrower | UC-02 | `src/ReportLayout.php` | `ReportLayoutTest::testBranchAndGroupAreAlwaysTheFirstTwoColumns`, `::testContactColumnsAreAppended`, `::testTypeColumnIndexIsReported`, `::testThereIsNoTypeColumnWithoutOne` |
| **FR-02.09** | The table fits the printable width | UC-02 | `src/ReportLayout.php` | `ReportLayoutTest::testTableFitsThePageWidth` |
| **FR-03.01** | Contacts come from the CRM tables | UC-02 | `src/ReportSqlBuilder.php` | `ReportSqlBuilderTest::testCrmContactsAreJoinedWhenTheTablesExist`, `::testCrmContactColumnsAreReported` |
| **FR-03.02** | Every action is listed, not only *general* | UC-02 | `src/ReportSqlBuilder.php` | `ReportSqlBuilderTest::testContactsAreNotRestrictedToTheGeneralAction` |
| **FR-03.03** | Contact action filter, applied inside the join | UC-02 | `src/ReportSqlBuilder.php` | `ReportSqlBuilderTest::testContactActionFilterIsAppliedInsideTheJoin`, `::testNoContactActionFilterByDefault` |
| **FR-03.04** | Fallback to the `cust_branch` contact columns | UC-02 | `src/ReportSqlBuilder.php` | `ReportSqlBuilderTest::testFallsBackToBranchContactColumns`, `::testBranchContactColumnsAreReported` |
| **FR-03.05** | Neither available: report still runs | UC-02 | `src/ReportSqlBuilder.php` | `ReportSqlBuilderTest::testNoContactColumnsWhenTheSchemaHasNone`, `::testAnUnreachableDatabaseDegradesToNoContactDetails` |
| **FR-04.01** | Reuse the native `SA_CUSTBULKREP` area | UC-03 | `hooks.php`, `reporting/rep_salesgroups_v1.php`, `reporting/rep_salesgroups_v2.php` | manual (UAT-04) |
| **FR-04.02** | No schema, no Composer runtime dependency | UC-03 | `sql/install.sql`, `composer.json` | manual (UAT-05) |
| **FR-04.03** | Version keeps the `2.4.x` prefix | UC-03 | `_init/config`, `hooks.php` | manual (UAT-05) |
| **NFR-01.01** | Runs on PHP 7.3 or later | — | `composer.json` | `composer.json` platform pin, CI gate |
| **NFR-01.02** | 100% line coverage of `src/` | — | `tests/`, `phpunit.xml` | `phpunit --coverage-text` |
| **NFR-01.03** | `ksfraser\FrontAccounting\RepSalesGroups\` | — | `src/*.php` | `php -l`, PSR-4 autoload check |
| **NFR-01.04** | A failed query prints the SQL | UC-01, UC-02 | `reporting/rep_salesgroups_v1.php`, `reporting/rep_salesgroups_v2.php` | manual (UAT-06) |

## Test coverage summary

| Class | Tests | Line coverage |
|-------|-------|----------------|
| `ReportSqlBuilder` | 16 | 100% |
| `ReportPresenter` | 17 | 100% |
| `ReportLayout` | 8 | 100% |
| **Total** | **41** | **100%** |
