<?php
/**
 * Created by PhpStorm.
 * User: 雨鱼
 * Date: 2017/3/3
 * Time: 14:45
 */
use App\Service\Server;
use Workerman\Worker;
use Illuminate\Database\Capsule\Manager as Capsule;
// Autoload 自动载入
require '../vendor/autoload.php';

// 属性配置
$config = require '../config/config.php';

// Eloquent ORM
$capsule = new Capsule;

$capsule->addConnection(require '../config/database.php');

$capsule->bootEloquent();

$server = new Server($config);
//$server->run();
Worker::$logFile = $config['logFile'];
Worker::runAll();



