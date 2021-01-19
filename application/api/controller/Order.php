<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/1/18 0018
 * Time: 16:07
 */

namespace app\api\controller;


use app\admin\model\Agoods;
use app\common\controller\Api;
use app\common\controller\Wxpay;
use app\admin\model\Auser;
use app\admin\model\Order as AOrder;
class Order extends Api
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
    * 购买机具
    * */
    public function buy()
    {
        $data = $this->request->param();
        $money = $data['price'] * $data['num'];
        $uid = $this->_uid;//用户id
        $user = AUser::get($uid);
        $orderno =  mt_rand(10,10000).'_'.date('YmdHis').'_'.$uid;//生成随机订单号
        $insert = array(
            'order_no' => $orderno,
            'ctime' => time(),
            'goods_id' => $data['goods_id'],
            'u_id' => $uid,
            'price' => $data['price'],
            'num' => $data['num'],
            'total' => $money
        );
        AOrder::create($insert);//保存预订单
        $openid = $user->openid;//用户的openid
        //2,调起支付
        $wx = new Wxpay();
        $return = $wx->getPrePayOrder($uid,$orderno,$money,$openid,'buy pos');
        $parameters = array(
            'appId' => 'wx52e5b542351a721e', //小程序ID
            'timeStamp' => '' . time() . '', //时间戳
            'nonceStr' => $wx->createNoncestr(), //随机串
            'package' => 'prepay_id=' . $return['prepay_id'], //数据包
            'signType' => 'MD5'//签名方式
        );
        //签名
        $parameters['paySign'] = $wx->getSign($parameters);
        $this->success('成功',$parameters,'0');
    }

    /*
     * 支付调试
     *
     * */
    public function buybak()
    {
        // 插入一条订单记录
        $goods_id = $this->request->param('goods_id');
        $num = (int)$this->request->param('num');
        $goodsInfo = Agoods::get($goods_id);
        if (!$goodsInfo) $this->success('无此商品','','1');
        $money = $goodsInfo->price * $num;
        $uid = $this->_uid;//用户id
        $orderno =  mt_rand(10,10000).'_'.date('YmdHis').'_'.$uid;//生成随机订单号
        $insert = array(
            'order_no' => $orderno,
            'ctime' => time(),
            'goods_id' => $goods_id,
            'u_id' => $uid,
            'price' => $goodsInfo->price,
            'num' => $num,
            'total' => $money,
            'status' => '1'
        );
        $res = AOrder::create($insert);//保存预订单
        if ( $res ){
            $this->success('购买成功','','0');
        }else{
            $this->success('购买失败','','1');
        }
    }

}