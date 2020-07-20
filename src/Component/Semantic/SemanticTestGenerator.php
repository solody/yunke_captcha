<?php

namespace Drupal\yunke_captcha\Component\Semantic;

use Drupal\yunke_captcha\Component\Semantic\SemanticTestGeneratorInterface;

/**
 * 默认语义测试产生器
 * author:云客【云游天下，做客四方】
 * email:phpworld@qq.com
 * site:http://www.indrupal.com/
 *
 * 用程序自动生成语义测试问答，模拟图灵测试，通常用于验证码：
 * <code>
 *   $option=[];
 *   $generator = new SemanticTestGenerator();
 *   $semanticTest=getSemanticTest();
 *   //$semanticTest==['5+3=?','8']
 * <code>
 *
 */
class SemanticTestGenerator implements SemanticTestGeneratorInterface
{
  //数字表示
  protected $num = [
    ['0', '零',],
    ['1', '一', '壹'],
    ['2', '二', '贰'],
    ['3', '三', '叁'],
    ['4', '四', '肆'],
    ['5', '五', '伍'],
    ['6', '六', '陆'],
    ['7', '七', '柒'],
    ['8', '八', '捌'],
    ['9', '九', '玖'],
  ];

  //运算符号
  protected $op = [
    [' + ', '加', '加上'],
    [' 乘上 ', '乘', '乘以'],
  ];

  //算术结果
  protected $result = ['=?', '等于几？', '等于多少？', '是什么？', '是？', '答案是？'];

  //提示
  protected $hint = '（请输入数字）';

  public function getSemanticTest()
  {
    $ask = '';
    $answer = '';
    $op = $this->random(0, 1);
    $num1 = $this->random(0, 9);
    $num2 = $this->random(0, 9);
    $str1 = $this->num[$num1][$this->random(0, count($this->num[$num1]) - 1)];
    $str2 = $this->num[$num2][$this->random(0, count($this->num[$num2]) - 1)];
    $strOp = '';
    if ($op == 0) {
      //相加操作
      $answer = $num1 + $num2;
      $strOp = $this->op[0][$this->random(0, count($this->op[0]) - 1)];
    } elseif ($op == 1) {
      //相乘操作
      $answer = $num1 * $num2;
      $strOp = $this->op[1][$this->random(0, count($this->op[1]) - 1)];
    }
    $strResult = $this->result[$this->random(0, count($this->result) - 1)];
    $ask = $str1 . $strOp . $str2 . $strResult . $this->hint;
    return [$ask, (string)$answer];
  }

  /**
   * 得到随机整数
   *
   * @return int
   */
  protected function random($min = 0, $max = NULL)
  {
    static $randomFun = NULL;
    if ($randomFun) {
      return $randomFun($min, $max);
    }
    if (function_exists('random_int')) {
      $randomFun = 'random_int';
    } else {
      $randomFun = 'mt_rand';
    }
    return $randomFun($min, $max);
  }
}
