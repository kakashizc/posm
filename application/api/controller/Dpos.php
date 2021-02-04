<?php
namespace app\api\controller;

use app\admin\model\AgoodsSn;
use app\common\controller\Api;
use think\Db;
use think\Exception;

class Dpos extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    protected $iv;

    protected $desKey;

    public $public_key;
    private $key;
    private $shakey;

    public function __construct()
    {
        parent::__construct();

        $this->iv = "1234567-";

        $this->key = '755d110bk03jrisn383hl3ns3desKeyPart1';

        $this->desKey = "k03jrisn383hl3ns";

        $this->shakey = "lsj452lh2ns2cj3ss3hf5k3b3ms3n31k";

        $this->public_key = 'MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAM9FI5xYRWZJcQKkHPYcYIrZM+jsc+kvAtXusbXyM4Aito+P3XNTMvyAPyu8B4a0Kgfe36i70hprV04Fr6xbaGUCAwEAAQ==';
    }

    /*
     * 约定的值:
     * shaKey：`lsj452lh2ns2cj3ss3hf5k3b3ms3n31k`
     * des3Key：`k03jrisn383hl3ns`
     * 公钥:MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAM9FI5xYRWZJcQKkHPYcYIrZM+jsc+kvAtXusbXyM4Aito+P3XNTMvyAPyu8B4a0Kgfe36i70hprV04Fr6xbaGUCAwEAAQ==
     *
     * 加密规则：
    (1) 生成8位字符的随机字符串：`randomKey`；

    (2) 用rsa私钥对`randomKey `进行加密得到结果：`one`；

    (3) 用约定的`randomKey`拼接`3desKeyPart1`得到`desKey`，对请求报文进行3des加密,然后base64编码后得到结果：`two`；

    (4) 将`one`, `two`, `SHAKey`依次拼接做sha1得到结果`three`；

    (5) 组装成json格式 `{“one”:one:”two”:two:”three”:three}`放入httpbody以post方式提交到服务器。

     * 接收的报文: {“one”:one:”two”:two:”three”:three}
     *
     * 解密步骤:
     * 1, 先对one 进行rsa 解密,获取 randomkey
     * 2, 用randomkey 拼接 `3desKeyPart1`得到`desKey` ,用 3des 解密数据,获取请求的报文
     *
     * */

    /**
     * Rsa解密
     * @param string $encryptData
     * @return string
     */
    public function decode($public_key,$encryptData) {
        $pem = chunk_split($public_key,64,"\n");//转换为pem格式的公钥

        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
        $publicKey = openssl_pkey_get_public($pem);

        // ($publicKey) or die('公钥不可用');

        if (!$publicKey) return '0004';

        //解密以后的数据
        $decryptData = '';
        $encryptData = strtr($encryptData, '-_', '+/');
        $encryptData = base64_decode($encryptData);

        // echo $encryptData;
        // echo "\n";
        ///////////////////////////////用公钥解密////////////////////////
        if (openssl_public_decrypt($encryptData, $decryptData, $publicKey,OPENSSL_PKCS1_PADDING)) {
            return $decryptData;
        } else {
            return '0003';
        }
    }

    /**
     * 3des加密
     */
    public function encrypt($input){
        $size = @mcrypt_get_block_size(MCRYPT_3DES,MCRYPT_MODE_CBC);
        $input = $this->pkcs5_pad($input, $size);
        $key = str_pad($this->key,24,'0');
        $td = @mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
        if( $this->iv == '' )
        {
            $iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        }
        else
        {
            $iv = $this->iv;
        }
        @mcrypt_generic_init($td, $key, $iv);
        $data = @mcrypt_generic($td, $input);
        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    /**
     * 3des解密
     */
    public function decrypt($encrypted, $key){
        $encrypted = strtr($encrypted, '-_', '+/');
        $encrypted  = base64_decode($encrypted);

        $key = str_pad($key,24,'0');

        $td = @mcrypt_module_open(MCRYPT_3DES,'',MCRYPT_MODE_CBC,'');

        if( $this->iv == '' )
        {
            $iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        }
        else
        {
            $iv = $this->iv;
        }

        $ks = @mcrypt_enc_get_key_size($td);
        @mcrypt_generic_init($td, $key, $iv);
        $decrypted = @mdecrypt_generic($td, $encrypted);
        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);
        $y=$this->pkcs5_unpad($decrypted);
        return $y;
    }

    public function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public function pkcs5_unpad($text){
        $pad = ord($text{strlen($text)-1});
        if ($pad > strlen($text)) {
            return 'false1';
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad){
            return 'false2';
        }
        return substr($text, 0, -1 * $pad);
    }

    public function PaddingPKCS7($data) {
        $block_size = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_CBC);
        $padding_char = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($padding_char),$padding_char);
        return $data;
    }
}