<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/17
 * Time: 10:38
 */

namespace app\api\controller;


use app\common\controller\Api;
use think\Db;
class Qrcode extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = '*';
    /*
     * 生成带参数的二维码
     * @param int $type 生成二维码的类型 1=小程序用户生成二维码 2= app师傅用户生成二维码
     * */
    public function get_qrcode($uid,$type)
    {
        $str = "user_id=$uid";
        $ACCESS_TOKEN = $this->token();
        //构建请求二维码参数
        //path是扫描二维码跳转的小程序路径，可以带参数?id=xxx
        //width是二维码宽度
        $qcode ="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$ACCESS_TOKEN";
        $param = json_encode(array("page"=>"pages/index/index","width"=> 150,"scene"=>$str));
        //POST参数
        $result = $this->httpRequest( $qcode, $param,"POST");
        $pname = time()+rand(100,1000);
        //生成二维码
        file_put_contents("uploads/qrcode/$pname.png", $result);
        $path = "/uploads/qrcode/$pname.png";
        $pname = 'https://'.$_SERVER['SERVER_NAME'].'/uploads/qrcode/'.$pname.'.png';
        return array('pname'=>$pname,'local_path'=>$path);
        //        $base64_image ="data:image/jpeg;base64,".base64_encode( $result );
        //        $path = 'upload/'.uniqid().'.jpg';
        //        file_put_contents($path,$base64_image);
    }

    /*
    * 获取token
    */
    private function token(){
        $token = Db::name("access_token")->find(1);
        $now = time();
        if( $token == null || $token['expire_time'] < $now  ){
            //没有token 或者 过期了 从新去取token
            $ACCESS_TOKEN = $this->getAccessToken();
            if($ACCESS_TOKEN){
                //更新token时间
                $up_data = [];
                $up_data['expire_time'] = $now+7100;//过期时间
                $up_data['access_token'] = $ACCESS_TOKEN;
                Db::name('access_token')->where('id=1')->update($up_data);
            }
        }else{
            $ACCESS_TOKEN = $token['access_token'];
        }
        return $ACCESS_TOKEN;
    }

    private function getAccessToken()
    {

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wx7b2cb15c44985669&secret=bc5e4434aa26d64296c864eee6434b68';
        $res = $this->curl_get($url);
        $result = json_decode($res,1);
        if(isset($result['access_token'])){
            return $result['access_token'];
        }
    }

    private function curl_get($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $re = curl_exec($ch);
        if(curl_error($ch))
        {
            echo 'error:' . curl_error($ch);exit;
        }
        curl_close($ch);
        return $re;
    }
    //把请求发送到微信服务器换取二维码(post)
    private function httpRequest($url, $data='', $method='GET'){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if($method=='POST')
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data != '')
            {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}