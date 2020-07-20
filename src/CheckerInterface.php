<?php

namespace Drupal\yunke_captcha;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;


/**
 * 验证器接口定义
 *
 *
 * @ingroup yunke_captcha
 */
interface CheckerInterface extends PluginInspectionInterface {

  /**
   * 构建表单验证码字段元素
   *
   * @param array              $form 表单验证码字段元素
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  public function buildCaptchaForm(array $form, FormStateInterface $form_state);

  /**
   * 验证表单验证码字段
   *
   * @param array              $form 表单验证码字段元素
   * @param FormStateInterface $form_state
   *
   * @return mixed
   */
  public function validateCaptchaForm(array &$form, FormStateInterface $form_state);

}
