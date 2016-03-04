<?php
// The MIT License (MIT)

// Copyright (c) 2016 Eric Hu

//  Permission is hereby granted, free of charge, to any person obtaining a
//  copy of this software and associated documentation files (the "Software"),
//  to deal in the Software without restriction, including without limitation
//  the rights to use, copy, modify, merge, publish, distribute, sublicense,
//  and/or sell copies of the Software, and to permit persons to whom the
//  Software is furnished to do so, subject to the following conditions:
//
//  The above copyright notice and this permission notice shall be included in
//  all copies or substantial portions of the Software.
//
//  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
//  OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
//  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
//  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
//  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
//  FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
//  DEALINGS IN THE SOFTWARE.

/**
 * 这是一个Maple服务端框架的trigger,php版本,原python版本内部实现了锁,来保证线程安全.
 * 而php本身是线程安全,所以没有实现锁机制.
 * @author Eric Hu <hushulin12321@126.com>
 */

/* code */
// demo

// $client = new Phptrigger('127.0.0.1' , 28000);
// if ($client->connect()) {
// 	$client->write_to_worker(['uid' => 123 , 'ax' => 456] , 2);
// 	$client->close();
// }

class Phptrigger
{

	private $socket;
	public $address;
	public $port;
	private $headerlen1 = 60;
	private $headerlen2 = 24;
	private $magic = 2037952207;

	function __construct($address , $port)
	{
		$this->address = $address;
		$this->port = $port;
	}

	public function genOptions($key , $value = null)
	{
		$string = "";
		switch ($key) {
			case 'magic':
				$string = hex2bin(base_convert($value, 10, 16));
				break;

			case 'version':
			case 'inner':
			case 'flag':
				$string = pack('n' , $value);
				break;

			case 'packet_len':
			case 'cmd':
				$string = pack('N' , $value);
				break;

			// 44 & 8
			case 'end':
				for ($i=0; $i < $value; $i++) {
					$string .= chr(0x00);
				}
				break;

			default:
				break;
		}

		return $string;
	}

	/**
	 * @param array $data 需要发送的数组 会组包成json格式
	 * @param integer $cmd 消息命令字
	 * @return none
	 */
	public function write_to_worker($data= [] , $cmd = 0)
	{
		$body = json_encode($data);
		// 透传到worker进行处理
		// 类似IP隧道的做法
		$buf = "";
		$buf .= $this->genOptions('magic' , $this->magic);
		$buf .= $this->genOptions('version' , 0);
		$buf .= $this->genOptions('inner' , 0);
		$buf .= $this->genOptions('packet_len' , $this->headerlen1 + $this->headerlen2 + strlen($body));
		$buf .= $this->genOptions('cmd' , 100);
		$buf .= $this->genOptions('end' , 44);
		$buf .= $this->genOptions('magic' , $this->magic);
		$buf .= $this->genOptions('version' , 0);
		$buf .= $this->genOptions('flag' , 0);
		$buf .= $this->genOptions('packet_len' , $this->headerlen2 + strlen($body));
		$buf .= $this->genOptions('cmd' , $cmd);
		$buf .= $this->genOptions('end' , 8);
		$buf .= $body;
		fwrite($this->socket, $buf);
	}

	/**
	 * 向用户下发消息
	 * @param array mixed $data_list 用户组
	 * 格式为
     * [(uids, box), (uids, box, userdata) ...]
     * uids是一个数组 [-1] 所有已登录连接
     * uids是一个数组 [-2] 所有连接
     * uids是一个数组 [-3] 所有未登录连接
     * userdata可不传，默认为0，conn.userdata & userdata == userdata
	 */
	public function write_to_users($data_list = [])
	{
		# code...
	}

	public function connect()
	{
		$address = gethostbyname($this->address);
		$this->socket = fsockopen($address , $this->port , $errno , $errstr , 60);

		if (!$this->socket) {
			error_log("fsockopen() $errno, $errstr \n");
			return false;
		}

		stream_set_timeout($this->socket, 5);
		stream_set_blocking($this->socket, 0);

		return true;
	}

	public function read($int = 8192 , $nb = false)
	{
		$string="";
		$togo = $int;

		if($nb){
			return fread($this->socket, $togo);
		}

		while (!feof($this->socket) && $togo>0) {
			$fread = fread($this->socket, $togo);
			$string .= $fread;
			$togo = $int - strlen($string);
		}

		return $string;
	}

	public function close()
	{
		fclose($this->socket);
		$this->socket = '';
	}
}
