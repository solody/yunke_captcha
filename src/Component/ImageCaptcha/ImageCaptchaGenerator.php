<?php

namespace Drupal\yunke_captcha\Component\ImageCaptcha;

use Drupal\yunke_captcha\Component\ImageCaptcha\ImageCaptchaGeneratorInterface;

/**
 * 图片验证码产生器
 * author:云客【云游天下，做客四方】
 * email:phpworld@qq.com
 * site:http://www.indrupal.com/
 *
 * 使用示例如下：
 * <code>
 *   $option=[];
 *   $generator = new ImageCaptchaGenerator($option);
 *   header("Content-type:$generator->getMimeType()");
 *   $generator->send();
 * <code>
 *
 * 本类不设置HTTP头，须由调用者负责 选项参数见类属性
 * Class ImageCaptchaGenerator
 */
class ImageCaptchaGenerator implements ImageCaptchaGeneratorInterface
{
    //图像宽 单位像素
    public $width = 100;

    //图像高 单位像素 宽高比推荐为2:1
    public $height = 50;

    //干扰像素密度 干扰像素点与总像素点的比值 
    //应大于等于0（0将没有干扰像素点）
    //小于等于1（1将非常密集，但并非全部布满，因为有部分干扰像素会重叠）
    public $pixelDensity = 0.05;

    //干扰直线数量，不设置则依据图像大小随机
    public $numLine = NULL;

    //干扰弧线数量，不设置则依据图像大小随机
    public $numArc = NULL;

    //字符是否沙粒化（像沙粒落地一样显示）,开启后和干扰像素融合在一起，破解识别难度更大
    public $isSand = false;

    //图片格式类型 仅支持最常用的三种格式：'gif', 'jpeg', 'png' ，默认为png
    public $imageType = 'png';

    //验证码中的文字，包括中文、字母、数字但不限于，也可混合，必须为utf-8编码
    public $str = '作者云客';

    //字体文件地址,如为NULL将在本脚本相同目录下查找，默认为楷体
    //可从'C:/Windows/Fonts/simkai.ttf'复制到本脚本目录，并改名为“fontFamily.TTF”;
    public $fontFamily = NULL;


    //图像资源
    protected $resource;
    //背景色
    protected $bgColor;

    /**
     * 图像验证码产生器构造函数
     *
     * @param array $option 选项数组，键名为本类属性名，含义见注释
     *
     * @throws Exception 字体文件缺失将抛出异常
     */
    public function __construct($option = [])
    {
        foreach ($option as $key => $value) {
            $this->set($key, $value);
        }

        if (empty($this->fontFamily) || !file_exists($this->fontFamily)) {
            $this->fontFamily = realpath(__DIR__) . "/" . "fontFamily.TTF";
        }
        if (!file_exists($this->fontFamily)) {
            $exceptionMessage = 'Font file does not exist, Copy one to ' . realpath(__DIR__) . "/" . "fontFamily.TTF";
            throw new \Exception($exceptionMessage);
        }
    }

    /**
     * 设置并过滤属性值 仅允许设置存在的属性
     *
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public function set($key, $value)
    {
        if (!property_exists($this, $key)) {
            return false;
        }
        switch ($key) {
            case 'width':
                $value = (int)$value;
                if ($value <= 0) {
                    return false;
                }
                $this->width = $value;
                break;
            case 'height':
                $value = (int)$value;
                if ($value <= 0) {
                    return false;
                }
                $this->height = $value;
                break;
            case 'pixelDensity':
                $value = (float)$value;
                if ($value < 0 || $value > 1) {
                    return false;
                }
                $this->pixelDensity = $value;
                break;
            case 'numLine':
                if ($value === NULL) {
                    return true;
                }
                $value = (int)$value;
                if ($value < 0) {
                    return false;
                }
                $this->numLine = $value;
                break;
            case 'numArc':
                if ($value === NULL) {
                    return true;
                }
                $value = (int)$value;
                if ($value < 0) {
                    return false;
                }
                $this->numArc = $value;
                break;
            case 'imageType':
                $value = (string)$value;
                $value = strtolower($value);
                if ($value === 'jpg') {
                    $value = 'jpeg';
                }
                if (!in_array($value, ['gif', 'jpeg', 'png'])) {
                    $value = 'png';
                }
                if ($value == 'gif' && !(imagetypes() & IMG_GIF)) {
                    $value = 'png';
                }
                $this->imageType = $value;
                break;
            case 'str':
                $value = (string)$value;
                $value = trim($value);
                if (empty($value)) {
                    $value = $this->random(10000, 99999);
                }
                $this->str = $value;
                break;
            default:
                $this->$key = $value;
        }
        return true;
    }

    /**
     * 获取属性值
     *
     * @param      $key     属性名
     * @param null $default 默认值
     *
     * @return null | mixed
     */
    public function get($key, $default = null)
    {
        if (property_exists($this, $key)) {
            return $this->$key;
        }
        return $default;
    }

