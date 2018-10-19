<?php

namespace Drupal\amp_replace\EventSubscriber;

use Drupal\amp\Routing\AmpContext;
use Drupal\amp\Render\AmpHtmlResponseMarkupProcessor;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Response subscriber to handle amp HTML responses.
 */
class AmpHtmlResponseSubscriber extends ServiceProviderBase implements EventSubscriberInterface {

  /**
   * The AMP HTML response markup processor service.
   *
   * @var \Drupal\amp_replace\Render\AmpHtmlResponseMarkupProcessor
   */
  protected $ampHtmlResponseMarkupProcessor;

  /**
   * AMP context service.
   *
   * @var Drupal\amp\Routing\AmpContext
   */
  protected $ampContext;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The route amp context to determine whether a route is an amp one.
   *
   * @var \Drupal\amp\Routing\AmpContext
   */
  protected $ampContext;

  /**
   * Constructs an AmpHtmlResponseSubscriber object.
   *
   * @param \Drupal\amp\Render\AmpHtmlResponseMarkupProcessor $amp_html_response_markup_processor
   *   The HTML response attachments processor service.
   */
  public function __construct(AmpHtmlResponseMarkupProcessor $amp_html_response_markup_processor, AmpContext $amp_context) {
    $this->ampHtmlResponseMarkupProcessor = $amp_html_response_markup_processor;
    $this->ampContext = $amp_context;
  }

  /**
   * Processes markup for HtmlResponse responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    $response = $event->getResponse();

    if (!$response instanceof HtmlResponse) {
      return;
    }

    if ($this->ampContext->isAmpRoute()) {
     $event->setResponse($this->ampHtmlResponseMarkupProcessor->processMarkupToAmp($response));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // We want to run this as late as possible, after the HTML has been modified by all the Response listeners
    $events[KernelEvents::RESPONSE][] = ['onRespond', -1024];
    return $events;
  }

}
