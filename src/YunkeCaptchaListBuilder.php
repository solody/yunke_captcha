<?php

namespace Drupal\yunke_captcha;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;

use Drupal\Core\Entity\EntityInterface;


/**
 * 定义列表构建器
 *
 * @see \Drupal\yunke_captcha\Entity\YunkeCaptcha
 */
class YunkeCaptchaListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['formID'] = t('Form ID');
    $header['label'] = t('Form page');
    $header['captchaType'] = t('Captcha type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['formID'] = $entity->getFormID();
    $row['label'] = $entity->label();
    $row['captchaType'] = $entity->getCaptchaType();
    return $row + parent::buildRow($entity);
  }

}
