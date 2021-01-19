<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/1/18 0018
 * Time: 16:07
 */

namespace app\api\controller;


use app\common\controller\Api;
use app\common\controller\Wxpay;
class Pay extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /*
     * 微信支付回调
     * */
    public function back()
    {
        $data = file_get_contents('php://input');
        //file_put_contents('test.txt', $data);
        $msg = (array)simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

        //验证签名
        $getsign = $msg['sign'];
        unset($msg['sign']);
        $wxpay = new Wxpay();
        $newsign = $wxpay->getSign($msg);
        //如果签名通过
        if ($getsign == $newsign) {
            if ($msg['result_code'] == "SUCCESS" && $msg['return_code'] == "SUCCESS") {
                //如果返回成功,插入一条已付款,代发货的购买记录

            }
        }
    }

}