<?php
namespace MyApp;
use PDO;
class Db {
    private static $_ins = null;
    private $_pdo,
            $_query,
            $_error = false;
    public  $_count = 0,
            $_results;
    public function __construct(){
        try {
            $servername  = "localhost";
            $db_name     = "chat_v2";
            $username    = "root";
            $password    = "";
            $this->_pdo = new \PDO("mysql:host=$servername;dbname=$db_name", $username, $password);
        } catch(PDOException $e){
            die($e->getMessage());
        }
    }
    public static function getInst(){
        if(!isset(self::$_ins)){
            self::$_ins = new Db();
        }
        return self::$_ins;
    }
    public function query($query,$parameters = array()){
        $this->_error = false;
        if($this->_query = $this->_pdo->prepare($query)){
            if(count($parameters) > 0){
                $x = 1;
                foreach($parameters as $parameter){
                    $this->_query->bindValue($x,$parameter);
                    $x++;
                }
            }
            if($this->_query->execute()){
               $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
               $this->_count   = $this->_query->rowCount();
            }else{
                $this->_error = true;
            }
        }
        return $this;
    }
    public function insert($table,$array){
        $i = 0;
        $x = 0;
        $cols = "";
        $vals = [];
        $insert = "";
        $params = "";
        foreach($array as $key => $value){
            $cols.= $key;
            $params.= "?";
            $i++;
            if($i != count($array)){
                $cols .= ",";
                $params.=",";
            }
            $vals[] = $value;
        }
        $Query =  "INSERT INTO ".$table. "(".$cols.")"." VALUES (".$params.")";
        if(!$this->query($Query,$vals)->_error){
            return true;
        }
        return false;   
    }

    public function delete($table,$conds){
        $x = 0;
        $stms = "";
        $params = [];
        foreach($conds as $stm => $val){
            $stms.=$stm." = ?";
            $x++;
            $params[] = $val;
            if($x != count($conds)){
                $stms.=" AND ";
            }
        }
        $Query = "DELETE FROM ".$table." WHERE ".$stms;
        if(!$this->query($Query,$params)->_error){
            return true;
        }
        return false;
    }
    public function update($table,$sets,$conds){
        $x = 0;
        $i = 0;
        $stms = "";
        $cols = "";
        $params = [];
        foreach($sets as $col => $val){
            $cols.=$col." = ?";
            $i++;
            $params[] = $val;
            if($i != count($sets)){
                $cols.=" , ";
            }
        }
        foreach($conds as $stm => $val){
            $stms.=$stm." = ?";
            $x++;
            $params[] = $val;
            if($x != count($conds)){
                $stms.=" AND ";
            }
        }
        $Query = "UPDATE ".$table." SET ".$cols." WHERE ".$stms;
        if(!$this->query($Query,$params)->_error){
            return true;
        }
        return false;
        
    }
    public function select($selectors,$table,$conds = array()){
        $x = 0;
        $i = 0;
        $stm = "";
        $stms = "";
        $params = [];
        foreach($selectors as $selector){
            $stm.=$selector;
            $i++;
            if($i != count($selectors)){
                $stm.=",";
            }
        }
        foreach($conds as $stm_e => $val){
            $params[] = $val;
            $stms.=$stm_e." = ?";
            $x++;
            if($x != count($conds)){
                $stms.=" AND ";
            }
        }
        $w = count($conds) > 0 ? " WHERE ".$stms : "";
        $Query = "SELECT ".$stm." FROM ".$table.$w;
        if(!$this->query($Query,$params)->_error){
            if(count($this->_results) == 1){
                return $this->_results[0];
            }
            return $this->_results;
        }
        return false;
    }
    public function get_count($table,$conds = array()){
        $x = 0;
        $stms = "";
        $params = [];
        if(count($conds) > 0){
            foreach($conds as $stm_e => $val){
                $params[] = $val;
                $stms.=$stm_e." = ?";
                $x++;
                if($x != count($conds)){
                    $stms.=" AND ";
                }
            }
            $Query = "SELECT id FROM $table WHERE $stms";
        }else{
            $Query = "SELECT id FROM $table";
        }
        if(!$this->query($Query,$params)->_error){
            return $this->_count;
        }
        return false;
    }
}