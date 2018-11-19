<?php
namespace Plugin\CustomerPdf;

use Eccube\Application;
use Eccube\Event\TemplateEvent;
use Plugin\CustomerPdf\Util\PdfType;

/**
 * Class OrderPdf Event.
 */
class CustomerPdfEvent
{
    protected $app;

    /**
     * CustomerPdfEvent constructor.
     * @param $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }


    /**
     * Event for new hook point.
     *
     * @param TemplateEvent $event
     */
    public function onMypageHistoryRender(TemplateEvent $event)
    {
        $source = $event->getSource();



        $event->setSource($source);
    }

    /**
     *
     * @param TemplateEvent $event
     */
    public function onShoppingIndexRender(TemplateEvent $event)
    {
        $source = $event->getSource();

        /** @var \Twig_Environment $twig */
        $twig = $this->app['twig'];
        $insertPart = $twig->getLoader()->getSource('CustomerPdf/Resource/template/default/Shopping/shopping_pdf_button.twig');
        $newSource = str_replace('{% block main %}', '{% block main %}'.$insertPart, $source);
        $parameters = $event->getParameters();
        $parameters['order_id'] = $parameters['Order']->getId();
        $parameters['export_type'] = PdfType::ORDER_ESTIMATE;
        $event->setParameters($parameters);
        $event->setSource($newSource);
    }
}
