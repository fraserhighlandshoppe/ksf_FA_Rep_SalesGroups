<?php
/**
 * FaSchema — controls what the stubbed FA database reports.
 *
 * The SQL builder probes the schema (SHOW TABLES / SHOW COLUMNS) to work
 * out where the branch contact details live. Tests describe the schema they
 * want and then assert on the SQL the builder produces.
 */

declare(strict_types=1);

namespace ksfraser\FrontAccounting\RepSalesGroups\Tests\Support;

class FaSchema
{
    /**
     * Describe the schema the stubbed database reports.
     *
     * @param string[] $tables  table names, without the TB_PREF prefix
     * @param array<string, string[]> $columns table name => column names
     */
    public static function describe(array $tables, array $columns = array()): void
    {
        $GLOBALS['__fa_test_schema']['tables'] = array_fill_keys($tables, true);
        $GLOBALS['__fa_test_schema']['columns'] = $columns;
        $GLOBALS['__fa_test_schema']['rows'] = array();
        $GLOBALS['__fa_test_schema']['result'] = array();
    }

    /**
     * The rows the next query returns.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public static function rows(array $rows): void
    {
        $GLOBALS['__fa_test_schema']['rows'] = $rows;
        $GLOBALS['__fa_test_schema']['result'] = array();
    }

    /**
     * Make the database reject every query, as a broken connection would.
     */
    public static function failQueries(bool $fail = true): void
    {
        $GLOBALS['__fa_test_schema']['fail'] = $fail;
    }

    /**
     * The last SQL that was run.
     */
    public static function lastSql(): string
    {
        return isset($GLOBALS['__fa_test_schema']['last_sql'])
            ? (string) $GLOBALS['__fa_test_schema']['last_sql']
            : '';
    }

    /**
     * Strip the table prefix from a prefixed table name.
     */
    public static function unprefix(string $name): string
    {
        if (defined('TB_PREF') && strpos($name, TB_PREF) === 0) {
            return substr($name, strlen(TB_PREF));
        }
        return $name;
    }
}
