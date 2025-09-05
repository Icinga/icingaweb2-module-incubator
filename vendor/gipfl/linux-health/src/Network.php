<?php

namespace gipfl\LinuxHealth;

class Network
{
    public static function getInterfaceCounters($procFile = '/proc/net/dev')
    {
        // Header looks like this:
        // Inter-|   Receive                                                |  Transmit
        //  face |bytes    packets errs drop fifo frame compressed multicast|bytes    packets
        //        (...from above line) errs drop fifo colls carrier compressed

        $lines = \file($procFile, FILE_IGNORE_NEW_LINES);
        \array_shift($lines);
        $headers = preg_split('/\|/', array_shift($lines));
        $rxHeaders = preg_split('/\s+/', $headers[1]);
        $txHeaders = preg_split('/\s+/', $headers[2]);

        $headers = [];
        foreach ($rxHeaders as $rx) {
            $headers[] = 'rx' . ucfirst($rx);
        }
        foreach ($txHeaders as $tx) {
            $headers[] = 'tx' . ucfirst($tx);
        }
        $interfaces = [];
        foreach ($lines as $line) {
            $parts = preg_split('/\s+|\|/', trim($line));
            $ifName = rtrim(array_shift($parts), ':');
            $interfaces[$ifName] = (object) array_combine($headers, $parts);
        }

        return $interfaces;
    }
}
