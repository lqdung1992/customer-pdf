<?php
namespace Plugin\CustomerPdf;

use Eccube\Application;
use Eccube\Event\TemplateEvent;

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

        /** @var \Twig_Environment $twig */
        $twig = $this->app['twig'];
        $insertPart = $twig->getLoader()->getSource('CustomerPdf/Resource/template/default/Mypage/mypage_pdf_button.twig');
        $newSource = str_replace('{% endblock %}', $insertPart.'{% endblock %}', $source);
        $event->setSource($newSource);
    }

    /**
     * @param TemplateEvent $event
     */
    public function onShoppingIndexRender(TemplateEvent $event)
    {
        if (!$this->app->isGranted('ROLE_USER')) {
            return;
        }
        $source = $event->getSource();

        /** @var \Twig_Environment $twig */
        $twig = $this->app['twig'];
        $insertPart = $twig->getLoader()->getSource('CustomerPdf/Resource/template/default/Shopping/shopping_pdf_button.twig');
        $newSource = str_replace('{% endblock %}', $insertPart.'{% endblock %}', $source);
        $event->setSource($newSource);
    }
}
