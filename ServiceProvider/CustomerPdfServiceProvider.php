<?php
/*
 * This file is part of the Order Pdf plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\CustomerPdf\ServiceProvider;

use Eccube\Common\Constant;
use Plugin\CustomerPdf\Service\CustomerPdfService;
use Plugin\CustomerPdf\Util\Version;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

// include log functions (for 3.0.0 - 3.0.11)
require_once __DIR__.'/../log.php';

/**
 * Class OrderPdfServiceProvider.
 */
class CustomerPdfServiceProvider implements ServiceProviderInterface
{
    /**
     * Register service function.
     *
     * @param BaseApplication $app
     */
    public function register(BaseApplication $app)
    {
        // ============================================================
        // コントローラの登録
        // ============================================================
        // 管理画面定義
        $front = $app['controllers_factory'];
        // 強制SSL
        if ($app['config']['force_ssl'] == Constant::ENABLED) {
            $front->requireHttps();
        }

        // PDFファイルダウンロード
        $front->post('/plugin/pdf/download/{id}/{type}', '\\Plugin\\CustomerPdf\\Controller\\CustomerPdfController::download')
            ->assert('id', '\d+')
            ->assert('type', '\d+')
            ->bind('plugin_customer_pdf_download');

        $app->mount('/', $front);

        // -----------------------------
        // サービスの登録
        // -----------------------------
        // 帳票作成
        $app['customer_pdf.service'] = $app->share(function () use ($app) {
            return new CustomerPdfService($app);
        });

        // ============================================================
        // メッセージ登録
        // ============================================================
        $file = __DIR__.'/../Resource/locale/message.'.$app['locale'].'.yml';
        if (file_exists($file)) {
            $app['translator']->addResource('yaml', $file, $app['locale']);
        }

        // initialize logger (for 3.0.0 - 3.0.8)
        if (!Version::isSupportMethod()) {
            eccube_log_init($app);
        }
    }

    /**
     * Boot function.
     *
     * @param BaseApplication $app
     */
    public function boot(BaseApplication $app)
    {
    }
}
