<?php
namespace Plugin\CustomerPdf\Controller;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Eccube\Repository\OrderRepository;
use Eccube\Util\EntityUtil;
use Plugin\CustomerPdf\Service\CustomerPdfService;
use Plugin\CustomerPdf\Util\PdfType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * Class OrderPdfController.
 */
class CustomerPdfController extends AbstractController
{
    /**
     * @param Application $app
     * @param Request $request
     * @param $id
     * @param int $type
     * @return Response
     */
    public function download(Application $app, Request $request, $id, $type = PdfType::ORDER_ESTIMATE)
    {
        $this->isTokenValid($app);

        if (!$app->isGranted('ROLE_USER')) {
            throw new BadRequestHttpException("please login to use this feature!");
        }
        /** @var OrderRepository $orderRepo */
        $orderRepo = $app['eccube.repository.order'];

        $order = $orderRepo->find($id);
        if (EntityUtil::isEmpty($order) || !$order) {
            throw new NotFoundHttpException();
        }

        // サービスの取得
        /* @var CustomerPdfService $service */
        $service = $app['customer_pdf.service'];

        // 購入情報からPDFを作成する
        $status = $service->makePdf($order, $type);

        // 異常終了した場合の処理
        if (!$status) {
            throw new NotFoundResourceException("please check input!");
        }

        // ダウンロードする
        $response = new Response(
            $service->outputPdf($type),
            200,
            array('content-type' => 'application/pdf')
        );

        // レスポンスヘッダーにContent-Dispositionをセットし、ファイル名をreceipt.pdfに指定
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$service->getPdfFileName($type).'"');
//        log_info('OrderPdf download success!', array('Order ID' => implode(',', $this->getIds($request))));

        return $response;
    }

    /**
     * requestから注文番号のID一覧を取得する.
     *
     * @param Request $request
     *
     * @return array $isList
     */
    protected function getIds(Request $request)
    {
        $isList = array();

        // その他メニューのバージョン
        $queryString = $request->getQueryString();

        if (empty($queryString)) {
            return $isList;
        }

        // クエリーをparseする
        // idsX以外はない想定
        parse_str($queryString, $ary);

        foreach ($ary as $key => $val) {
            // キーが一致
            if (preg_match('/^ids\d+$/', $key)) {
                if (!empty($val) && $val == 'on') {
                    $isList[] = intval(str_replace('ids', '', $key));
                }
            }
        }

        // id順にソートする
        sort($isList);

        return $isList;
    }
}
