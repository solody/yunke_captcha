Introduction：
Yunke captcha module is designed for Drupal 8 and above, you can use it to add
verification code to any form in the system, and you can set up different 
verification styles and details for each form. In default, there are two 
kinds of verification styles:
Semantic questions:
give user a unique question (and a hint), let the user to answer. For example, 
“Where was Achilles weak spot? (hint: one word)”，the user has to answer “heel” 
to pass the test. All those questions are defined in semanticList.php, it is 
recommended to add more questions before use. Starting with version 2.0, 
you can also use automatic questions.
Image captcha:
this is the most common captcha type, but in Yunke captcha, There is a powerful 
image generator，so site administrators can adjust the details of the captcha image. 
The output type can be Chinese words, English alphabets, Numbers, Symbol or combined. 
Site administrators can customize the complexity of the captcha image, such as 
the number of interfering lines, the concentration of interfering pixels, image 
distortion, text turn into sand(sort of…please see the screenshots) , the hength
and width of the captcha picture, and the format of the captcha picture (png, 
jpg or gif) etc.


Usage and configuration:
Just install this module like all other modules.
after installation go to ‘/admin/config/system/yunke_captcha’ for configuration. 
You can also add a captcha test right under a form from a frontend page (if there 
is a form on the page).
Semantic question library (Please read comments before change anything) :
yunke_captcha/data/semanticList.php
Chinese-seeds file (add or delete some chinese words if you want to) :
yunke_captcha/data/cnChrList.php
If you want to change the font of the captcha text, just change this file:
yunke_captcha/src/Component/ImageCaptcha/fontFamily.TTF


The following is a description in Chinese:
		    
		    云客验证码（yunke_captcha）模块
                           V2.0.0   
软件作者：云客【云游天下，做客四方】  
联系方式：微信号：indrupal，QQ交流群：203286137  


【简介】：
云客验证码模块（yunke_captcha）是为Drupal8及以上版本设计的，能够为系统中
任意表单添加验证码，并能独立设置不同的验证类型和细节，模块默认提供了两种
验证类型：

语义问答验证：
这是基于“图灵测试”的一种验证码，即给用户输出一句话，让用户回答，如“历史
上的西楚霸王是谁？”，用户必须回答“项羽”才能通过验证，这是通过语义库来实
现的，系统自带了若干问答语义库，用户可以自定义更多语义库，在使用前强烈推
荐采用自定义的语义问答。从2.0版本开始，系统提供了自动语义库，即让程序自动
生成问答配合人工问答一起使用，用户也可自定义生成程序，这种设计带来很大灵
活性，对于专业领域的网站尤为有用。

图片验证码：
这是我们最为常见的验证码类型，但不同的是本模块实现了底层接口，功能非常强
大，验证字符可输出中文、字母、数字、特殊符号，或者混合输出，可以自定义验
证码图片的复杂性，如干扰线条数量、干扰像素点浓度、变形散沙、图片宽高等，
通过配置表单可以控制所有这些细节

虽然默认仅提供了以上两种验证码类型，但该模块被设计成一个强大的验证码基础
平台，通过插件等机制可以轻松实现各种类型的验证码，如邮件验证、手机验证、
js终端识别等验证类型


【使用】：
按照标准安装流程进行安装，安装后即可进入管理界面或表单界面进行验证码设置
管理界面位于（首页/管理/配置/系统/验证码通用管理），或者在模块列表页（扩
展）找到本模块后，点击“配置”按钮即可进入管理。
语义库自定义文件（按注释提示操作）：
  yunke_captcha/data/semanticList.php
中文字符种子文件（可自由增删汉字）：
  yunke_captcha/data/cnChrList.php
如需更换字体，请复制一个字体文件替换以下文件：
  yunke_captcha/src/Component/ImageCaptcha/fontFamily.TTF


【设计思想】：
让我们来思考一下验证码的本质是什么呢？我的回答是：
“利用人类智能与机器智能之间的能力差距或信息不对称来识别机器，并阻止其提交表单。”
典型的如：
图片验证码：利用了能力差距，机器在图片中有干扰像素时识别能力不如人类
手机验证：利用信息不对称，让验证码通过其他渠道传递，机器无法获取
语义问答：如“十里长街送总理中的总理全名？”，回答“周恩来”，这种验证码综合
利用了能力差和信息差，机器首先要有理解语义的能力，这是很困难的，其次要知
道这个典故信息才能突破验证码，类似图灵测试

云客验证码模块（yunke_captcha）即以此思想为核心进行设计。


【设计架构】：
模块特别强调规范化，代码注释详尽，所有功能的实现均建立在标准的基础API之上，
验证类型使用插件机制，每一种验证类型实现一个插件即可，完全遵循系统插件机制，
为扩展验证类型提供了良好的基础；模块建立了验证码实体类型，每一个表单的验证
码配置信息独立储存于一个实体对象，提供了良好的管理体验，管理员可从中心位置
统一为不同表单添加并设置验证码，也可从表单页面直接进行管理；在权限控制上提
供了跳过验证权限，让可信用户免于验证；在国际化方面，以英语作为开发元语言，
安装后自动导入中文翻译，其他国家用户可通过翻译界面提供各语言的翻译。


【注意事项】：
有些表单是起到过滤显示信息而设置的，并非提交保存信息，如内容管理页面的头部
筛选表单，这一类表单虽然也可以为其设置验证码，但不应该这么干，它们初始状态
就会自动提交，如设置了验证码将阻挡这一行为，导致出错什么也不显示。
