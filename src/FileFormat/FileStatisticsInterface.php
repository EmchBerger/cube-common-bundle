<?php
namespace CubeTools\CubeCommonBundle\FileFormat;

/**
 * Interface for classes responsible for file statistics.
 */
interface FileStatisticsInterface
{
    /**
     * Set filename.
     *
     * @param string $filename path of file to be analyzed
     * @return bool true if file is readable, false otherwise
     */
    public function setFilename($filename);

    /**
     * Method checks, if document pages are in color.
     * @return array each element is index of page, which is in color (starts from 0)
     */
    public function getPagesInColor();

    /**
     * Function return number of pages in document.
     * @return int number of pages
     */
    public function getNumberOfPages();

    /**
     * Method returning format of pages.
     * @return array key is page index, value - size format in text (like 'A4')
     */
    public function getFormatOfPages();
}
