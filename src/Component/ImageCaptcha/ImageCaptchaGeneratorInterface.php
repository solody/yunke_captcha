<?php

namespace Drupal\yunke_captcha\Component\ImageCaptcha;

/*
示例：
$option = [
    'width'     => 100,
    'height'    => 50,
    'pixelDensity' => 0.05,
    'isSand'=>false,
    'numLine'      => NULL,
    'numArc'=>null,
    'imageType' => 'png',
    'str'       => 'kuft',
    'fontFamily' => realpath(__DIR__) . "/" . 'simkai.ttf',
];

$generator = new ImageCaptchaGenerator($option);
header("Content-type:image/png");
$generator->send();

 */

/**
 * 图片验证码产生器接口定义
 *
 * @ingroup yunke_captcha
 */
interface ImageCaptchaGeneratorInterface
{

    /**
     * 设置并过滤属性值 仅允许设置存在的属性
     *
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public function set($key, $value);

    /**
     * 获取属性值
     *
     * @param      $key     属性名
     * @param null $default 默认值
     *
     * @return null | mixed
     */
    public function get($key, $default = null);

    /**
     * 返回输出图片的MIME类型
     *
     * @return mixed
     */
    public function getMimeType();

    /**
     * 输出图片流到浏览器并释放内存
     *
     * @return bool
     */
    public function send();

    /**
     * 保存图片到文件
     *
     * @param $path
     *
     * @return bool
     */
    public function save($path, $quality = null);
    
}
