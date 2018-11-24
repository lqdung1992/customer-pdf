<?php

namespace Plugin\CustomerPdf\Service;

use Eccube\Application;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Help;
use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;
use Plugin\CustomerPdf\Util\PdfType;

/**
 * Class OrderPdfService.
 * Do export pdf function.
 */
class CustomerPdfService extends AbstractFPDIService
{
    // ====================================
    // 定数宣言
    // ====================================
    /** OrderPdf用リポジトリ名 */
    const REPOSITORY_ORDER_PDF = 'eccube.repository.order';

    const MONEY_PREFIX = '¥ ';

    /** ダウンロードするPDFファイルのデフォルト名 */
    const DEFAULT_PDF_FILE_NAME = 'nouhinsyo.pdf';

    /** FONT ゴシック */
    const FONT_GOTHIC = 'kozgopromedium';
    /** FONT 明朝 */
    const FONT_SJIS = 'kozminproregular';

    /** @var Application */
    public $app;

    /** @var BaseInfo */
    public $BaseInfo;

    /** 最後に処理した注文番号 @var string */
    private $orderId = null;

    // --------------------------------------
    // Font情報のバックアップデータ
    /** @var string フォント名 */
    private $bakFontFamily;
    /** @var string フォントスタイル */
    private $bakFontStyle;
    /** @var string フォントサイズ */
    private $bakFontSize;
    // --------------------------------------

    // lfTextのoffset
    private $baseOffsetX = 0;
    private $baseOffsetY = -4;

    /** 発行日 @var string */
    private $issueDate;

    private $type;

    /** @var array */
    private $headerData;

    private $defaultFontSize = 12;
    private $headerFontSize = 10;

    /**
     * コンストラクタ.
     *
     * @param object $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->BaseInfo = $app['eccube.repository.base_info']->get();
        parent::__construct();

        // Fontの設定しておかないと文字化けを起こす
        $this->SetFont(self::FONT_SJIS);

        // PDFの余白(上左右)を設定
        $this->SetMargins(15, 20);

        // ヘッダーの出力を無効化
        $this->setPrintHeader(true);
        $this->setHeaderMargin(11);
        $this->setHeaderFont(array(self::FONT_SJIS, '', $this->headerFontSize));
        // フッターの出力を無効化
        $this->setPrintFooter(false);
    }

    /**
     * @param Order $order
     * @param $type
     * @return bool
     */
    public function makePdf(Order $order, $type)
    {
        $now = new \DateTime();
        $orderDate = $order->getCreateDate()->format('Y年m月d日');
        if ($order->getOrderDate()) {
            $orderDate = $order->getOrderDate()->format('Y年m月d日');
        }
        $this->issueDate = $orderDate ? $orderDate : $now->format('Y年m月d日');
        $this->orderId = $order->getId();
        $this->type = $type;

        $templateName = $this->getTemplateName($type);
        $templateFilePath = __DIR__ . '/../Resource/template/pdf/' . $templateName;
        $this->setSourceFile($templateFilePath);

        // set header data
        switch ($type) {
            case PdfType::HISTORY_DELIVERY:
                $this->headerData = array(
                    'order date' => ': ' . $orderDate,
                    'order id' => ': ' . $order->getId(),
                    'todo' => ': todo todo'
                );
                break;
            case PdfType::HISTORY_INVOICE:
                $this->headerData = array(
                    'order date' => $orderDate,
                    'order id' => $this->orderId,
                );
                break;
        }
        // add new page + header
        $this->addPdfPage();

        switch ($this->type) {
            case PdfType::HISTORY_INVOICE:
                $this->renderHistoryInvoice($order);
                break;
            case PdfType::ORDER_ESTIMATE:
            case PdfType::HISTORY_DELIVERY:
                // render data of order
                $this->renderOrderData($order);
                // render data detail of order
                $this->renderOrderDetailData($order);
                // render message customer input
                $this->renderMessage($order->getMessage());
                break;

            case PdfType::ORDER_INVOICE:
                $this->renderOrderInvoice($order);
                break;
        }

        return true;
    }

    /**
     * PDFファイルを出力する.
     *
     * @return string|mixed
     */
    public function outputPdf($type)
    {
        return $this->Output($this->getPdfFileName($type), 'S');
    }

    /**
     * @param $type
     * @return string
     */
    public function getPdfFileName($type)
    {
        if ($this->orderId) {
            return $this->getDownloadFileName($type) . '-' . $this->orderId . '.pdf';
        }
        return $this->getDownloadFileName($type) . '.pdf';
    }

