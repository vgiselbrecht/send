<!DOCTYPE html>

<meta charset="utf-8" />

<title>WebSocket Test</title> 
<style media="screen">
    .tabl{
        border: 1px black solid;
    }
    .tfield{
        border: 1px black solid;
        width: 110px;
        height: 110px;   
    }
</style>   

<script language="javascript" type="text/javascript">

  var wsUri = "ws://send.local:8090";
  var output;
  var gameid;
  var al;
  var pln;
  var gn;

  function init()
  {
    output = document.getElementById("output");
    testWebSocket();
  }

  function testWebSocket()
  {
    websocket = new WebSocket(wsUri);
    websocket.onopen = function(evt) { onOpen(evt) };
    websocket.onclose = function(evt) { onClose(evt) };
    websocket.onmessage = function(evt) { onMessage(evt) };
    websocket.onerror = function(evt) { onError(evt) };
  }

  function onOpen(evt)
  {
    //writeToScreen("CONNECTED");
    document.getElementById('status').innerHTML = 'Status: Verbunden';
    var send = {
        "type":"ifNew",
        "value":""
        }; 
    doSend(JSON.stringify(send));
  }

  function onClose(evt)
  {
    //writeToScreen("DISCONNECTED");
    document.getElementById('status').innerHTML = 'Status: Getrennt';
  }

  function onMessage(evt)
  {
    //writeToScreen('<span style="color: blue;">RESPONSE: ' + evt.data+'</span>');
    //document.getElementById('output').innerHTML = evt.data;
    var request = JSON.parse(evt.data);
    var value = request.value;
    
    switch(request.type)
    {
        case 'nameOK':
            if (value == 'true'){
                document.getElementById('output').innerHTML = 'Bitte Warten!';
                newGame();
            }
            else
            {
                setName();
            }
            break;
        case 'wait':
            if (value == 'true'){
                document.getElementById('output').innerHTML = 'Bitte Warten bis ein Gegner gefunden wird!';
            }
            break;
        case 'gameStart':
                gameStart(value);
            break;
        case 'setPos':
                setPos(value);
            break;
         case 'roundFin':
                roundFin(value);
            break;
         case 'gegLos':
                gegLos();
            break;
         case 'isNew':
             if (value == 'false')
             {
                 setName();      
             }
             else
             {
                 newGame();
             }
             break;
    }
  }

  function onError(evt)
  {
    writeToScreen('<span style="color: red;">ERROR:</span> ' + evt.data);
  }

  function doSend(message)
  {
    //writeToScreen("SENT: " + message); 
    websocket.send(message);
  }

  function writeToScreen(message)
  {
    var pre = document.createElement("p");
    pre.style.wordWrap = "break-word";
    pre.innerHTML = message;
    output.appendChild(pre);
  }
  
  function sendName()
  {
    var send = {
        "type":"setName",
        "value":document.getElementById('namein').value
        }; 
    doSend(JSON.stringify(send));
  }
  
  function newGame()
  {
      var send = {
        "type":"newGame",
        "value":""
        }; 
    doSend(JSON.stringify(send));
  }
  
  function gameStart(value)
  {
      gameid = value.gid;
      var out = 'Sie spielen gegen: <b style="color:red">'+value.gegna+'</b><br><div id="acplay"></div><br>';
      var out = out + '<table cellspacing="0" cellpadding="0" class="tabl"><tr><td class="tfield" onclick="setX(1,1)" id="f_1_1"></td>\
                                                <td class="tfield" onclick="setX(1,2)" id="f_1_2"></td>\
                                                <td class="tfield" onclick="setX(1,3)" id="f_1_3"></td>\
                                           </tr>\
                                           <tr><td class="tfield" onclick="setX(2,1)" id="f_2_1"></td>\
                                                <td class="tfield" onclick="setX(2,2)" id="f_2_2"></td>\
                                                <td class="tfield" onclick="setX(2,3)" id="f_2_3"></td>\
                                            </tr>\
                                            <tr><td class="tfield" onclick="setX(3,1)" id="f_3_1"></td>\
                                                <td class="tfield" onclick="setX(3,2)" id="f_3_2"></td>\
                                                <td class="tfield" onclick="setX(3,3)" id="f_3_3"></td>\
                                            </tr></table>';
    document.getElementById('output').innerHTML = out;
    if (value.pn == 1)
        {
            document.getElementById('acplay').innerHTML = '<b style="color:blue">Sie sind dran</b>';
            al = 1;
            pln = 1;
            gn = 2;
        }
        else 
        {
            document.getElementById('acplay').innerHTML = '<b style="color:red">Ihr Gegner ist dran</b>';
            al = 0;
            pln = 2;
            gn = 1;
        }
  }
  
  function setPos(value)
  {
        for (var i = 1; i <=5 ;i++)
        {
            for (var j = 1; j <=5 ;j++)
            {
                var id = 'f_'+i+'_'+j;
                if (value[i-1][j-1] == pln)
                {
                    document.getElementById(id).innerHTML = '<img src="images/bluex.png">';
                }
                else if (value[i-1][j-1] == gn)
                {
                    document.getElementById(id).innerHTML = '<img src="images/redx.png">';
                }
                
            }
        }
      
      
      if (al == 1)
      {
        document.getElementById('acplay').innerHTML = '<b style="color:red">Ihr Gegner ist dran</b>';
        al = 0;
      }
      else if (al == 0)
      {
        document.getElementById('acplay').innerHTML = '<b style="color:blue">Sie sind dran</b>';
        al = 1;
      }
  }
  
  function setName()
  {
    document.getElementById('output').innerHTML = '<form action="javascript:sendName()">Name: <input name="name" id="namein"><input onclick="" value="Weiter" type="submit"></form><br><br>';
  }
  
  function setX(x,y)
  {
    var send = {
        "type":"setPos",
        "value":[gameid,x,y]
        }; 
    doSend(JSON.stringify(send));
  }
  
  function roundFin(value)
  {
    if (value.win == gn)
    {
        document.getElementById('acplay').innerHTML = '<b style="color:red">Sei haben Verloren! </b><input type="submit" value="Neues Spiel" onclick="newGame()">';   
    }
    else if (value.win == pln)
    {
        document.getElementById('acplay').innerHTML = '<b style="color:blue">Sei haben Gewonnen! </b><input type="submit" value="Neues Spiel" onclick="newGame()">';
    }
    else 
    {
        document.getElementById('acplay').innerHTML = 'Spiel ist Fertig! Es gibt keinen Sieger!  <input type="submit" value="Neues Spiel" onclick="newGame()">          ';
    }
       
  }
  
  function gegLos()
  {
    document.getElementById('acplay').innerHTML = 'Der Gegner hat das Spiel verlassen! <input type="submit" value="Neues Spiel" onclick="newGame()">';
  }

  window.addEventListener("load", init, false);

</script>

<h2>Tic Tac Toe</h2>

<!--<textarea name="text" id="textinpute" onkeyup ="clicksend()"></textarea><br>-->

<div id="output"></div>

<div id="status"></div>

</html> 