<?php
/**
 *  by:yunke
 *  email:phpworld@qq.com
 *  time:20200706
 */

namespace Drupal\yunke_captcha\Controller;

use Drupal\yunke_captcha\ImageCaptchaResponse;
use Drupal\yunke_captcha\ImageCheckerInterface;
use Drupal\yunke_captcha\Component\ImageCaptcha\ImageCaptchaGenerator;

class ImageCaptcha {

  /**
   * 通过AJAX得到刷新后的验证码元素后，由该控制器输出图片二进制内容
   *
   * @param $formID 表单id
   * @param $pageID 页面id
   *
   * @return ImageCaptchaResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getImage($formID, $pageID) {
    $entity = \Drupal::entityTypeManager()
      ->getStorage('yunke_captcha')
      ->load($formID);
    $checker = $entity->getPlugin();
    if ($checker instanceof ImageCheckerInterface) {
      $imageCaptchaGenerator = $checker->getImageCaptcha($formID, $pageID);
    }
    else {
      $option = ['str' => t('Image error'),];
      $imageCaptchaGenerator = new ImageCaptchaGenerator($option);
    }
    return new ImageCaptchaResponse($imageCaptchaGenerator);
  }

}

