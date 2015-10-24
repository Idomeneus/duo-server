<?php

function define_routes(Slim\App $app) {
  $container = $app->getContainer();

  $app->get('/', function($req, $rsp) use ($container) {
    $basePath = $container->get('settings')['basePath'];
    return $rsp->withBody(
      new GuzzleHttp\Psr7\LazyOpenStream($basePath . '/public/index.html', 'r')
    );
  });
  return $app;
}
