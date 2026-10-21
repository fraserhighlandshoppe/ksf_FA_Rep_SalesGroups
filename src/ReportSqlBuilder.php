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
    /** Contact details come from the CRM tables (crm_contacts/crm_persons) */
    const CONTACT_CRM = 'crm';
    /** Contact details come from columns on cust_branch itself */
    const CONTACT_BRANCH = 'branch';
    /** No contact details available in this installation */
    const CONTACT_NONE = 'none';

    private int $version;
    private ?string $groupFilter;
    private ?string $customerFilter;
    private ?string $contactAction = null;
    private ?string $contactMode = null;

    public function __construct(int $version, ?string $groupFilter = null, ?string $customerFilter = null, ?string $contactAction = null)
    {
        $this->version = $version;
        $this->groupFilter = $groupFilter;
        $this->customerFilter = $customerFilter;
        $this->contactAction = $contactAction;
    }

    /**
     * The contact action (general, delivery, invoice, order) to report on.
     * Null or empty means "all actions".
     */
    public function contactAction(): ?string
    {
        return $this->contactAction !== null && $this->contactAction !== '' ? $this->contactAction : null;
    }

    /**
     * Where the branch contact details live in this installation.
     *
     * FA 2.4 keeps them in the CRM tables, but an installation may not have
     * them populated (or the tables may not exist at all), in which case
     * cust_branch may still carry its own contact columns.
     */
    public function contactMode(): string
    {
        if ($this->contactMode === null) {
            $this->contactMode = self::detectContactMode();
        }
        return $this->contactMode;
    }

    /**
     * Contact detail columns available for display, as [label, sql alias].
     */
    public function contactColumns(): array
    {
        switch ($this->contactMode()) {
            case self::CONTACT_CRM:
                return array(
                    array('Contact', 'contact_name'),
                    array('Type', 'contact_type'),
                    array('Phone', 'contact_phone'),
                    array('Email', 'contact_email'),
                );
            case self::CONTACT_BRANCH:
                $cols = self::tableColumns('cust_branch');
                $out = array();
                foreach (array('contact' => 'Contact', 'phone' => 'Phone', 'fax' => 'Fax', 'email' => 'Email') as $key => $label) {
                    foreach ($cols as $col) {
                        if (stripos($col, $key) !== false) {
                            $out[] = array($label, 'contact_' . $key);
                            break;
                        }
                    }
                }
                return $out;
            default:
                return array();
        }
    }

    /**
     * Does a table with this name exist? SHOW TABLES never fails, unlike a
     * query against a missing table.
     */
    private static function tableExists(string $table): bool
    {
        $res = @db_query("SHOW TABLES LIKE '" . TB_PREF . $table . "'");
        if (!$res) {
            return false;
        }
        $row = db_fetch($res);
        return !empty($row);
    }

    /**
     * Column names of a table (empty when the table does not exist).
     */
    private static function tableColumns(string $table): array
    {
        $res = @db_query("SHOW COLUMNS FROM " . TB_PREF . $table);
        $cols = array();
        if (!$res) {
            return $cols;
        }
        while ($row = db_fetch($res)) {
            if (isset($row['Field'])) {
                $cols[] = $row['Field'];
            }
        }
        return $cols;
    }

    private static function detectContactMode(): string
    {
        if (self::tableExists('crm_contacts') && self::tableExists('crm_persons')) {
            return self::CONTACT_CRM;
        }
        $cols = self::tableColumns('cust_branch');
        foreach ($cols as $col) {
            if (stripos($col, 'contact') !== false || stripos($col, 'phone') !== false
                || stripos($col, 'email') !== false) {
                return self::CONTACT_BRANCH;
            }
        }
        return self::CONTACT_NONE;
    }

    /**
     * SQL fragment selecting the contact details, and the joins it needs.
     *
     * Returns array(select, joins).
     */
    private function contactSelectAndJoins(): array
    {
        switch ($this->contactMode()) {
            case self::CONTACT_CRM:
                // Every contact associated with the branch is listed, whatever
                // its action is (general, delivery, invoice, order); the
                // action is reported in the "Type" column. The action filter
                // is applied inside the join so that a branch without a
                // contact of that action still appears (with empty contact
                // columns) instead of dropping out of the report.
                $select = "p.id AS contact_person_id,\n"
                        . "p.name AS contact_name,\n"
                        . "c.action AS contact_type,\n"
                        . "p.phone AS contact_phone,\n"
                        . "p.email AS contact_email";
                $action = $this->contactAction();
                $filter = $action === null ? '' : " AND c.action = " . db_escape($action);
                $joins = "LEFT JOIN " . TB_PREF . "crm_contacts c\n"
                       . "  ON c.entity_id = b.branch_code AND c.type = 'cust_branch'" . $filter . "\n"
                       . "LEFT JOIN " . TB_PREF . "crm_persons p ON p.id = c.person_id\n";
                return array($select, $joins);

            case self::CONTACT_BRANCH:
                $select = '';
                $joins = '';
                foreach ($this->contactColumns() as $col) {
                    list($label, $alias) = $col;
                    $key = substr($alias, strlen('contact_'));
                    $actual = null;
                    foreach (self::tableColumns('cust_branch') as $c) {
                        if (stripos($c, $key) !== false) {
                            $actual = $c;
                            break;
                        }
                    }
                    if ($actual === null) {
                        continue;
                    }
                    $select .= ($select === '' ? '' : ",\n") . "b." . $actual . " AS " . $alias;
                }
                return array($select, $joins);

            default:
                return array('', '');
        }
    }

    /**
     * True when a specific sales group is selected.
     *
     * The dropdowns submit '' (ALL_TEXT) or '0' for "no selection", so both
     * have to be treated as "show everything".
     */
    public function hasGroupFilter(): bool
    {
        return $this->groupFilter !== null
            && $this->groupFilter !== ''
            && $this->groupFilter !== '0'
            && $this->groupFilter !== ALL_TEXT;
    }

    /**
     * True when a specific customer is selected.
     */
    public function hasCustomerFilter(): bool
    {
        return $this->customerFilter !== null
            && $this->customerFilter !== ''
            && $this->customerFilter !== '0'
            && $this->customerFilter !== ALL_TEXT;
    }

    /**
     * Build SQL for version 1: Group header + Debtor / Branch list.
     */
    public function buildV1Sql(): string
    {
        $sql = "SELECT g.id AS group_id, g.description AS group_name, d.debtor_no, d.name, b.branch_ref\n"
             . "FROM " . TB_PREF . "groups g\n"
             . "LEFT JOIN " . TB_PREF . "cust_branch b ON b.group_no = g.id\n"
             . "LEFT JOIN " . TB_PREF . "debtors_master d ON d.debtor_no = b.debtor_no\n"
             . "WHERE 1=1";

        if ($this->hasGroupFilter()) {
            $sql .= " AND g.id = " . db_escape($this->groupFilter);
        }
        $sql .= " ORDER BY g.description, d.name, b.branch_ref";
        return $sql;
    }

    /**
     * Build SQL for version 2: Customer / Branch / Group detail with the
     * contact details of each branch.
     *
     * When a sales group is selected the query is driven from the groups
     * table with inner joins. Filtering a LEFT JOINed table in the WHERE
     * clause would silently discard every customer that has no branch in
     * that group, which made the report come back empty.
     */
    public function buildV2Sql(): string
    {
        list($contactSelect, $contactJoins) = $this->contactSelectAndJoins();

        $select = "d.debtor_no, d.name AS customer_name, b.branch_ref, b.br_name,\n"
                . "g.description AS group_name"
                . ($contactSelect === '' ? '' : ",\n" . $contactSelect);

        if ($this->hasGroupFilter()) {
            $sql = "SELECT " . $select . "\n"
                 . "FROM " . TB_PREF . "groups g\n"
                 . "INNER JOIN " . TB_PREF . "cust_branch b ON b.group_no = g.id\n"
                 . "INNER JOIN " . TB_PREF . "debtors_master d ON d.debtor_no = b.debtor_no\n";
        } else {
            $sql = "SELECT " . $select . "\n"
                 . "FROM " . TB_PREF . "debtors_master d\n"
                 . "LEFT JOIN " . TB_PREF . "cust_branch b ON b.debtor_no = d.debtor_no\n"
                 . "LEFT JOIN " . TB_PREF . "groups g ON g.id = b.group_no\n";
        }

        $sql .= $contactJoins;
        $sql .= "WHERE 1=1";

        if ($this->hasCustomerFilter()) {
            $sql .= " AND d.debtor_no = " . db_escape($this->customerFilter);
        }
        if ($this->hasGroupFilter()) {
            $sql .= " AND g.id = " . db_escape($this->groupFilter);
        }
        $sql .= " ORDER BY d.name, g.description, b.branch_ref";
        if ($this->contactMode() === self::CONTACT_CRM) {
            $sql .= ", c.action, p.name";
        }
        return $sql;
    }

    /**
     * Filter-modified WHERE array for QueryBuilder integration.
     */
    public function getWhereClauses(): array
    {
        $clauses = [];
        if ($this->hasGroupFilter()) {
            $clauses[] = "group_filter";
        }
        if ($this->hasCustomerFilter()) {
            $clauses[] = "customer_filter";
        }
        return $clauses;
    }
}
