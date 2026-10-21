<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\RepSalesGroups\Tests\Unit;

use ksfraser\FrontAccounting\RepSalesGroups\ReportLayout;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the report column layout.
 *
 * @BABOK Related: FR-02.08 - column layout
 */
class ReportLayoutTest extends TestCase
{
    /**
     * @BABOK Related: FR-02.08 - column layout
     */
    public function testBranchAndGroupAreAlwaysTheFirstTwoColumns(): void
    {
        $layout = new ReportLayout(array());

        $this->assertSame(array('Branch', 'Sales Group'), $layout->headers());
        $this->assertSame(array('left', 'left'), $layout->aligns());
        $this->assertSame(array(0, 75, 165), $layout->cols());
        $this->assertSame(2, $layout->lastCol());
    }

    /**
     * @BABOK Related: FR-02.08 - column layout
     */
    public function testContactColumnsAreAppended(): void
    {
        $columns = array(
            array('label' => 'Contact', 'alias' => 'contact_name'),
            array('label' => 'Type', 'alias' => 'contact_type'),
            array('label' => 'Phone', 'alias' => 'contact_phone'),
        );

        $layout = new ReportLayout($columns);

        $this->assertSame(
            array('Branch', 'Sales Group', 'Contact', 'Type', 'Phone'),
            $layout->headers()
        );
        $this->assertSame(5, $layout->lastCol());
        $this->assertSame(array(0, 75, 165, 260, 330, 425), $layout->cols());
    }

    /**
     * The Type cell is narrower than the other contact columns, and the
     * report needs to know which column it is.
     *
     * @BABOK Related: FR-02.08 - column layout
     */
    public function testTypeColumnIndexIsReported(): void
    {
        $columns = array(
            array('label' => 'Contact', 'alias' => 'contact_name'),
            array('label' => 'Type', 'alias' => 'contact_type'),
            array('label' => 'Phone', 'alias' => 'contact_phone'),
        );

        $layout = new ReportLayout($columns);

        $this->assertSame(3, $layout->typeIndex());
    }

    /**
     * @BABOK Related: FR-02.08 - column layout
     */
    public function testThereIsNoTypeColumnWithoutOne(): void
    {
        $layout = new ReportLayout(array(
            array('label' => 'Contact', 'alias' => 'contact_name'),
        ));

        $this->assertNull($layout->typeIndex());
    }

    /**
     * @BABOK Related: FR-02.08 - column layout
     */
    public function testTheContactColumnsAreKept(): void
    {
        $columns = array(
            array('label' => 'Contact', 'alias' => 'contact_name', 'column' => null),
        );

        $layout = new ReportLayout($columns);

        $this->assertSame($columns, $layout->contactColumns());
    }

    /**
     * The table must fit the printable width of an A4 portrait page.
     *
     * @BABOK Related: FR-02.09 - printable width
     */
    public function testTableFitsThePageWidth(): void
    {
        $columns = array(
            array('label' => 'Contact', 'alias' => 'contact_name'),
            array('label' => 'Type', 'alias' => 'contact_type'),
            array('label' => 'Phone', 'alias' => 'contact_phone'),
            array('label' => 'Email', 'alias' => 'contact_email'),
        );

        $layout = new ReportLayout($columns);

        // A4 portrait: 595pt wide, 40pt left and 30pt right margin.
        $this->assertLessThanOrEqual(595 - 40 - 30, $layout->cols()[$layout->lastCol()]);
    }
}
