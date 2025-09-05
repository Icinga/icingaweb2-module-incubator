<?php

namespace gipfl\Socket;

use RuntimeException;
use React\Socket\ConnectionInterface;
use function array_shift;
use function file_exists;
use function is_int;
use function posix_getgrgid;
use function posix_getpwuid;
use function preg_split;
use function socket_get_option;
use function socket_import_stream;
use function stat;
use function strlen;
use function trim;

class UnixSocketInspection
{
    /**
     * @param ConnectionInterface $connection
     * @return UnixSocketPeer
     */
    public static function getPeer(ConnectionInterface $connection)
    {
        $socket = socket_import_stream($connection->stream);
        $remotePid = static::getRemotePidFromSocket($socket);
        $stat = static::statProcFile($remotePid);
        $uid = $stat['uid'];
        $gid = $stat['gid'];
        $userInfo = static::getUserInfo($uid);
        $gecosParts = preg_split('/,/', $userInfo['gecos']);
        $fullName = trim(array_shift($gecosParts));
        $groupInfo = static::getGroupInfo($gid);

        return new UnixSocketPeer(
            $remotePid,
            $uid,
            $gid,
            $userInfo['name'],
            strlen($fullName) ? $fullName : null,
            $groupInfo['name']
        );
    }

    protected static function getRemotePidFromSocket($socket)
    {
        // SO_PEERCRED = 17
        $remotePid = socket_get_option($socket, SOL_SOCKET, 17);
        if (! is_int($remotePid) || ! $remotePid > 0) {
            throw new RuntimeException("Remote PID expected, got " . var_export($remotePid));
        }

        return $remotePid;
    }

    protected static function statProcFile($pid)
    {
        $procDir = "/proc/$pid";
        if (file_exists($procDir)) {
            return stat($procDir);
        } else {
            throw new RuntimeException("Got no proc dir ($procDir) for remote node");
        }
    }

    protected static function getUserInfo($uid)
    {
        $userInfo = posix_getpwuid($uid);

        if ($userInfo === false) {
            throw new RuntimeException("Unable to resolve remote UID '$uid'");
        }

        return $userInfo;
    }

    protected static function getGroupInfo($gid)
    {
        $groupInfo = posix_getgrgid($gid);

        if ($groupInfo === false) {
            throw new RuntimeException("Unable to resolve remote GID '$gid'");
        }

        return $groupInfo;
    }
}
