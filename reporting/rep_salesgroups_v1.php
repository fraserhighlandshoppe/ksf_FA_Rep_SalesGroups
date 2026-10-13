<?php
/*
 * ksf_FA_Rep_SalesGroups — Version 1 Report
 * Group header + 2-column table (Customer / Debtor | Branches)
 */
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

page_header(_('Sales Group Report — Groups & Branches'), '', false);

// Filter defaults
$group_filter = isset($_POST['group_filter']) ? $_POST['group_filter'] : ALL_TEXT;

require_once('src/ReportSqlBuilder.php');
use Ksfraser\Frontaccounting\SalesGroups\ReportSqlBuilder;

$builder = new ReportSqlBuilder(1, $group_filter);
$sql = $builder->buildV1Sql();

start_table();
echo "<h3>" . _('Sales Group — Version 1') . "</h3>";
echo "<table border='1' cellpadding='2' cellspacing='0'>";
echo "<tr><th>" . _('Sales Group') . "</th><th>" . _('Debtor / Customer') . "</th><th>" . _('Branch') . "</th></tr>";

// Note: native db_query() usage; replace table/column names with actual schema
$result = db_query($sql, "No data returned");
while ($row = db_fetch($result)) {
    echo "<tr>"
       . "<td>" . $row['group_name'] . "</td>"
       . "<td>" . $row['name'] . "</td>"
       . "<td>" . $row['branch_ref'] . "</td>"
       . "</tr>";
}
echo "</table>";
end_table();
end_page();
