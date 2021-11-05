<?php

namespace gipfl\LinuxHealth;

class Cpu
{
    public static function getCounters($procFile = '/proc/stat')
    {
        $info = [];
        $cpus = [];

        $cpuKeys = [     // From 'man proc':
            'user',      // Time spent in user mode.
            'nice',      // Time spent in user mode with low priority (nice).
            'system',    // Time spent in system mode.
            'idle',      // Time spent in the idle task.
            // This value should be USER_HZ times the second entry in the
            // /proc/uptime pseudo-file.
            'iowait',    // Time waiting for I/O to complete. (Linux >= 2.5.41)
            'irq',       // Time servicing interrupts. (Linux >= 2.6.0-test4)
            'softirq',   // Time servicing softirqs. (Linux >= 2.6.0-test4)
            'steal',     // Stolen time, which is the time spent in other operating
            // systems when running in a virtualized environment
            // (Linux >= 2.6.11)
            'guest',     // Time spent running a virtual CPU for guest operating systems
            // under the control of the Linux kernel. (Linux >= 2.6.24)
            'guest_nice' // Time spent running a niced guest (virtual CPU for guest
            // operating systems under the control of the Linux kernel).
            // (Linux >= 2.6.33)
        ];

        // TODO:
        // ctxt 891299797    -> The number of context switches that the system underwent
        // btime 1540828526  -> boot   time,  in  seconds  since  the  Epoch
        // processes 2079015 -> Number of forks since boot
        // procs_running 6   -> Number of processes in  runnable  state
        // procs_blocked 0   -> Number  of processes blocked waiting for I/O to complete

        foreach (file($procFile, FILE_IGNORE_NEW_LINES) as $line) {
            $parts = preg_split('/\s+/', $line);
            $key = array_shift($parts);
            if (substr($key, 0, 3) === 'cpu') {
                // TODO: handle count mismatch
                $cpus[$key] = array_combine(
                    array_slice($cpuKeys, 0, count($parts), true),
                    $parts
                );

                for ($i = count($cpus[$key]) - 1; $i < count($cpuKeys); $i++) {
                    $cpus[$key][$cpuKeys[$i]] = 0;
                }
            } else {
                $info[$key] = $parts;
            }
        }

        return $cpus;
    }
}
