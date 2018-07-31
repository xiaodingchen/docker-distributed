<?php 

/**
 * 使用哈希算法实现分布式缓存
 */

interface storeInterface
{
    public function set($key, $value = null);

    public function get($key);

    public function has($key);

    public function remove($key);

    //public function cluster();
}


class redisClient implements storeInterface
{
    private $nums = 10;
    private $timeout = 20;
    static private $_static = null;
    private $key;

    static public function getInstance()
    {
        if(!self::$_static instanceof store){
            self::$_static = new self;
        }

        return self::$_static;
    }

    public function set($key, $value = null)
    {
        return $this->client($key)->set($key, $value);
    }

    public function get($key)
    {
        return $this->client($key)->get($key);
    }

    public function has($key)
    {
        return $this->client($key)->exists($key);
    }

    public function remove($key)
    {
        return $this->client($key)->delete($key);
    }

    public function close()
    {
        // if($this->key){
        //     return $this->client($this->key)->close();
        // }

        return true;
    }


    /**
     * @return object Redis
     */
    protected function client($key)
    {
        $server = explode(':', $this->server($key));
        if(!isset($server[1])){
            $server[1] = 6379;
        }
        try {
            $redis = new Redis;
            $redis->connect($server[0], $server[1], $this->timeout);
            $redis->ping();
        } catch (Exception $e) {
            // 如果redis连不上，在实际业务中要做个业务报警，把这个服务器从当前集群中剔除，然后重新获取服务器
            // 这里只是简单处理，直接中断程序运行
            print_r($e->getMessage());exit;
        }
        
        return $redis;
    }

    /**
     * @return string
     */
    protected function server($key)
    {
        $this->key = $key;
        $servers = $this->config();
        $tmpArr = [];
        $codes = [];
        // 增加虚拟节点，形成一个环
        foreach ($servers as $value) {
            for ($i=0; $i < $this->nums; $i++) {
                $hashCode = crc32($value . '#' . $i); 
                $tmpArr[$hashCode] = $value . '#' . $i;
                $codes[] = $hashCode;
            }
        }

        sort($codes);
        $keyCode = crc32($key);
        foreach ($codes as $code) {
            // 在环上顺时针获取离键最近的一个服务器节点
            if($code > $keyCode){
                $server = explode('#', $tmpArr[$code]);
                //print_r($server);
                return $server[0];
            }
        }


        return $servers[0];
    }

    // 按照设计来讲，集群配置是不应该写在这的。
    // 本示例只是用来测试
    protected function config()
    {
        return [
            'server4_redis_1',
            'server3_redis_1',
            'server2_redis_1',
            'server1_redis_1',
        ];
    }
}

class store
{
    static public function getInstance()
    {
        return redisClient::getInstance();
    }
}

