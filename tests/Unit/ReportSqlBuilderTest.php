<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\RepSalesGroups\Tests\Unit;

use ksfraser\FrontAccounting\RepSalesGroups\ReportSqlBuilder;
use ksfraser\FrontAccounting\RepSalesGroups\Tests\Support\FaSchema;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SQL builder.
 *
 * @BABOK Related: FR-01.01 through FR-01.05 - report queries
 */
class ReportSqlBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // A stock FA 2.4 installation: CRM tables present.
        FaSchema::describe(
            array('debtors_master', 'cust_branch', 'groups', 'crm_contacts', 'crm_persons')
        );
    }

    /**
     * @BABOK Related: FR-01.02 - no filter means no WHERE clause
     */
    public function testNoGroupFilterMeansNoGroupPredicate(): void
    {
        $builder = new ReportSqlBuilder(1, null);

        $this->assertFalse($builder->hasGroupFilter());
        $this->assertStringNotContainsString('AND g.id', $builder->buildV1Sql());
    }

    /**
     * The dropdowns submit '' (ALL_TEXT) or '0' for "no selection".
     *
     * @dataProvider emptyFilterProvider
     * @BABOK Related: FR-01.02 - empty filter values
     */
    public function testEmptyFiltersAreTreatedAsNoFilter($value): void
    {
        $builder = new ReportSqlBuilder(1, $value);

        $this->assertFalse($builder->hasGroupFilter());
        $this->assertStringNotContainsString('AND g.id', $builder->buildV1Sql());
    }

    public function emptyFilterProvider(): array
    {
        return array(
            'empty string' => array(''),
            'zero' => array('0'),
            'all text' => array(ALL_TEXT),
        );
    }

    /**
     * @BABOK Related: FR-01.02 - a selected group is filtered on
     */
    public function testSelectedGroupIsFiltered(): void
    {
        $builder = new ReportSqlBuilder(1, '1');

        $this->assertTrue($builder->hasGroupFilter());
        $this->assertStringContainsString("AND g.id = '1'", $builder->buildV1Sql());
    }

    /**
     * The group filter must be applied to the driving table. Filtering a
     * LEFT JOINed table silently drops every customer that has no branch in
     * the group.
     *
     * @BABOK Related: FR-01.03 - group driven customer query
     */
    public function testGroupFilterDrivesTheCustomerQueryFromGroups(): void
    {
        $builder = new ReportSqlBuilder(2, '1', null);
        $sql = $builder->buildV2Sql();

        $this->assertStringContainsString('FROM ' . TB_PREF . 'groups g', $sql);
        $this->assertStringContainsString('INNER JOIN ' . TB_PREF . 'cust_branch b ON b.group_no = g.id', $sql);
        $this->assertStringContainsString('INNER JOIN ' . TB_PREF . 'debtors_master d ON d.debtor_no = b.debtor_no', $sql);
        $this->assertStringContainsString("AND g.id = '1'", $sql);
    }

    /**
     * Without a group filter every customer is listed, whether or not they
     * are in a sales group.
     *
     * @BABOK Related: FR-01.04 - unfiltered customer query
     */
    public function testWithoutGroupFilterTheCustomerQueryListsEverybody(): void
    {
        $builder = new ReportSqlBuilder(2, null, null);
        $sql = $builder->buildV2Sql();

        $this->assertStringContainsString('FROM ' . TB_PREF . 'debtors_master d', $sql);
        $this->assertStringContainsString('LEFT JOIN ' . TB_PREF . 'cust_branch b ON b.debtor_no = d.debtor_no', $sql);
        $this->assertStringContainsString('LEFT JOIN ' . TB_PREF . 'groups g ON g.id = b.group_no', $sql);
        $this->assertStringNotContainsString('INNER JOIN', $sql);
    }

    /**
     * @BABOK Related: FR-01.05 - customer filter
     */
    public function testCustomerFilterIsApplied(): void
    {
        $builder = new ReportSqlBuilder(2, null, 'C001');
        $sql = $builder->buildV2Sql();

        $this->assertTrue($builder->hasCustomerFilter());
        $this->assertStringContainsString("AND d.debtor_no = 'C001'", $sql);
    }

    /**
     * @BABOK Related: FR-01.05 - customer filter
     */
    public function testEmptyCustomerFilterIsNotApplied(): void
    {
        $builder = new ReportSqlBuilder(2, null, '');

        $this->assertFalse($builder->hasCustomerFilter());
        $this->assertStringNotContainsString('AND d.debtor_no', $builder->buildV2Sql());
    }

    /**
     * @BABOK Related: FR-03.01 - branch contacts come from the CRM tables
     */
    public function testCrmContactsAreJoinedWhenTheTablesExist(): void
    {
        $builder = new ReportSqlBuilder(2, null, null);
        $sql = $builder->buildV2Sql();

        $this->assertSame(ReportSqlBuilder::CONTACT_CRM, $builder->contactMode());
        $this->assertStringContainsString('LEFT JOIN ' . TB_PREF . 'crm_contacts c', $sql);
        $this->assertStringContainsString("c.entity_id = b.branch_code AND c.type = 'cust_branch'", $sql);
        $this->assertStringContainsString('LEFT JOIN ' . TB_PREF . 'crm_persons p ON p.id = c.person_id', $sql);
    }

    /**
     * Every action is reported, not just "general".
     *
     * @BABOK Related: FR-03.02 - all contact actions are listed
     */
    public function testContactsAreNotRestrictedToTheGeneralAction(): void
    {
        $builder = new ReportSqlBuilder(2, null, null);
        $sql = $builder->buildV2Sql();

        $this->assertStringNotContainsString("c.action = 'general'", $sql);
        $this->assertStringContainsString('c.action AS contact_type', $sql);
    }

    /**
     * The action filter is applied inside the join, so a branch without a
     * contact of that action still appears.
     *
     * @BABOK Related: FR-03.03 - contact action filter
     */
    public function testContactActionFilterIsAppliedInsideTheJoin(): void
    {
        $builder = new ReportSqlBuilder(2, null, null, 'delivery');
        $sql = $builder->buildV2Sql();

        $this->assertSame('delivery', $builder->contactAction());
        $this->assertStringContainsString("AND c.action = 'delivery'", $sql);
        // the predicate belongs to the join, not to a WHERE clause
        $this->assertStringNotContainsString('WHERE 1=1 AND c.action', $sql);
    }

    /**
     * @BABOK Related: FR-03.03 - contact action filter
     */
    public function testNoContactActionFilterByDefault(): void
    {
        $builder = new ReportSqlBuilder(2, null, null);

        $this->assertNull($builder->contactAction());
        $this->assertStringNotContainsString('AND c.action', $builder->buildV2Sql());
    }

    /**
     * An installation without the CRM tables falls back to the contact
     * columns on cust_branch.
     *
     * @BABOK Related: FR-03.04 - schema detection
     */
    public function testFallsBackToBranchContactColumns(): void
    {
        FaSchema::describe(
            array('debtors_master', 'cust_branch', 'groups'),
            array('cust_branch' => array('branch_code', 'debtor_no', 'br_contact', 'br_phone', 'br_email'))
        );

        $builder = new ReportSqlBuilder(2, null, null);
        $sql = $builder->buildV2Sql();

        $this->assertSame(ReportSqlBuilder::CONTACT_BRANCH, $builder->contactMode());
        $this->assertStringContainsString('b.br_contact AS contact_contact', $sql);
        $this->assertStringContainsString('b.br_phone AS contact_phone', $sql);
        $this->assertStringContainsString('b.br_email AS contact_email', $sql);
        $this->assertStringNotContainsString('crm_contacts', $sql);
    }

    /**
     * An installation with neither has no contact details at all, and the
     * report still runs.
     *
     * @BABOK Related: FR-03.05 - no contact details available
     */
    public function testNoContactColumnsWhenTheSchemaHasNone(): void
    {
        FaSchema::describe(
            array('debtors_master', 'cust_branch', 'groups'),
            array('cust_branch' => array('branch_code', 'debtor_no', 'br_name', 'branch_ref', 'group_no'))
        );

        $builder = new ReportSqlBuilder(2, null, null);
        $sql = $builder->buildV2Sql();

        $this->assertSame(ReportSqlBuilder::CONTACT_NONE, $builder->contactMode());
        $this->assertSame(array(), $builder->contactColumns());
        $this->assertStringNotContainsString('crm_contacts', $sql);
        $this->assertStringNotContainsString('contact_name', $sql);
    }

    /**
     * @BABOK Related: FR-01.01 - report queries
     */
    public function testTheWhereClauseListReportsTheActiveFilters(): void
    {
        $builder = new ReportSqlBuilder(2, '1', 'C001');

        $this->assertSame(array('group_filter', 'customer_filter'), $builder->getWhereClauses());
    }

    /**
     * @BABOK Related: FR-01.01 - report queries
     */
    public function testTheWhereClauseListIsEmptyWithoutFilters(): void
    {
        $builder = new ReportSqlBuilder(2, null, null);

        $this->assertSame(array(), $builder->getWhereClauses());
    }

    /**
     * @BABOK Related: FR-03.01 - branch contacts come from the CRM tables
     */
    public function testCrmContactColumnsAreReported(): void
    {
        $builder = new ReportSqlBuilder(2, null, null);

        $this->assertSame(
            array(
                array('label' => 'Contact', 'alias' => 'contact_name', 'column' => null),
                array('label' => 'Type', 'alias' => 'contact_type', 'column' => null),
                array('label' => 'Phone', 'alias' => 'contact_phone', 'column' => null),
                array('label' => 'Email', 'alias' => 'contact_email', 'column' => null),
            ),
            $builder->contactColumns()
        );
    }

    /**
     * @BABOK Related: FR-03.04 - schema detection
     */
    public function testBranchContactColumnsAreReported(): void
    {
        FaSchema::describe(
            array('debtors_master', 'cust_branch', 'groups'),
            array('cust_branch' => array('branch_code', 'debtor_no', 'br_contact', 'br_phone'))
        );

        $builder = new ReportSqlBuilder(2, null, null);

        $this->assertSame(
            array(
                array('label' => 'Contact', 'alias' => 'contact_contact', 'column' => 'br_contact'),
                array('label' => 'Phone', 'alias' => 'contact_phone', 'column' => 'br_phone'),
            ),
            $builder->contactColumns()
        );
    }

    /**
     * A database that rejects every query must not take the report down: the
     * builder falls back to no contact details at all.
     *
     * @BABOK Related: FR-03.05 - no contact details available
     */
    public function testAnUnreachableDatabaseDegradesToNoContactDetails(): void
    {
        FaSchema::failQueries();

        $builder = new ReportSqlBuilder(2, null, null);

        $this->assertSame(ReportSqlBuilder::CONTACT_NONE, $builder->contactMode());
        $this->assertSame(array(), $builder->contactColumns());
        $this->assertStringNotContainsString('crm_contacts', $builder->buildV2Sql());

        FaSchema::failQueries(false);
    }
}
