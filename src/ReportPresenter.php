<?php
/**
 * ReportPresenter — turns report rows into printable sections.
 *
 * Two shapes are produced:
 *
 *   byGroup()    one section per sales group, each listing the debtor and
 *                branch pairs assigned to that group.
 *
 *   byCustomer() one section per customer, each listing the branches with
 *                their sales group and the contacts associated with the
 *                branch. A person associated with a branch for several
 *                actions is listed once, with all of the actions collected
 *                into the Type cell.
 */

declare(strict_types=1);

namespace ksfraser\FrontAccounting\RepSalesGroups;

class ReportPresenter
{
    /** Order the contact types are listed in. */
    const TYPE_ORDER = array(
        'general' => 0,
        'delivery' => 1,
        'invoice' => 2,
        'order' => 3,
    );

    /**
     * Bucket the rows into one section per sales group.
     *
     * A group with no customers assigned is only included when
     * $showEmptyGroups is true; its rows list is then empty.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{title: string, rows: array<int, array{debtor: string, branch: string}>}>
     */
    public static function byGroup(array $rows, bool $showEmptyGroups = true): array
    {
        $grouped = array();
        $order = array();

        foreach ($rows as $row) {
            $title = isset($row['group_name']) && $row['group_name'] !== null
                ? (string) $row['group_name']
                : '(no group)';
            if (!isset($grouped[$title])) {
                $grouped[$title] = array();
                $order[] = $title;
            }
            if (empty($row['debtor_no'])) {
                continue; // group has no customers assigned
            }
            $grouped[$title][] = array(
                'debtor' => isset($row['name']) && $row['name'] !== null ? (string) $row['name'] : '',
                'branch' => isset($row['branch_ref']) && $row['branch_ref'] !== null ? (string) $row['branch_ref'] : '',
            );
        }

        $sections = array();
        foreach ($order as $title) {
            if (count($grouped[$title]) === 0 && !$showEmptyGroups) {
                continue;
            }
            $sections[] = array('title' => $title, 'rows' => $grouped[$title]);
        }

        return $sections;
    }

    /**
     * Bucket the rows into one section per customer.
     *
     * Rows are merged by branch and contact person, so a person that is
     * associated with a branch for several actions is a single row whose
     * types are collected in $row['types'].
     *
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, array{label: string, alias: string}> $contactColumns
     * @return array<int, array{title: string, rows: array<int, array<string, mixed>>}>
     */
    public static function byCustomer(array $rows, array $contactColumns = array()): array
    {
        $customers = array();
        $order = array();

        foreach ($rows as $row) {
            $title = isset($row['customer_name']) && $row['customer_name'] !== null
                ? (string) $row['customer_name']
                : '(no customer)';
            if (!isset($customers[$title])) {
                $customers[$title] = array();
                $order[] = $title;
            }

            $branch = self::branchLabel($row);
            $person = isset($row['contact_person_id']) ? (string) $row['contact_person_id'] : '';
            $key = $branch . "\0" . $person;

            if (!isset($customers[$title][$key])) {
                $entry = array(
                    'branch' => $branch,
                    'group' => isset($row['group_name']) && $row['group_name'] !== null
                        ? (string) $row['group_name'] : '',
                    'values' => array(),
                    'types' => array(),
                );
                foreach ($contactColumns as $column) {
                    $alias = $column['alias'];
                    $entry['values'][$alias] = isset($row[$alias]) && $row[$alias] !== null
                        ? (string) $row[$alias] : '';
                }
                $customers[$title][$key] = $entry;
            }

            $type = isset($row['contact_type']) ? (string) $row['contact_type'] : '';
            if ($type !== '' && !in_array($type, $customers[$title][$key]['types'], true)) {
                $customers[$title][$key]['types'][] = $type;
            }
        }

        $sections = array();
        foreach ($order as $title) {
            $entries = array_values($customers[$title]);
            foreach ($entries as $index => $entry) {
                $entries[$index]['types'] = self::orderTypes($entry['types']);
            }
            $sections[] = array('title' => $title, 'rows' => $entries);
        }

        return $sections;
    }

    /**
     * Order contact types the way they are used: general, delivery, invoice,
     * order, then anything else alphabetically.
     *
     * The types are presented capitalised, whatever the database holds.
     *
     * @param string[] $types
     * @return string[]
     */
    public static function orderTypes(array $types): array
    {
        $types = array_map('ucfirst', $types);
        usort($types, function ($a, $b) {
            $pa = isset(self::TYPE_ORDER[strtolower($a)]) ? self::TYPE_ORDER[strtolower($a)] : 99;
            $pb = isset(self::TYPE_ORDER[strtolower($b)]) ? self::TYPE_ORDER[strtolower($b)] : 99;
            if ($pa === $pb) {
                return strcmp($a, $b);
            }
            return ($pa < $pb) ? -1 : 1;
        });
        return $types;
    }

    /**
     * How many of the given entries carry contact details.
     *
     * @param array<int, array<string, mixed>> $entries
     * @param array<int, array{label: string, alias: string}> $contactColumns
     */
    public static function countWithContact(array $entries, array $contactColumns): int
    {
        $count = 0;
        foreach ($entries as $entry) {
            foreach ($contactColumns as $column) {
                if (!empty($entry['values'][$column['alias']])) {
                    $count++;
                    break;
                }
            }
        }
        return $count;
    }

    /**
     * The branch label: the short reference, falling back to the full name.
     *
     * @param array<string, mixed> $row
     */
    private static function branchLabel(array $row): string
    {
        if (!empty($row['branch_ref'])) {
            return (string) $row['branch_ref'];
        }
        if (!empty($row['br_name'])) {
            return (string) $row['br_name'];
        }
        return '';
    }
}
