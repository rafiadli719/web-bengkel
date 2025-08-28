<?php
$connection = new mysqli("localhost","fitmotor_LOGIN","Sayalupa12","fitmotor_dbbengkel");
if (! $connection){
    die("Error in connection".$connection->connect_error);
}