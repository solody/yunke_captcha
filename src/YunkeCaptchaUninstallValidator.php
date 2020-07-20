<?php

namespace Drupal\yunke_captcha;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * Prevents uninstallation of modules providing used captcha checker plugins.
 */
class YunkeCaptchaUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * modules providing captcha checker plugin in use
   *
   * @var array
   */
  protected $modules = [];


  /**
   * YunkeCaptchaUninstallValidator constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param TranslationInterface       $string_translation
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entityTypeManager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if (empty($this->modules)) {

      $entities = $this->entityTypeManager->getStorage('yunke_captcha')
        ->loadMultiple();
      if (empty($entities)) {
        return $reasons;
      }
      foreach ($entities as $entity) {
        $provider = $entity->getPlugin()->getPluginDefinition()['provider'];
        if ($provider === 'yunke_captcha') {
          continue;
        }
        if (!isset($this->modules[$provider])) {
          $this->modules[$provider] = [];
        }
        $this->modules[$provider][] = $entity->label();
      }
    }

    if (array_key_exists($module, $this->modules)) {
      $reasons[] = $this->t(
        'the module provides a captcha checker in use, form:( :label ), <a href=":url">Remove</a>',
        [
          ':label' => implode(',', $this->modules[$module]),
          ':url'   => Url::fromRoute('entity.yunke_captcha.collection')
            ->toString(),
        ]
      );
    }
    return $reasons;
  }

}
