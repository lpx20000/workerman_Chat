<?php
/**
 * Created by PhpStorm.
 * User: 雨鱼
 * Date: 2017/3/3
 * Time: 15:10
 */

namespace App\Service;



use App\Models\User;
use Workerman\Worker;

class Server
{
    /**
     * @var Worker
     */
    protected $worker;
    /**
     * @var
     */
    protected $config;

    /**
     * 客户端资源
     * @var array
     */
    protected $members = [];
    /**
     * 客户端名字
     * @var array
     */
    protected  $client = [];
    /**
     * Server constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->worker = new Worker($this->config['type'].$this->config['port']);
        $this->init();
    }

    /**
     * Create: 雨鱼
     * init config
     */
    protected function init()
    {
        $this->worker->count = $this->config['count'];
        $this->worker->name = $this->config['name'];
        $this->worker->reusePort = true;
        $this->worker->listen();
        $workerMethod = [
            'WorkerStart',
            'Connect',
            'Message',
            'Close',
            'WorkerStop',
            'WorkerReload'
        ];
        array_map([$this, 'start'],$workerMethod);
    }

    /**
     * @param $method
     * Create: 雨鱼
     */
    protected function start($method)
    {
        $this->worker->{'on'.$method} = [$this, lcfirst($method)];
    }
    /**
     * @param $worker
     * Create: 雨鱼
     */
    public function workerStart($worker)
    {
        echo 'worker start'."\n";
        echo "进程：worker->id={$worker->id}\n";
    }

    /**
     * @param $connection
     * @return bool
     * Create: 雨鱼
     */
    public function connect($connection)
    {
        //多进程，避免进程间id重复
//        if ($this->config['count'] > 1) {
//            foreach($this->worker->connections as $con) {
//                $con->id .= $this->worker->id;
//            }
//        }
//        echo $connection->id;
        $user = 'chat'.rand(1, 2000);

        if (!isset($this->client[$connection->id])) {
            $this->client[$connection->id] = $user;
            $connection->name = $user;
            $this->members[$connection->id] = $connection;
        }

        //离线时间上线发送消息

        //通知所有客户端，有新用户上线
        $message = [
            'type' => 'login',
            'count' => count($this->client),
            'member' => $connection->name,
            'all' => $this->client,
        ];
        echo $connection->name.'进入聊天室'."\n";
        return $this->broadCast($message);
    }

    /**
     * @param $connection
     * @param $data
     * Create: 雨鱼
     */
    public function message($connection, $data)
    {
        $data = json_decode($data, true);
        $this->swtichTypeMessage($data, $connection);
    }

    /**
     * @param $connection
     * Create: 雨鱼
     */
    public function close($connection)
    {
        unset($this->members[$connection->id]);
//        $key = array_search($connection->name, static::$client);
        unset($this->client[$connection->id]);
        $message = [
            'type' => 'logout',
            'count' => count($this->client),
            'member' => $connection->name,
            'all' => $this->client,
        ];
        echo $connection->name.'离开聊天室。。'."\n";
        $this->broadCast($message);
    }

    /**
     * Create: 雨鱼
     */
    public function workerStop()
    {
        echo 'worker stopping...'. "\n";
    }

    /**
     * Create: 雨鱼
     */
    public function workerReload()
    {
        $message = [
            'type' => 'info',
            'message' => 'worker reloading'
        ];
        $this->broadCast($message);
    }

    /**
     * @param $data
     * @param $connection
     * Create: 雨鱼
     * @return bool
     * @internal param $to
     * @internal param $message
     */
    protected function swtichTypeMessage($data, $connection)
    {
        $info = $this->checkTextOrImage($data);

        if ($data['type'] == 'all') {
            $message = [
                'type' => 'say',
                'name' =>$connection->name,
                'message'=>$info,
                'infoType' => $data['infoType']
            ];

            return $this->broadCast($message, $connection);
        }

        if ($connection = $this->members[$data['type']]) {
            $message = $info;
            $to = $connection->name;
        } else{
            $to = $connection->name;
            $message = '该用户不存在。。。';
        }

        $message = ['type' => 'say','name' =>$connection->name, 'message'=>$message, 'infoType' => $data['infoType'], 'to' => $to];
        $this->sendMessageByUid($message, $connection);
    }

    /**
     * @param $data
     * @return string
     * Create: 雨鱼
     */
    protected function checkTextOrImage($data)
    {
        //消息分为图片或文本消息
        if ($data['infoType'] == 'image') {
            $image = time().uniqid();
            //获取图片后缀
            preg_match('/^(data:\s*image\/(\w+);base64,)/', $data['message'], $result);
            $image = '/image/'.$image.'.'.$result[2];
            file_put_contents('../public'.$image, base64_decode(str_replace($result[1], '', $data['message'])));
            $html = '';
            $html .='<div id="example1" data-chocolat-title="Set title">';
            $html .='<a class="chocolat-image" href="http://'.$this->config['DOMAIN'].$image.'" title="图片">';
            $html .='<img src="http://'.$this->config['DOMAIN'].$image.'" width="100" height="100"></a>';
            $html .='</div>';
            return $html;
        }
        return $data['message'];
    }

    /**
     * @param $message
     * @param $connection
     * @return bool
     * Create: 雨鱼
     */
    protected function sendMessageByUid($message, $connection)
    {
        if($connection->id) {
            $connection->send(json_encode($message));
        }
        return true;
    }

    /**
     * send message to client
     * @param $message
     * @param null $connection
     * @return bool Create: 雨鱼
     * Create: 雨鱼
     */
    protected function broadCast($message, $connection = null)
    {
        foreach ($this->members as $connect){
            if ($connect == $connection && $connection != null) continue;
            $connect->send(json_encode($message));
        }
        return true;
    }

    /**
     * Create: 雨鱼
     * run workerman
     */
    public function run()
    {
        Worker::$logFile = $this->config['logFile'];
        Worker::runAll();
    }
}