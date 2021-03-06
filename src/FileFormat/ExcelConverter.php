<?php

namespace CubeTools\CubeCommonBundle\FileFormat;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Helper for exporting html to excel file.
 */
class ExcelConverter
{
    /**
     * Convert html to excel file.
     *
     * @param string|Crawler|\DomNode|Response $html     Html to export
     * @param string|null                      $selector Css selector for part to export (like table or #id), defaults to all
     *
     * @return \PHPExcel converted excel object (file)
     */
    public function fromHtml($html, $selector = null)
    {
        $cr = $htmlStr = null;
        if ($html instanceof Response) {
            if (!$html->isSuccessful()) {
                $msg = 'Request for 1st argument of '.__METHOD__.' must be successful, check isSuccessful() first.';
                throw new \LogicException($msg);
            }
            $html = $html->getContent();
        }
        if (is_string($html)) {
            if (null === $selector) {
                $htmlStr = $html;
            } else {
                $cr = new Crawler();
                $cr->addHtmlContent($html);
            }
        } elseif ($html instanceof \DOMNode) {
            $node = $html;
            if (null !== $selector) {
                $cr = new Crawler();
                $cr->addNode($node);
            } else {
                $htmlStr = $node->ownerDocument->saveHTML($node);
            }
        } elseif ($html instanceof Crawler) {
            $cr = $html;
        } else {
            $type = is_object($html) ? get_class($html) : gettype($html);
            $msg = '1st argument must by string, Crawler, DOMNode or Response, but is '.$type;
            throw new \InvalidArgumentException($msg);
        }

        if (null === $htmlStr) {
            if (null !== $selector) {
                $cr = $cr->filter($selector)->first();
            }
            $node = $cr->getNode(0); // $cr->html() only returns html of children
            if (!$node->ownerDocument) {
                throw new \OutOfBoundsException('node has no ownerDocument, selector probaly returned nothing');
            }
            $htmlStr = $node->ownerDocument->saveHTML($node);
        }

        if (false === strpos($htmlStr, '<body>')) {
            $htmlStr = "<html>\n<head>\n<meta charset=\"UTF-8\">\n</head>\n<body>\n".$htmlStr."</body>\n</html>\n";
        }

        $tmpFile = $this->getTempHtmlFile($htmlStr); // as temporary file because it must have a filename

        return IOFactory::load($tmpFile['path']);
        // tmpfile is deleted automatically
    }

    /**
     * Create response with excel download.
     *
     * @param Spreadsheet $spreadsheet Excel object to create the download from
     * @param string      $filename    filename to give to the download
     * @param string      $format      format of write (like Xlsx)
     * @param string      $contentType mime content type
     *
     * @return \Symfony\Component\HtmlFoundation\Response
     */
    public function createResponse(Spreadsheet $spreadsheet, $filename, $format, $contentType)
    {
        return self::createExcelResponse($spreadsheet, $filename, $format, $contentType);
    }

    /**
     * Create response with excel download.
     *
     * @param Spreadsheet $spreadsheet Excel object to create the download from
     * @param string      $filename    filename to give to the download
     * @param string      $writerType  format of write (like Xlsx)
     * @param string      $contentType mime content type
     *
     * @return \Symfony\Component\HtmlFoundation\Response
     */
    public static function createExcelResponse(Spreadsheet $spreadsheet, $filename, $writerType, $contentType)
    {
        $writer = IOFactory::createWriter($spreadsheet, $writerType);

        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200
        );

        $headers = $response->headers;
        $headers->set('Content-Type', $contentType.'; charset=utf-8');
        $headers->set('Content-Disposition', $headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        ));

        return $response;
    }

    /**
     * Generates a temporary file with the extension ".html".
     *
     * The file is deleted when the returned array is unset.
     *
     * @param string $html html to save to file
     *
     * @return array with filepath in ['path']
     */
    private function getTempHtmlFile($html)
    {
        $tf = tmpfile();
        $tfPath = stream_get_meta_data($tf)['uri'];
        if (!rename($tfPath, $tfPath.'.html')) { // rename open file will not work on windows
            $err = error_get_last();
            throw new \ErrorException('failed to rename file: '.$err['message']);
        }
        $tfPath .= '.html';
        fwrite($tf, $html);

        // return reference as well, because file is deleted when reference is closed
        return ['path' => $tfPath, 'ref' => $tf];
    }
}
