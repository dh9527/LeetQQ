<?php
namespace protocol\web\io;

use phqagent\message\MessageQueue;
use phqagent\message\Message;
use protocol\web\SavedSession;
use phqagent\utils\Curl;

class MessageSender extends \Thread{

    private $cookie;
    private $outbox;

    public function __construct(){
        $this->cookie = SavedSession::$cookie;
        $this->outbox = MessageQueue::getInstance()->getOutbox();
        Curl::init();
        SavedSession::init();
        Message::init();
    }

    public function run(){
        $curl = new Curl();
        while(!$this->shutdown){
            while(count($this->outbox) > 0){
                $message = unserialize($this->outbox->shift());
                $div = 250;
                $lenth = mb_strlen($message['content']);
                $payload = [];
                $count = (int)($lenth / $div);
                for($i = 0; $i <= $count; $i++){
                    $oneline = mb_substr($message['content'], 0, $div);
                    $message['content'] = str_replace($oneline, '', $message['content']);
                    $payload[] = $oneline;
                }
                foreach($payload as $oneline){
                    if($message['type'] == Message::USER){
                        $this->sendUser($curl, $message['target'], $oneline);
                    }elseif($message['type'] == Message::GROUP){
                        $this->sendGroup($curl, $message['target'], $oneline);
                    }
                }
            }
            usleep(100);
        }
    }

    private function sendUser($curl, $uin, $content){
        $this->messageid++;
        $curl->
        setUrl('http://d1.web2.qq.com/channel/send_buddy_msg2')->
        setReferer('http://d1.web2.qq.com/proxy.html?v=20151105001')->
        setPost([
            'r' => json_encode([
                'to' => $uin,
                'content' => '["'.$content.'",["font",{"name":"宋体","size":10,"style":[0,0,0],"color":"000000"}]]',
                'face' => 603,
                'clientid' => SavedSession::$clientid,
                'msg_id' => $this->messageid,
                'psessionid' => SavedSession::$psessionid,
            ], JSON_FORCE_OBJECT)
        ])->
        setCookie($this->cookie)->
        returnHeader(false)->
        setTimeOut(5)->
        exec();
        return true;
    }

    private function sendGroup($curl, $uin, $content){
        $this->messageid++;
        $curl->
        setUrl('http://d1.web2.qq.com/channel/send_qun_msg2')->
        setReferer('http://d1.web2.qq.com/proxy.html?v=20151105001')->
        setPost([
            'r' => json_encode([
                'group_uin' => $uin,
                'content' => '["'.$content.'",["font",{"name":"宋体","size":10,"style":[0,0,0],"color":"000000"}]]',
                'face' => 603,
                'clientid' => SavedSession::$clientid,
                'msg_id' => $this->messageid,
                'psessionid' => SavedSession::$psessionid,
            ], JSON_FORCE_OBJECT)
        ])->
        setCookie($this->cookie)->
        returnHeader(false)->
        setTimeOut(5)->
        exec();
    }
}