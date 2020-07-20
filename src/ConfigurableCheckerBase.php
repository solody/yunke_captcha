<?php

namespace Drupal\yunke_captcha;

use Drupal\Core\Form\FormStateInterface;
use Drupal\yunke_captcha\CheckerBase;
use Drupal\yunke_captcha\ConfigurableCheckerInterface;

/**
 * 验证器接口定义
 *
 *
 * @ingroup yunke_captcha
 */
abstract class ConfigurableCheckerBase extends CheckerBase implements ConfigurableCheckerInterface {

  protected $configuration;

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = array_intersect_key($configuration, static::defaultConfiguration()) + static::defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   * @see \Drupal\yunke_captcha\Form\CaptchaDefaultForm::iniConfigurationForm
   */
  abstract public function buildConfigurationForm(array $form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  abstract public function submitConfigurationForm(array &$form, FormStateInterface $form_state);

}
