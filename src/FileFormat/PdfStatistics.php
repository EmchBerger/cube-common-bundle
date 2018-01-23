<?php
namespace CubeTools\CubeCommonBundle\FileFormat;

/**
 * Class handling statistics for PDF files.
 */
class PdfStatistics
{
    /**
     * Because 72 pixels make one inch (~254 mm), this parameter shows, how big in mm is one pixel (for 72 ppi).
     */
    const RATIO_PIXEL_TO_MM = 0.352941176;

    /**
     * Suffix for rotated pages (can be empty).
     */
    const PAGE_ROTATED_SUFFIX = '';

    /**
     * Size of 4A0 format in 72 ppi
     */
    const PAGE_FORMAT_4A0_SIZE = '4768x6741';

    /**
     * Size of 2A0 format in 72 ppi
     */
    const PAGE_FORMAT_2A0_SIZE = '3370x4768';

    /**
     * Size of A0 format in 72 ppi
     */
    const PAGE_FORMAT_A0_SIZE = '2384x3370';

    /**
     * Size of A1 format in 72 ppi
     */
    const PAGE_FORMAT_A1_SIZE = '1684x2384';

    /**
     * Size of A2 format in 72 ppi
     */
    const PAGE_FORMAT_A2_SIZE = '1191x1684';

    /**
     * Size of A3 format in 72 ppi
     */
    const PAGE_FORMAT_A3_SIZE = '842x1191';

    /**
     * Size of A4 format in 72 ppi
     */
    const PAGE_FORMAT_A4_SIZE = '595x842';

    /**
     * Size of A5 format in 72 ppi
     */
    const PAGE_FORMAT_A5_SIZE = '420x595';

    /**
     * Size of A6 format in 72 ppi
     */
    const PAGE_FORMAT_A6_SIZE = '298x420';

    /**
     * Size of A7 format in 72 ppi
     */
    const PAGE_FORMAT_A7_SIZE = '210x298';

    /**
     * Size of A8 format in 72 ppi
     */
    const PAGE_FORMAT_A8_SIZE = '147x210';

    /**
     * Size of A9 format in 72 ppi
     */
    const PAGE_FORMAT_A9_SIZE = '105x147	';

    /**
     * Size of A10 format in 72 ppi
     */
    const PAGE_FORMAT_A10_SIZE = '74x105';

    /**
     * @var array key is size in 72 ppi and value - name of format
     */
    protected $pageFormats = array(
        self::PAGE_FORMAT_4A0_SIZE => '4A0',
        self::PAGE_FORMAT_2A0_SIZE => '2A0',
        self::PAGE_FORMAT_A0_SIZE => 'A0',
        self::PAGE_FORMAT_A1_SIZE => 'A1',
        self::PAGE_FORMAT_A2_SIZE => 'A2',
        self::PAGE_FORMAT_A3_SIZE => 'A3',
        self::PAGE_FORMAT_A4_SIZE => 'A4',
        self::PAGE_FORMAT_A5_SIZE => 'A5',
        self::PAGE_FORMAT_A6_SIZE => 'A6',
        self::PAGE_FORMAT_A7_SIZE => 'A7',
        self::PAGE_FORMAT_A8_SIZE => 'A8',
        self::PAGE_FORMAT_A9_SIZE => 'A9',
        self::PAGE_FORMAT_A10_SIZE => 'A10',
    );

    /**
     * @var \Imagick Imagick object
     */
    protected $imagickDocument;

    /**
     * Constructor checks, if Imagick extension is available. If yes, instance is created.
     * @throws \RuntimeException if Imagick extension is not available
     */
    public function __construct()
    {
        if (!extension_loaded('imagick')) {
            new \RuntimeException('Imagick php extension is needed to perform PDF statistics.');
        }

        $this->imagickDocument = new \Imagick();
    }

    /**
     * Set filename.
     *
     * @param string $filename path of pdf file to be analysed
     *
     */
    public function setFilename($filename)
    {
        $this->imagickDocument->clear();
        $this->imagickDocument->setResolution(72, 72);
        $this->imagickDocument->readImage($filename);
        $this->imagickDocument->setImageUnits(\Imagick::RESOLUTION_PIXELSPERINCH);
    }

    /**
     * Method checks, if pdf document pages are in color.
     * @return array each element is index of page, which is in color (starts from 0)
     */
    public function getPagesInColor()
    {
        $pagesInColor = array();
        $pageIterator = 0;

        foreach ($this->imagickDocument as $singlePage) {
            $histogram = $singlePage->getImageHistogram();

            foreach ($histogram as $pixel) {
                if (!(
                    ($pixel->getColorValue(\Imagick::COLOR_RED)==$pixel->getColorValue(\Imagick::COLOR_GREEN)) &&
                    ($pixel->getColorValue(\Imagick::COLOR_GREEN)==$pixel->getColorValue(\Imagick::COLOR_BLUE))
                )) {    // grayscale is only, when rgb values are the same
                    $pagesInColor[] = $pageIterator;
                    break;
                }
            }

            $pageIterator++;
        }

        return $pagesInColor;
    }

    /**
     * Function return number of pages in pdf document.
     * @return int number of pages
     */
    public function getNumberOfPages()
    {
        return $this->imagickDocument->getNumberImages();
    }

    /**
     * Method identifying page format.
     *
     * @param string $pageSize page size in 1x1 format
     * @param bool $returnPageSizeInMmIfNotMatched if set to false and page size does not fit then function return false (default true)
     *
     * @return string|bool name of format; if it does not match: page size or false depending on $returnPageSizeInMmIfNotMatched flag
     */
    public function identifyPageSize($pageSize, $returnPageSizeInMmIfNotMatched = true)
    {
        if (!isset($this->pageFormats[$pageSize])) {
            /* format not found, try to rotate it */
            $pageSizeArray = explode('x', $pageSize);
            $pageSizeRotated = $pageSizeArray[1] . 'x' . $pageSizeArray[0];

            if (isset($this->pageFormats[$pageSizeRotated])) {
                $returnPageSize = $this->pageFormats[$pageSizeRotated] . static::PAGE_ROTATED_SUFFIX;
            } else {
                if ($returnPageSizeInMmIfNotMatched) {
                    $returnPageSize = sprintf('%dmm x %dmm',
                            floor($pageSizeArray[0]*self::RATIO_PIXEL_TO_MM),
                            floor($pageSizeArray[1]*self::RATIO_PIXEL_TO_MM)
                        );
                } else {
                    $returnPageSize = false;
                }
            }
        } else {
            $returnPageSize = $this->pageFormats[$pageSize];
        }

        return $returnPageSize;
    }

    /**
     * Method returning format of pages.
     * @return array key is page index, value - size format in text (like 'A4')
     */
    public function getFormatOfPages()
    {
        $pageFormats = array();
        $this->imagickDocument->getImageResolution();
        foreach ($this->imagickDocument as $singlePage) {
            $pageSize = $singlePage->getImageWidth() . 'x' . $singlePage->getImageHeight();
            $pageFormats[] = $this->identifyPageSize($pageSize);
        }

        return $pageFormats;
    }
}
