<?php
namespace CubeTools\CubeCommonBundle\Subscriptions\Reports;

use CubeTools\CubeCommonBundle\Subscriptions\Conditions\AbstractCondition;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractReport
{
    /**
     * name of folder, where generated reports are temporarily stored
     */
    const PATH_REPORTS_TMP_FOLDER = '/tmp/TestManagerReports';

    /**
     * Key with report title in reportArray
     */
    const KEY_REPORT_TITLE = 'title';

    /**
     * Key with url in reportArray
     */
    const KEY_REPORT_URL = 'url';

    /**
     * Key with resource in reportArray
     */
    const KEY_REPORT_PATH = 'path';

    /**
     * Key with file name
     */
    const KEY_REPORT_FILE_NAME = 'fileName';

    /**
     * Key with file content type
     */
    const KEY_REPORT_FILE_CONTENT_TYPE = 'fileContentType';

    /**
     * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface 
     */
    protected $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Method preparing data for reports.
     * @param array $conditionOutputData output data from condition
     * @return array each element is describing one report
     */
    public function getReportArray($conditionOutputData) {
        $reportOutputArray = array();

        foreach ($conditionOutputData[AbstractCondition::KEY_ELEMENTS] as $conditionOutputElement) {
            $reportOutputElement = array();
            $reportOutputElement[self::KEY_REPORT_TITLE] = $this->getReportTitle($conditionOutputElement);
            $reportOutputElement[self::KEY_REPORT_URL] = $this->getReportUrl($conditionOutputElement);
            $reportOutputElement[self::KEY_REPORT_PATH] = $this->getReportPath($conditionOutputElement);
            $reportOutputElement[self::KEY_REPORT_FILE_NAME] = sprintf('%s.%s',
                    $this->getReportFileName($conditionOutputElement), $this->getReportFileExtension()
            );
            $reportOutputElement[self::KEY_REPORT_FILE_CONTENT_TYPE] = $this->getReportFileContentType();

            $reportOutputArray[] = $reportOutputElement;
        }

        return $reportOutputArray;
    }

    abstract protected function getReportTitle($conditionOutputElement);

    abstract protected function getReportUrl($conditionOutputElement);

    abstract protected function getReportPath($conditionOutputElement);

    abstract protected function getReportFileName($conditionOutputElement);

    abstract protected function getReportFileExtension();

    abstract protected function getReportFileContentType();
}
