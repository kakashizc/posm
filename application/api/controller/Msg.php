<?php

namespace app\api\controller;


use app\common\controller\Api;
use app\common\controller\SignatureHelper;
use think\Cache;
header('Access-Control-Allow-Origin:*');
class Msg extends Api{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function send()
    {
        $phone = $this->request->param('mobile');
        if( !preg_match("/^1[345789]{1}\d{9}$/",$phone) ){
            $this->success('手机格式错误','','1');
        }
        $code = mt_rand(10000,99999);
        $params = array();

        // *** 需用户填写部分 ***
        // fixme 必填：是否启用https
        $security = false;

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "";//阿里云不让往git传, 这里就不传了, 具体在哪 自己去查
        $accessKeySecret = "";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $phone;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "石家庄永顺金服信息技术有";

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = "SMS_208265230";

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = Array (
            "code" => $code,
        );


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            )),
            $security
        );
        if ($content->Code == 'OK' && $content->Message == 'OK' ){
            Cache::set($phone,$code,600);
            $this->success('成功','','0');
        }else{
            $this->success('失败',$content,'1');
        }
    }
}


