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
use think\Db;

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
        if (!$goods_id  || !$num){
            $this->success('缺少参数','','1');
        }
        $goodsInfo = Agoods::get($goods_id);
        if ($goodsInfo->stock < $num)$this->success('库存不足,请联系平台','','1');
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
            $data['order_no'] = $orderno;
            $data['ctime'] = date('Y-m-d H:i:s',time());
            //减少库存
            $goodsInfo->setDec('stock',$num);
            $this->success('购买成功',$data,'0');
        }else{
            $this->success('购买失败','','1');
        }
    }

    /*
     * 用户获取全部订单
     * */
    public function orders()
    {
        $status = $this->request->param('status')??5;//订单状态:0=未付款,1=待发货,2=已发货,3=已收货,4=已失效,5=全部订单
        if ($status != 5){
            $where = ['order.status'=>$status];
        }else{
            $where = '';
        }

        $datas = collection(AOrder::with(['agoods'])
            ->where('u_id',$this->_uid)
            ->where($where)
            ->select())
            ->toArray();

        if ( !$datas ){
            $this->success('无数据','','1');
        }
        foreach ($datas as &$v){
            $v['agoods']['image'] = IMG. $v['agoods']['image'];
        }
        $this->success('成功',$datas,'0');
    }
}