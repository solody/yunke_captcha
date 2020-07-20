<?php

namespace Drupal\yunke_captcha;

use Drupal\Core\Security\TrustedCallbackInterface;


/**
 * 提供可信回调执行的辅助类
 *
 * @ingroup yunke_captcha
 */
class TrustedCallbacks implements TrustedCallbackInterface {

  public static function trustedCallbacks() {
    return ['sortCaptcha'];
  }

  /**
   * 排序验证码位置，让其在表单末尾
   *
   * @param $element 表单元素
   *
   * @return array
   */
  public static function sortCaptcha($element) {
    return _yunke_captcha_sortCaptcha($element);
  }

}
