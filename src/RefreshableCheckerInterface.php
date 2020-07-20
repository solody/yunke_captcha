<?php

namespace Drupal\yunke_captcha;

use Drupal\yunke_captcha\CheckerInterface;


/**
 * 可刷新验证器接口定义
 * 比如图片验证器就属于此类接口，当用户看不清楚时，点击刷新将更换验证码图片
 *
 * @ingroup yunke_captcha
 */
interface RefreshableCheckerInterface extends CheckerInterface {

  /**
   * 得到刷新后的验证码，比如验证码图片、验证问题等
   *
   * @param $formID
   * @param $pageID
   *
   * @return mixed
   */
  public function getRefresh($formID, $pageID);

}
