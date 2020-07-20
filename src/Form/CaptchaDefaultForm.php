<?php


/**
 * 表单验证码配置实体编辑、新建表单
 */

namespace Drupal\yunke_captcha\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\yunke_captcha\ConfigurableCheckerInterface;


class CaptchaDefaultForm extends EntityForm {

  //配置对象，储存验证码通用设置
  protected $config = NULL;

  //验证码插件管理器
  protected $pluginManagerYunkeCaptcha = NULL;


  public function __construct($configFactory, $pluginManagerYunkeCaptcha) {
    $this->config = $configFactory->get('yunke_captcha.settings');
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
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $form['#title'] = t('Form captcha settings');
    $form['id'] = [
      '#type'          => 'textfield',
      '#required'      => TRUE,
      '#title'         => t('Form ID'),
      '#default_value' => $entity->id(),
      '#description'   => t('The form_id field value of the form, It cannot be edited unless being created new. If you do not know how to fill here, you can open the \'Captcha is managed from forms\' and add it directly from the form page'),
    ];
    if (!$entity->isNew()) {
      $form['id']['#disabled'] = TRUE;
    }
    $form['label'] = [
      '#type'          => 'textfield',
      '#required'      => TRUE,
      '#title'         => t('Form page name'),
      '#default_value' => $entity->label(),
      '#description'   => t('Use the name for management'),
    ];
    $captchaType = [];
    foreach ($this->pluginManagerYunkeCaptcha->getDefinitions() as $pluginID => $definitions) {
      $captchaType[$pluginID] = $definitions['label'];
    }
    if ($entity->isNew()) {
      $pluginId = $this->config->get('captchaDefaultType');
      $entity->setCaptchaType($pluginId);
    }
    else {
      $pluginId = $entity->get('plugin');
    }
    $checker = $entity->getPlugin();
    $form['plugin'] = [
      '#type'          => 'select',
      '#title'         => t('Captcha type'),
      '#description'   => t('Different types of captchas work differently, you can extend more types through the plugin mechanism'),
      '#required'      => TRUE,
      '#options'       => $captchaType,
      '#default_value' => $pluginId,
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
      '#ajax'          => [
        'callback' => '::checkerSettingsForm',
        'wrapper'  => 'settings-wrapper',
        'method'   => 'html',
      ],
    ];

    $form['configuration'] = $this->iniConfigurationForm();
    $form['configuration']['#checker'] = $checker;
    if ($checker instanceof ConfigurableCheckerInterface) {
      $form['configuration'] = $checker->buildConfigurationForm($form['configuration'], $form_state);
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return parent::form($form, $form_state, $entity);
  }

  /**
   * 初始化验证器配置表单
   *
   * @return array
   */
  protected function iniConfigurationForm() {
    return [
      '#type'             => FALSE, //一些模块必须要求该属性，避免错误日志
      '#theme'            => 'yunke_captcha_checker_settings',
      '#tree'             => TRUE,
      '#checker'          => NULL,
      '#attributes'       => [
        'id' => 'settings-wrapper',
      ],
      '#process'          => [
        [$this, 'processSettingsForm'],
      ],
      '#element_validate' => [
        [$this, 'validateSettings'],
      ],
    ];
  }

  /**
   * 验证控件设置
   *
   * @param array              $settings
   * @param FormStateInterface $form_state
   * @param                    $complete_form
   */
  public function validateSettings(array &$settings, FormStateInterface $form_state, &$complete_form) {
    $checker = $settings['#checker'];
    if ($checker instanceof ConfigurableCheckerInterface) {
      $checker->validateConfigurationForm($settings, $form_state, $complete_form);
    }
  }

  /**
   * AJAX回调返回的内容不会再经过表单构建器，因此设置表单将没有正确的name值，通过该方法处理，提前为回调准备好内容
   *
   * @param                    $element
   * @param FormStateInterface $form_state
   * @param                    $complete_form
   *
   * @return mixed
   */
  public function processSettingsForm(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!$form_state->isProcessingInput()) {
      return $element;
    }
    $inputPluginId = NestedArray::getValue($form_state->getUserInput(), $complete_form['plugin']['#parents']);
    if ($inputPluginId === $complete_form['plugin']['#default_value']) {
      return $element;
    }
    $this->entity->setCaptchaType($inputPluginId);
    $checker = $this->entity->getPlugin();
    $iniElement = $this->iniConfigurationForm();
    $iniElement['#parents'] = $element['#parents'];
    $iniElement['#array_parents'] = $element['#array_parents'];
    $element = $iniElement;
    $element['#checker'] = $checker;
    if ($checker instanceof ConfigurableCheckerInterface) {
      $element = $checker->buildConfigurationForm($element, $form_state);
    }
    return $element;
  }

  /**
   * ajax方式返回验证码插件设置表单
   */
  public function checkerSettingsForm($form, FormStateInterface $form_state) {
    $settingsForm = $form['configuration'];
    $checker = $settingsForm['#checker'];
    if (!($checker instanceof ConfigurableCheckerInterface)) {
      return ['#markup' => t('Non-configurable captcha checker')];
    }
    foreach ($settingsForm as $key => $value) {
      if ($key[0] === '#') {
        unset($settingsForm[$key]);
      }
    }
    return $settingsForm;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $checker = $entity->getPlugin();
    //注意这里不能用$form['configuration']['#checker']
    //它们虽然类相同，但不是同一个实例

    if ($checker instanceof ConfigurableCheckerInterface) {
      $checker->submitConfigurationForm($form['configuration'], $form_state);
    }
    $entity->set('label', trim($entity->label()));
    $status = $entity->save();

    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')
      ->toString();
    if ($status == SAVED_UPDATED) {
      $this->messenger()
        ->addStatus($this->t('Captcha %label settings has been updated', ['%label' => $entity->label()]));
      $this->logger('user')
        ->notice('Captcha %label settings has been updated, <a href="%link">Edit</a>', [
          '%label' => $entity->label(),
          '%link'  => $edit_link,
        ]);
    }
    else {
      $this->messenger()
        ->addStatus($this->t('Captcha %label settings has been saved', ['%label' => $entity->label()]));
      $this->logger('user')
        ->notice('Captcha %label settings has been saved, <a href="%link">Edit</a>', [
          '%label' => $entity->label(),
          '%link'  => $edit_link,
        ]);
    }
    $form_state->setRedirect('entity.yunke_captcha.collection');
  }

}
