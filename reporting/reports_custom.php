<?php
/**
 * Register Sales Group reports with native FA reporting framework.
 */
global $reports, $dim;

// RC_CUSTOMER is 0, and addReportClass() treats a falsy id as "give me a new
// one", so ask for the id back and register the reports against it.
$sales_group_class = $reports->addReportClass(_('Sales Group'));

// Custom control handlers.
$reports->register_controls(function ($name, $type) {
    global $path_to_root;

    if ($type === 'SALES_GROUPS') {
        // Sales groups dropdown built from the native FA "groups" table.
        include_once($path_to_root . "/sales/includes/db/sales_groups_db.inc");
        include_once($path_to_root . "/includes/ui/ui_lists.inc");
        return sales_groups_list($name, null, true);
    }

    if ($type === 'CONTACT_ACTIONS') {
        // Which contact action (general, delivery, invoice, order) to report
        // on. Built from the actions actually present in the installation.
        include_once($path_to_root . "/includes/ui/ui_lists.inc");
        $labels = array(
            'general' => _('General'),
            'delivery' => _('Delivery'),
            'invoice' => _('Invoice'),
            'order' => _('Order'),
        );
        $items = array('' => _('All'));
        $res = @db_query("SELECT DISTINCT action FROM " . TB_PREF . "crm_contacts ORDER BY action");
        if ($res) {
            while ($row = db_fetch($res)) {
                $action = $row['action'];
                if ($action === null || $action === '') {
                    continue;
                }
                $items[$action] = isset($labels[$action]) ? $labels[$action] : ucfirst($action);
            }
        }
        return array_selector($name, null, $items, array('default' => ''));
    }

    if ($type === 'YES_NO_DEFAULT_YES') {
        // Yes/No selector that defaults to Yes when nothing was posted.
        include_once($path_to_root . "/includes/ui/ui_lists.inc");
        return array_selector($name, null, array('0' => _('No'), '1' => _('Yes')),
            array('default' => '1'));
    }

    return '';
});

$reports->addReport($sales_group_class, '_salesgroups_v1', _('Sales Groups by Group'),
    array(
        _('Sales Group') => 'SALES_GROUPS',
        _('Show Groups With No Customers') => 'YES_NO_DEFAULT_YES',
        _('Comments') => 'TEXTBOX',
        _('Destination') => 'DESTINATION'));

$reports->addReport($sales_group_class, '_salesgroups_v2', _('Sales Group by Customer'),
    array(
        _('Customer') => 'CUSTOMERS_NO_FILTER',
        _('Sales Group') => 'SALES_GROUPS',
        _('Contact Action') => 'CONTACT_ACTIONS',
        _('Comments') => 'TEXTBOX',
        _('Destination') => 'DESTINATION'));

// Temporary diagnostics report - remove once the contact lookup is confirmed.
$reports->addReport($sales_group_class, '_salesgroups_diag', _('ZZ Diagnostics'),
    array(
        _('Comments') => 'TEXTBOX',
        _('Destination') => 'DESTINATION'));
