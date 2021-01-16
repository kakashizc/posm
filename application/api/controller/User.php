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
            $data['avatar'] = IMG.$data['avatar'];
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

    /*
     * 更换头像
     * */
    public function chead()
    {
        $imgstr = $this->request->param('img');
        $up = Auser::where('id',$this->_uid)->update(['avatar'=>$imgstr]);
        if ($up){
            $this->success('成功',['img'=>IMG.$imgstr],'0');
        }else{
            $this->success('修改失败','','1');
        }
    }
    /*
     * 更换昵称
     * */
    public function chname()
    {
        $imgstr = $this->request->param('name');
        $up = Auser::where('id',$this->_uid)->update(['nickName'=>$imgstr]);
        if ($up){
            $this->success('修改成功','','0');
        }else{
            $this->success('修改失败','','1');
        }
    }

    /*
     * 刷手,实名认证
     * */
    public function indent()
    {
        $up = [];
        $up['indent_name'] = $this->request->param('indent_name');
        $up['indent_no'] = $this->request->param('indent_no');
        $up['indent_face_image'] = $this->request->param('front');
        $up['indent_back_image'] = $this->request->param('back');
        $up['status'] = '1';
        $res = Auser::where('id',$this->_uid)->update($up);
        if ($res){
            $this->success('申请成功,等待审核','','0');
        }else{
            $this->success('提交失败','','1');
        }
    }
    /*
     * 查看实名认证状态
     * */
    public function indent_status()
    {
        $res = Auser::get(function ($query){
            $query->where('id',$this->_uid)->field('status');
        });
        if (!$res){
            $res['status'] = '4';
        }
        $this->success('成功',$res,'0');
    }
}
