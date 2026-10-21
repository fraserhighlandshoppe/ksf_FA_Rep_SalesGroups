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
use ksfraser\FrontAccounting\RepSalesGroups\ReportSqlBuilder;

/**
 * Order contact types the way they are used: general, delivery, invoice,
 * order, then anything else alphabetically.
 */
function sales_group_contact_type_order($a, $b)
{
    $order = array('general' => 0, 'delivery' => 1, 'invoice' => 2, 'order' => 3);
    $pa = isset($order[strtolower($a)]) ? $order[strtolower($a)] : 99;
    $pb = isset($order[strtolower($b)]) ? $order[strtolower($b)] : 99;
    if ($pa === $pb) {
        return strcmp($a, $b);
    }
    return ($pa < $pb) ? -1 : 1;
}

$builder = new ReportSqlBuilder(2, $group_filter !== '' ? $group_filter : null,
    $customer_filter !== '' ? $customer_filter : null,
    $contact_action !== '' ? $contact_action : null);
$sql = $builder->buildV2Sql();

// Column layout: Branch | Sales Group | <whatever contact columns exist>
$contact_columns = $builder->contactColumns();
$headers = array(_('Branch'), _('Sales Group'));
$aligns = array('left', 'left');
$cols = array(0);
$x = 0;
foreach (array(75, 90) as $w) {
    $x += $w;
    $cols[] = $x;
}
foreach ($contact_columns as $col) {
    $headers[] = $col[0];
    $aligns[] = 'left';
    $x += ($col[0] === 'Type') ? 70 : 100;
    $cols[] = $x;
}
$last_col = count($cols) - 1;

// Column index of the Type cell, whose content is the list of contact types.
$type_index = null;
foreach ($contact_columns as $i => $col) {
    if ($col[1] === 'contact_type') {
        $type_index = $i + 2;
    }
}

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

// Bucket the rows by customer, then by branch + person, so a contact that
// is associated with the branch for several actions is listed once with all
// of its types.
$customers = array();
$customer_order = array();
$contact_count = 0;
while ($row = db_fetch($result)) {
    $cname = $row['customer_name'] !== null ? $row['customer_name'] : _('(no customer)');
    if (!isset($customers[$cname])) {
        $customers[$cname] = array();
        $customer_order[] = $cname;
    }
    $branch = $row['branch_ref'] !== null && $row['branch_ref'] !== ''
        ? $row['branch_ref'] : ($row['br_name'] !== null ? $row['br_name'] : '');
    $person = isset($row['contact_person_id']) ? (string) $row['contact_person_id'] : '';
    $key = $branch . "\x00" . $person;

    if (!isset($customers[$cname][$key])) {
        $customers[$cname][$key] = array(
            'branch' => $branch,
            'group' => $row['group_name'] !== null ? $row['group_name'] : '',
            'types' => array(),
        );
        foreach ($contact_columns as $col) {
            $customers[$cname][$key][$col[1]] = isset($row[$col[1]]) ? $row[$col[1]] : null;
        }
        $contact_count++;
    }
    $type = isset($row['contact_type']) ? $row['contact_type'] : null;
    if ($type !== null && $type !== '') {
        $type = ucfirst($type); // general -> General
    }
    if ($type !== null && $type !== '' && !in_array($type, $customers[$cname][$key]['types'], true)) {
        $customers[$cname][$key]['types'][] = $type;
    }
}

// How many entries actually carry contact details.
$with_contact = 0;
foreach ($customer_order as $cname) {
    foreach ($customers[$cname] as $entry) {
        foreach ($contact_columns as $col) {
            if (!empty($entry[$col[1]])) {
                $with_contact++;
                break;
            }
        }
    }
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
    4 => array('text' => _('Customers'), 'from' => (string) count($customer_order), 'to' => ''),
    5 => array('text' => _('Contacts'), 'from' => (string) $contact_count, 'to' => ''),
    6 => array('text' => _('With Contact'), 'from' => (string) $with_contact, 'to' => ''),
);

$rep->Info($params, $cols, $headers, $aligns);
$rep->NewPage();

if (count($customer_order) == 0) {
    $rep->NewLine();
    $rep->Font('bold');
    $rep->TextCol(0, $last_col, '--NO CUSTOMERS TO DISPLAY--');
    $rep->Font();
    $rep->NewLine();
} else {
    foreach ($customer_order as $cname) {
        // Section title: the customer (company) name
        $rep->NewLine(2);
        $rep->Font('bold');
        $rep->TextCol(0, $last_col, $cname);
        $rep->Font();
        $rep->NewLine();

        // Column headings for the table under this customer
        $rep->Font('italic');
        for ($i = 0; $i < count($headers); $i++) {
            $rep->TextCol($i, $i + 1, $headers[$i]);
        }
        $rep->Font();
        $rep->NewLine();

        foreach ($customers[$cname] as $entry) {
            $rep->NewLine();
            $rep->TextCol(0, 1, $entry['branch']);
            $rep->TextCol(1, 2, $entry['group']);

            // Every other contact column, leaving the Type cell to the end:
            // a multi line cell moves the row on, so it has to be drawn last.
            $types = $entry['types'];
            usort($types, 'sales_group_contact_type_order');
            $type_text = implode("\n", $types);
            for ($i = 0; $i < count($contact_columns); $i++) {
                $alias = $contact_columns[$i][1];
                if ($alias === 'contact_type') {
                    continue;
                }
                $value = isset($entry[$alias]) ? $entry[$alias] : null;
                $rep->TextCol($i + 2, $i + 3, $value !== null ? $value : '');
            }
            if ($type_index !== null && $type_text !== '') {
                if ($destination) {
                    // The Excel report class ignores multi line cells.
                    $rep->TextCol($type_index, $type_index + 1, implode(', ', $types));
                } else {
                    $rep->TextColLines($type_index, $type_index + 1, $type_text);
                }
            }
            $rep->NewLine(0, 1);
        }
    }
}

$rep->End();
