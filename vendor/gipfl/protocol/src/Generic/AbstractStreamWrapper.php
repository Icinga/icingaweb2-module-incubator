<?php

namespace gipfl\Protocol\Generic;

use Evenement\EventEmitterTrait;
use Exception;
use React\Stream\DuplexStreamInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;
use RuntimeException;

abstract class AbstractStreamWrapper implements DuplexStreamInterface
{
    use EventEmitterTrait;

    /** @var ReadableStreamInterface */
    protected $input;

    /** @var WritableStreamInterface */
    protected $output;

    private $closed = false;

    public function __construct(ReadableStreamInterface $in, WritableStreamInterface $out = null)
    {
        $this->readFrom($in);
        if ($out === null && $in instanceof WritableStreamInterface) {
            $this->writeTo($in);
        } else {
            $this->writeTo($out);
        }
    }

    abstract public function handleData($data);

    protected function readFrom(ReadableStreamInterface $input)
    {
        $this->input = $input;
        if (! $input->isReadable()) {
            $this->close();
            return;
        }
        $input->on('data', function ($data) {
            $this->handleData($data);
        });
        $input->on('end', function () {
            $this->handleEnd();
        });
        $input->on('close', function () {
            $this->close();
        });
        $input->on('error', function (Exception $error) {
            $this->handleError($error);
        });
    }

    protected function writeTo(WritableStreamInterface $output)
    {
        $this->output = $output;
        if (! $this->output->isWritable()) {
            $this->close();
            throw new RuntimeException('Cannot write to output');
        }

        $output->on('drain', function () {
            $this->handleDrain();
        });
        $output->on('close', function () {
            $this->close();
        });
        $output->on('error', function (Exception $error) {
            $this->handleError($error);
        });
    }

    protected function handleDrain()
    {
        $this->emit('drain');
    }

    protected function handleEnd()
    {
        if (! $this->closed) {
            $this->emit('end');
            $this->close();
        }
    }

    public function isReadable()
    {
        return !$this->closed && $this->input->isReadable();
    }

    public function isWritable()
    {
        return !$this->closed && $this->output->isWritable();
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->input->close();
        $this->output->close();

        $this->emit('close');
        $this->removeAllListeners();
    }

    public function pause()
    {
        $this->input->pause();
    }

    public function resume()
    {
        $this->input->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = [])
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    /**
     * @param Exception $error
     */
    protected function handleError(Exception $error)
    {
        $this->emit('error', [$error]);
        $this->close();
    }
}
