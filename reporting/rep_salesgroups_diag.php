<?php
/*
 * ksf_FA_Rep_SalesGroups — Diagnostics (temporary)
 * Dumps database facts for the sales group / branch contact lookup.
 * REP_ID: _salesgroups_diag
 * PARAM_0: Comments (TEXTBOX)
 * PARAM_1: Destination (DESTINATION)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
while (ob_get_level() > 0) {
    ob_end_flush();
}

$page_security = 'SA_CUSTBULKREP';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e !== null && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        echo "\n!!! FATAL: ", $e['message'], " @ ", $e['file'], ":", $e['line'], "\n";
    }
    flush();
});

function d_out($line)
{
    echo $line, "\n";
    flush();
}

function d_query($sql, $label = null)
{
    $res = @db_query($sql);
    if ($res === false || $res === null) {
        d_out("  QUERY FAILED: " . $sql);
        return false;
    }
    $out = array();
    while ($row = db_fetch($res)) {
        $out[] = implode(' | ', $row);
    }
    if ($label !== null) {
        d_out($label . ': ' . (count($out) ? implode(' ;; ', $out) : '(none)'));
    }
    return $out;
}

d_out("=== SHOW TABLES LIKE crm ===");
d_query("SHOW TABLES LIKE '" . TB_PREF . "crm%'", 'crm tables');

d_out("");
d_out("=== SHOW COLUMNS FROM cust_branch ===");
d_query("SHOW COLUMNS FROM " . TB_PREF . "cust_branch", 'cust_branch columns');

d_out("");
d_out("=== SHOW COLUMNS FROM debtors_master ===");
d_query("SHOW COLUMNS FROM " . TB_PREF . "debtors_master", 'debtors_master columns');

d_out("");
d_out("=== COUNTS ===");
foreach (array('debtors_master', 'cust_branch', 'groups', 'crm_contacts', 'crm_persons') as $t) {
    d_query("SELECT COUNT(*) FROM " . TB_PREF . $t, 'count ' . $t);
}

d_out("");
d_out("=== crm_contacts type/action ===");
d_query("SELECT type, action, COUNT(*) FROM " . TB_PREF . "crm_contacts GROUP BY type, action", 'breakdown');

d_out("");
d_out("=== sample crm_contacts + person ===");
d_query("SELECT c.id, c.type, c.action, c.entity_id, c.person_id, p.name, p.phone, p.email"
    . " FROM " . TB_PREF . "crm_contacts c"
    . " LEFT JOIN " . TB_PREF . "crm_persons p ON p.id = c.person_id LIMIT 10", 'sample');

d_out("");
d_out("=== sample cust_branch ===");
d_query("SELECT branch_code, debtor_no, br_name, branch_ref, group_no FROM " . TB_PREF . "cust_branch LIMIT 10", 'sample');

d_out("");
d_out("=== branches with a group assigned ===");
d_query("SELECT branch_code, debtor_no, branch_ref, group_no FROM " . TB_PREF . "cust_branch"
    . " WHERE group_no IS NOT NULL AND group_no <> 0 LIMIT 20", 'assigned');

d_out("");
d_out("=== report query test (customer driven, no crm) ===");
$sql = "SELECT d.debtor_no, d.name AS customer_name, b.branch_ref, b.br_name, g.description AS group_name"
    . " FROM " . TB_PREF . "debtors_master d"
    . " LEFT JOIN " . TB_PREF . "cust_branch b ON b.debtor_no = d.debtor_no"
    . " LEFT JOIN " . TB_PREF . "groups g ON g.id = b.group_no"
    . " ORDER BY d.name, g.description, b.branch_ref";
d_out($sql);
$res = @db_query($sql);
$n = 0;
if ($res) {
    while ($row = db_fetch($res)) {
        $n++;
    }
}
d_out("  rows = " . $n);

d_out("");
d_out("=== report query test (with crm joins) ===");
$sql2 = "SELECT d.debtor_no, d.name AS customer_name, b.branch_ref,"
    . " p.name AS contact_name, c.action AS contact_type, p.phone AS contact_phone"
    . " FROM " . TB_PREF . "debtors_master d"
    . " LEFT JOIN " . TB_PREF . "cust_branch b ON b.debtor_no = d.debtor_no"
    . " LEFT JOIN " . TB_PREF . "crm_contacts c ON c.entity_id = b.branch_code AND c.type = 'cust_branch'"
    . " LEFT JOIN " . TB_PREF . "crm_persons p ON p.id = c.person_id"
    . " LIMIT 20";
d_out($sql2);
$res = @db_query($sql2);
if ($res) {
    $i = 0;
    while ($row = db_fetch($res)) {
        d_out("  " . implode(' | ', $row));
        $i++;
    }
    d_out("  (fetched " . $i . " rows)");
} else {
    d_out("  QUERY FAILED");
}

d_out("");
d_out("=== END OF DIAGNOSTICS ===");
