<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/1/18 0018
 * Time: 16:07
 */

namespace app\api\controller;


use app\common\controller\Api;

class Pay extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /*
     * 乐刷支付回调
     * */
    public function back()
    {

    }
}