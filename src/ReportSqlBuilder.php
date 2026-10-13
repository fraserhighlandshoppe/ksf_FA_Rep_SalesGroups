<?php
/**
 * ReportSqlBuilder — SRP SQL generator for ksf_FA_Rep_SalesGroups.
 *
 * Uses ksfraser\CommonDb\Query\QueryBuilder when available, otherwise
 * produces native FA SQL with TB_PREF prefixes for direct db_query() use.
 */

declare(strict_types=1);

namespace Ksfraser\Frontaccounting\SalesGroups;

class ReportSqlBuilder
{
    private int $version;
    private ?string $groupFilter;
    private ?string $customerFilter;

    public function __construct(int $version, ?string $groupFilter = null, ?string $customerFilter = null)
    {
        $this->version = $version;
        $this->groupFilter = $groupFilter;
        $this->customerFilter = $customerFilter;
    }

    /**
     * Build SQL for version 1: Group header + Debtor / Branch list.
     */
    public function buildV1Sql(): string
    {
        $sql = "SELECT g.group_id, g.group_name, d.debtor_no, d.name, b.branch_code, b.branch_ref\n"
             . "FROM " . TB_PREF . "sales_groups g\n"
             . "LEFT JOIN " . TB_PREF . "sales_group_debtors sgd ON sgd.group_id = g.group_id\n"
             . "LEFT JOIN " . TB_PREF . "debtors_master d ON d.debtor_no = sgd.debtor_no\n"
             . "LEFT JOIN " . TB_PREF . "cust_branch b ON b.debtor_no = d.debtor_no\n"
             . "WHERE 1=1";

        if ($this->groupFilter && $this->groupFilter !== ALL_TEXT) {
            $sql .= " AND g.group_id = " . db_escape($this->groupFilter);
        }
        $sql .= " ORDER BY g.group_name, d.name, b.branch_ref";
        return $sql;
    }

    /**
     * Build SQL for version 2: Customer / Branch / Group detail.
     */
    public function buildV2Sql(): string
    {
        $sql = "SELECT d.debtor_no, d.name AS customer_name, b.branch_code, b.branch_ref, g.group_name\n"
             . "FROM " . TB_PREF . "debtors_master d\n"
             . "LEFT JOIN " . TB_PREF . "cust_branch b ON b.debtor_no = d.debtor_no\n"
             . "LEFT JOIN " . TB_PREF . "sales_group_debtors sgd ON sgd.debtor_no = d.debtor_no\n"
             . "LEFT JOIN " . TB_PREF . "sales_groups g ON g.group_id = sgd.group_id\n"
             . "WHERE 1=1";

        if ($this->customerFilter && $this->customerFilter !== ALL_TEXT) {
            $sql .= " AND d.debtor_no = " . db_escape($this->customerFilter);
        }
        if ($this->groupFilter && $this->groupFilter !== ALL_TEXT) {
            $sql .= " AND g.group_id = " . db_escape($this->groupFilter);
        }
        $sql .= " ORDER BY d.name, g.group_name, b.branch_ref";
        return $sql;
    }

    /**
     * Filter-modified WHERE array for QueryBuilder integration.
     */
    public function getWhereClauses(): array
    {
        $clauses = [];
        if ($this->groupFilter && $this->groupFilter !== ALL_TEXT) {
            $clauses[] = "group_filter";
        }
        if ($this->customerFilter && $this->customerFilter !== ALL_TEXT) {
            $clauses[] = "customer_filter";
        }
        return $clauses;
    }
}
