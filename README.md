# maple框架下的php版trigger

这是maple框架的触发器,目前只实现了write_to_worker,具体参看[maple](https://github.com/dantezhu/maple)

# 安装
``git clone git@github.com:hushulin/maple.git``


# 快速开始

``$client = new Phptrigger('127.0.0.1' , 28000);``

``  if ($client->connect()) {``

``  $client->write_to_worker(['uid' => 123 , 'ax' => 456] , 2);``

``  $client->close();``

``}``

````

