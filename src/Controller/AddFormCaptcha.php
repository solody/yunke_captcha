<?php

namespace Drupal\yunke_captcha\Controller;

use Drupal\yunke_captcha\Entity\YunkeCaptchaInterface;
use Drupal\Core\Controller\ControllerBase;


/**
 * 添加表单验证码
 */
class AddFormCaptcha extends ControllerBase {

  public function add($formID = NULL, $label = NULL) {
    $storage = $this->entityTypeManager()->getStorage('yunke_captcha');
    if ($formID === NULL) {
      $entity = $storage->create([]);
      return $this->entityFormBuilder()->getForm($entity);
    }
    $captchaEntity = \Drupal::entityQuery('yunke_captcha')
      ->condition('id', $formID)
      ->execute();
    if (empty($captchaEntity)) {
      $entity = $storage->create(['id' => $formID, 'label' => $label]);
    }
    else {
      $entity = $storage->load($formID);
    }
    return $this->entityFormBuilder()->getForm($entity);
  }

}
