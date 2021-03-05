<?php

namespace app\api\controller;

use app\admin\model\AgoodsSn;
use app\admin\model\Notice;
use app\admin\model\NoticeGg;
use app\common\controller\Api;
use app\admin\model\Agoods;
use fast\Http;
use think\Db;
use think\Request;
use app\admin\model\Auser;
use app\admin\model\Order as AOrder;
use think\cache;
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
     * 客服电话
     * */
    public function kefu()
    {
        $kefu = Db::name('kefu')->find();
        if ($kefu){
            $this->success('成功',$kefu,'0');
        }else{
            $this->success('无','','1');
        }
    }
    /*
    * 短信修改密码
    * */
    public function rep()
    {  
        $phone = $this->request->param('mobile');
        
        $pass = $this->request->param('password');
        if ( !$pass || !$phone || !preg_match("/^1[345789]{1}\d{9}$/",$phone) ) {
            $this->success('缺少参数或手机号格式错误','','1');
        }

        //验证手机短信验证码,暂时不用
        $msg = $this->request->param('msg');//短信验证码
        $cache_msg = Cache::get($phone);
        if ($msg != $cache_msg) {//如果验证码不正确,退出
            $this->success('短信验证码错误或者超时', '','1');
        }

        $user = Auser::get(['mobile'=>$phone]);
        if (!$user){
            $this->success('账号不存在','','1');
        }
        $user->mobile = $phone;
        $user->password = md5($pass);
        $res = $user->save();
        if ($res){
            $this->success('成功','','0');
        }else{
            $this->success('失败','','1');
        }
    }
    /*
     * 获取机具
     *
     * */
    public function goodList()
    {
        $list = Agoods::all(function ($query){
            $query->where('status','1')->field("id,name,price,factory,type,concat('$this->img',image) as image,stock");
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
        $mobile = $this->request->param("mobile");
        if ($pidstr){
            $pid = $pidstr;
        }
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $Appid . '&secret=' . $AppSecret . '&js_code=' . $code . '&grant_type=authorization_code';
        $arr = Http::get($url);
        //获取openid
        $arr = json_decode($arr, true);

        //获取当前的openid
        $openId = $arr['openid'];
        //进行查询
        $personnel_find = Db::name('auser')->where("mobile", $mobile)->find();

        if ( $personnel_find['openid'] ) {

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

            Db::name('auser')->where('mobile',$mobile)->update($personnel_data);
            //进行查询
            $personnel_find = Db::name('auser')->where('mobile',$mobile)->find();
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

    /*
     * 通知 / 公告
     * */
    public function notice()
    {
        $notice = Notice::all();
        foreach ($notice  as $k => &$v){
            $v['content'] = str_replace('src="', 'src="http://' . $_SERVER['HTTP_HOST'], $v['content']);
        }
        $gg = NoticeGg::all();
        foreach ($gg  as $kk => &$vv){
            $vv['content'] = str_replace('src="', 'src="http://' . $_SERVER['HTTP_HOST'], $vv['content']);
        }
        $ret = [
            'tongzhi' => $notice,
            'gonggao' => $gg
        ];
        $this->success('获取成功',$ret,'0');
    }
    /*
     * 关于我们
     * */
    public function us()
    {
        $data = Db::name('us')->find();
        $data['content'] = str_replace('src="', 'src="http://' . $_SERVER['HTTP_HOST'], $data['content']);
        $this->success('获取成功',$data,'0');
    }

    /*
     * 注册协议
     * */
    public function xy()
    {
        $data = Db::name('xy')->find();
        $data['content'] = str_replace('src="', 'src="http://' . $_SERVER['HTTP_HOST'], $data['content']);
        $this->success('获取成功',$data,'0');
    }
    /*
     * 隐私协议
     * */
    public function xyys()
    {
        $data = Db::name('xyys')->find();
        $data['content'] = str_replace('src="', 'src="http://' . $_SERVER['HTTP_HOST'], $data['content']);
        $this->success('获取成功',$data,'0');
    }
    /*
     * 展业培训
     * */
    public function zy()
    {
        $data = Db::name('zy')->find();
        $data['content'] = str_replace('src="', 'src="http://' . $_SERVER['HTTP_HOST'], $data['content']);
        $this->success('获取成功',$data,'0');
    }
    /*
     * 首页推荐活动
     * */
    public function rec()
    {
        $data = Db::name('rec')->field('id,image')->select()->toArray();
        foreach ($data as $k=>$v){
            //$data[$k]['content'] = str_replace('src="', 'src="http://' . $_SERVER['HTTP_HOST'], $v['content']);
            $data[$k]['image'] = IMG.$v['image'];
        }
        $this->success('获取成功',$data,'0');
    }
    /*
     * 首页推荐活动->内容
     * */
    public function rec_content()
    {
        $id = $this->request->param('id');
        $data = Db::name('rec')->field('content')->find($id);
        $data['content'] = str_replace('src="', 'src="http://' . $_SERVER['HTTP_HOST'], $data['content']);
        $this->success('获取成功',$data,'0');
    }
    /*
     * 意见反馈
     * */
    public function fankui()
    {
        $data['cont'] = $this->request->param('cont');
        $data['mobile'] = $this->request->param('mobile');
        $res = Db::name('fankui')->insertGetId($data);
        if ($res){
            $this->success('反馈成功','','0');
        }else{
            $this->success('反馈失败','','1');
        }
    }
    
    /*
     * 获取海报
     * */
    public function haibao()
    {
        $hai = Db::name('haibao')->find();
        $data['image'] = IMG.$hai['image'];
        $this->success('成功',$data,'0');
    }
}
