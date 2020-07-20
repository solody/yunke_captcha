<?php
/**
 *  by:yunke
 *  email:phpworld@qq.com
 *  time:20200706
 */

namespace Drupal\yunke_captcha\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\yunke_captcha\RefreshableCheckerInterface;

class RefreshCaptcha {

  /**
   * 通过AJAX刷新验证码
   */
  public function getRefresh($formID, $pageID) {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $captchaEntity = NULL;
    $captchaEntityID = \Drupal::entityQuery('yunke_captcha')
      ->condition('id', $formID)
      ->execute();
    if (!empty($captchaEntityID)) {
      $captchaEntity = \Drupal::entityTypeManager()
        ->getStorage('yunke_captcha')
        ->load($formID);
      $entityStatus = $captchaEntity->status();
    }

    if ($captchaEntity === NULL || !$entityStatus) {
      $refreshedContent = [
        '#markup' => t('Captcha has been cancelled, to refresh the page'),
      ];
    }
    else {
      $checker = $captchaEntity->getPlugin();
      if ($checker instanceof RefreshableCheckerInterface) {
        $refreshedContent = $checker->getRefresh($formID, $pageID);
      }
      else {
        $refreshedContent = [
          '#markup' => t('Non-refreshing captcha'),
        ];
      }
    }
    $refreshedContent = \Drupal::service('renderer')
      ->renderRoot($refreshedContent);
    $response = new Response($refreshedContent);
    return $response;
  }

}

