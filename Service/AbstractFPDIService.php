<?php
namespace Plugin\CustomerPdf\Service;

$includePath = get_include_path().';'.__DIR__.'/../vendor/tcpdf';
$includePath = $includePath.';'.__DIR__.'/../vendor/FPDI';
set_include_path($includePath);

require_once __DIR__.'/../vendor/tcpdf/tcpdf.php';
require_once __DIR__.'/../vendor/FPDI/fpdi.php';

/**
 * FPDIのラッパークラス.
 */
abstract class AbstractFPDIService extends \FPDI
{
}
