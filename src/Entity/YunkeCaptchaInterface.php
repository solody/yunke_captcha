<?php

namespace Drupal\yunke_captcha\Entity;


/**
 * 定义表单验证码配置实体接口
 *
 * @ingroup yunke_captcha
 */
interface YunkeCaptchaInterface {

  /**
   * 得到表单id
   *
   * @return string
   */
  public function getFormID();

  /**
   * 设置表单id
   *
   * @param $formID string
   *
   * @return $this
   */
  public function setFormID($formID);

  /**
   * 得到验证码类型（验证器插件ID）
   *
   * @return string
   */
  public function getCaptchaType();

  /**
   * 设置验证码类型（验证器插件ID）
   *
   * @param $captchaType string
   *
   * @return $this
   */
  public function setCaptchaType($captchaType);

  /**
   * 判断是否自动附加到表单
   *
   * @return bool
   */
  public function isAutoAttached();

  /**
   * 设置是否自动附加到表单
   *
   * @param $auto_attached
   * @return $this
   */
  public function setAutoAttached($auto_attached);

  /**
   * 返回验证器插件对象
   *
   * @return \Drupal\yunke_captcha\CheckerInterface
   */
  public function getPlugin();

}
