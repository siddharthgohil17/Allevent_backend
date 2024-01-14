<?php
 
 class DB {
    private $host = "mysql-3395be8e-siddharthgohil07-1a55.a.aivencloud.com";
    private $user = "avnadmin";
    private $pass = "AVNS_Btp7vSyDm7lE2Vwnh-0";
    private $database = "Events";
    private $port = "27353";
 
    //connect with  database
    public function connect() {
        try {
            $conn_str = "mysql:host=$this->host;port=$this->port;dbname=$this->database";
            $conn = new PDO($conn_str, $this->user, $this->pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $conn;
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            exit();
        }
    }
}

