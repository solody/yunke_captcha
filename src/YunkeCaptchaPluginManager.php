<?php

namespace Drupal\yunke_captcha;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;


/**
 * 为验证器提供一个插件管理器
 *
 * @ingroup yunke_captcha
 */
class YunkeCaptchaPluginManager extends DefaultPluginManager {

  /**
   * 构造验证器插件管理器.
   *
   * @param \Traversable           $namespaces
   * @param CacheBackendInterface  $cache_backend
   * @param ModuleHandlerInterface $module_handler
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/YunkeCaptcha', $namespaces, $module_handler, 'Drupal\yunke_captcha\CheckerInterface', 'Drupal\yunke_captcha\Annotation\YunkeCaptcha');

    $this->setCacheBackend($cache_backend, 'yunke_captcha_checker');
    $this->alterInfo('yunke_captcha_type');
  }

}
