<?php

namespace Drupal\yunke_captcha\Plugin\YunkeCaptcha;

use Drupal\yunke_captcha\ConfigurableCheckerBase;
use Drupal\yunke_captcha\RefreshableCheckerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\yunke_captcha\Component\ImageCaptcha\ImageCaptchaGenerator;
use Drupal\yunke_captcha\ImageCheckerInterface;
use Drupal\Component\Utility\NestedArray;


/**
 * 定义传统类型的图片验证码，支持中文汉字、字母、数字或特殊符号
 *
 * @YunkeCaptcha(
 *   id = "image",
 *   label = @Translation("Image captcha"),
 *   description = @Translation("Display captcha in an image with disturbance pixels, support Chinese, Letters, Numbers and Symbols"),
 * )
 */
class ImageChecker extends ConfigurableCheckerBase implements ImageCheckerInterface, RefreshableCheckerInterface, ContainerFactoryPluginInterface {

  //配置数组
  protected $configuration = [];

  //验证码全局默认配置，即配置对象'yunke_captcha.settings'
  protected $defaultConfig;

  //一个数组表示的中文汉字
  protected $cnChrList = NULL;

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
      'title'           => $this->defaultConfig->get('title') ?: 'Captcha',
      //验证码字段标题
      'size'            => 30,
      //验证码输入框尺寸
      'invalid_time'    => $this->defaultConfig->get('invalid_time') ?: 10800,
      //验证码有效时间 默认3小时
      'types'           => ['number', 'letter'],
      //验证码字符类型，常用中文汉字cn、数字number、字母letter、特殊符号symbol
      'isCaseSensitive' => FALSE,
      //是否大小写敏感，默认大小写均可
      'number'          => 4,
      //验证码字符数
      'width'           => 200,
      //图片宽度
      'height'          => 80,
      //图片高度
      'pixelDensity'    => 0.05,
      //干扰点浓度
      'isSand'          => FALSE,
      //是否开启沙粒化效果
      'numLine'         => NULL,
      //干扰直线条数
      'numArc'          => NULL,
      //干扰弧线条数
      'imageType'       => 'png',
      //验证码图片格式
    ];
  }

  /**
   * 得到验证码字符类型
   *
   * @return array
   */
  protected function getCharTypes() {
    return [
      'number' => t('number'),
      'letter' => t('letter'),
      'symbol' => t('specific symbol'),
      'cn'     => t('chinese character'),
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
      '#default_value' => $this->configuration['title'],
      '#description'   => t('If left blank, the default captcha field name will be used'),
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
      '#description'   => t('Not recommended, it is difficult to recognize the case in image, Enable will increase the probability of error'),
      '#default_value' => $this->configuration['isCaseSensitive'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $typeOptions = $this->getCharTypes();
    $form['types'] = [
      '#type'          => 'checkboxes',
      '#title'         => t('The types of characters that captcha can contain'),
      '#required'      => TRUE,
      '#options'       => $typeOptions,
      '#default_value' => $this->configuration['types'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['number'] = [
      '#type'          => 'number',
      '#title'         => t('Number of captcha characters'),
      '#description'   => t('at least one'),
      '#required'      => TRUE,
      '#min'           => 1,
      '#default_value' => $this->configuration['number'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['width'] = [
      '#type'          => 'number',
      '#title'         => t('Captcha image width'),
      '#required'      => TRUE,
      '#description'   => t('An integer in pixels'),
      '#min'           => 20,
      '#default_value' => $this->configuration['width'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['height'] = [
      '#type'          => 'number',
      '#title'         => t('Captcha image height'),
      '#required'      => TRUE,
      '#description'   => t('An integer in pixels'),
      '#min'           => 20,
      '#default_value' => $this->configuration['height'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['pixelDensity'] = [
      '#type'          => 'number',
      '#title'         => t('Disturbance pixels density'),
      '#required'      => TRUE,
      '#description'   => t('The ratio of disturbance pixels to the total pixels of the image, between 0 and 1, if 0, disturbance pixels will not be added'),
      '#max'           => 1,
      '#min'           => 0,
      '#step'          => 0.01,
      '#default_value' => $this->configuration['pixelDensity'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['isSand'] = [
      '#type'          => 'checkbox',
      '#title'         => t('Captcha characters turn into sand'),
      '#description'   => t('Random to display captcha characters like sand falling to the ground, the disturbance pixels are mixed together, more difficult to identify'),
      '#default_value' => $this->configuration['isSand'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['numLine'] = [
      '#type'          => 'number',
      '#title'         => t('Number of disturbance lines'),
      '#description'   => t('0 will not be added, left blank will be generated automatically according to the image size'),
      '#min'           => 0,
      '#default_value' => $this->configuration['numLine'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $form['numArc'] = [
      '#type'          => 'number',
      '#title'         => t('Number of disturbance arcs'),
      '#description'   => t('0 will not be added, left blank will be generated automatically according to the image size'),
      '#min'           => 0,
      '#default_value' => $this->configuration['numArc'],
      '#attributes'    => [
        'autocomplete' => 'off',
      ],
    ];
    $imageType = [
      'png'  => 'png',
      'jpeg' => 'jpeg',
      'gif'  => 'gif',
    ];
    $form['imageType'] = [
      '#type'          => 'select',
      '#title'         => t('image type'),
      '#required'      => TRUE,
      '#options'       => $imageType,
      '#description'   => t('Captcha image format type（MimeType）'),
      '#default_value' => $this->configuration['imageType'],
      '#attributes'    => [
        'autocomplete' => 'off',
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

    $types = $form_state->getValue($form['types']['#parents']);
    $types = array_keys(array_filter($types));
    $validTypes = array_keys($this->getCharTypes());
    if (!empty(array_diff($types, $validTypes)) || empty($types)) {
      $form_state->setError($form['types'], t('The captcha characters type is incorrect or not selected'));
    }
    $form_state->setValue($form['types']['#parents'], $types);

    $isCaseSensitive = (bool) $form_state->getValue($form['isCaseSensitive']['#parents']);
    $form_state->setValue($form['isCaseSensitive']['#parents'], $isCaseSensitive);

    $isSand = (bool) $form_state->getValue($form['isSand']['#parents']);
    $form_state->setValue($form['isSand']['#parents'], $isSand);

    $numLine = $form_state->getValue($form['numLine']['#parents']);
    if (!is_numeric($numLine)) {
      $form_state->setValue($form['numLine']['#parents'], NULL);
    }

    $numArc = $form_state->getValue($form['numArc']['#parents']);
    if (!is_numeric($numArc)) {
      $form_state->setValue($form['numArc']['#parents'], NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue($form['title']['#parents']);
    $this->configuration['size'] = $form_state->getValue($form['size']['#parents']);
    $this->configuration['invalid_time'] = $form_state->getValue($form['invalid_time']['#parents']);
    $this->configuration['types'] = $form_state->getValue($form['types']['#parents']);
    $this->configuration['isCaseSensitive'] = $form_state->getValue($form['isCaseSensitive']['#parents']);
    $this->configuration['number'] = $form_state->getValue($form['number']['#parents']);
    $this->configuration['width'] = $form_state->getValue($form['width']['#parents']);
    $this->configuration['height'] = $form_state->getValue($form['height']['#parents']);
    $this->configuration['pixelDensity'] = $form_state->getValue($form['pixelDensity']['#parents']);
    $this->configuration['isSand'] = $form_state->getValue($form['isSand']['#parents']);
    $this->configuration['numLine'] = $form_state->getValue($form['numLine']['#parents']);
    $this->configuration['numArc'] = $form_state->getValue($form['numArc']['#parents']);
    $this->configuration['imageType'] = $form_state->getValue($form['imageType']['#parents']);
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
      //检查器的该方法返回渲染数组，因此也可以返回自定义资源库
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
    /* 验证码储存格式
       $captcha[$pluginID][$formID][$pageID]=[
            'expiration'=>time()+$invalid_time,
            'captcha'=> $captcha,
           ];
    */
    $captcha = $session->get('yunkeCaptcha', NULL);
    $preCaptcha = NULL;
    if (isset($captcha[$pluginID][$formID][$pageID]['captcha'])) {
      $preCaptcha = $captcha[$pluginID][$formID][$pageID]['captcha'];
    }
    $types = $this->configuration['types'];
    $length = $this->configuration['number'];
    while (TRUE) {
      $newCaptcha = $this->getRandomString($types, $length);
      if ($newCaptcha !== $preCaptcha) {
        break;
      }
    }
    $captcha[$pluginID][$formID][$pageID] = [
      'captcha'    => $newCaptcha,
      'expiration' => time() + $this->configuration['invalid_time'],
    ];
    $session->set('yunkeCaptcha', $captcha);

    //该处输出一个图片HTML元素 图片二进制内容由专用路由负责输出
    $url = Url::fromRoute('yunke_captcha.imageCaptcha',
      ['formID' => $formID, 'pageID' => $pageID],
      ['query' => ['key' => $this->random(10000, 99999)]])
      ->toString(FALSE);
    $ask = [
      '#type'       => 'html_tag',
      '#tag'        => 'img',
      '#attributes' => ['src' => $url],
      '#cache'      => ['max-age' => 0],
    ];
    return $ask;
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
    $key = $captcha[$pluginID][$formID][$pageID]['captcha'];
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

  /**
   * 随机得到一个数字
   *
   * @return string
   */
  protected function getRandomNumber() {
    if ($this->configuration['types'] === ['number']) {
      return $this->random(0, 9);
    }
    //去掉0，任意和Oo混淆
    return $this->random(1, 9);
  }

  /**
   * 随机得到一个字母
   *
   * @return string
   */
  protected function getRandomLetter() {
    $str = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
    //将iIlOo去掉（容易和1、0混淆）,剩下47个
    $index = $this->random(0, 46);
    return $str[$index];
  }

  /**
   * 随机得到一个特殊符号
   *
   * @return string
   */
  protected function getRandomSymbol() {
    $str = '@#$%&';
    //*是让人疑惑的，？有中英文问题，=容易和线条混淆 因此不采用
    $index = $this->random(0, strlen($str) - 1);
    return $str[$index];
  }

  /**
   * 随机得到单个中文字符
   *
   * @return string
   */
  protected function getRandomCn() {
    $cnChrList = $this->getCnChrList();
    $max = count($cnChrList) - 1;
    if ($max > 0) {
      $index = $this->random(0, $max);
    }
    else {
      $index = 0;
    }
    return $cnChrList[$index];
  }

  /**
   * 得到中文字符列表数组
   *
   * @return string[]
   */
  protected function getCnChrList() {
    if (isset($this->cnChrList)) {
      return $this->cnChrList;
    }
    $cnChrList = '中文验证码由云客开发';
    $cnChrListFile = \Drupal::moduleHandler()
        ->getModule("yunke_captcha")
        ->getPath() . '/data/cnChrList.php';
    if (file_exists($cnChrListFile)) {
      include($cnChrListFile);
    }
    $this->cnChrList = preg_split('/(?<!^)(?!$)/u', $cnChrList);
    return $this->cnChrList;
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
   * 得到一个随机字符串
   *
   * @param array $types 可包含的字符类型
   * @param int   $num   字符串中的字符个数
   *
   * @return string
   */
  protected function getRandomString($types = [
    'number',
    'letter',
  ], $length = 4) {
    $length = (int) $length;
    if ($length <= 0) {
      $length = 1;
    }
    $validTypes = array_keys($this->getCharTypes());
    if (!empty(array_diff($types, $validTypes)) || empty($types)) {
      $types = ['number', 'letter'];
    }
    $str = '';
    for ($i = 0; $i < $length; $i++) {
      $type = $types[$this->random(0, count($types) - 1)];
      $methodName = 'getRandom' . ucfirst($type);
      $str .= $this->$methodName();
    }
    return $str;
  }

  /**
   * 得到验证码辅助描述
   *
   * @param string $formID
   *
   * @return array
   */
  protected function getDescription($formID = '') {
    $msg = '';
    if ($this->configuration['isCaseSensitive']) {
      $msg .= t('case sensitive');
    }
    else {
      $msg .= t('case insensitive');
    }
    return [
      '#markup' => t('input the characters in image, ') . $msg,
    ];

  }

  /**
   * 得到验证码问题
   *
   * @param $formID
   * @param $pageID
   *
   * @return array
   */
  protected function getAsk($formID, $pageID) {
    $url = Url::fromRoute('yunke_captcha.refreshCaptcha', [
      'formID' => $formID,
      'pageID' => $pageID,
    ])
      ->toString(FALSE);
    $ask = [
      'content' => [
        '#type'       => 'html_tag',
        '#tag'        => 'div',
        '#attributes' => [
          'class' => [
            'yunke_captcha_ask_content',
            'yunke_captcha_ask_content_' . $formID,
          ],
          'style' => "width:{$this->configuration['width']}px; height:{$this->configuration['height']}px;",
        ],
      ],
      'refresh' => [
        '#type'       => 'html_tag',
        '#tag'        => 'a',
        '#attributes' => [
          'class' => [
            'yunke_captcha_refresh',
            'yunke_captcha_refresh_' . $formID,
          ],
          'href'  => $url,
        ],
        '#value'      => t('Refresh'),
      ],
    ];
    $ask['#attached']['library'] = ['yunke_captcha/captcha'];
    return $ask;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageCaptcha($formID, $pageID) {
    $option = [
      'width',
      'height',
      'pixelDensity',
      'isSand',
      'numLine',
      'numArc',
      'imageType',
    ];
    $option = array_intersect_key($this->configuration, array_flip($option));
    $pluginID = $this->getPluginId();
    $session = $this->getSession();
    $captcha = $session->get('yunkeCaptcha', NULL);
    if (isset($captcha[$pluginID][$formID][$pageID]['captcha'])) {
      $option['str'] = $captcha[$pluginID][$formID][$pageID]['captcha'];
    }
    else {
      $option['str'] = t('Missing Captcha');
    }
    return new ImageCaptchaGenerator($option);
  }

}
