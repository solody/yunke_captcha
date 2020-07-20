<?php

namespace Drupal\yunke_captcha\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * 为表单提供验证码
 *
 * @FormElement("yunke_captcha")
 */
class yunkeCaptcha extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this); //不要用$this直接调用，因为信息数组会被缓存，引起缓存不更新问题
    return [
      '#input'            => TRUE,
      '#required'         => TRUE,
      '#process'          => [
        [$class, 'processYunkeCaptcha'],
        [$class, 'processGroup'],
      ],
      '#element_validate' => [
        [$class, 'validateYunkeCaptcha'],
      ],
      '#pre_render'       => [
        [$class, 'preRenderGroup'],
      ],
      '#theme'            => 'yunke_captcha_captcha',
      '#theme_wrappers'   => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return NULL;
    }
    return TRUE;
  }


  /**
   * 添加表单验证码
   *
   * @param                    $element
   * @param FormStateInterface $form_state
   * @param                    $complete_form
   *
   * @return array
   */
  public static function processYunkeCaptcha(&$element, FormStateInterface $form_state, &$complete_form) {
    $formID = $form_state->getFormObject()->getFormId();
    $pageID = \Drupal::service("uuid")->generate();
    $checker = \Drupal::entityTypeManager()
      ->getStorage('yunke_captcha')
      ->load($formID)
      ->getPlugin();
    $element['#formID'] = $formID;
    $element['#pageID'] = $pageID;
    $element['#checker'] = $checker;
    $element['#formState'] = $form_state; //提供给修改钩子使用

    $class = ['yunke_captcha', 'yunke_captcha_' . $formID];
    if (isset($element['#attributes']['class'])) {
      $element['#attributes']['class'] = array_merge($element['#attributes']['class'], $class);
    }
    else {
      $element['#attributes']['class'] = $class;
    }

    $element = $checker->buildCaptchaForm($element, $form_state);
    \Drupal::moduleHandler()->alter('yunke_captcha_element', $element, $formID);

    if (empty($element['#title'])) {
      $element['#title'] = \Drupal::config('yunke_captcha.settings')
        ->get('title') ?: t('Captcha');
    }
    return $element;
  }


  /**
   * 执行验证码验证
   *
   * @param                    $element
   * @param FormStateInterface $form_state
   * @param                    $complete_form
   */
  public static function validateYunkeCaptcha(&$element, FormStateInterface $form_state, &$complete_form) {
    $checker = $element['#checker'];
    $checker->validateCaptchaForm($element, $form_state, $complete_form);
  }


}