    /**
     * 返回输出图片的MIME类型
     *
     * @return mixed
     */
    public function getMimeType()
    {
        $types = ['png' => 'image/png', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif'];
        return $types[$this->imageType];
    }

    /**
     * 输出图片流到浏览器并释放内存
     *
     * @return bool
     */
    public function send()
    {
        //这里不执行：header('Content-Type: image/jpeg');需要由调用者负责
        $this->generate();
        $imageSendFun = 'image' . $this->imageType;
        $result = $imageSendFun($this->resource);
        imagedestroy($this->resource);
        return $result;
    }

    /**
     * 保存图片到文件
     *
     * @param $path
     *
     * @return bool
     */
    public function save($path, $quality = null)
    {
        //imagegif($im);imagejpeg($im);imagepng($im);
        $imageSaveFun = 'image' . $this->imageType;
        $result = $imageSaveFun($this->resource, $path, $quality);
        imagedestroy($this->resource);
        return $result;
    }

    /**
     * 产生图片
     */
    protected function generate()
    {
        $this->resource = imagecreatetruecolor($this->width, $this->height);
        //采用浅色做背景色
        $this->bgColor = imagecolorallocate($this->resource, $this->random(150, 255), $this->random(150, 255), $this->random(150, 255));
        imagefill($this->resource, 0, 0, $this->bgColor);
        $addMethods = ['addLine', 'addStr', 'addArc', 'addDistortion', 'addPixel'];
        shuffle($addMethods);
        foreach ($addMethods as $method) {
            $this->$method();
        }
        //$this->addDistortion(); //总是扭曲 默认关闭
    }

    /**
     * 扭曲图像并沙粒化文字 云客原创算法
     */
    protected function addDistortion()
    {
        $copyImage = imagecreatetruecolor($this->width, $this->height);
        imagefill($copyImage, 0, 0, $this->bgColor);
        $waveLength = 5; //波长因子 值越大扭曲波长越长
        $amplitude = 2;  //振幅因子 值越大扭曲偏振越大
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $fromColor = imagecolorat($this->resource, $x, $y);
                if ($this->isSand) {
                    $waveLength = $this->random(2, 8);
                }
                imagesetpixel($copyImage, (int)($x + sin($y / $waveLength) * $amplitude), $y, $fromColor);

            }
        }
        $this->resource = $copyImage;
    }

    /**
     * 写入验证码字符
     */
    protected function addStr()
    {
        $fontSize = ceil($this->height / 2);
        $fontY = ceil($this->height - $fontSize / 1.5);
        $strArr = preg_split('/(?<!^)(?!$)/u', $this->str);
        $numStr = count($strArr);
        $padding = 5; //设置安全内边距
        if ($this->width <= 10) {
            $padding = 0;
        }
        $xStep = floor(($this->width - $padding * 2) / $numStr);
        for ($i = 0; $i < $numStr; $i++) {
            $strColor = imagecolorallocate($this->resource, $this->random(0, 150), $this->random(0, 150), $this->random(0, 150));
            imagettftext($this->resource,
                $this->random(ceil($fontSize / 2), $fontSize), //字体尺寸
                $this->random(-20, 20), //认读角度方向 从左到右为0
                $i * $xStep + $padding, //X基线
                $fontY, //Y基线
                $strColor, //颜色
                $this->fontFamily, //字体
                $strArr[$i]//被写入文字
            );
        }
    }

    /**
     * 添加干扰线
     */
    protected function addLine()
    {
        $numLine = $this->numLine;
        if ($numLine === NULL) {
            $numLine = ceil($this->width / 40);
        }
        for ($i = 0; $i < $numLine; $i++) {
            if ($this->width <= 100) {
                $thickness = 1;
            } elseif ($this->width > 100 && $this->width <= 200) {
                $thickness = $this->random(1, 2);
            } elseif ($this->width > 200 && $this->width <= 300) {
                $thickness = $this->random(1, 3);
            } else {
                $thickness = $this->random(1, 6);
            }
            imagesetthickness($this->resource, $thickness);
            $lineColor = imagecolorallocate($this->resource, $this->random(0, 255), $this->random(0, 255), $this->random(0, 255));
            imageline($this->resource,
                $this->random(0, $this->width),
                $this->random(0, $this->height),
                $this->random(0, $this->width),
                $this->random(0, $this->height),
                $lineColor);
        }
    }


    /**
     * 添加干扰弧线
     */
    protected function addArc()
    {
        $numArc = $this->numArc;
        if ($numArc === NULL) {
            $numArc = ceil($this->width / 40);
        }
        for ($i = 0; $i < $numArc; $i++) {
            if ($this->width <= 100) {
                $thickness = 1;
            } elseif ($this->width > 100 && $this->width <= 200) {
                $thickness = $this->random(1, 2);
            } elseif ($this->width > 200 && $this->width <= 300) {
                $thickness = $this->random(1, 3);
            } else {
                $thickness = $this->random(1, 6);
            }
            imagesetthickness($this->resource, $thickness);
            $arcColor = imagecolorallocate($this->resource, $this->random(0, 255), $this->random(0, 255), $this->random(0, 255));
            imagearc($this->resource,
                $this->random(0, $this->width),
                $this->random(0, $this->height),
                $this->random(0, $this->width),
                $this->random(0, $this->height),
                $this->random(0, 360),
                $this->random(0, 360), $arcColor);
        }
    }

    /**
     * 画点 依据干扰像素密度而定 颜色和位置随机
     */
    protected function addPixel()
    {
        $numPixel = ceil($this->width * $this->height * $this->pixelDensity);
        for ($i = 0; $i < $numPixel; $i++) {
            $pixelColor = imagecolorallocate($this->resource, $this->random(0, 255), $this->random(0, 255), $this->random(0, 255));
            imagesetpixel($this->resource, $this->random(0, $this->width), $this->random(0, $this->height), $pixelColor);
        }
    }

    /**
     * 返回质量更高的随机整数
     *
     * @param int  $min
     * @param null $max
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




