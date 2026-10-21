<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\RepSalesGroups\Tests\Unit;

use ksfraser\FrontAccounting\RepSalesGroups\ReportPresenter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the report presenter.
 *
 * @BABOK Related: FR-02.01 through FR-02.04 - report layout
 */
class ReportPresenterTest extends TestCase
{
    /**
     * @BABOK Related: FR-02.01 - one section per sales group
     */
    public function testGroupsBecomeOneSectionEach(): void
    {
        $rows = array(
            array('group_name' => 'Small', 'debtor_no' => '16', 'name' => 'MFC Travel', 'branch_ref' => 'Janice'),
            array('group_name' => 'Small', 'debtor_no' => '4', 'name' => 'Piping Perf', 'branch_ref' => 'Ashleigh'),
            array('group_name' => 'Large', 'debtor_no' => '9', 'name' => 'Abbie Mac', 'branch_ref' => 'Main'),
        );

        $sections = ReportPresenter::byGroup($rows);

        $this->assertCount(2, $sections);
        $this->assertSame('Small', $sections[0]['title']);
        $this->assertCount(2, $sections[0]['rows']);
        $this->assertSame('Large', $sections[1]['title']);
        $this->assertCount(1, $sections[1]['rows']);
    }

    /**
     * @BABOK Related: FR-02.02 - debtor and branch of a group
     */
    public function testGroupRowsCarryDebtorAndBranch(): void
    {
        $rows = array(
            array('group_name' => 'Small', 'debtor_no' => '16', 'name' => 'MFC Travel', 'branch_ref' => 'Janice'),
        );

        $sections = ReportPresenter::byGroup($rows);

        $this->assertSame(
            array('debtor' => 'MFC Travel', 'branch' => 'Janice'),
            $sections[0]['rows'][0]
        );
    }

    /**
     * A group with no customers assigned still has a section, but no rows.
     *
     * @BABOK Related: FR-02.03 - groups with no customers
     */
    public function testEmptyGroupHasASectionButNoRows(): void
    {
        $rows = array(
            array('group_name' => 'Large', 'debtor_no' => null, 'name' => null, 'branch_ref' => null),
        );

        $sections = ReportPresenter::byGroup($rows, true);

        $this->assertCount(1, $sections);
        $this->assertSame('Large', $sections[0]['title']);
        $this->assertSame(array(), $sections[0]['rows']);
    }

    /**
     * @BABOK Related: FR-02.03 - groups with no customers
     */
    public function testEmptyGroupsCanBeSuppressed(): void
    {
        $rows = array(
            array('group_name' => 'Large', 'debtor_no' => null, 'name' => null, 'branch_ref' => null),
            array('group_name' => 'Small', 'debtor_no' => '16', 'name' => 'MFC Travel', 'branch_ref' => 'Janice'),
        );

        $sections = ReportPresenter::byGroup($rows, false);

        $this->assertCount(1, $sections);
        $this->assertSame('Small', $sections[0]['title']);
    }

    /**
     * @BABOK Related: FR-02.01 - one section per sales group
     */
    public function testNoRowsMeansNoSections(): void
    {
        $this->assertSame(array(), ReportPresenter::byGroup(array()));
    }

    /**
     * A row without a group name still gets a section.
     *
     * @BABOK Related: FR-02.01 - one section per sales group
     */
    public function testAGroupWithoutANameGetsAPlaceholderTitle(): void
    {
        $rows = array(
            array('group_name' => null, 'debtor_no' => '16', 'name' => 'MFC Travel', 'branch_ref' => 'Janice'),
        );

        $sections = ReportPresenter::byGroup($rows);

        $this->assertSame('(no group)', $sections[0]['title']);
    }

    /**
     * @BABOK Related: FR-02.04 - one section per customer
     */
    public function testCustomersBecomeOneSectionEach(): void
    {
        $rows = array(
            array('customer_name' => 'Acme', 'branch_ref' => 'Main', 'group_name' => 'Small'),
            array('customer_name' => 'Acme', 'branch_ref' => 'Depot', 'group_name' => 'Large'),
            array('customer_name' => 'Bravo', 'branch_ref' => 'Main', 'group_name' => null),
        );

        $sections = ReportPresenter::byCustomer($rows);

        $this->assertCount(2, $sections);
        $this->assertSame('Acme', $sections[0]['title']);
        $this->assertCount(2, $sections[0]['rows']);
        $this->assertSame('Bravo', $sections[1]['title']);
        $this->assertCount(1, $sections[1]['rows']);
    }

