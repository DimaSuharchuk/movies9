<?php

namespace Drupal\only_ukraine\EventSubscriber;

use Drupal\Component\Serialization\SerializationInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class Subscriber implements EventSubscriberInterface {

  protected RequestStack $request;

  protected ClientInterface $http;

  protected SerializationInterface $serialization;

  public function __construct(RequestStack $request_stack, ClientInterface $http_client, SerializationInterface $serialization) {
    $this->request = $request_stack;
    $this->http = $http_client;
    $this->serialization = $serialization;
  }

  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 255];

    return $events;
  }

  public function onKernelRequest(RequestEvent $event): void {
    // Only handle the main request, not sub-requests.
    if (!$event->isMainRequest()) {
      return;
    }

    $ip = $this->request->getCurrentRequest()->getClientIp();
    $url = "http://www.geoplugin.net/json.gp?ip=$ip";

    try {
      $response = $this->http->request('GET', $url);
      $data = $this->serialization->decode($response->getBody()->getContents());

      if (
        !empty($data['geoplugin_countryCode'])
        && $data['geoplugin_countryCode'] != 'UA'
      ) {
        $response = new Response();
        $response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
        $event->setResponse($response);
      }
    }
    catch (GuzzleException) {
    }
  }

}
