<?php

namespace CubeTools\CubeCommonBundle\FileFormat;

use PHPExcel_Worksheet;

/**
 * Helper for importing data form spreadsheet file.
 */
class ExcelReader
{
    /**
     * Iterates over all rows of an excel worksheet.
     *
     * @param \PHPExcel_Worksheet  $xlSheet
     * @param string|string[]      $startCell
     * @param string|string[]|null $endCell
     *
     * @return string[][]
     */
    public static function iterateOverRows(PHPExcel_Worksheet $xlSheet, $startCell = 'A1', $endCell = null)
    {
        list($endCol, $endRow) = self::getEndColRow($xlSheet, $endCell);
        list($startCol, $startRow) = self::getColRow($startCell);

        $colsTempl = array();
        for ($col = $startCol; $col <= $endCol; ++$col) {
            $colsTempl[$col] = null;
        }
        for ($row = $startRow; $row <= $endRow; ++$row) {
            $oneRow = $colsTempl;
            for ($col = $startCol; $col <= $endCol; ++$col) {
                $oneRow[$col] = $xlSheet->getCell($col.$row)->getValue();
            }

            yield $row => $oneRow;
        }
    }

    /**
     * Get Column and Row separated.
     *
     * @param string|string[] $cell
     *
     * @return string[] is ['col', 'row']
     *
     * @throws \InvalidArgumentException
     */
    public static function getColRow($cell)
    {
        if (is_string($cell)) {
            if (strlen($cell) < 2) {
                $msg = 'when $cell is string, it must at least have size 2 (like "B7"), but it is "'.$cell.'"';
                throw new \InvalidArgumentException($msg);
            }

            if (is_numeric($cell[0])) {
                throw new \InvalidArgumentException('$cell as string must start non-numeric');
            }
            if (is_numeric($cell[1])) {
                return array($cell[0], substr($cell, 1));
            }
            $matches = array();
            if (preg_match('/[0-9]/', $cell, $matches, PREG_OFFSET_CAPTURE)) {
                $numberOffset = $matches[0][1];

                return array(substr($cell, 0, $numberOffset), substr($cell, $numberOffset));
            } else {
                throw new \InvalidArgumentException('$cell as string must containt a numeric as row, but is '.$cell);
            }
        } elseif (!is_array($cell) || $cell instanceof \ArrayAccess) {
            if (is_object($cell)) {
                $type = get_class($cell);
            } else {
                $type = gettype($cell);
            }
            throw new \InvalidArgumentException('$cell must be array or string, but it is '.$type);
        }
        if (2 !== count($cell)) {
            throw new \InvalidArgumentException('when $cell is an array, it must have size 2, bit it is size '.count($cell));
        }

        return $cell;
    }

    public static function getEndColRow($xlSheet, $endCell)
    {
        if (null === $endCell) {
            $endCol = null;
            $endRow = null;
        } else {
            list($endCol, $endRow) = self::getColRow($endCell);
        }

        if (null === $endCol) {
            $endCol = (string) $xlSheet->getHighestDataColumn();
        }
        if (null === $endRow) {
            $endRow = (string) $xlSheet->getHighestDataRow();
        }

        return array($endCol, $endRow);
    }
}
