<?php
/*
 * ksf_FA_Rep_SalesGroups — Report 1 "Sales Groups by Group"
 * One table per sales group: the group name is the section title and the
 * table below it lists the debtor / branch pairs assigned to that group.
 * REP_ID: _salesgroups_v1
 * PARAM_0: Sales Group (SALES_GROUPS)
 * PARAM_1: Show Groups With No Customers (YES_NO_DEFAULT_YES)
 * PARAM_2: Comments (TEXTBOX)
 * PARAM_3: Destination (DESTINATION)
 */

$page_security = 'SA_CUSTBULKREP';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");

$group_filter = isset($_POST['PARAM_0']) ? $_POST['PARAM_0'] : '';
$show_empty = isset($_POST['PARAM_1']) ? $_POST['PARAM_1'] : '1';
$comments = isset($_POST['PARAM_2']) ? $_POST['PARAM_2'] : '';
$destination = isset($_POST['PARAM_3']) ? $_POST['PARAM_3'] : '';

if ($destination) {
    include_once($path_to_root . "/reporting/includes/excel_report.inc");
} else {
    include_once($path_to_root . "/reporting/includes/pdf_report.inc");
}

require_once('src/ReportSqlBuilder.php');
use ksfraser\FrontAccounting\RepSalesGroups\ReportSqlBuilder;

$builder = new ReportSqlBuilder(1, $group_filter !== '' ? $group_filter : null);
$sql = $builder->buildV1Sql();

$cols = array(0, 150, 300, 450);
$headers = array(_('Sales Group'), _('Debtor / Customer'), _('Branch'));
$aligns = array('left', 'left', 'left');
$orientation = 'P';

$result = @db_query($sql);
if ($result === false) {
    // A failed query would otherwise take the whole report page down with it.
    $rep = new FrontReport(_('Sales Groups by Group'), "SalesGroupsBranches", user_pagesize(), 9, $orientation);
    $rep->Font();
    $rep->Info(array(), $cols, $headers, $aligns);
    $rep->NewPage();
    $rep->NewLine();
    $rep->Font('bold');
    $rep->TextCol(0, 3, '--QUERY FAILED--');
    $rep->Font();
    $rep->NewLine();
    $rep->NewLine();
    $rep->TextCol(0, 3, $sql);
    $rep->End();
    exit;
}

// Bucket the rows by sales group, keeping the order the query returned.
$groups = array();
$group_order = array();
while ($row = db_fetch($result)) {
    $gname = $row['group_name'] !== null ? $row['group_name'] : _('(no group)');
    if (!isset($groups[$gname])) {
        $groups[$gname] = array();
        $group_order[] = $gname;
    }
    if (!empty($row['debtor_no'])) {
        $groups[$gname][] = $row;
    }
}

// Groups with no customers are kept only when the filter asks for them.
$shown = array();
foreach ($group_order as $gname) {
    if (count($groups[$gname]) > 0 || $show_empty) {
        $shown[] = $gname;
    }
}

$group_name = ($group_filter !== '' && $group_filter !== '0')
    ? get_sales_group_name($group_filter) : _('All');

$params = array(
    0 => $comments,
    1 => array('text' => _('Sales Group'), 'from' => $group_name, 'to' => ''),
    2 => array('text' => _('Groups'), 'from' => (string) count($shown), 'to' => ''),
);

$rep = new FrontReport(_('Sales Groups by Group'), "SalesGroupsBranches", user_pagesize(), 9, $orientation);
$rep->Font();
$rep->Info($params, $cols, $headers, $aligns);
$rep->NewPage();

if (count($shown) == 0) {
    $rep->NewLine();
    $rep->Font('bold');
    $rep->TextCol(0, 3, '--NO GROUPS ASSIGNED TO CUSTOMERS--');
    $rep->Font();
    $rep->NewLine();
} else {
    foreach ($shown as $gname) {
        // Section title: the sales group name
        $rep->NewLine(2);
        $rep->Font('bold');
        $rep->TextCol(0, 3, $gname);
        $rep->Font();
        $rep->NewLine();

        // Column headings for the table under this group
        $rep->Font('italic');
        $rep->TextCol(1, 2, _('Debtor / Customer'));
        $rep->TextCol(2, 3, _('Branch'));
        $rep->Font();
        $rep->NewLine();

        if (count($groups[$gname]) == 0) {
            $rep->NewLine();
            $rep->TextCol(1, 3, '--NO CUSTOMERS IN THIS GROUP--');
            $rep->NewLine(0, 1);
            continue;
        }

        foreach ($groups[$gname] as $row) {
            $rep->NewLine();
            $rep->TextCol(1, 2, $row['name'] !== null ? $row['name'] : '');
            $rep->TextCol(2, 3, $row['branch_ref'] !== null ? $row['branch_ref'] : '');
            $rep->NewLine(0, 1);
        }
    }
}

$rep->End();
