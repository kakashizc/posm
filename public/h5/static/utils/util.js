import api from './api.js'
import Qq from './request.js'
module.exports = {
	judeg: function judeg(text) {
		wx.showToast({
			title: text,
			icon: 'none',
			duration: 2000
		})
	},
	tips:function tips(content) {
		uni.showModal({
			content,
			success: res => {
				if (res.confirm) {
					uni.navigateTo({
						url: '/pages/login/login'
					})
				}else{
					setTimeout(()=>{
						uni.switchTab({
							url:'/pages/index/index'
						})
					},300)
				}
			}
		})
	},
	shiming(url) {
		Qq.userReq(api.indent_status).then(res => {
			if (res.data.data.status == 2) {
				uni.navigateTo({
					url
				})
			} else {
				this.judeg('实名成功后才可查看')
			}
		})
	},
}
