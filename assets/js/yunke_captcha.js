/**
 * @yunke_captcha
 * 提供验证码显示及刷新功能
 */

(function ($, Drupal) {
  /**
   * 通过Drupal API 执行文档就绪任务
   */
  Drupal.behaviors.yunkeCaptchaAutoAttach = {
    attach(context, settings) {
      const $context = $(context);
      let elements;
      elements = $context.find('a.yunke_captcha_refresh');
      elements.once('yunkeCaptcha').on('click.yunkeCaptcha', Drupal.yunkeCaptcha.refreshCaptcha);
      elements.trigger('click');
    },
    detach(context, settings, trigger) {
      const $context = $(context);
      let elements;
      if (trigger === 'unload') {
        elements = $context.find('a.yunke_captcha_refresh');
        elements.off('click.yunkeCaptcha', Drupal.yunkeCaptcha.refreshCaptcha);
      }
    },
  };


  /**
   * 处理验证码问题
   * @namespace
   */
  Drupal.yunkeCaptcha = Drupal.yunkeCaptcha || {

    /**
     * 刷新验证码质询问题
     */
    refreshCaptcha(event) {
      event.preventDefault();
      var url = $(this).attr('href');
      var ask = $(this).closest('form').find('.yunke_captcha_ask_content');
      if (ask.length <= 0) {
        return;
      }
      $.ajax({
        type: "get",
        url: url,
        success: function (msg) {
          ask.html(msg);
        },
        error: function (msg) {
          ask.html(msg);
        },
      });

    },

  };
}(jQuery, Drupal));
