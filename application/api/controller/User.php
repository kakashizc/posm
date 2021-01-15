<?php

namespace app\api\controller;

use app\common\controller\Api;
use fast\Http;
use think\Db;
use think\Request;
use app\admin\model\Auser;
/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    private $_uid;
    public function _initialize()
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

    /*
     * 我的信息
     * */
    public function myinfo()
    {
        $uid = $this->_uid;
        $data = Auser::get(function ($list) use ($uid){
            $list->where('id',$uid);
        });
        if ($data){
            $this->success('获取成功',$data,'0');
        }else{
            $this->success('无数据','','1');
        }
    }

    /*
     * 绑定手机号
     * */
    public function bindm()
    {
        $phone = $this->request->param('mobile');
        if ( !$phone || !preg_match("/^1[345789]{1}\d{9}$/",$phone) ) {
            $this->success('缺少参数或手机号格式错误','','1');
        }
        $user = Auser::get($this->_uid);
        $user->mobile = $phone;
        $res = $user->save();
        if ($res){
            $this->success('绑定成功','','0');
        }else{
            $this->success('绑定失败','','1');
        }
    }

}
