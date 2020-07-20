<?php

namespace Drupal\yunke_captcha;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\yunke_captcha\CheckerInterface;

/**
 * 验证器接口定义
 *
 *
 * @ingroup yunke_captcha
 */
abstract class CheckerBase extends PluginBase implements CheckerInterface {

  /**
   * 构建表单验证码字段元素
   *
   * @param array              $form 表单验证码字段元素
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  abstract public function buildCaptchaForm(array $form, FormStateInterface $form_state);

  /**
   * 验证表单验证码字段
   *
   * @param array              $form 表单验证码字段元素
   * @param FormStateInterface $form_state
   *
   * @return mixed
   */
  public function validateCaptchaForm(array &$form, FormStateInterface $form_state) {
  }

}
