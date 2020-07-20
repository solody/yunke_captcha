<?php

namespace Drupal\yunke_captcha\Component\Semantic;

/**
 * 语义测试产生器接口定义
 *
 * @ingroup yunke_captcha
 */
interface SemanticTestGeneratorInterface
{
  /**
   * 得到语义测试问答，如返回：['5+3=?','8']
   * @return array
   */
    public function getSemanticTest();

}
