<?php


/**
 * yunke_captcha模块通用管理表单（管理首页）
 */

namespace Drupal\yunke_captcha\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\Cache;


class yunkeCaptchaAdminForm extends FormBase {

  //可编辑配置对象，储存验证码设置
  protected $config = NULL;

  //验证码插件管理器
  protected $pluginManagerYunkeCaptcha = NULL;


  public function __construct($configFactory, $pluginManagerYunkeCaptcha) {
    $this->config = $configFactory->getEditable('yunke_captcha.settings');
    $this->pluginManagerYunkeCaptcha = $pluginManagerYunkeCaptcha;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.yunkeCaptcha')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yunke_captcha_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = t('Captcha manage');
    $form['explain'] = [
      '#type'  => 'html_tag',
      '#tag'   => 'p',
      '#value' => t('The Settings on this page will be applied to all forms with captcha enabled. Click the form captcha manage TAB for a specific form'),
    ];

    $form['manageFromForm'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Whether to display a captcha manage link in form, Only for users with captcha settings permission'),
      '#description'   => t('When enabled, captcha related Settings can be directly managed in the target form. Because the manage link will be added to all forms (including the admin form) of the whole site, it is strongly recommended to close the option after completing the configuration of the captcha'),
      '#default_value' => $this->config->get('manageFromForm'),
      '#field_prefix'  => '<strong>' . t('Captcha is managed from forms') . '</strong><br>',
    ];

    $form['title'] = [
      '#type'          => 'textfield',
      '#required'      => TRUE,
      '#title'         => t('default captcha field name'),
      '#default_value' => $this->config->get('title'),
      '#description'   => t('The label that appears before a captcha field, usually "Captcha",You can customize it here'),
    ];
    $form['invalid_time'] = [
      '#type'          => 'number',
      '#required'      => TRUE,
      '#title'         => t('default captcha valid time'),
      '#min'           => 0,
      '#default_value' => $this->config->get('invalid_time'),
      '#description'   => t('An integer, in seconds, defaults to 10800 (3 hours), after which the captcha must be fetched again before submission'),
    ];

    $captchaType = [];
    foreach ($this->pluginManagerYunkeCaptcha->getDefinitions() as $pluginID => $definitions) {
      $captchaType[$pluginID] = $definitions['label'];
    }
    $form['captchaDefaultType'] = [
      '#type'          => 'select',
      '#title'         => t('default captcha type'),
      '#description'   => t('The default type used when creating a captcha for a form. Each form can be configured independently'),
      '#required'      => TRUE,
      '#options'       => $captchaType,
      '#default_value' => $this->config->get('captchaDefaultType'),
    ];


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $declaration = \Drupal::moduleHandler()
        ->getModule("yunke_captcha")
        ->getPath() . '/data/declaration.txt';
    if (file_exists($declaration) && is_readable($declaration)) {
      $form['#suffix'] = new FormattableMarkup(file_get_contents($declaration), []);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //其他基本验证系统会自动进行
    if (empty(trim($form_state->getValue('title')))) {
      $form_state->setError($form['title'], t('input default captcha field name'));
    }
    $isManageFromForm = (bool) $form_state->getValue('manageFromForm');
    $form_state->setValue('manageFromForm', $isManageFromForm);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $values = $form_state->getValues();
    $this->config->set('title', $values['title']);
    $this->config->set('invalid_time', $values['invalid_time']);
    $this->config->set('captchaDefaultType', $values['captchaDefaultType']);
    $this->config->set('manageFromForm', $values['manageFromForm']);
    $this->config->save();
    Cache::invalidateTags(['yunkeCaptcha']);
    //失效系统所有表单 高负载系统可能导致缓存血崩 因此不要随便设置通用表单
    \Drupal::messenger()->addStatus(t('Save successfully'));
  }

}