    public function Header()
    {
        switch ($this->type) {
            case PdfType::HISTORY_DELIVERY:
                foreach ($this->headerData as $key => $headerDatum) {
                    $this->SetX(140);
                    $this->MultiCell(20, 0, $key, 0, null, false, 0);
                    $this->MultiCell(0, 0, $headerDatum, 0, null, false, 1);
                }
                break;
            case PdfType::HISTORY_INVOICE:
                $this->setHeaderMargin(9);
                $this->setHeaderFont(array(self::FONT_SJIS, '', $this->headerFontSize + 1));
                foreach ($this->headerData as $headerDatum) {
                    $this->SetX(164);
                    $this->MultiCell(0, 6, $headerDatum, 0, null, false, 1);
                }
                break;
            case PdfType::ORDER_ESTIMATE:
                $this->Cell(0, 0, $this->issueDate, 0, 0, 'R');
                break;
        }
    }

    protected function renderOrderInvoice(Order $order)
    {
        $this->backupFont();
        $font = $this->defaultFontSize;
        $style = 'B';

        $width = array(
            10, 60, 20, 8, 50
        );

        $customer = $order->getName01().$order->getName02();
        $arrOrderDetail = array(
            $order->getOrderDetails()->toArray()
        );
        if ($order->getOrderDetails()->count() > 10) {
            $arrOrderDetail = array_chunk($order->getOrderDetails()->toArray(), 10);
        }

        foreach ($arrOrderDetail as $keyChunk => $chunk) {
            $this->SetFont('', $style, $font);
            $this->setBasePosition(0, 113);
            /** @var OrderDetail $orderDetail */
            foreach ($chunk as $orderDetail) {
                $row = array(
                    '',
                    $orderDetail->getId(),
                    number_format($orderDetail->getQuantity()),
                    '',
                    $customer
                );
                $i = 0;
                $h = 11.5;
                $cellHeight = 0;
                foreach ($row as $key => $col) {
                    $align = 'L';
                    if ($key == 2) {
                        $align = 'R';
                    }

                    if ($h >= $cellHeight) {
                        $cellHeight = $h;
                    }

                    // (0: 右へ移動(既定)/1: 次の行へ移動/2: 下へ移動)
                    $ln = ($i == (count($row) - 1)) ? 1 : 0;

                    $this->MultiCell(
                        $width[$i],             // セル幅
                        $cellHeight,        // セルの最小の高さ
                        $col,               // 文字列
                        0,                  // 境界線の描画方法を指定
                        $align,             // テキストの整列
                        false,              // 背景の塗つぶし指定
                        $ln                 // 出力後のカーソルの移動方法
                    );
                    $h = $this->getLastH();

                    ++$i;
                }
            }

            if ($keyChunk != (count($arrOrderDetail) - 1)) {
                $this->addPdfPage();
            }
        }


        $this->restoreFont();
    }

    protected function renderHistoryInvoice(Order $order)
    {
        $this->backupFont();
        $font = $this->defaultFontSize + 6;
        $style = 'B';
        $this->SetFont(self::FONT_SJIS, $style, $font);

        // set customer name
        $customerName = $order->getName01() . $order->getName02();
        $this->lfText(20, 29, $customerName, $font, $style);

        $totalPayment = number_format($order->getPaymentTotal());
        $this->lfText(73, 53, $totalPayment, $font + 7, $style);

        $subTotal = number_format($order->getPaymentTotal() - $order->getTax());
        $this->lfText(47, 121, $subTotal, $font, $style);

        $this->lfText(47, 131, number_format($order->getTax()), $font, $style);

        $this->restoreFont();
    }

    /**
     * 購入者情報を設定する.
     *
     * @param Order $Order
     */
    protected function renderOrderData(Order $Order)
    {
        $this->setBasePosition();
        $this->backupFont();

        $defaultFontSize = $this->defaultFontSize;

        $text = $Order->getZip01() . ' - ' . $Order->getZip02();
        $this->lfText(20, 34, $text, $defaultFontSize);

        $text = $Order->getPref() . $Order->getAddr01();
        $this->lfText(20, 39, $text, $defaultFontSize);
        $this->lfText(20, 44, $Order->getAddr02(), $defaultFontSize); //購入者住所2

        $this->lfText(20, 49, $Order->getTel01() . '-' . $Order->getTel02() . '-' . $Order->getTel03(), $defaultFontSize); //購入者住所2

        $text = $Order->getName01() . '　' . $Order->getName02() . '　様';
        $this->lfText(20, 55, $text, $defaultFontSize + 2);


//        $this->SetFont(self::FONT_SJIS, '', $defaultFontSize);
        // total
        $this->SetFont(self::FONT_SJIS, 'B', $defaultFontSize + 3);
        $paymentTotalText = self::MONEY_PREFIX . number_format($Order->getPaymentTotal());
        $this->lfText(90, 77, $paymentTotalText, $defaultFontSize + 3, 'B');

        $this->restoreFont();
    }

