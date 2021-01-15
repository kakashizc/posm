<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/1/15 0015
 * Time: 10:02
 */

namespace app\api\controller;


use app\common\controller\Api;
/*
 * 需要token的操作
 *
 * */
class Total extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    private $_uid;

    protected function _initialize()
    {
        parent::_initialize();
        $jwt = $this->request->header('Authorization');
        if($jwt){
            $this->check_token($jwt);
            $this->_uid = $this->_token['uid'];
        }else{
            $this->success('缺少token','','401');
        }
    }

}