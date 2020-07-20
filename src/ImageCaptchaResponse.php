<?php

/**
 *  图片验证码响应
 */

namespace Drupal\yunke_captcha;

use Symfony\Component\HttpFoundation\Response;
use Drupal\yunke_captcha\Component\ImageCaptcha\ImageCaptchaGeneratorInterface;

class ImageCaptchaResponse extends Response {

  protected $imageCaptchaGenerator = NULL;

  public function __construct(ImageCaptchaGeneratorInterface $imageCaptchaGenerator, $status = 200, $headers = []) {
    parent::__construct(NULL, $status, $headers);
    $this->imageCaptchaGenerator = $imageCaptchaGenerator;
  }

  public function sendHeaders() {
    $this->headers->set('content-type', $this->imageCaptchaGenerator->getMimeType());
    parent::sendHeaders();
  }

  public function sendContent() {
    $this->imageCaptchaGenerator->send();
    return $this;
  }

}

