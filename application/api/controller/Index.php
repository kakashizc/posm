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
    private $_rsa;
    public function _initialize()
    {
        parent::_initialize();
        $this->_rsa = new Rsa();
    }

    /**
     * 首页
     *
     */
    public function index()
    {

        $data['name'] = 'Tom';
        $data['age']  = '20';
//        $privEncrypt = $this->_rsa->privEncrypt(json_encode($data));
//        echo '私钥加密:'.$privEncrypt.'<br>';

//        $publicDecrypt = $this->_rsa->publicDecrypt($privEncrypt);
//        echo '公钥解密:'.$publicDecrypt.'<br>';

//        $publicEncrypt = $this->_rsa->publicEncrypt(json_encode($data));
//        echo '公钥加密:'.$publicEncrypt.'<br>';
        //$publicEncrypt = "KwThwcb97W23MSWZiTjkjhS1wqjLAVbiCfOEFOt2Bs5krZCZCU/mpVwz91UBMl8wZ3isdX/I0/pmdbpIqBHLOQ==";
        $publicEncrypt = "q7Qs/T2ksp3I6aXSsjC75u8HXhVZSUWrD%2BooPQLcqhFz8uCmOk5G9CM/HvEzBecUHg3jKHNCrw7OelyAJeykmw==";
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
}
