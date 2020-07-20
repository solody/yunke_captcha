<?php

/**
 * @yunke_captcha
 * Hooks for yunke_captcha module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * 修改表单验证码元素
 *
 * @param $element
 *               验证码元素
 * @param $formId
 *               表单ID
 *
 * @see \Drupal\yunke_captcha\Element\yunkeCaptcha::processYunkeCaptcha
 */
function hook_yunke_captcha_element_alter(&$element, $formId)
{
    if ($formId == 'yunke_test') {
        unset($element['yunkeCaptchaDescription']);
    }
}

/**
 * @} End of "addtogroup hooks".
 */
