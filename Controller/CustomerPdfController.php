<?php
/**
 * Author: Dung Le Quoc
 * Email: lqdung1992@gmail.com
 * Date: 11/5/2018
 * Time: 2:30 PM
 */


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
     * @throws Application\AuthenticationCredentialsNotFoundException
     */
    public function download(Application $app, Request $request, $id, $type = PdfType::ORDER_ESTIMATE)
    {
        $this->isTokenValid($app);

        if (!$app->isGranted('ROLE_USER')) {
            throw new BadRequestHttpException("Please login to use this feature!");
        }
        /** @var OrderRepository $orderRepo */
        $orderRepo = $app['eccube.repository.order'];

        $order = $orderRepo->find($id);
        if (EntityUtil::isEmpty($order) || !$order) {
            throw new NotFoundHttpException();
        }

        /* @var CustomerPdfService $service */
        $service = $app['customer_pdf.service'];

        $status = $service->makePdf($order, $type);

        if (!$status) {
            throw new NotFoundResourceException("please check input!");
        }

        $response = new Response(
            $service->outputPdf($type),
            200,
            array('content-type' => 'application/pdf')
        );

        $response->headers->set('Content-Disposition', 'attachment; filename="'.$service->getPdfFileName($type).'"');

        return $response;
    }
}
