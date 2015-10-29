<?php
namespace App;

require '/data/system/libraries/Service.php';
/**
 * Class Msg
 * @package App
 * 弹窗
 */
class Msg
{
    public $handler;
    public $worker_id;
    private $config;
    public $service;
    public $ch;

    function __construct($handler)
    {
        $this->handler = $handler;
        $this->config = \Swoole::$php->config['msg']['master'];
        $this->service = new \Service('chelun');
        $this->ch = new \Swoole\Client\CURL();
    }

    function alert($msg)
    {
        $this->sendWeiXin($msg);
        $this->worker_id = $this->handler->alert->worker_id;
        $mobiles = explode(',',$msg['alert_mobiles']);
        if (!empty($mobiles))
        {
            $msg = $this->handler->build_msg($msg);
            $this->_send($mobiles,$msg);
        }
        else
        {
            $this->log("task worker {$this->worker_id} error.".print_r($msg,1));
        }
    }

    function sendWeiXin($msg)
    {
        //增加微信发送
        $token = $this->getToken();
        if ($token) {
            $msg_url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token={$token}";
            $data = array(
                'touser' => '@all',
                'toparty' => '',
                'totag' => '',
                'msgtype' => 'text',
                'agentid' => 0,
                'text' => array(
                    'content' => $msg,
                ),
                'safe' => 0
            );
            $res = $this->ch->post($msg_url,json_encode($data,JSON_UNESCAPED_UNICODE));
            $this->log("wei xin send msg {$msg}".var_export($res,1));
        } else {
            $this->log("wei xin get token failed");
        }

    }

    function getToken()
    {
        $key = "weixin_token";
        $token = \Swoole::$php->redis->get($key);
        if (!empty($token))
        {
            return $token;
        } else {
            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=wxc45d2ffe103e99c1&corpsecret=7T5TQbFHCYT5J2Z23qPKH3OaefjAIdO3FJjcap_28KUUFAbI0exS5lL4yI2fHKp1";

            $res = $this->ch->get($url);
            $t = json_decode($res,1);
            if (!empty($t['access_token']))
            {
                $token = $t['access_token'];
                \Swoole::$php->redis->set($key,$token,$t['expires_in']-100);
                return $token;
            }
            return false;

        }
    }

    private function _send($mobiles,$message)
    {
        if (!empty($mobiles))
        {
            foreach ($mobiles as $number)
            {
                $res = $this->service->call('Common\SMS::send', $number, $message)->getResult();
                if ($res)
                {
                    $this->log("task worker {$this->worker_id} send msg success: $number  {$message}");
                }
                else
                {
                    $this->log("task worker {$this->worker_id} send msg failed: $number  {$message}");
                }
            }
        }
        else
        {
            $this->log("task worker {$this->worker_id} mobiles empty ");
        }
    }

    public function log($msg)
    {
        $this->handler->alert->log($msg);
    }
}