    /**
     * A person associated with a branch for several actions is one row, with
     * all of the actions collected.
     *
     * @BABOK Related: FR-02.05 - one row per contact
     */
    public function testContactWithSeveralActionsIsOneRow(): void
    {
        $columns = array(
            array('label' => 'Contact', 'alias' => 'contact_name'),
            array('label' => 'Type', 'alias' => 'contact_type'),
        );
        $rows = array(
            array('customer_name' => 'Acme', 'branch_ref' => 'Main', 'group_name' => 'Small',
                'contact_person_id' => '7', 'contact_name' => 'Tara', 'contact_type' => 'general'),
            array('customer_name' => 'Acme', 'branch_ref' => 'Main', 'group_name' => 'Small',
                'contact_person_id' => '7', 'contact_name' => 'Tara', 'contact_type' => 'delivery'),
            array('customer_name' => 'Acme', 'branch_ref' => 'Main', 'group_name' => 'Small',
                'contact_person_id' => '7', 'contact_name' => 'Tara', 'contact_type' => 'invoice'),
        );

        $sections = ReportPresenter::byCustomer($rows, $columns);

        $this->assertCount(1, $sections);
        $this->assertCount(1, $sections[0]['rows']);
        $this->assertSame(array('General', 'Delivery', 'Invoice'), $sections[0]['rows'][0]['types']);
    }

    /**
     * Two different people on the same branch are two rows.
     *
     * @BABOK Related: FR-02.05 - one row per contact
     */
    public function testDifferentPeopleOnTheSameBranchAreSeparateRows(): void
    {
        $columns = array(
            array('label' => 'Contact', 'alias' => 'contact_name'),
        );
        $rows = array(
            array('customer_name' => 'Acme', 'branch_ref' => 'Main', 'group_name' => 'Small',
                'contact_person_id' => '7', 'contact_name' => 'Tara'),
            array('customer_name' => 'Acme', 'branch_ref' => 'Main', 'group_name' => 'Small',
                'contact_person_id' => '8', 'contact_name' => 'Ross'),
        );

        $sections = ReportPresenter::byCustomer($rows, $columns);

        $this->assertCount(2, $sections[0]['rows']);
    }

    /**
     * @BABOK Related: FR-02.05 - one row per contact
     */
    public function testContactTypesAreOrderedByUse(): void
    {
        $types = array('Invoice', 'General', 'Delivery', 'Order');

        $this->assertSame(
            array('General', 'Delivery', 'Invoice', 'Order'),
            ReportPresenter::orderTypes($types)
        );
    }

    /**
     * @BABOK Related: FR-02.05 - one row per contact
     */
    public function testUnknownContactTypesSortAlphabetically(): void
    {
        $types = array('warehouse', 'general', 'audit');

        $this->assertSame(
            array('General', 'Audit', 'Warehouse'),
            ReportPresenter::orderTypes($types)
        );
    }

    /**
     * @BABOK Related: FR-02.06 - branch label
     */
    public function testBranchLabelFallsBackToTheBranchName(): void
    {
        $columns = array();
        $rows = array(
            array('customer_name' => 'Acme', 'branch_ref' => '', 'br_name' => 'Head Office', 'group_name' => null),
        );

        $sections = ReportPresenter::byCustomer($rows, $columns);

        $this->assertSame('Head Office', $sections[0]['rows'][0]['branch']);
    }

    /**
     * @BABOK Related: FR-02.06 - branch label
     */
    public function testBranchLabelIsEmptyWhenThereIsNoBranch(): void
    {
        $rows = array(
            array('customer_name' => 'Acme', 'branch_ref' => '', 'br_name' => '', 'group_name' => null),
        );

        $sections = ReportPresenter::byCustomer($rows);

        $this->assertSame('', $sections[0]['rows'][0]['branch']);
    }

    /**
     * A row without a customer name still gets a section.
     *
     * @BABOK Related: FR-02.04 - one section per customer
     */
    public function testACustomerWithoutANameGetsAPlaceholderTitle(): void
    {
        $rows = array(
            array('customer_name' => null, 'branch_ref' => 'Main', 'group_name' => null),
        );

        $sections = ReportPresenter::byCustomer($rows);

        $this->assertSame('(no customer)', $sections[0]['title']);
    }

    /**
     * @BABOK Related: FR-02.07 - contact coverage
     */
    public function testCountsOnlyTheEntriesThatCarryContactDetails(): void
    {
        $columns = array(
            array('label' => 'Contact', 'alias' => 'contact_name'),
            array('label' => 'Phone', 'alias' => 'contact_phone'),
        );
        $entries = array(
            array('values' => array('contact_name' => 'Tara', 'contact_phone' => '')),
            array('values' => array('contact_name' => '', 'contact_phone' => '403-555-1212')),
            array('values' => array('contact_name' => '', 'contact_phone' => '')),
        );

        $this->assertSame(2, ReportPresenter::countWithContact($entries, $columns));
    }
}
