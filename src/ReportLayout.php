<?php
/**
 * ReportLayout — column geometry for the Sales Group reports.
 *
 * Builds the x positions, headers and alignments for a report whose
 * columns are "Branch" and "Sales Group" followed by whichever contact
 * detail columns the installation provides.
 */

declare(strict_types=1);

namespace ksfraser\FrontAccounting\RepSalesGroups;

class ReportLayout
{
    /** Width of the Branch column, in points. */
    const WIDTH_BRANCH = 75;
    /** Width of the Sales Group column, in points. */
    const WIDTH_GROUP = 90;
    /** Width of a contact detail column, in points. */
    const WIDTH_CONTACT = 95;
    /** Width of the Type column, in points. */
    const WIDTH_TYPE = 70;

    /** @var array<int, array{label: string, alias: string}> */
    private $contactColumns;

    /** @var int[] x position of each column, offset by the left margin */
    private $cols = array();

    /** @var string[] */
    private $headers = array();

    /** @var string[] */
    private $aligns = array();

    /** @var int|null column index of the Type cell */
    private $typeIndex = null;

    /**
     * @param array<int, array{label: string, alias: string}> $contactColumns
     */
    public function __construct(array $contactColumns = array())
    {
        $this->contactColumns = $contactColumns;

        $this->headers = array('Branch', 'Sales Group');
        $this->aligns = array('left', 'left');
        $this->cols = array(0);

        $x = 0;
        $x += self::WIDTH_BRANCH;
        $this->cols[] = $x;
        $x += self::WIDTH_GROUP;
        $this->cols[] = $x;

        foreach ($contactColumns as $index => $column) {
            $this->headers[] = $column['label'];
            $this->aligns[] = 'left';
            $x += ($column['alias'] === 'contact_type') ? self::WIDTH_TYPE : self::WIDTH_CONTACT;
            $this->cols[] = $x;
            if ($column['alias'] === 'contact_type') {
                $this->typeIndex = $index + 2;
            }
        }
    }

    /**
     * @return int[] x position of each column
     */
    public function cols(): array
    {
        return $this->cols;
    }

    /**
     * @return string[] column headings
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return string[] alignment of each column
     */
    public function aligns(): array
    {
        return $this->aligns;
    }

    /**
     * Index of the Type cell, or null when there is no Type column.
     */
    public function typeIndex(): ?int
    {
        return $this->typeIndex;
    }

    /**
     * Index of the last column, for cells that span the whole table.
     */
    public function lastCol(): int
    {
        return count($this->cols) - 1;
    }

    /**
     * @return array<int, array{label: string, alias: string}>
     */
    public function contactColumns(): array
    {
        return $this->contactColumns;
    }
}
