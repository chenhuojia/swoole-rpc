<?php
abstract class TcpInterface
{

   abstract protected function start();

    abstract protected function recv();

    abstract protected function send();

}