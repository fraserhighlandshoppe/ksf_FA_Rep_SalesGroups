<?php
/*
 * ksf_FA_Rep_SalesGroups — Version 2 Report
 * 3-column table: Customer | Branch | Group
 * Filters: Group (DDL) + Customer (DDL, default ALL)
 */
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

page_header(_('Sales Group Report — Full Detail'), '', false);

$group_filter = isset($_POST['group_filter']) ? $_POST['group_filter'] : ALL_TEXT;
$customer_filter = isset($_POST['customer_filter']) ? $_POST['customer_filter'] : ALL_TEXT;

require_once('src/ReportSqlBuilder.php');
use Ksfraser\Frontaccounting\SalesGroups\ReportSqlBuilder;

$builder = new ReportSqlBuilder(2, $group_filter, $customer_filter);
$sql = $builder->buildV2Sql();

start_table();
echo "<h3>" . _('Sales Group — Version 2 (Customer Detail)') . "</h3>";
echo "<table border='1' cellpadding='2' cellspacing='0'>";
echo "<tr><th>" . _('Customer') . "</th><th>" . _('Branch') . "</th><th>" . _('Sales Group') . "</th></tr>";

$result = db_query($sql, "No data returned");
while ($row = db_fetch($result)) {
    echo "<tr>"
       . "<td>" . $row['customer_name'] . "</td>"
       . "<td>" . $row['branch_ref'] . "</td>"
       . "<td>" . $row['group_name'] . "</td>"
       . "</tr>";
}
echo "</table>";
end_table();
end_page();
