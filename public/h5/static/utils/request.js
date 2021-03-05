module.exports = {
    request(url, data = {}, method = "post",header = "{ 'Content-Type': 'application/json', }") {
        return new Promise((resolve, reject) => {
            wx.request({
                url: url,
                data: data,
                method: method,
                header,
                success: res => {
                    if (res.statusCode == 200) {
                        resolve(res)
                    } else {
                        reject(res)
                    }
                }
            })
        })
    },
	userReq(url, data = {}, method = "post") {
        return new Promise((resolve, reject) => {
            wx.request({
                url: url,
                data: data,
                method: method,
                header:{
					'Authorization':uni.getStorageSync('loginInfo').token,
					'Content-Type':'application/x-www-form-urlencoded'
				},
                success: res => {
                    if (res.statusCode == 200) {
                        resolve(res)
                    } else {
                        reject(res)
                    }
                }
            })
        })
    },
	
}