# maple
PHP Kits for maple
# 安装
git clone 
# 快速开始

$client = new Phptrigger('127.0.0.1' , 28000);
  if ($client->connect()) {
  $client->write_to_worker(['uid' => 123 , 'ax' => 456] , 2);
  $client->close();
}
