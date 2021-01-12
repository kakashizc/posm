<?php

namespace app\api\controller;

use app\common\controller\Api;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页123
     *
     */
    public function index()
    {
        $rsa = new Rsa();
        $data['name'] = 'Tom';
        $data['age']  = '20';
//        $privEncrypt = $rsa->privEncrypt(json_encode($data));
//        echo '私钥加密:'.$privEncrypt.'<br>';
//
//        $publicDecrypt = $rsa->publicDecrypt($privEncrypt);
//        echo '公钥解密:'.$publicDecrypt.'<br>';

        $publicEncrypt = $rsa->publicEncrypt(json_encode($data));
        echo '公钥加密:'.$publicEncrypt.'<br>';

        $privDecrypt = $rsa->privDecrypt($publicEncrypt);
        echo '私钥解密:'.$privDecrypt.'<br>';

    }
}
