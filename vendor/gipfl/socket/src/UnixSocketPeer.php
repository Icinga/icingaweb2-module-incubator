<?php

namespace gipfl\Socket;

use gipfl\Json\JsonSerialization;

class UnixSocketPeer implements JsonSerialization
{
    /** @var int */
    protected $pid;

    /** @var int */
    protected $uid;

    /** @var int */
    protected $gid;

    /** @var string */
    protected $username;

    /** @var ?string */
    protected $fullName;

    /** @var string */
    protected $groupName;

    public function __construct($pid, $uid, $gid, $username, $fullName, $groupName)
    {
        $this->pid = $pid;
        $this->uid = $uid;
        $this->gid = $gid;
        $this->username = $username;
        $this->fullName = $fullName;
        $this->groupName = $groupName;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return int
     */
    public function getGid()
    {
        return $this->gid;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string|null
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    public static function fromSerialization($any)
    {
        return new static($any->pid, $any->uid, $any->gid, $any->username, $any->fullName, $any->groupName);
    }

    public function jsonSerialize()
    {
        return (object) [
            'pid' => $this->pid,
            'uid' => $this->uid,
            'gid' => $this->gid,
            'username'  => $this->username,
            'fullName'  => $this->fullName,
            'groupName' => $this->groupName,
        ];
    }
}
