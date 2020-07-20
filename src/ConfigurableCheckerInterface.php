<?php

namespace Drupal\yunke_captcha;

use Drupal\yunke_captcha\CheckerInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * 可配置验证器接口定义
 *
 *
 * @ingroup yunke_captcha
 */
interface ConfigurableCheckerInterface extends CheckerInterface, ConfigurableInterface, PluginFormInterface {

}
