<?php

namespace Drupal\yunke_captcha;

use Drupal\yunke_captcha\Component\Semantic\SemanticTestGeneratorInterface;


/**
 * 为语义问答产生器提供一个管理器
 *
 * @ingroup yunke_captcha
 */
class SemanticTestGeneratorManager implements SemanticTestGeneratorInterface {

  protected $generators = [];

  protected $sortedGenerators = NULL;

  /**
   * 添加产生器
   *
   * @param SemanticTestGeneratorInterface $generator
   * @param int                            $priority
   *
   * @return $this
   */
  public function addGenerator(SemanticTestGeneratorInterface $generator, $priority = 0) {
    $this->generators[$priority][] = $generator;
    $this->sortedGenerators = NULL;
    return $this;
  }

  /**
   * 按优先级排序产生器
   *
   * @return \Drupal\yunke_captcha\Component\Semantic\SemanticTestGeneratorInterface[]
   */
  protected function sortGenerators() {
    $sorted = [];
    krsort($this->generators);

    foreach ($this->generators as $generators) {
      $sorted = array_merge($sorted, $generators);
    }
    return $sorted;
  }

  /**
   * {@inheritdoc}
   */
  public function getSemanticTest() {
    if ($this->sortedGenerators === NULL) {
      $this->sortedGenerators = $this->sortGenerators();
    }
    foreach ($this->sortedGenerators as $generator) {
      $semanticTest = $generator->getSemanticTest();
      if ($semanticTest !== FALSE) {
        return $semanticTest;
      }
    }
    return FALSE;
  }

}
