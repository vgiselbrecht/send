<?php
require_once 'SocketServer.php';

class gameplay
{
    var $wait;
    var $gameidcounter = 0;
    var $players = array();
    var $games = array();
    
    function setUser($name, $server, $sender)
    {
        $player = new player();
        $player->id = $sender->id;
        $player->name = $name;
        $this->players[] = $player;
        return true;
    }
    
    function newGame($server, $sender)
    {
        if ($this->wait AND $this->wait != $sender->id)
        {
            if (!$this->playerInGame($server, $sender))
            {
                $this->gameidcounter ++;
                $game = new game();
                $game->id =  $this->gameidcounter;
                $game->act = $this->wait;
                $game->player1 = $this->wait;
                $game->player2 = $sender->id;
                //Player1
                $send = array('type'=>'gameStart', 'value'=>array('gegid'=>$game->player2,'gegna'=>$this->getPlayerNameById($game->player2),'gid'=>$game->id, 'pn'=>1));
                $this->sendGeg($game->player1, $send, $server);


                //Player2
                $send = array('type'=>'gameStart', 'value'=>array('gegid'=>$game->player1,'gegna'=>$this->getPlayerNameById($game->player1),'gid'=>$game->id, 'pn'=>2));
                $sender->send(json_encode($send));
                $this->wait = 0;
                $this->games[] = $game;

            }
            return true;
        }
        else
        {
            $this->wait = $sender->id;
            return false;
        }
        
    }
    
    function playerInGame($server, $sender)
    {
        $info = $this->getGameByUserid($sender->id);
        $game = $info[0];
        $geg = $info[1];
        if (!$game)
        {
            return false;
        }
        else 
        {
           $send = array('type'=>'gameStart', 'value'=>array('gegid'=>$geg,'gegna'=>$this->getPlayerNameById($geg),'gid'=>$game->id, 'pn'=>$info[2]));
           $sender->send(json_encode($send)); 
           
           $send = array('type'=>'setPos', 'value'=>$game->pos);
           $sender->send(json_encode($send)); 
            
            
            return true;
        }
            
    }
    
    function getGameByUserid($id)
    {
        foreach ($this->games as $key => $game)
        {
            if ($game->player2 == $id )
            {
                return array($game,$game->player1,2);
            }
            if ($game->player1 == $id)
            {
                return array($game,$game->player2,1);
            }
        }
        return false;
    }
    
    function setPos($server, $sender, $value){
        $gameid = $value[0];
        $game = $this->getGameById($gameid);
        if($game)
        {
            if ($sender->id == $game->act)
            {
                $x= $value[1] -1; 
                $y= $value[2] -1; 
                if ($game->pos[$x][$y] == 0)
                {
                    if ($game->act == $game->player1)
                    {
                        $game->pos[$x][$y] = 1;
                        $geg = $game->player2; 
                    }
                    if ($game->act == $game->player2)
                    {
                        $game->pos[$x][$y] = 2;
                        $geg = $game->player1; 
                    }
                    //gegner
                    $send = array('type'=>'setPos', 'value'=>$game->pos);
                    $this->sendGeg($geg, $send, $server);

                    //ich
                    $sender->send(json_encode($send));
                    $game->act = $geg;

                    $win = $this->controlPos($game->pos, $x, $y);
                    
                    if($win)
                    {
                        $this->deleteGameByUser($sender->id, $server);
                        $send = array('type'=>'roundFin', 'value'=>array("win"=>$win));
                        $this->sendGeg($geg, $send, $server);

                        //ich
                        $sender->send(json_encode($send));
                    }
                }
            }
        }  
    }
    
    function controlPos($pos, $x, $y)
    {
        for ($i = 1; $i <= 2; $i++)
        {
            for ($h = 0; $h <= 2; $h++)
            {
                if ($pos[$h][0] == $i AND $pos[$h][1] == $i AND $pos[$h][2] == $i)
                {
                    return $i;
                }
                if ($pos[0][$h] == $i AND $pos[1][$h] == $i AND $pos[2][$h] == $i)
                {
                    return $i;
                }
            }
            if ($pos[0][0] == $i AND $pos[1][1] == $i AND $pos[2][2] == $i)
            {
                return $i;
            }
            if ($pos[0][2] == $i AND $pos[1][1] == $i AND $pos[2][0] == $i)
            {
                return $i;
            }
        }
        
        foreach($pos as $x)
        {
            foreach($x as $y)
            {
                if ($y == 0)
                {
                    return false;
                }
            }
        }
        
        return 3;
    }
    
