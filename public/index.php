<?php

require dirname(__DIR__) . '/vendor/autoload.php';

function load_config() {
  $baseDir = dirname(__DIR__);
  $settings = require $baseDir . '/config/config.php.dist';
  $localFile = $baseDir . '/config/config.php';
  if(file_exists($baseDir) && is_readable($baseDir)) {
    $localSettings = require $localFile;
    $settings = array_replace_recursive($settings, $localSettings);
  }
  $settings['basePath'] = $baseDir;
  return $settings;
}

define_routes(
  define_middleware(
    new Slim\App(
      define_services(
        new Slim\Container(['settings' => load_config()])
      )
    )
  )
)->run();
