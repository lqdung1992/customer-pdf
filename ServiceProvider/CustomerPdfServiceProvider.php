<?php
/**
 * Author: Dung Le Quoc
 * Email: lqdung1992@gmail.com
 * Date: 11/5/2018
 * Time: 2:30 PM
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
        $front = $app['controllers_factory'];
        if ($app['config']['force_ssl'] == Constant::ENABLED) {
            $front->requireHttps();
        }

        $front->post('/plugin/pdf/download/{id}/{type}', '\\Plugin\\CustomerPdf\\Controller\\CustomerPdfController::download')
            ->assert('id', '\d+')
            ->assert('type', '\d+')
            ->bind('plugin_customer_pdf_download');

        $app->mount('/', $front);

        $app['customer_pdf.service'] = $app->share(function () use ($app) {
            return new CustomerPdfService($app);
        });

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
