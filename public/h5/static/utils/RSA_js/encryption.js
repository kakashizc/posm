import {JSEncrypt} from './jsencrypt.min.js'
let encrypt = new JSEncrypt();
module.exports = {
	// 加密
	encrypt(str) {
		let jiami =`
			MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBALnbZIjRDZEkcHMT0nPw89Nf2ocNGWDJ
			XzkoLboLcHHro+EN4cBOHJn/75TzqC0E2zALR4z/40rG99q6ispE7BECAwEAAQ==`
		encrypt.setPublicKey(jiami);
		return encrypt.encrypt(str);
	},
	// 解密
	decrypt(str){
		let jiemi = `
			MIIBVQIBADANBgkqhkiG9w0BAQEFAASCAT8wggE7AgEAAkEAudtkiNENkSRwcxPS
			c/Dz01/ahw0ZYMlfOSgtugtwceuj4Q3hwE4cmf/vlPOoLQTbMAtHjP/jSsb32rqK
			ykTsEQIDAQABAkEAmCzGmC9zSE/pso+cVUoImh11s/ZJvTGkQgxfxGDxZJQB70D3
			GJy/3iir0XuIp5uwx9B/usLGAmQ3s8wEwidvsQIhAOfwZCYVihiRTPa6fax3UPx6
			ZX0CLZghto/0L70yHiorAiEAzSMzSsOJS3K9zUa91w4h0zRuA268/j/455EINvYA
			ULMCIQC3RRtNgI2bNgzzlI7DXZCPwAM7kSIH6PPBsfia7eQp+wIgOk67EZEqwStI
			D8T/yNTXgHTyoD8lsQ717LwonZam2EkCIHmG8Jl75aXNOfjyNwpQ5am8f8gYd8i6
			slIXcif1dYhj` 
		encrypt.setPublicKey(jiemi);
		return encrypt.decrypt(str);
	}
}

