<?php
namespace Tests\CubeTools\CubeCommonBundle\FileFormat;

use CubeTools\CubeCommonBundle\FileFormat\ImageStatistics;
use PHPUnit\Framework\TestCase;

class ImageStatisticsTest extends TestCase
{
    protected $folderWithPdfsForTests = 'tests' . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR;

    /**
     * @var \CubeTools\CubeCommonBundle\FileFormat\ImageStatistics
     */
    protected $testObject;

    public function setUp()
    {
        $this->testObject = new ImageStatistics();
    }

    public function testGetFormatOfPages()
    {
        $this->testObject->setFilename($this->folderWithPdfsForTests . '100x100pxInGray.png');
        $this->assertEquals(array('100px x 100px'), $this->testObject->getFormatOfPages());

        $this->testObject->setFilename($this->folderWithPdfsForTests . '100x100pxInColor.png');
        $this->assertEquals(array('100px x 100px'), $this->testObject->getFormatOfPages());
    }

    public function testGetPagesInColor()
    {
        $this->testObject->setFilename($this->folderWithPdfsForTests . '100x100pxInGray.png');
        $this->assertEquals(0, count($this->testObject->getPagesInColor()));

        $this->testObject->setFilename($this->folderWithPdfsForTests . '100x100pxInColor.png');
        $this->assertEquals(1, count($this->testObject->getPagesInColor()));
    }

    public function testGetNumberOfPages()
    {
        $this->testObject->setFilename($this->folderWithPdfsForTests . '100x100pxInColor.png');
        $this->assertEquals(1, $this->testObject->getNumberOfPages(), 'Wrong number of pages calculated.');
    }
}
