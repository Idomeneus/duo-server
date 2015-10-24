<?php

function define_services(Pimple\Container $container) {
  $container['aws'] = function($c) {
    return new Aws\Sdk($c->get('settings')['aws']);
  };

  $container['s3'] = function($c) {
    $aws = $c->get('aws');
    return $aws('S3');
  };

  return $container;
}
