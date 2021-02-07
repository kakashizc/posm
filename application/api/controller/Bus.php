<?php
/**
 * Created by jsong
 * User: Administrator
 * Date: 2021/2/5 0005
 * Time: 11:19
 */

namespace app\api\controller;


use app\common\controller\Api;

class Bus extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /*
     * 业务逻辑:
     * 1, 用户购买一台pos机是88元, 此pos刷卡(信用卡刷)就返给用户88元
     * 2, pos机刷满5000才算真激活
     * 3, v1-v8 8个级别,v1->万1的利润 ,依次类推v8是万8
     *
     * 平台营销奖励:
     *
     *
     * */

}