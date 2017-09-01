<?php

namespace Tests\CubeTools\CubeCommonBundle\FileFormat;

use CubeTools\CubeCommonBundle\FileFormat\ExcelReader;
use Liuggio\ExcelBundle\Factory as ExcelFactory;
use PHPUnit\Framework\TestCase;

class ExcelReaderTest extends TestCase
{
    public function testGetColRow()
    {
        $rSrvc = $this->getService();

        $this->assertSame(array('A', '1'), $rSrvc->getColRow('A1'));
        $this->assertSame(array('BC', '23'), $rSrvc->getColRow(array('BC', '23')));
        //...
        $this->assertSame(array('CD', '8219'), $rSrvc->getColRow('CD8219'));
    }

    public function testGetEndColRow()
    {
        $rSrvc = $this->getService();
        $dummySheet = null;

        $this->assertSame(array('B', '9'), $rSrvc->getEndColRow($dummySheet, 'B9'));
        $this->assertSame(array('BF', '39'), $rSrvc->getEndColRow($dummySheet, array('BF', '39')));
        $this->assertSame(array('BZ', '194'), $rSrvc->getEndColRow($dummySheet, 'BZ194'));

        $sheet = $this->getXlSheet();
        $this->assertSame(array('A', '46'), $rSrvc->getEndColRow($sheet, array(null, '46')), 'auto column');
        $this->assertSame(array('AA', '1'), $rSrvc->getEndColRow($sheet, array('AA', null)), 'auto row');

        $sheet->setCellValue('B5', 8);
        $this->assertSame(array('B', '9'), $rSrvc->getEndColRow($sheet, array(null, '9')), 'auto column with data');
        $this->assertSame(array('P', '5'), $rSrvc->getEndColRow($sheet, array('P', null)), 'auto row with data');
    }

    public function testIterateOverRows()
    {
        $sheet = $this->getXlSheet();
        $rSrvc = $this->getService();

        $noRows = $rSrvc->iterateOverRows($sheet);
        $this->checkRows(array(1 => array('A' => null)), $noRows, 'empty sheet');

        $sheet->setCellValue('A2', 5.1);
        $someRows = $rSrvc->iterateOverRows($sheet, 'A1', 'B3');
        $someExpected = array(
            1 => array('A' => null, 'B' => null),
            array('A' => 5.1, 'B' => null),
            array('A' => null, 'B' => null),
        );
        $this->checkRows($someExpected, $someRows, 'some values'); // TODO fails
    }

    private function getService()
    {
        $rSrvc = new ExcelReader();

        return $rSrvc;
    }

    private function getXlSheet()
    {
        $xlFct = new ExcelFactory();
        $xlo = $xlFct->createPHPExcelObject();

        return $xlo->getSheet(0);
    }

    private function checkRows($expected, $result, $msg = null)
    {
        $this->assertSame($expected, iterator_to_array($result), $msg);
    }
}
