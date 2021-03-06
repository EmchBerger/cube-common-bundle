<?php

namespace Tests\CubeTools\CubeCommonBundle\FileFormat;

use CubeTools\CubeCommonBundle\FileFormat\ExcelConverter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class ExcelConverterTest extends TestCase
{
    /**
     * @dataProvider provideHtmlData
     */
    public function testExportAll($data)
    {
        $h2e = $this->getService();
        $xlo = $h2e->fromHtml($data);
        $this->assertInstanceOf(Spreadsheet::class, $xlo);
    }

    public function testInvalidArg()
    {
        $h2e = $this->getService();
        $data = new self();
        $this->expectException(\InvalidArgumentException::class);
        $h2e->fromHtml($data);
    }

    /**
     * @dataProvider provideHtmlData
     */
    public function testExportPart($data)
    {
        $selector = '#tst';
        $h2e = $this->getService();
        try {
            $xlo = $h2e->fromHtml($data, $selector);
        } catch (\RuntimeException $e) {
            if (false === strpos($e->getMessage(), 'Symfony CssSelector')) {
                throw $e;
            }
            // CssSelector not installed, but enough code checked
            return;
        }
        $this->assertInstanceOf(Spreadsheet::class, $xlo);
    }

    /**
     * @depends testExportAll
     */
    public function testCreateResponse()
    {
        $fileName = 'anyName.xlsx';
        $format = 'Xlsx';
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $h2e = $this->getService();
        $xlo = $h2e->fromHtml('<table><tr><th>r1</th><td>dö£</td></tr></table>');
        $r = $h2e->createResponse($xlo, $fileName, $format, $contentType);
        $this->assertInstanceOf(Response::class, $r);
    }

    public static function provideHtmlData()
    {
        $c = new Crawler();
        $c->addHtmlContent('<p>a</p><div id="tst">3</div><span>q</span>');

        return [
            ['string' => '<table><tr><td>1</td><td>x</td></tr><tr id="tst"><td>2</td></tr></table>'],
            ['node' => $c->getNode(0)],
            ['Crawler' => $c],
        ];
    }

    private function getService()
    {
        $es = new ExcelConverter();

        return $es;
    }
}
