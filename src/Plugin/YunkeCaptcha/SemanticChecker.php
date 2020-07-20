<?php

namespace Drupal\yunke_captcha\Plugin\YunkeCaptcha;

use Drupal\Component\Utility\NestedArray;
use Drupal\yunke_captcha\ConfigurableCheckerBase;
use Drupal\yunke_captcha\RefreshableCheckerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;


/**
 * 定义一个语义问答类型的验证器，如“十里长街送总理中总理的名字？",答“周恩来”
 * 模拟图灵测试，需提供语义库，将从中随机选择
 *
 * @YunkeCaptcha(
 *   id = "semantic",
 *   label = @Translation("Semantic answer"),
 *   description = @Translation("To simulate Turing test, randomly extract questions from semantic question-answering library for verification, it is suggested to set up semantic question-answering library first"),
 * )
 */
class SemanticChecker extends ConfigurableCheckerBase implements RefreshableCheckerInterface, ContainerFactoryPluginInterface {

  //配置数组
  protected $configuration = [];

  //验证码全局默认配置，即配置对象'yunke_captcha.settings'
  protected $defaultConfig;

  //一个数组表示的语义列表文件
  protected $semanticList = NULL;

  public function __construct($configuration, $plugin_id, $plugin_definition, $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->defaultConfig = $config_factory->get('yunke_captcha.settings');
    $this->configuration = array_intersect_key($configuration, static::defaultConfiguration()) + static::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title'                   => $this->defaultConfig->get('title') ?: 'Captcha',
      //验证码字段标题
      'size'                    => 30,
      //验证码输入框尺寸
      'invalid_time'            => $this->defaultConfig->get('invalid_time') ?: 10800,
      //验证码有效时间 默认3小时
      'isCaseSensitive'         => FALSE,
      //是否大小写敏感，默认大小写均可
      'isEnableAutoSemantic'    => TRUE,
      //是否采用自动语义库
      'autoSemanticProbability' => 0.4,
      //采用自动语义库的概率
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $description = $this->getPluginDefinition()['description'] ?: '';
    $form['description'] = ['#markup' => $description,];
    $form['title'] = [
      '#type'          => 'textfield',
      '#size'          => 20,
      '#title'         => t('Captcha field name'),
      '#description'   => t('If left blank, the default captcha field name will be used'),
      '#default_value' => $this->configuration['title'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['size'] = [
      '#type'          => 'number',
      '#required'      => TRUE,
      '#title'         => t('captcha input field widget size'),
      '#min'           => 1,
      '#default_value' => $this->configuration['size'],
      '#description'   => t('An integer, default 30'),
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['invalid_time'] = [
      '#type'          => 'number',
      '#title'         => t('Captcha valid time'),
      '#min'           => 0,
      '#default_value' => $this->configuration['invalid_time'],
      '#description'   => t('An integer, in seconds, defaults to 10800 (3 hours), after which the captcha must be fetched again before submission'),
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['isCaseSensitive'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Whether letters are case sensitive'),
      '#default_value' => $this->configuration['isCaseSensitive'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['isEnableAutoSemantic'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Whether to enable automatic semantic libraries'),
      '#description'   => t('When enabled, System can automatically generate semantic questions, which are recommended when the number of semantic libraries is insufficient'),
      '#default_value' => $this->configuration['isEnableAutoSemantic'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['autoSemanticProbability'] = [
      '#type'          => 'number',
      '#title'         => t('Probability of applying automatic semantic libraries'),
      '#description'   => t('Between 0 and 1, if 0, Automatic semantic libraries will not be used at all, the higher the number, the higher the probability, if 1, it is all'),
      '#max'           => 1,
      '#min'           => 0,
      '#step'          => 0.01,
      '#default_value' => $this->configuration['autoSemanticProbability'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
      '#states'        => [
        'visible' => [
          [':input[name="configuration[isEnableAutoSemantic]"]' => ['checked' => TRUE,]],
        ],
      ],
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $title = $form_state->getValue($form['title']['#parents']);
    $form_state->setValue($form['title']['#parents'], empty($title) ? NULL : $title);

    $isCaseSensitive = (bool) $form_state->getValue($form['isCaseSensitive']['#parents']);
    $form_state->setValue($form['isCaseSensitive']['#parents'], $isCaseSensitive);

    $isEnableAutoSemantic = (bool) $form_state->getValue($form['isEnableAutoSemantic']['#parents']);
    $form_state->setValue($form['isEnableAutoSemantic']['#parents'], $isEnableAutoSemantic);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue($form['title']['#parents']);
    $this->configuration['size'] = $form_state->getValue($form['size']['#parents']);
    $this->configuration['invalid_time'] = $form_state->getValue($form['invalid_time']['#parents']);
    $this->configuration['isCaseSensitive'] = $form_state->getValue($form['isCaseSensitive']['#parents']);
    $this->configuration['isEnableAutoSemantic'] = $form_state->getValue($form['isEnableAutoSemantic']['#parents']);
    $this->configuration['autoSemanticProbability'] = (float) $form_state->getValue($form['autoSemanticProbability']['#parents']);
  }


  /**
   * {@inheritdoc}
   */
  public function buildCaptchaForm(array $form, FormStateInterface $form_state) {
    $formID = $form['#formID'];
    $pageID = $form['#pageID'];
    $pluginID = str_replace(static::DERIVATIVE_SEPARATOR, '_', $this->getPluginId());
    $class = ['yunke_captcha_' . $pluginID];
    $form['#attributes']['class'] = array_merge($form['#attributes']['class'], $class);
    if (!empty($this->configuration['title'])) {
      $form['#title'] = t($this->configuration['title']);
    }

    $form['yunkeCaptchaAsk'] = [
      'ask'         => $this->getAsk($formID, $pageID),
      '#theme'      => 'yunke_captcha_ask',
      '#attributes' => [
        'class' => ['yunke_captcha_ask', 'yunke_captcha_ask_' . $formID],
      ],
    ];

    $form['yunkeCaptchaInput'] = [
      'yunkeCaptcha'       => [
        '#type'           => 'textfield',
        '#size'           => $this->configuration['size'],
        '#required'       => TRUE,
        '#attributes'     => ['autocomplete' => 'off'],
        '#theme_wrappers' => NULL,
      ],
      'yunkeCaptchaPageID' => [
        '#type'  => 'hidden',
        '#value' => $pageID,
      ],
      '#theme'             => 'yunke_captcha_input',
      '#attributes'        => [
        'class' => ['yunke_captcha_input', 'yunke_captcha_input_' . $formID],
      ],
    ];

    $form['yunkeCaptchaDescription'] = [
      'description' => $this->getDescription($formID),
      '#theme'      => 'yunke_captcha_description',
      '#attributes' => [
        'class' => [
          'yunke_captcha_description',
          'yunke_captcha_description_' . $formID,
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefresh($formID, $pageID) {
    $pluginID = $this->getPluginId();
    $session = $this->getSession();
    $captcha = $session->get('yunkeCaptcha', NULL);
    $preCaptcha = NULL;
    if (isset($captcha[$pluginID][$formID][$pageID]['captcha'])) {
      $preCaptcha = $captcha[$pluginID][$formID][$pageID]['captcha'];
    }

    $semanticList = $this->getSemanticList();
    $ListNum = count($semanticList);
    if ($ListNum > 1) {
      while (TRUE) {
        $index = $this->random(0, $ListNum - 1);
        $newCaptcha = $semanticList[$index];
        if ($newCaptcha !== $preCaptcha) {
          break;
        }
      }
    }
    else {
      if ($ListNum == 1) {
        $newCaptcha = $semanticList[0];
      }
      else {
        $newCaptcha = ['2012-1984=?', '28']; //云客的彩蛋 在这一年女儿出生
      }
    }

    $ask = [
      '#markup' => $newCaptcha[0],
      '#cache'  => ['max-age' => 0],
    ];
    $captcha[$pluginID][$formID][$pageID] = [
      'captcha'    => $newCaptcha,
      'expiration' => time() + $this->configuration['invalid_time'],
    ];
    $session->set('yunkeCaptcha', $captcha);
    return $ask;
  }


  /**
   * 得到随机整数
   *
   * @return int
   */
  protected function random($min = 0, $max = NULL) {
    static $randomFun = NULL;
    if ($randomFun) {
      return $randomFun($min, $max);
    }
    if (function_exists('random_int')) {
      $randomFun = 'random_int';
    }
    else {
      $randomFun = 'mt_rand';
    }
    return $randomFun($min, $max);
  }

  /**
   * 得到会话并清理失效的验证码数据
   */
  protected function getSession() {
    $request = \Drupal::request();
    if (!$request->hasSession()) {
      //通常不会执行这里，在系统初期http堆栈阶段，服务http_middleware.session会启动会话
      //但预防其他模块干扰，此处以备会话丢失，确保验证器以此得到会话：$session = $request->getSession();
      $session = \Drupal::service('session');
      $session->start();
      $request->setSession($session);
    }
    $session = $request->getSession();

    //清理失效验证码 超期时间储存在：$captcha[$pluginID][$formID][$pageID]['expiration']
    $captcha = $session->get('yunkeCaptcha', []);
    $pluginID = $this->getPluginId();
    foreach ($captcha as $plugin_ID => $pluginData) {
      if ($plugin_ID === $pluginID && is_array($pluginData)) {
        foreach ($pluginData as $formID => $formData) {
          if (is_array($formData)) {
            foreach ($formData as $pageID => $pageData) {
              if (is_array($pageData)) {
                if (isset($pageData['expiration']) && $pageData['expiration'] < time()) {
                  unset($captcha[$pluginID][$formID][$pageID]);
                }
              }
            }
          }
        }
      }
    }
    $session->set('yunkeCaptcha', $captcha);

    return $session;
  }

  protected function getDescription($formID = '') {
    $msg = '';
    if ($this->configuration['isCaseSensitive']) {
      $msg .= t('case sensitive');
    }
    else {
      $msg .= t('case insensitive');
    }
    return [
      '#markup' => t('input the answer to the above question. if need to another question, then refresh, ') . $msg,
    ];

  }


  protected function getAsk($formID, $pageID) {
    $url = Url::fromRoute('yunke_captcha.refreshCaptcha', [
      'formID' => $formID,
      'pageID' => $pageID,
    ])
      ->toString(FALSE);
    $ask = [
      'content' => [
        '#markup' => '<span class="yunke_captcha_ask_content yunke_captcha_ask_content_' . $formID . '"></span>',
      ],
      'refresh' => [
        '#markup' => ' <a href="' . $url . '" class="yunke_captcha_refresh yunke_captcha_refresh_' . $formID . '">' . t('Refresh') . '</a>',
      ],
    ];
    $ask['#attached']['library'] = ['yunke_captcha/captcha'];
    return $ask;
  }

  protected function getSemanticList() {
    if (isset($this->semanticList)) {
      return $this->semanticList;
    }
    $semanticList = [];
    $semanticListFile = \Drupal::moduleHandler()
        ->getModule("yunke_captcha")
        ->getPath() . '/data/semanticList.php';
    if (file_exists($semanticListFile)) {
      include($semanticListFile);
    }
    $this->semanticList = $semanticList;
    if (!$this->configuration['isEnableAutoSemantic'] || $this->configuration['autoSemanticProbability'] <= 0) {
      return $this->semanticList; //采用人工语义库
    }
    $probability = $this->configuration['autoSemanticProbability'] * 100;
    if ($this->random(0, 100) <= $probability) {
      //采用自动语义库
      $semanticTest = \Drupal::service('yunke_captcha.semanticTestGeneratorManager')
        ->getSemanticTest();
      //不从构造函数注入该服务是为了节约性能
      if ($semanticTest === FALSE) {
        return $this->semanticList;
      }
      return [$semanticTest];
    }
    else {
      return $this->semanticList;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateCaptchaForm(array &$form, FormStateInterface $form_state) {
    $formID = $form['#formID'];
    $pageID = NestedArray::getValue(
      $form_state->getUserInput(),
      $form['yunkeCaptchaInput']['yunkeCaptchaPageID']['#parents']
    );
    $pluginID = $this->getPluginId();
    $session = $this->getSession();
    $captcha = $session->get('yunkeCaptcha', NULL);
    if (!isset($captcha[$pluginID][$formID][$pageID]['captcha'])) {//失效验证码被清理了
      $form_state->setError($form, $form['#title'] . ' ' . t('expired, please refresh'));
      return;
    }
    if ($captcha[$pluginID][$formID][$pageID]['expiration'] < time()) {
      $form_state->setError($form, $form['#title'] . ' ' . t('expired, please refresh'));
      unset($captcha[$pluginID][$formID][$pageID]);
      $session->set('yunkeCaptcha', $captcha);
      return;
    }

    $input = NestedArray::getValue(
      $form_state->getUserInput(),
      $form['yunkeCaptchaInput']['yunkeCaptcha']['#parents']
    );
    $input = trim($input);
    $key = $captcha[$pluginID][$formID][$pageID]['captcha'][1];
    if (!$this->configuration['isCaseSensitive']) {
      $key = mb_strtolower($key);
      $input = mb_strtolower($input);
    }
    if ($input !== $key) {
      $form_state->setError($form, $form['#title'] . ' ' . t('error'));
    }
    unset($captcha[$pluginID][$formID][$pageID]);
    $session->set('yunkeCaptcha', $captcha);
  }

}
