<?php
/**
 * Created by PhpStorm.
 * User: 雨鱼
 * Date: 2017/3/3
 * Time: 15:07
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $timestamps = false;
    public $table = 'message';
}