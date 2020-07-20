<?php

namespace Drupal\yunke_captcha\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\yunke_captcha\Entity\YunkeCaptchaInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * 定义表单验证码配置实体类
 *
 * @ConfigEntityType(
 *   id = "yunke_captcha",
 *   label = @Translation("form captcha"),
 *   label_collection = @Translation("form captcha"),
 *   label_singular = @Translation("form captcha"),
 *   label_plural = @Translation("form captchas"),
 *   label_count = @PluralTranslation(
 *     singular = "@count form captcha",
 *     plural = "@count form captchas",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\yunke_captcha\YunkeCaptchaListBuilder",
 *     "form" = {
 *       "default" = "Drupal\yunke_captcha\Form\CaptchaDefaultForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     }
 *   },
 *   admin_permission = "yunke_captcha settings",
 *   config_prefix = "formSettings",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" ="status",
 *   },
 *   links = {
 *     "delete-form" =
 *     "/admin/config/system/yunke_captcha/form/{yunke_captcha}/delete",
 *     "edit-form" = "/admin/config/system/yunke_captcha/form/{yunke_captcha}",
 *     "enable" =
 *     "/admin/config/system/yunke_captcha/form/{yunke_captcha}/enable",
 *     "disable" =
 *     "/admin/config/system/yunke_captcha/form/{yunke_captcha}/disable",
 *     "collection" = "/admin/config/system/yunke_captcha/form",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "configuration",
 *     "status",
 *   }
 * )
 */
class YunkeCaptcha extends ConfigEntityBase implements YunkeCaptchaInterface, EntityWithPluginCollectionInterface {

  /**
   * 本实体类型的实体id,同时也是表单id
   *
   * @var string
   */
  protected $id;

  /**
   * 表单标题，通常是使用表单页面的标题，来自表单渲染数组的#title属性，如为空，将采用表单id
   *
   * @var string
   */
  protected $label;


  /**
   * 验证码类型(验证器的插件id)
   *
   * @var string
   */
  protected $plugin = '';

  /**
   * 验证器设置数组
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The plugin collection that stores Checker plugins.
   *
   * @var Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $pluginCollection;

  /**
   * Encapsulates the creation of the action's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The action's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.yunkeCaptcha'), $this->plugin, $this->configuration);
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['configuration' => $this->getPluginCollection()];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return $this->get('id');
  }

  /**
   * {@inheritdoc}
   */
  public function setFormID($formID) {
    $this->set('id', $formID); //@todo 是否标记实体为新
    $this->enforceIsNew(TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCaptchaType() {
    return $this->get('plugin');
  }

  /**
   * {@inheritdoc}
   */
  public function setCaptchaType($captchaType) {
    $this->plugin = $captchaType;
    $this->getPluginCollection()->addInstanceId($captchaType);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (empty($this->label)) {
      $this->label = $this->id();
    }
    $this->label = (string) $this->label;
  }

  /**
   * {@inheritdoc}
   *
   * 保存时失效表单缓存
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);
    Cache::invalidateTags($this->getCacheTagsToInvalidate());
  }

  /**
   * {@inheritdoc}
   *
   * 删除时失效表单缓存
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities) {
    parent::invalidateTagsOnDelete($entity_type, $entities);
    foreach ($entities as $entity) {
      Cache::invalidateTags($entity->getCacheTagsToInvalidate());
    }
  }

}
