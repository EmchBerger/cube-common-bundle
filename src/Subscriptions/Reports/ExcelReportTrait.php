<?php
namespace CubeTools\CubeCommonBundle\Subscriptions\Reports;

trait ExcelReportTrait
{
    public function getReportFileExtension()
    {
        return 'xlsx';
    }

    public function getReportFileContentType()
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
}
