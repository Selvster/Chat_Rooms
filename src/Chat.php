<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
/*
-> Message Types
=> [
    '1' => 'pending', => Duo
    '2' => 'connecting', =>Duo
    '3' => 'connected', => Duo,Group
    '4' => 'normal msg', => Duo,Group
    '5' => 'Disconnected', => Duo,Group
    '6' => 'Disconnected During waiting' => Duo
    '7' => connected //Current Client =>Group
   ]
-> Room Types
=> [
    '1' => 'Duo',
    '2' => 'Group'
   ]
*/
class Chat implements MessageComponentInterface {
    protected $clients;
    public function __construct() {
        $this->clients = [];
    }
    public function onOpen(ConnectionInterface $conn) {
        //Extract  Parameters
        $str = $conn->httpRequest->getUri()->getQuery();
        $type = explode('&',explode('=',$str)[1])[0];
        $ClientUsername = explode('&',explode('username=',$str)[1])[0];
        $time = explode('time=',$str)[1];
        //Asign ClientUsername to connection
        $this->clients[$ClientUsername] = $conn; 
        //Client Entered Group
        if($type == "Group"){
        //Check if Registered Before
            if(db::getInst()->get_count('users',array('username'=>$ClientUsername)) == 0){
            //Get Random Color
            $Colors = ['#00c7ff','#ff2828','#ffaa23','#450cff','#aa0000','#ff00e5','#00ff21','#00e9ff','#bc002f','#9e9e9e','#606060','#000000'];
            $Random_color = $Colors[rand(0,count($Colors)-1)];
            //Insert Into Users
            Db::getInst()->insert('users',array('username'=>$ClientUsername,'entered'=>'Group','color'=>$Random_color));
            //Send Message to current client
                //Get Current Time
            $to_client_Message = [
                'type' => '7',
                'color' => 'red',
                'time' => $time
            ];
            $this->clients[$ClientUsername]->send(json_encode($to_client_Message));
            //Check if Freem Room Exists
                if(Db::getInst()->query('SELECT id FROM group_chat_rooms WHERE clients_number < 10')->_count > 0){
                    //If Room Found
                        //Get Room-Id / Clients_Number
                    $Room_Data = db::getInst()->query("SELECT * FROM group_chat_rooms WHERE clients_number < 10 ORDER BY id ASC LIMIT 1")->_results[0];
                        //Insert into Room Users
                    db::getInst()->insert('users_rooms',array('username'=>$ClientUsername,'room_type'=>2,'room_id'=>$Room_Data->id));
                        //Update Clients Number
                    db::getInst()->update('group_chat_rooms',array('clients_number'=>$Room_Data->clients_number + 1),array('id'=>$Room_Data->id));
                        //Insert Into Messages
                    db::getInst()->insert('messages',array('sender_username'=>$ClientUsername,'room_id'=>$Room_Data->id,'room_type'=>2,'message_type'=>3,'color'=>$Random_color,'time'=>$time));
                        //Send Message to Connected Clients on Room
                            //Prepare Message
                    $Message = [
                        'type' => '3',
                        'username' => $ClientUsername,
                        'time' => $time,
                        'color' => $Random_color
                    ];
                            //Get Their Count
                    $clients_count = db::getInst()->query("SELECT id FROM users_rooms WHERE room_id = ? AND username <> ? AND room_type = 2",array($Room_Data->id,$ClientUsername))->_count;
                            //More than One
                    if($clients_count > 1){
                        foreach (db::getInst()->query("SELECT username FROM users_rooms WHERE room_id = ? AND username <> ? AND room_type = 2",array($Room_Data->id,$ClientUsername))->_results as $username) {
                            //Check if Connected
                            if(array_key_exists($username->username,$this->clients)){
                                $this->clients[$username->username]->send(json_encode($Message));
                            }
                        }
                                //One
                    }else if($clients_count == 1){
                        $Other_Client_Username = db::getInst()->query("SELECT username FROM users_rooms WHERE room_id = ? AND username <> ? AND room_type = 2",array($Room_Data->id,$ClientUsername))->_results[0]->username;
                        if(array_key_exists($Other_Client_Username,$this->clients)){
                            //Check if Connected
                            $this->clients[$Other_Client_Username]->send(json_encode($Message));
                        }
                    }
                    //If No Room Found
                }else{
                    //Insert Into Group Chat
                    db::getInst()->insert('group_chat_rooms',array('clients_number'=>1));
                    //Get Room-Id / Clients_Number
                    $Room_Data = db::getInst()->query("SELECT * FROM group_chat_rooms WHERE clients_number < 10 ORDER BY id ASC LIMIT 1")->_results[0];
                    //Insert into Room Users
                    db::getInst()->insert('users_rooms',array('username'=>$ClientUsername,'room_type'=>2,'room_id'=>$Room_Data->id));
                    //Insert into messages
                    db::getInst()->insert('messages',array('sender_username'=>$ClientUsername,'room_id'=>$Room_Data->id,'room_type'=>2,'message_type'=>3,'color'=>$Random_color,'time'=>$time));
                }
            }
        //Client Entered Duo
        }else{
            //Check if Registered Before
            if(db::getInst()->get_count('users',array('username'=>$ClientUsername)) == 0){
                //Get Random Color
                $Colors = ['#00c7ff','#ff2828','#ffaa23','#450cff','#aa0000','#ff00e5','#00ff21','#00e9ff','#bc002f','#9e9e9e','#606060','#000000'];
                $Random_color = $Colors[rand(0,count($Colors)-1)];
                //Insert Into Users
                Db::getInst()->insert('users',array('username'=>$ClientUsername,'entered'=>'Duo','color'=>$Random_color));
                //Check if other Client is waiting
                    //Waiting Client Found
                if(Db::getInst()->get_count('pending') > 0){
                    //Create Room
                    Db::getInst()->insert('duo_chat_rooms',array('full'=>0)); //check
                    //Get Room Id
                    $Room_id = Db::getInst()->select(array('id'),'duo_chat_rooms',array('full'=>0))->id;
                    //Get Other Client Username / Delete From Pending
                        //Get his Username
                    $Other_Client_Username = Db::getInst()->query("SELECT username FROM pending ORDER BY id Asc LIMIT 1")->_results[0]->username;
                        //Get his color
                    $Other_Client_Color = Db::getInst()->select(array('color'),'users',array('username'=>$Other_Client_Username))->color;
                        //Delete From Pending
                    Db::getInst()->delete('pending',array('username'=>$Other_Client_Username));
                    //Insert Both Clients to users rooms
                    Db::getInst()->insert('users_rooms',array('username'=>$ClientUsername,'room_type'=>1,'room_id'=>$Room_id));
                    Db::getInst()->insert('users_rooms',array('username'=>$Other_Client_Username,'room_type'=>1,'room_id'=>$Room_id));
                    //Make Room Full
                    Db::getInst()->update('duo_chat_rooms',array('full'=>1),array('id'=>$Room_id));
                    //Insert Connected Messages of Both Clients
                        //Insert
                    Db::getInst()->insert('messages',array('sender_username'=>$ClientUsername,'room_type'=>1,'room_id'=>$Room_id,'message_type'=>3,'color'=>$Random_color,'time'=>$time));
                    Db::getInst()->insert('messages',array('sender_username'=>$Other_Client_Username,'room_type'=>1,'room_id'=>$Room_id,'message_type'=>3,'color'=>$Other_Client_Color,'time'=>$time));
                    //Send Message to Both Clients
                        //Prepare Messages
                    $to_client_Message = [
                        'type' => '2',
                        'username' => $Other_Client_Username,
                        'color' => $Other_Client_Color,
                        'time' => $time
                    ];
                    $to_other_client_Message = [
                        'type' => '2',
                        'username' => $ClientUsername,
                        'color' => $Random_color,
                        'time' => $time
                    ];
                        //Send
                    $this->clients[$ClientUsername]->send(json_encode($to_client_Message));
                    $this->clients[$Other_Client_Username]->send(json_encode($to_other_client_Message));
                    //No Waiting Clients Found
                }else{                    
                    //Insert To waiting ->Pending
                    Db::getInst()->insert('pending',array('username'=>$ClientUsername));
                    //Send Waiting Message
                        //Prepare Message
                    $Message = [
                        'type' => '1'
                    ];
                        //Send
                    $this->clients[$ClientUsername]->send(json_encode($Message));
                }
            }
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        //Convert to Json 
        $Message_Object = json_decode($msg);
        //Get Type of Room of the sender
        $Type_of_room = DB::getInst()->select(array('entered'),'users',array('username'=>$Message_Object->username))->entered;
        if($Message_Object->type == "4"){
           //If User in Duo Room
            if($Type_of_room == "Duo"){
               //Get Room Id
               $Room_id = DB::getInst()->select(array('room_id'),'users_rooms',array('room_type'=>1,'username'=>$Message_Object->username))->room_id;
               //Insert to Messages
               Db::getInst()->insert('messages',array('sender_username'=>$Message_Object->username,'room_type'=>1,'room_id'=>$Room_id,'message_type'=>4,'text'=>$Message_Object->text,'color'=>$Message_Object->color,'time'=>$Message_Object->time));
               //Get Other Client
               $Other_Client_Username = DB::getInst()->query("SELECT username FROM users_rooms WHERE room_type = 1 AND room_id = ? AND username <> ?",array($Room_id,$Message_Object->username))->_results[0]->username;
               //Send to Him the Msg
                    //Prepare
                $Message = [
                    'type' => '4',
                    'username' => $Message_Object->username,
                    'color' => $Message_Object->color,
                    'time' => $Message_Object->time,
                    'text' => $Message_Object->text
                ];
                    //Send
                $this->clients[$Other_Client_Username]->send(json_encode($Message));
            //If User in Group Room
            }else{
                //Get Room Id
                $Room_id = DB::getInst()->select(array('room_id'),'users_rooms',array('room_type'=>2,'username'=>$Message_Object->username))->room_id;
                //Insert to Messages
                Db::getInst()->insert('messages',array('sender_username'=>$Message_Object->username,'room_type'=>2,'room_id'=>$Room_id,'message_type'=>4,'text'=>$Message_Object->text,'color'=>$Message_Object->color,'time'=>$Message_Object->time));
                //Send Message to Connected Clients on Room
                    //Prepare Message
                $Message = [
                    'type' => '4',
                    'username' => $Message_Object->username,
                    'time' => $Message_Object->time,
                    'color' => $Message_Object->color,
                    'text' => $Message_Object->text
                ];
                    //Get Other Clients Count
                $clients_count = db::getInst()->query("SELECT id FROM users_rooms WHERE username <> ? AND room_id = ? AND room_type = 2",array($Message_Object->username,$Room_id))->_count;
                    //More than One
                if($clients_count > 1){
                    foreach (db::getInst()->query("SELECT username FROM users_rooms WHERE username <> ? AND room_id = ? AND room_type = 2",array($Message_Object->username,$Room_id))->_results as $username) {
                        //Check if Connected
                        if(array_key_exists($username->username,$this->clients)){
                            $this->clients[$username->username]->send(json_encode($Message));
                        }
                    }
                    //One
                }else if($clients_count == 1){
                    $Other_Client_Username = db::getInst()->query("SELECT username FROM users_rooms WHERE username <> ? AND room_id = ? AND room_type = 2",array($Message_Object->username,$Room_id))->_results[0]->username;
                    if(array_key_exists($Other_Client_Username,$this->clients)){
                        //Check if Connected
                        $this->clients[$Other_Client_Username]->send(json_encode($Message));
                    }
                }
            }
        }else if($Message_Object->type == "5"){
            //Duo
            if($Type_of_room == "Duo"){
                //Get Room Id
                $Room_id = DB::getInst()->select(array('room_id'),'users_rooms',array('room_type'=>1,'username'=>$Message_Object->username))->room_id;
                 //Prepare Message
                 $To_Send_Message = [
                    'username' => $Message_Object->username,
                    'type' => '5'
                ];
                //Get Other User
                $Other_User = Db::getInst()->query("SELECT username FROM users_rooms WHERE room_type = 1 AND room_id = ? AND username <> ?",array($Room_id,$Message_Object->username))->_results[0]->username;
                //Send To the other client Message
                $this->clients[$Other_User]->send(json_encode($To_Send_Message));
                //Delete The Room
                Db::getInst()->delete('duo_chat_rooms',array('id'=>$Room_id));
                //Delete From users_rooms
                Db::getInst()->delete('users_rooms',array('username'=>$Message_Object->username));
                Db::getInst()->delete('users_rooms',array('username'=>$Other_User));
                //Delete Client
                Db::getInst()->delete('users',array('username'=>$Message_Object->username));
                //Delete Other Client
                Db::getInst()->delete('users',array('username'=>$Other_User));    
                //Delete Messages of The Room
                Db::getInst()->delete('messages',array('room_type'=>'1','room_id'=>$Room_id));
            //Group
            }else{
                //Get Room Id
                $Room_id = DB::getInst()->select(array('room_id'),'users_rooms',array('room_type'=>2,'username'=>$Message_Object->username))->room_id;
                //Get Number of clients of the room
                $clients_number = Db::getInst()->select(array('clients_number'),'group_chat_rooms',array('id'=>$Room_id))->clients_number;
                //Subtract One from clients number
                Db::getInst()->update('group_chat_rooms',array('clients_number'=>$clients_number - 1),array('id'=>$Room_id));
                //Delete Client from users
                Db::getInst()->delete('users',array('username'=>$Message_Object->username));
                //Delete From users rooms
                Db::getInst()->delete('users_rooms',array('username'=>$Message_Object->username));
                //Delete his Messages
                Db::getInst()->delete('messages',array('sender_username'=>$Message_Object->username));
                //Prepare Message
                $Message = [
                    'username' => $Message_Object->username,
                    'type' => '5'
                ];
                //Get Other Clients count
                $clients_count = DB::getInst()->get_count('users_rooms',array('room_type'=>2,'room_id'=>$Room_id));
                //If More than one client in room
                if($clients_count > 1){
                    //Get Username of each Client
                    foreach (DB::getInst()->select(array('username'),'users_rooms',array('room_type'=>2,'room_id'=>$Room_id)) as $username) {
                        //Check if currently connected
                        if(array_key_exists($username->username,$this->clients)){
                            //Send Message to other Clients
                            $this->clients[$username->username]->send(json_encode($Message));
                        }
                    }
                //If One client is found
                }else if($clients_count == 1){
                    //Get his Username
                    $username = DB::getInst()->select(array('username'),'users_rooms',array('room_type'=>2,'room_id'=>$Room_id))->username;
                    //Check if connected
                    if(array_key_exists($username,$this->clients)){
                        //Send Message to the other client
                        $this->clients[$username]->send(json_encode($Message));
                    }
                }    
            }
        }else if($Message_Object->type == "6"){
            //Delete From users
            DB::getInst()->delete('users',array('username'=>$Message_Object->username));
            //Delete From Pending
            DB::getInst()->delete('pending',array('username'=>$Message_Object->username)); 
        }
      
    }
    public function onClose(ConnectionInterface $conn) {
       $conn->close();
    }
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}