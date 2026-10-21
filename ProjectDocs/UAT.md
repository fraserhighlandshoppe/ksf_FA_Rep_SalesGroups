# UAT Plan — ksf_FA_Rep_SalesGroups

The unit suite covers the SQL, the sections and the table geometry. What it
cannot cover is the report page scripts themselves: they are FA page scripts
whose output is a PDF or an Excel file, driven by the FA Report Center.

Each step below is run in FA, as a user holding the `SA_CUSTBULKREP` access
area.

## Preconditions

| # | Precondition | How to check |
|---|--------------|--------------|
| P1 | The module is installed and activated for the company | Setup → Extensions → *Activated for* your company shows the module checked |
| P2 | The user holds *Sales & Customer Reports → Customer bulk listing* | Setup → Security → Access Levels, and the role shows the area |
| P3 | The reports are listed | Reporting → Report Center shows a *Sales Group* class with *Sales Groups by Group* and *Sales Group by Customer* |
| P4 | The *ZZ Diagnostics* report is **not** listed | Reporting → Report Center → *Sales Group* shows only the two reports |

## Test cases

### UAT-01 — The report class and the two reports

| Step | Action | Expected |
|------|--------|----------|
| 1 | Open Reporting → Report Center | A *Sales Group* class is listed |
| 2 | Click *Sales Groups by Group* | The form appears with *Sales Group*, *Show Groups With No Customers*, *Comments* and *Destination* |
| 3 | Click *Sales Group by Customer* | The form appears with *Customer*, *Sales Group*, *Contact Action*, *Comments* and *Destination* |
| 4 | Check the standard *Customer* class | It holds the standard customer reports only — the sales group reports are not mixed in |

### UAT-02 — Sales Groups by Group

| Step | Action | Expected |
|------|--------|----------|
| 1 | *Sales Group* = *All*, *Show Groups With No Customers* = Yes, Display | One table per sales group, the group name as the section title, and the debtor and branch of each customer |
| 2 | *Sales Group* = a group that has customers | Only that group is listed |
| 3 | *Sales Group* = a group with no customers, *Show Groups* = Yes | The section is listed with `--NO CUSTOMERS IN THIS GROUP--` |
| 4 | *Sales Group* = a group with no customers, *Show Groups* = No | The group is not listed |
| 5 | A filter that matches nothing at all | The report shows `--NO GROUPS ASSIGNED TO CUSTOMERS--` |
| 6 | Add text to *Comments* | The comment appears in the report header |
| 7 | *Destination* = Excel | An Excel file is produced, with the same content |

### UAT-03 — Sales Group by Customer

| Step | Action | Expected |
|------|--------|----------|
| 1 | All filters at their default, Display | One table per customer, the customer name as the section title, and for each branch its sales group and its contacts |
| 2 | Read the report header | *Customers*, *Contacts* and *With Contact* are stated |
| 3 | *Customer* = a customer with several branches | Only that customer's table is listed |
| 4 | *Sales Group* = a group | Only the branches in that group are listed |
| 5 | *Contact Action* = *General* | Only the general contacts are listed; branches with no general contact still appear, with empty contact cells |
| 6 | *Contact Action* = *All* | Contacts of every action are listed |
| 7 | A branch with contacts for several actions | The contact appears **once**, with the types stacked one below the other in the Type cell |
| 8 | A filter combination that matches nothing | The report shows `--NO CUSTOMERS TO DISPLAY--` |

### UAT-04 — Access control

| Step | Action | Expected |
|------|--------|----------|
| 1 | Remove *Customer bulk listing* from the user's role | The reports are not offered, or the report is refused with "The security settings on your account do not permit you to print this report" |
| 2 | Restore the access area | The reports run again |
| 3 | Open Setup → Security → Access Levels | The screen renders. It lists the areas of the core areas; the module adds none, and must not break the screen |

### UAT-05 — Install and activate

| Step | Action | Expected |
|------|--------|----------|
| 1 | Install the module from the local package | The install completes |
| 2 | Activate it for the company | Activation completes and reports success. No schema is created, and no SQL script is run |
| 3 | Check the module's version in the Extensions list | The version is shown, and the module can be re-activated without an "incompatible with current application version" error |
| 4 | Deactivate and re-activate | Both succeed |

### UAT-06 — Failure handling

| Step | Action | Expected |
|------|--------|----------|
| 1 | Run a report against a deliberately broken query | The report shows `--QUERY FAILED--` and the SQL, rather than spinning or showing a blank page |

### UAT-07 — A different installation shape

Run the by customer report in an installation whose `crm_contacts` table is
empty, and in one where the table does not exist.

| Step | Action | Expected |
|------|--------|----------|
| 1 | `crm_contacts` empty | The report still lists customers, branches and groups; the contact cells are empty |
| 2 | `crm_contacts` missing | The report still runs. If the installation's `cust_branch` carries contact columns they are used; otherwise there are no contact columns, and the report says so in the header |

## Sign-off

| Test | Result | Tester | Date |
|------|--------|--------|------|
| UAT-01 | | | |
| UAT-02 | | | |
| UAT-03 | | | |
| UAT-04 | | | |
| UAT-05 | | | |
| UAT-06 | | | |
| UAT-07 | | | |