    /**
     * 作成するPDFのテンプレートファイルを指定する.
     */
    protected function addPdfPage()
    {
        // ページを追加
        $this->AddPage();

        // テンプレートに使うテンプレートファイルのページ番号を取得
        $tplIdx = $this->importPage(1);

        // テンプレートに使うテンプレートファイルのページ番号を指定
        $this->useTemplate($tplIdx, null, null, null, null, true);

        // set header by manual because it overdrive by template
        if ($this->print_header) {
            $this->setHeader();
        }
    }

    /**
     * @param string $message
     */
    protected function renderMessage($message)
    {
        // フォント情報のバックアップ
        $this->backupFont();

        $this->SetFont(self::FONT_SJIS, '', $this->defaultFontSize + 3);

        $this->Ln();
        // rtrimを行う
        $text = preg_replace('/\s+$/us', '', $message);
        $this->MultiCell(0, 0, $text, 0, null, false, 0);

        // フォント情報の復元
        $this->restoreFont();
    }

    /**
     * 購入商品詳細情報を設定する.
     *
     * @param Order $Order
     */
    protected function renderOrderDetailData(Order $Order)
    {
        $arrOrder = array();
        $i = 0;
        /* @var OrderDetail $OrderDetail */
        foreach ($Order->getOrderDetails() as $OrderDetail) {
            // class categoryの生成
            $classCategory = '';
            if ($OrderDetail->getClassCategoryName1()) {
                $classCategory .= ' [ ' . $OrderDetail->getClassCategoryName1();
                if ($OrderDetail->getClassCategoryName2() == '') {
                    $classCategory .= ' ]';
                } else {
                    $classCategory .= ' * ' . $OrderDetail->getClassCategoryName2() . ' ]';
                }
            }
            // 税
//            $tax = $this->app['eccube.service.tax_rule']->calcTax($OrderDetail->getPrice(), $OrderDetail->getTaxRate(), $OrderDetail->getTaxRule());
            $OrderDetail->setPriceIncTax($OrderDetail->getPrice());

            // todo: order code
            $arrOrder[$i][0] = $OrderDetail->getId();
            // product
            $productName = $OrderDetail->getProductName();
            if ($OrderDetail->getProductCode()) {
                $productName .= ' / ' . $OrderDetail->getProductCode();
            }
            if ($classCategory) {
                $productName .= ' / ' . $classCategory;
            }
            $arrOrder[$i][1] = $productName;
            // 税込金額（単価）
            $arrOrder[$i][2] = number_format($OrderDetail->getPrice());
            // 購入数量
            $arrOrder[$i][3] = number_format($OrderDetail->getQuantity());
            // 小計（商品毎）
            $arrOrder[$i][4] = number_format($OrderDetail->getTotalPrice());

            ++$i;
        }

        $arrOrder[$i][0] = '';
        $arrOrder[$i][1] = '送料';
        $arrOrder[$i][2] = number_format($Order->getDeliveryFeeTotal());
        $arrOrder[$i][3] = 1;
        $arrOrder[$i][4] = number_format($Order->getDeliveryFeeTotal());

        if ($Order->getCharge() > 0) {
            ++$i;
            $arrOrder[$i][0] = '';
            $arrOrder[$i][1] = '手数料';
            $arrOrder[$i][2] = number_format($Order->getCharge());
            $arrOrder[$i][3] = 1;
            $arrOrder[$i][4] = number_format($Order->getCharge());
        }

        $total['subtotal'] = number_format($Order->getPaymentTotal() - $Order->getTax());
        $total['tax'] = number_format($Order->getTax());
        $total['total'] = number_format($Order->getPaymentTotal());

        $widthCell = array(36, 80, 19, 20, 28);
        $this->setFancyTable($total, $arrOrder, $widthCell);
    }

