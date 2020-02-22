<?php
namespace Tests\CubeTools\CubeCommonBundle\FileFormat;

use CubeTools\CubeCommonBundle\FileFormat\PdfStatistics;
use PHPUnit\Framework\TestCase;

class PdfStatisticsTest extends TestCase
{
    protected $folderWithPdfsForTests = 'tests' . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;

    /**
     * @var \CubeTools\CubeCommonBundle\FileFormat\PdfStatistics
     */
    protected $testObject;

    public function setUp()
    {
        $this->testObject = new PdfStatistics();
    }

    public function testSetFilename()
    {
        $this->setFilenameSuccessful('oneA4grayscale.pdf');
        $this->assertFalse($this->testObject->setFilename($this->folderWithPdfsForTests . 'wrongContent.jpg'));
        $this->assertFalse($this->testObject->setFilename($this->folderWithPdfsForTests . 'fileNotExists.pdf'));
        $this->assertFalse($this->testObject->setFilename(__FILE__)); // file extension is not handled
    }

    public function testGetFormatOfPages()
    {
        $this->setFilenameSuccessful('oneA4grayscale.pdf');
        $this->assertEquals(array('A4'), $this->testObject->getFormatOfPages());

        $this->setFilenameSuccessful('twoA4firstGrayscaleSecondColor.pdf');
        $this->assertEquals(array('A4', 'A4'), $this->testObject->getFormatOfPages());
    }

    public function testGetPagesInColor()
    {
        $this->setFilenameSuccessful('oneA4grayscale.pdf');
        $this->assertEquals(0, count($this->testObject->getPagesInColor()));

        $this->setFilenameSuccessful('twoA4firstGrayscaleSecondColor.pdf');
        $this->assertEquals(array(1), $this->testObject->getPagesInColor());
    }

    public function testGetNumberOfPages()
    {
        $this->setFilenameSuccessful('oneA4grayscale.pdf');
        $this->assertEquals(1, $this->testObject->getNumberOfPages(), 'Wrong number of pages calculated.');

        $this->setFilenameSuccessful('twoA4firstGrayscaleSecondColor.pdf');
        $this->assertEquals(2, $this->testObject->getNumberOfPages(), 'Wrong number of pages calculated.');
    }

    private function setFilenameSuccessful($fileName)
    {
        $this->assertTrue($this->testObject->setFilename($this->folderWithPdfsForTests.$fileName), 'set filename: '.$fileName);
    }
}
