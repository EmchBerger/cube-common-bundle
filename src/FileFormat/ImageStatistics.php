<?php
namespace CubeTools\CubeCommonBundle\FileFormat;

/**
 * Class handling statistics for image files (based on statistics for PDF files).
 */
class ImageStatistics extends PdfStatistics
{
    /**
     * Method identifying image resolution. One image is one page.
     *
     * @param string $pageSize size in 1x1 format
     * @param bool $returnPageSizeInMmIfNotMatched currently not implemented (default parameter)
     *
     * @return string image size in px (for example "100px x 100px")
     */
    public function identifyPageSize($pageSize, $returnPageSizeInMmIfNotMatched = true)
    {
        $pageSizeArray = explode('x', $pageSize);

        return sprintf('%dpx x %dpx',
                $pageSizeArray[0],
                $pageSizeArray[1]
                );
    }
}
