# Use Cases — ksf_FA_Rep_SalesGroups

## Actors

| Actor | Description |
|-------|-------------|
| **Finance / Sales manager** | Runs the reports to see which branches sit in which sales group, and who to contact at a branch |
| **FA Administrator** | Installs and activates the module, grants the `SA_CUSTBULKREP` access area to the roles that need it |

## UC-01 — List the branches in a sales group

| | |
|---|---|
| **Actor** | Finance / Sales manager |
| **Goal** | See which customers and branches are assigned to a sales group |
| **Report** | Sales Groups by Group (`_salesgroups_v1`) |
| **Trigger** | Reporting → Report Center → *Sales Group* → *Sales Groups by Group* → Display |

**Preconditions**

* The module is installed and activated.
* The user holds the `SA_CUSTBULKREP` access area.

**Flow**

1. The user picks a sales group from the dropdown, or leaves it on *All*.
2. The user picks whether groups with no customers are shown.
3. The user may add comments, and picks PDF or Excel.
4. The report is produced: one table per sales group, the group name as the
   section title, and the debtor and branch of every customer in it.

**Postconditions**

* Nothing is written to the database.

**Exceptions**

| Situation | Result |
|-----------|--------|
| The selected group has no customers | The section shows `--NO CUSTOMERS IN THIS GROUP--`, or is suppressed if empty groups are filtered out |
| No group has any customer | The report shows `--NO GROUPS ASSIGNED TO CUSTOMERS--` |
| The query fails | The report shows `--QUERY FAILED--` and the SQL |

---

## UC-02 — List a customer's branches, groups and contacts

| | |
|---|---|
| **Actor** | Finance / Sales manager |
| **Goal** | See every branch of a customer, the sales group of each branch, and who to contact at that branch |
| **Report** | Sales Group by Customer (`_salesgroups_v2`) |
| **Trigger** | Reporting → Report Center → *Sales Group* → *Sales Group by Customer* → Display |

**Preconditions**

* The module is installed and activated.
* The user holds the `SA_CUSTBULKREP` access area.

**Flow**

1. The user picks a customer (or *No Customer Filter*), a sales group (or
   *All*) and a contact action (or *All*).
2. The user may add comments, and picks PDF or Excel.
3. The report is produced: one table per customer, the customer name as the
   section title, and for each branch its sales group and its contacts.

**Postconditions**

* Nothing is written to the database.

**Exceptions**

| Situation | Result |
|-----------|--------|
| Nothing matches the filters | The report shows `--NO CUSTOMERS TO DISPLAY--` |
| A branch has no contact for the selected action | The branch is still listed, with empty contact columns |
| The installation has no CRM tables | The report has no contact columns, and still lists customers, branches and groups |
| The query fails | The report shows `--QUERY FAILED--` and the SQL |

---

## UC-03 — Install and activate the module

| | |
|---|---|
| **Actor** | FA Administrator |
| **Goal** | Make the reports available to the users who need them |
| **Trigger** | Setup → Extensions → Install / Activate |

**Flow**

1. The administrator installs the module from the local package.
2. The module is activated for the company. Activation creates no tables and
   runs no schema script.
3. The administrator grants `SA_CUSTBULKREP` — *Sales & Customer Reports →
   Customer bulk listing* — to the roles that should see the reports.

**Postconditions**

* The two reports appear in the Report Center under the *Sales Group* class.

**Exceptions**

| Situation | Result |
|-----------|--------|
| The version does not carry the `2.4.x` prefix | FA refuses to activate the module: "incompatible with current application version" |

---

## UC-04 — Diagnose an installation with no branch contacts

| | |
|---|---|
| **Actor** | Developer |
| **Goal** | Establish where the branch contact details live in a given installation |
| **Trigger** | Reporting → Report Center → *Sales Group* → *ZZ Diagnostics* |

> The diagnostics report is not registered in the report menu since v1.0.0.
> The file remains in the repository (`reporting/rep_salesgroups_diag.php`) and
> can be run by pointing a report request at `REP_ID=_salesgroups_diag`.

**Flow**

1. The report prints the row counts of the tables involved, the `crm_contacts`
   type and action breakdown, sample rows, the generated SQL, and the number
   of rows that carry a contact.
2. The developer compares that with what the module expects:
   `crm_contacts.type` = `cust_branch` with `entity_id` = `cust_branch.branch_code`.

**Postconditions**

* Nothing is written to the database.
