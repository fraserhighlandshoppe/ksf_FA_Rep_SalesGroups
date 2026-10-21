<?php
/**
 * Bootstrap for the ksf_FA_Rep_SalesGroups unit tests.
 *
 * The module has no runtime Composer dependencies, so the autoloader is only
 * needed for the test suite. FA's db_* functions are stubbed here rather
 * than taken from famock: the SQL builder probes the schema with SHOW
 * TABLES / SHOW COLUMNS, so the tests need to control what the database
 * reports. Everything else in the FA surface comes from famock.
 */

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    fwrite(STDERR, "Composer autoloader not found. Run 'composer install' first.\n");
    exit(1);
}
require_once $autoload;

/*
 * Real FA db_escape() returns the value already wrapped in single quotes
 * (db_escape('hi') -> 'hi'). Define ours first so famock's
 * function_exists() guard skips its own, less faithful, version.
 */
if (!function_exists('db_escape')) {
    function db_escape($value = '')
    {
        return "'" . addslashes((string)$value) . "'";
    }
}

/** ALL_TEXT is the value FA's "all" dropdown options submit. */
if (!defined('ALL_TEXT')) {
    define('ALL_TEXT', '');
}

/*
 * Schema and result set the stubs report. Tests set these up through
 * FaSchema::describe().
 */
$GLOBALS['__fa_test_schema'] = array(
    'tables'  => array(),
    'columns' => array(),
    'rows'    => array(),
    'result'  => array(),
    'fail'    => false,
);

if (!function_exists('db_query')) {
    function db_query($sql, $err_msg = null)
    {
        $GLOBALS['__fa_test_schema']['last_sql'] = $sql;
        if (!empty($GLOBALS['__fa_test_schema']['fail'])) {
            return false;
        }
        $trimmed = trim($sql);

        if (preg_match("/^SHOW TABLES LIKE '(.+)'$/i", $trimmed, $m)) {
            $table = \ksfraser\FrontAccounting\RepSalesGroups\Tests\Support\FaSchema::unprefix($m[1]);
            $GLOBALS['__fa_test_schema']['result'] = isset($GLOBALS['__fa_test_schema']['tables'][$table])
                ? array(array('Tables_in_db' => $m[1]))
                : array();
            return 'SHOW_TABLES';
        }

        if (preg_match('/^SHOW COLUMNS FROM\s+(\S+)/i', $trimmed, $m)) {
            $table = \ksfraser\FrontAccounting\RepSalesGroups\Tests\Support\FaSchema::unprefix(trim($m[1], '`'));
            $columns = isset($GLOBALS['__fa_test_schema']['columns'][$table])
                ? $GLOBALS['__fa_test_schema']['columns'][$table]
                : array();
            $rows = array();
            foreach ($columns as $column) {
                $rows[] = array('Field' => $column);
            }
            $GLOBALS['__fa_test_schema']['result'] = $rows;
            return 'SHOW_COLUMNS';
        }

        $GLOBALS['__fa_test_schema']['result'] = $GLOBALS['__fa_test_schema']['rows'];
        return 'RESULT_SET';
    }
}

if (!function_exists('db_fetch')) {
    function db_fetch($result)
    {
        if (empty($GLOBALS['__fa_test_schema']['result'])) {
            return false;
        }
        return array_shift($GLOBALS['__fa_test_schema']['result']);
    }
}

require_once __DIR__ . '/Support/FaSchema.php';

/* The rest of the FA surface, from famock. */
$famockDir = __DIR__ . '/../vendor/ksfraser/famock/php';
if (is_dir($famockDir)) {
    require_once $famockDir . '/FaConstantStubs.php';
    require_once $famockDir . '/FaBusinessStubs.php';
    require_once $famockDir . '/FaDateStubs.php';
    require_once $famockDir . '/FaSessionStubs.php';
    require_once $famockDir . '/FaSecurityStubs.php';
}
