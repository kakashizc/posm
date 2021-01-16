<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\admin\model\Agoods;
use fast\Http;
use think\Db;
use think\Request;
use app\admin\model\Auser;
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
    /*
     * 获取机具
     *
     * */
    public function goodList()
    {
        $list = Agoods::all(function ($query){
            $query->where('status','1')->field("id,name,price,factory,type,concat('$this->img',image) as image");
        });
        if ($list){
            $this->success('成功',$list,'0');
        }else{
            $this->success('无机具','','1');
        }
    }

    /*
     * 微信授权登录
     * */
    public function wxLogin()
    {
        $Appid = 'wx7b2cb15c44985669';
        $AppSecret = 'bc5e4434aa26d64296c864eee6434b68';

        $code = $this->request->param("code");
        $avatarUrl = $this->request->param("avatar");
        $nickName = $this->request->param("name");
        $pidstr = $this->request->param('pidstr');

        if ($pidstr){
            // 示例: pidstr=12
            $pidstr = explode('=',$pidstr);
            $pid = $pidstr[1];
        }
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $Appid . '&secret=' . $AppSecret . '&js_code=' . $code . '&grant_type=authorization_code';
        $arr = Http::get($url);
        //获取openid
        $arr = json_decode($arr, true);

        //获取当前的openid
        $openId = $arr['openid'];
        //进行查询
        $personnel_find = Db::name('auser')->where("openid", $openId)->find();

        if (!empty($personnel_find)) {

            $payload = array('iss'=>'admin','iat'=>time(),'exp'=>time()+72000000000,'nbf'=>time(),'sub'=>'www.admin.com','uid'=>$personnel_find['id']);
            $token = Jwt::getToken($payload);
            $data = [
                'token' => $token
            ];
            $this->success('登录成功', $data, '0');
        } else {
            //获取当前openId
            $personnel_data['openid'] = $openId;
            //获取当前昵称
            $personnel_data['nickName'] = $nickName;
            //获取当前头像
            $personnel_data['avatar'] = $avatarUrl;
            //设置上级
            $personnel_data['pid'] = isset($pid)?:0;
            //获取当前时间
            $personnel_data['ctime'] = time();
            //执行添加操作
            $personnel_id = Db::name('auser')->insertGetId($personnel_data);
            //进行查询
            $personnel_find = Db::name('auser')->where('id', $personnel_id)->find();
            $payload = array('iss'=>'admin','iat'=>time(),'exp'=>time()+72000000000,'nbf'=>time(),'sub'=>'www.admin.com','uid'=>$personnel_find['id']);
            $token = Jwt::getToken($payload);
            $data = [
                'token' => $token
            ];
            $this->getCode($personnel_find['id']);
            $this->success('登录成功', $data, '0');
        }
    }

    /*
     * 生成不重复的推荐码
     * */
    private function getCode($uid)
    {
        $user = Auser::get($uid);
        if (!$user->code){
            //生成code
            $code =  mt_rand(10000,999999);
            //查看生成的推荐码是否已存在
            $is = Auser::where(['code'=>$code])->find();
            if ($is){
                $this->getCode($uid);
            }else{
                //如果没人用这个推荐码, 那么更新为当前用户的推荐码
                $user->code = $code;
                $user->save();
                return  $code;
            }
        }else{
            return $user->code;
        }
    }
}
