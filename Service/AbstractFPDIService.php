<?php
/**
 * Author: Dung Le Quoc
 * Email: lqdung1992@gmail.com
 * Date: 11/5/2018
 * Time: 2:30 PM
 */

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
