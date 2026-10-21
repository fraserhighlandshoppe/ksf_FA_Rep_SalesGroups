<?php
/*
 * ksf_FA_Rep_SalesGroups — Report 2 "Sales Group by Customer"
 * One table per customer (company): the customer name is the section title
 * and the table below it lists the branches with their sales group and every
 * contact associated with the branch.
 * REP_ID: _salesgroups_v2
 * PARAM_0: Customer (CUSTOMERS_NO_FILTER)
 * PARAM_1: Sales Group (SALES_GROUPS)
 * PARAM_2: Contact Action (CONTACT_ACTIONS)
 * PARAM_3: Comments (TEXTBOX)
 * PARAM_4: Destination (DESTINATION)
 */

$page_security = 'SA_CUSTBULKREP';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");

$customer_filter = isset($_POST['PARAM_0']) ? $_POST['PARAM_0'] : '';
$group_filter = isset($_POST['PARAM_1']) ? $_POST['PARAM_1'] : '';
$contact_action = isset($_POST['PARAM_2']) ? $_POST['PARAM_2'] : '';
$comments = isset($_POST['PARAM_3']) ? $_POST['PARAM_3'] : '';
$destination = isset($_POST['PARAM_4']) ? $_POST['PARAM_4'] : '';

if ($destination) {
    include_once($path_to_root . "/reporting/includes/excel_report.inc");
} else {
    include_once($path_to_root . "/reporting/includes/pdf_report.inc");
}

require_once('src/ReportSqlBuilder.php');
require_once('src/ReportPresenter.php');
require_once('src/ReportLayout.php');
use ksfraser\FrontAccounting\RepSalesGroups\ReportSqlBuilder;
use ksfraser\FrontAccounting\RepSalesGroups\ReportPresenter;
use ksfraser\FrontAccounting\RepSalesGroups\ReportLayout;

$builder = new ReportSqlBuilder(2, $group_filter !== '' ? $group_filter : null,
    $customer_filter !== '' ? $customer_filter : null,
    $contact_action !== '' ? $contact_action : null);
$sql = $builder->buildV2Sql();

$contact_columns = $builder->contactColumns();
$layout = new ReportLayout($contact_columns);
$cols = $layout->cols();
$headers = $layout->headers();
$aligns = $layout->aligns();
$last_col = $layout->lastCol();
$type_index = $layout->typeIndex();
$orientation = 'P';

$rep = new FrontReport(_('Sales Group by Customer'), "SalesGroupsDetail", user_pagesize(), 9, $orientation);
$rep->Font();

// A failed query would otherwise take the whole report page down with it.
$result = @db_query($sql);
if ($result === false) {
    $rep->Info(array(), $cols, $headers, $aligns);
    $rep->NewPage();
    $rep->NewLine();
    $rep->Font('bold');
    $rep->TextCol(0, $last_col, '--QUERY FAILED--');
    $rep->Font();
    $rep->NewLine();
    $rep->NewLine();
    $rep->TextCol(0, $last_col, $sql);
    $rep->End();
    exit;
}

$rows = array();
while ($row = db_fetch($result)) {
    $rows[] = $row;
}

$sections = ReportPresenter::byCustomer($rows, $contact_columns);

$contact_count = 0;
$with_contact = 0;
foreach ($sections as $section) {
    $contact_count += count($section['rows']);
    $with_contact += ReportPresenter::countWithContact($section['rows'], $contact_columns);
}

$cust_name = ($customer_filter !== '' && $customer_filter !== '0')
    ? get_customer_name($customer_filter) : _('All');
$group_name = ($group_filter !== '' && $group_filter !== '0')
    ? get_sales_group_name($group_filter) : _('All');

$params = array(
    0 => $comments,
    1 => array('text' => _('Customer'), 'from' => $cust_name, 'to' => ''),
    2 => array('text' => _('Sales Group'), 'from' => $group_name, 'to' => ''),
    3 => array('text' => _('Contact Action'), 'from' => $contact_action !== '' ? $contact_action : _('All'), 'to' => ''),
    4 => array('text' => _('Customers'), 'from' => (string) count($sections), 'to' => ''),
    5 => array('text' => _('Contacts'), 'from' => (string) $contact_count, 'to' => ''),
    6 => array('text' => _('With Contact'), 'from' => (string) $with_contact, 'to' => ''),
);

$rep->Info($params, $cols, $headers, $aligns);
$rep->NewPage();

if (count($sections) == 0) {
    $rep->NewLine();
    $rep->Font('bold');
    $rep->TextCol(0, $last_col, '--NO CUSTOMERS TO DISPLAY--');
    $rep->Font();
    $rep->NewLine();
} else {
    foreach ($sections as $section) {
        // Section title: the customer (company) name
        $rep->NewLine(2);
        $rep->Font('bold');
        $rep->TextCol(0, $last_col, $section['title']);
        $rep->Font();
        $rep->NewLine();

        // Column headings for the table under this customer
        $rep->Font('italic');
        for ($i = 0; $i < count($headers); $i++) {
            $rep->TextCol($i, $i + 1, $headers[$i]);
        }
        $rep->Font();
        $rep->NewLine();

        foreach ($section['rows'] as $entry) {
            $rep->NewLine();
            $rep->TextCol(0, 1, $entry['branch']);
            $rep->TextCol(1, 2, $entry['group']);

            // Every other contact column, leaving the Type cell to the end:
            // a multi line cell moves the row on, so it has to be drawn last.
            $types = $entry['types'];
            foreach ($contact_columns as $i => $column) {
                if ($column['alias'] === 'contact_type') {
                    continue;
                }
                $value = isset($entry['values'][$column['alias']]) ? $entry['values'][$column['alias']] : '';
                $rep->TextCol($i + 2, $i + 3, $value);
            }
            if ($type_index !== null && count($types) > 0) {
                if ($destination) {
                    // The Excel report class ignores multi line cells.
                    $rep->TextCol($type_index, $type_index + 1, implode(', ', $types));
                } else {
                    $rep->TextColLines($type_index, $type_index + 1, implode("\n", $types));
                }
            }
            $rep->NewLine(0, 1);
        }
    }
}

$rep->End();
