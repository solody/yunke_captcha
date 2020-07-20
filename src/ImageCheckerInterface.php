<?php

namespace Drupal\yunke_captcha;


/**
 * 定义图片验证器接口
 *
 * @ingroup yunke_captcha
 */
interface ImageCheckerInterface extends CheckerInterface {

  /**
   * @param $formID 表单id
   * @param $pageID 页面id
   *
   * @return \Drupal\yunke_captcha\Component\ImageCaptcha\ImageCaptchaGeneratorInterface
   */
  public function getImageCaptcha($formID, $pageID);

}
