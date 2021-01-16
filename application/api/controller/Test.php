<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/1/15 0015
 * Time: 18:53
 */

namespace app\api\controller;


use app\common\controller\Api;
/*
 *
 * 比如A和B都有一套自己的公钥和私钥，
 * 当A要给B发送消息时，先用B的公钥对消息加密，再对加密的消息使用A的私钥加签名，达到既不泄露也不被篡改，更能保证消息的安全性。
 * 总结: 前端用后端的公钥加密、后端用自己的私钥解密、前端用自己的私钥签名、后端用前端的公钥验签。
 * */
class Test extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    private $_rsa;

    //假设为客户端的公钥
    private $pubKey = '-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBANxHli+mY+q0WTp9kT9oVxstFepgCFYd
gbDLXfEq0G7WNgcOf8+HJKr7ils132UC//CsxhFnb2knu8n6k7018+kCAwEAAQ==
-----END PUBLIC KEY-----';
    //假设客户端的私钥
    private  $priKey = '-----BEGIN PRIVATE KEY-----
MIIBVQIBADANBgkqhkiG9w0BAQEFAASCAT8wggE7AgEAAkEA3EeWL6Zj6rRZOn2R
P2hXGy0V6mAIVh2BsMtd8SrQbtY2Bw5/z4ckqvuKWzXfZQL/8KzGEWdvaSe7yfqT
vTXz6QIDAQABAkEA1Dg3rjKMwpr7+AjvfXolqW33VjUs8uI8hzp2iBkqdAwsDQ5R
Q2Jd+iRonjD4d6ApbV5Sd2U8XXzUcL+epR9Z/QIhAPpm35bFoUQF0RjSQFyC54Px
ByPrAzMv8gHDj91VkzbXAiEA4TRQiZRi9GrJcH+nz7LAc5RInFEOAATFiaPLN5yy
kz8CIANMHX+fxJrftLwt8JkHREMxhlWLv7QJ2pb5W0if2ttrAiBFr0FogPDpvo1c
cTPE8gPY/75EGFSjrtZNE9DTAXrEUwIhAMWre932fbzF0rZOn3orZn3mJUHYfG+1
FLAQDxhtSPwx
-----END PRIVATE KEY-----';

    public function _initialize()
    {
        parent::_initialize();
        $this->_rsa = new Rsa();
    }

    /*
     * 可以先签名 再加密
     *
     * */
    public function aa()
    {
        //1, A先把数据签名,然后把签名的数据加密
        $data['name'] = 'Tom';
        $data['age']  = '20';

        //获取预处理字符串
        $signString = $this->getSignString($data);
        //获取签名
        $sign = $this->getSign($signString,$this->priKey);
        //echo $sign;exit;
        $data['sign'] = $sign;
        $publicEncrypt = $this->_rsa->publicEncrypt(json_encode($data));
        echo '公钥加密:'.$publicEncrypt.'<br>';

        //2, b接受到数据后 先解密,然后验证签名

    }

    public function index()
    {

        $data['name'] = 'Tom';
        $data['age']  = '20';
        $data['sign'] = '113';
//        $privEncrypt = $this->_rsa->privEncrypt(json_encode($data));
//        echo '私钥加密:'.$privEncrypt.'<br>';

//        $publicDecrypt = $this->_rsa->publicDecrypt($privEncrypt);
//        echo '公钥解密:'.$publicDecrypt.'<br>';

        $publicEncrypt = $this->_rsa->publicEncrypt(json_encode($data));
        echo '公钥加密:'.$publicEncrypt.'<br>';

        $publicEncrypt = str_replace('%2B','+',$publicEncrypt);
        $privDecrypt = $this->_rsa->privDecrypt($publicEncrypt);
        echo '私钥解密:'.$privDecrypt.'<br>';
    }

    /*
     * 加解密测试
     * */
    public function test()
    {
        $data = $this->request->param('pass');
        $publicDecrypt = $this->_rsa->publicDecrypt($data);
        echo '公钥解密:'.$publicDecrypt.'<br>';
    }


    //私钥格式化
    public function formatPriKey($priKey) {
        $fKey = "-----BEGIN PRIVATE KEY-----\n";
        $len = strlen($priKey);
        for($i = 0; $i < $len; ) {
            $fKey = $fKey . substr($priKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PRIVATE KEY-----";
        return $fKey;
    }

    //公钥格式化
    public function formatPubKey($pubKey) {
        $fKey = "-----BEGIN PUBLIC KEY-----\n";
        $len = strlen($pubKey);
        for($i = 0; $i < $len; ) {
            $fKey = $fKey . substr($pubKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PUBLIC KEY-----";
        return $fKey;
    }

    /**
     * 生成签名
     * @param    string     $signString 待签名字符串
     * @param    [type]     $priKey     私钥
     * @return   string     base64结果值
     */
    public function getSign($signString,$priKey){
        $privKeyId = openssl_pkey_get_private($priKey);
        $signature = '';
        openssl_sign($signString, $signature, $privKeyId);
        openssl_free_key($privKeyId);
        return base64_encode($signature);
    }

    /**
     * 校验签名
     * @param    string     $pubKey 公钥
     * @param    string     $sign   签名
     * @param    string     $toSign 待签名字符串
     * @param    string     $signature_alg 签名方式 比如 sha1WithRSAEncryption 或者sha512
     * @return   bool
     */
    public function checkSign($pubKey,$sign,$toSign,$signature_alg=OPENSSL_ALGO_SHA1){
        $publicKeyId = openssl_pkey_get_public($pubKey);
        $result = openssl_verify($toSign, base64_decode($sign), $publicKeyId,$signature_alg);
        openssl_free_key($publicKeyId);
        return $result === 1 ? true : false;
    }

    /**
     * 获取待签名字符串
     * @param    array     $params 参数数组
     * @return   string
     */
    public function getSignString($params){
        unset($params['sign']);
        ksort($params);
        reset($params);
        $pairs = array();
        foreach ($params as $k => $v) {
            if(!empty($v)){
                $pairs[] = "$k=$v";
            }
        }

        return implode('&', $pairs);
    }


}

//$pubKey = '-----BEGIN PUBLIC KEY-----
//MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAM12kYNmHHZ7cgvmMIb+JNYAfubG4XP+
//FqDIKPzvU+iQmXZaTWWlI1MGc+U2UL9jNcoOVZtMuU87Lh756O8bW5sCAwEAAQ==
//-----END PUBLIC KEY-----';
//
//$priKey = '-----BEGIN PRIVATE KEY-----
//MIIBVAIBADANBgkqhkiG9w0BAQEFAASCAT4wggE6AgEAAkEAzXaRg2YcdntyC+Yw
//hv4k1gB+5sbhc/4WoMgo/O9T6JCZdlpNZaUjUwZz5TZQv2M1yg5Vm0y5TzsuHvno
//7xtbmwIDAQABAkEAmxEfpaINXUaxvlVOzrNErdbV7+quAVMFVPd8J9mg5GXTJkIe
//tljnbBjuJOj8SckNyG3Q/v6t/V0JEUbUhNTecQIhAPGLRhZYJBjAwPaACHtIup9q
//1w0A5h5WXLXaE/wv5ij3AiEA2cJ+tyNo2e92jdrRDv4UJpxacJtlbjkQH4Zu6oCd
//vX0CIFXZV27HowS9NZgnB1yyC8pvUcHIaQGtVkQ4H1RJvfcfAiAs7ELX5SYsT4pV
//mV6niSL/FCJUOLqkEoGQ/1rCZeYkwQIgZMX5LTa8SYIDSg0nDdYoeaSHSUB1J/a4
//qbGVu2RQoRo=
//-----END PRIVATE KEY-----';
//
////$priKey = formatPriKey($priKey);
////$pubKey = formatPubKey($pubKey);
//$params = [
//    "merchant_id"=>"1",
//    "uid"=>"2122334455",
//    "out_trade_id"=>"13423423423",
//    "amount"=>"88",
//    "subject"=>"活动红包"
//];
//
////获取预处理字符串
//$signString = getSignString($params);
////获取签名
//$sign = getSign($signString,$priKey);
//
////验证签名
//$res = checkSign($pubKey,$sign,$signString);
//var_dump($res);//结果为 true
//
//
///**
// * 生成签名
// * @param    string     $signString 待签名字符串
// * @param    [type]     $priKey     私钥
// * @return   string     base64结果值
// */
//function getSign($signString,$priKey){
//    $privKeyId = openssl_pkey_get_private($priKey);
//    $signature = '';
//    openssl_sign($signString, $signature, $privKeyId,OPENSSL_ALGO_SHA256);
//    openssl_free_key($privKeyId);
//    return base64_encode($signature);
//}
//
///**
// * 校验签名
// * @param    string     $pubKey 公钥
// * @param    string     $sign   签名
// * @param    string     $toSign 待签名字符串
// * @param    string     $signature_alg 签名方式 比如 sha1WithRSAEncryption 或者sha512
// * @return   bool
// */
//function checkSign($pubKey,$sign,$toSign,$signature_alg=OPENSSL_ALGO_SHA256){
//    $publicKeyId = openssl_pkey_get_public($pubKey);
//    $result = openssl_verify($toSign, base64_decode($sign), $publicKeyId,$signature_alg);
//    openssl_free_key($publicKeyId);
//    return $result === 1 ? true : false;
//}
//
///**
// * 获取待签名字符串
// * @param    array     $params 参数数组
// * @return   string
// */
//function getSignString($params){
//    unset($params['sign']);
//    ksort($params);
//    reset($params);
//    $pairs = array();
//    foreach ($params as $k => $v) {
//        if(!empty($v)){
//            $pairs[] = "$k=$v";
//        }
//    }
//
//    return implode('&', $pairs);
//}
