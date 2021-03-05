// var url = 'https://pos.zhoujiasong.top/api/';
var url = "https://www.yongshunjinfu.com/api/"

module.exports = {
	wxLogin: url + 'index/wxLogin', //登录授权
	goodList: url + 'index/goodList', //获取机具列表
	userMyinfo: url + 'user/myinfo', //我的信息
	userChead: url + 'user/chead', //修改用户头像	
	userChname: url + 'user/chname', //修改用户昵称	
	indent_status: url + 'user/indent_status', //查看实名认证状态
	user_indent: url + 'user/indent', //实名认证
	user_bindm: url + 'user/bindm', //用户绑定手机号
	editAddress: url + 'user/editAddress', //收货地址
	indexNotice: url + 'index/notice', //通知
	user_myadd: url + 'user/myadd', //我的地址

	user_mycode: url + 'user/mycode', //我的推荐二维码
	order_buybak: url + 'order/buybak', //支付测试接口
	order_orders: url + 'order/orders', //获取全部订单

	wait_set: url + 'finance/wait_set', //待装机
	wati_sign: url + 'finance/wati_sign', //待签约
	finance_sons: url + 'finance/sons', //我的下级人员列表(app中的 通讯录)

	index_us: url + 'index/us', //关于我们
	index_fankui: url + 'index/fankui', //反馈

	finance_my_sn: url + 'finance/my_sn', //给下级划拨--获取sn列表
	wait_set_sel: url + 'finance/wait_set_sel', //待装机--搜索某个用户
	finance_give: url + 'finance/give', //给下级划拨机具
	finance_inrec: url + 'finance/inrec', //入库记录
	finance_inrec_one: url + 'finance/inrec_one', //入库记录--单个搜索
	finance_findpos: url + 'finance/findpos', //机具查询
	finance_select_son: url + 'finance/select_son', //通讯录--搜索
	kefu: url + 'index/kefu', //客服
	feed_detail: url + 'finance/feed_detail', //余额明细
	feed_today: url + 'finance/today', //今日收益

	finance_tixian: url + 'finance/tixian', //提现
	index_xy: url + 'index/xy', //注册协议
	bus_register: url + 'bus/register', //注册
	bus_login: url + 'bus/login', //登录
	user_newm: url + 'user/newm', //登录
	sendMassge: url + 'msg/send', //发送短信验证码
	huodong: url + 'index/rec', //推荐活动
	huodongDetails: url + 'index/rec_content', //推荐活动详情
	zy: url + 'index/zy', //展业培训
	zhexian: url + 'user/weeks', //折线图
	yinsi: url + 'index/xyys', //隐私协议
	forgetPassword: url + 'index/rep',//忘记密码
	uploadFile: url + 'upload/up', //上传图片接口


}