    /**
     * PDFへのテキスト書き込み
     *
     * @param int $x X座標
     * @param int $y Y座標
     * @param string $text テキスト
     * @param int $size フォントサイズ
     * @param string $style フォントスタイル
     */
    protected function lfText($x, $y, $text, $size = 0, $style = '')
    {
        // 退避
        $bakFontStyle = $this->FontStyle;
        $bakFontSize = $this->FontSizePt;

        $this->SetFont('', $style, $size);
        $this->Text($x + $this->baseOffsetX, $y + $this->baseOffsetY, $text);

        // 復元
        $this->SetFont('', $bakFontStyle, $bakFontSize);
    }

    /**
     * @param $total
     * @param $data
     * @param $w
     */
    protected function setFancyTable($total, $data, $w)
    {
        // フォント情報のバックアップ
        $this->backupFont();
        $this->setBasePosition(0, 100);

        $this->SetLineWidth(0.6);

        $this->SetFont(self::FONT_SJIS, 'B', $this->defaultFontSize);
        $this->SetFont('', 'B');

        foreach ($data as $rowKey => $row) {
            $i = 0;
            $h = 14;
            $cellHeight = 0;
            foreach ($row as $key => $col) {
                $align = 'C';
                if ($key == 1 && !empty($row[0])) {
                    $align = 'L';
                }

                if ($h >= $cellHeight) {
                    $cellHeight = $h;
                }

                // (0: 右へ移動(既定)/1: 次の行へ移動/2: 下へ移動)
                $ln = ($i == (count($row) - 1)) ? 1 : 0;

                $this->MultiCell(
                    $w[$i],             // セル幅
                    $cellHeight,        // セルの最小の高さ
                    $col,               // 文字列
                    0,                  // 境界線の描画方法を指定
                    $align,             // テキストの整列
                    false,              // 背景の塗つぶし指定
                    $ln                 // 出力後のカーソルの移動方法
                );
                $h = $this->getLastH();

                ++$i;
            }
        }
//
//        $rowNum = count($data);
//
//        if ($rowNum > 9) {
//            $tmp = 0;
//        } else {
//            $tmp = 9 - $rowNum;
//        }
//        for ($in = 0; $in < $tmp; $in++) {
//            $this->Ln();
//        }
//        $this->Ln(5);

        $this->setBasePosition(15, 222);
        $h = 9;
        foreach ($total as $item) {
            $this->Cell(0, $h, $item, 0, 1, 'R');
            $h = $this->getLastH();
        }

        // フォント情報の復元
        $this->restoreFont();
    }

    /**
     * 基準座標を設定する.
     *
     * @param int $x
     * @param int $y
     */
    protected function setBasePosition($x = null, $y = null)
    {
        // 現在のマージンを取得する
        $result = $this->getMargins();

        // 基準座標を指定する
        $actualX = is_null($x) ? $result['left'] : $x;
        $this->SetX($actualX);
        $actualY = is_null($y) ? $result['top'] : $y;
        $this->SetY($actualY);
    }

    /**
     * Font情報のバックアップ.
     */
    protected function backupFont()
    {
        // フォント情報のバックアップ
        $this->bakFontFamily = $this->FontFamily;
        $this->bakFontStyle = $this->FontStyle;
        $this->bakFontSize = $this->FontSizePt;
    }

    /**
     * Font情報の復元.
     */
    protected function restoreFont()
    {
        $this->SetFont($this->bakFontFamily, $this->bakFontStyle, $this->bakFontSize);
    }

    /**
     * @param $type
     * @return string
     */
    protected function getTemplateName($type)
    {
        $templateName = 'shopping_est.pdf';
        switch ($type) {
            case PdfType::ORDER_INVOICE:
                $templateName = 'shopping_invoice.pdf';
                break;
            case PdfType::HISTORY_DELIVERY:
                $templateName = 'history_delivery.pdf';
                break;
            case PdfType::HISTORY_INVOICE:
                $templateName = 'history_invoice.pdf';
                break;
        }
        return $templateName;
    }

    /**
     * @param $type
     * @return string
     */
    protected function getDownloadFileName($type)
    {
        $templateName = 'shopping_estimate';
        switch ($type) {
            case PdfType::ORDER_INVOICE:
                $templateName = 'shopping_invoice';
                break;
            case PdfType::HISTORY_DELIVERY:
                $templateName = 'history_delivery';
                break;
            case PdfType::HISTORY_INVOICE:
                $templateName = 'history_invoice';
                break;
        }
        return $templateName;
    }
}