    function disConClient($server, $sender)
    {
        foreach ($this->players as $key => $player)
        {
            if ($player->id == $sender->id)
            {
                unset($this->players[$key]);
            }
        }
        if ($this->wait == $sender->id)
        {
            $this->wait = 0;
        }
        $this->deleteGameByUser($sender->id, $server, true);
    }
    
    function deleteGameByUser($userid, $server, $kickUser = false)
    {
        foreach ($this->games as $key => $game)
        {
            if ($game->player2 == $userid )
            {
                unset($this->games[$key]);
                if ($kickUser)
                {
                    $send = array('type'=>'gegLos', 'value'=>'');
                    $this->sendGeg($game->player1, $send, $server);
                }
            }
            if ($game->player1 == $userid)
            {
                unset($this->games[$key]);
                if ($kickUser)
                {
                    $send = array('type'=>'gegLos', 'value'=>'');
                    $this->sendGeg($game->player2, $send, $server);
                }
            }
        }
    }
    
    function getGameById($gameid)
    {
        foreach ($this->games as $game)
        {
            if ($game->id == $gameid)
            {
                return $game;
            }
        }
        return false;
    }
    
    function getPlayerNameById($id)
    {
        foreach ($this->players as $player)
        {
            if ($player->id == $id)
            {
                return $player->name;
            }
        }
    }
    
    function sendGeg($id, $send, $server)
    {
        $clients = $server->getClients();
        foreach ($clients as $client) {
            if ($id == $client->id)
            {
                $client->send(json_encode($send));
            }
        }
    }
    
    function ifNew($server, $sender)
    {
        $send;
        foreach ($this->players as $player)
        {
            if ($player->id == $sender->id)
            {
                $send = array('type'=>'isNew', 'value'=>'true');
                break;
            }
        }
        if (!$send)
        {
            $send = array('type'=>'isNew', 'value'=>'false');
        }
        $sender->send(json_encode($send));
    } 
}

class player {
    var $id;
    var $name;
}

class game {
    var $id;
    var $player1;
    var $player2;
    var $pos = array(array(0,0,0,0,0),array(0,0,0,0,0),array(0,0,0,0,0),array(0,0,0,0,0),array(0,0,0,0,0));
    var $act;
}

class Server implements SocketListener {
    
        var $game;
    
        public function __construct(gameplay $games) {
            $this->game = $games;
        }
    
	public function onMessageRecieved(
		SocketServer $server,
		SocketClient $sender,
		$message
	) {
			//$sender->send($message);
                        /*$clients = $server->getClients();

                        foreach ($clients as $client) {
                            if ($sender->id != $client->id)
                            {
                                $client->send($message);
                            }
                        }*/
                        
                        $request = json_decode($message, true);
                        $value = $request['value'];
                        switch($request['type'])
                        {
                           case 'ifNew':
                               $this->game->ifNew($server, $sender);
                               break;
                            case 'setName':
                               if($this->game->setUser($value, $server, $sender))
                               {
                                   $send = array('type'=>'nameOK', 'value'=>'true');
                                   
                               }
                               else
                               {
                                   $send = array('type'=>'nameOK', 'value'=>'false');
                               }
                               $sender->send(json_encode($send));
                               break;
                           case 'newGame':
                                if(!$this->game->newGame($server, $sender))
                                {
                                    $send = array('type'=>'wait', 'value'=>'true');
                                    $sender->send(json_encode($send));
                                }
                               break;
                          case 'setPos':
                               $this->game->setPos($server, $sender, $value);
                               break;
                        }
                        
                       // exit();
	}

	public function onClientConnected(SocketServer $server, SocketClient $newClient) {
            
	}

	public function onClientDisconnected(SocketServer $server, SocketClient $leftClient) {
            $this->game->disConClient($server, $leftClient);
	}

	public function onLogMessage(
		SocketServer $server,
		$message
	) {
                echo $message."\n";
                //exit();
	}
}


try {
	$game = new gameplay();
        $server = new Server($game);

	$webSocket = new SocketServer('send.local', 8090);
	$webSocket->addListener($server);
	$webSocket->start();
} catch (Exception $e) {
	echo 'Fatal exception occured: '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine()."\n";
}

?>