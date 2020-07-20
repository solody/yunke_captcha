<?php

namespace Drupal\yunke_captcha\Controller;

use Drupal\yunke_captcha\Entity\YunkeCaptchaInterface;
use Drupal\Core\Controller\ControllerBase;


/**
 * 表单验证码启用、禁用控制器
 */
class CaptchaStatusController extends ControllerBase {


  /**
   * 执行表单验证码启用、禁用操作
   *
   * @param \Drupal\yunke_captcha\Entity\YunkeCaptchaInterface $yunke_captcha
   *   参数转化后的实体对象
   * @param string                                             $op
   *   启用禁用操作 'enable'或'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   重定向到表单管理列表页
   */
  public function performOperation(YunkeCaptchaInterface $yunke_captcha, $op) {
    $yunke_captcha->$op()->save();
    $this->messenger()->addStatus($this->t('Captcha status has been updated'));
    return $this->redirect('entity.yunke_captcha.collection');
  }

}
