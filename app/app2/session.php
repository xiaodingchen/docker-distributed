<?php 

class sysSession implements SessionHandlerInterface
{
    private $client;
    // 依赖注入
    public function __construct(storeInterface $client)
    {
        $this->client = $client;
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        return $this->client->get($id);
    }

    public function write($id, $data)
    {
        return $this->client->set($id, $data);
    }

    public function destroy($id)
    {
        return $this->client->remove($id);
    }

    public function gc($lifetime)
    {
        return true;
    }
}
