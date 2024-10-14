<?php

namespace IMEdge\Web\Grapher\Graph;

use Icinga\Web\UrlParams;
use InvalidArgumentException;

use function ctype_digit;
use function is_int;
use function sprintf;

class TimeRange
{
    protected const DEFAULT_START = 'end-1day';
    protected const DEFAULT_END = 'now';

    /** @var int|string|null */
    protected $start;
    /** @var int|string|null */
    protected $end;
    protected ?int $epochStart;
    protected ?int $epochEnd;
    protected ?int $step;

    /**
     * @param string|int|null $start
     * @param string|int|null $end
     * @param ?int $step
     */
    public function __construct($start = null, $end = null, ?int $step = null)
    {
        $this->setEnd($end);
        $this->setStart($start);
        $this->step = $step;
    }

    /**
     * @return int|string
     */
    public function getStart()
    {
        return $this->start ?? self::DEFAULT_START;
    }

    /**
     * @return int|string
     */
    public function getEnd()
    {
        return $this->end ?? self::DEFAULT_END;
    }

    /**
     * @param string|int|null $end
     */
    public function setEnd($end): void
    {
        if ($end === null) {
            $this->end = null;
            $this->epochEnd = null;
        } else {
            $this->epochEnd = self::wantEpoch($end);
            $this->end = $end;
        }
    }

    /**
     * @param string|int|null $start
     */
    public function setStart($start): void
    {
        if ($start === null) {
            $this->start = null;
            $this->epochStart = null;
        } else {
            $this->start = $start;
            $this->epochStart = $this->calculateStartEpoch($start);
        }
    }

    /**
     * @param int|string $start
     */
    protected function calculateStartEpoch($start): int
    {
        return self::wantEpoch(
            is_string($start)
                ? str_replace('end', (string) $this->getEnd(), $start)
                : $start
        );
    }

    /**
     * @param string|int|null $start
     * @param string|int|null $end
     */
    public function set($start, $end): void
    {
        $this->setEnd($end);
        $this->setStart($start);
    }

    public function getDuration(): int
    {
        return $this->getEpochEnd() - $this->getEpochStart();
    }

    public function getStep(): ?int
    {
        return $this->step;
    }

    public function endsNow(): bool
    {
        return $this->end === 'now';
    }

    public function getEpochStart(): int
    {
        return $this->epochStart ?? self::wantEpoch($this->calculateStartEpoch(self::DEFAULT_END));
    }

    public function getEpochEnd(): int
    {
        return $this->epochEnd ?? self::wantEpoch(self::DEFAULT_END);
    }

    public static function fromUrlParams(UrlParams $params): TimeRange
    {
        if ($step = $params->get('step')) {
            $step = (int) $step;
        }
        return new TimeRange(
            $params->get('start', self::DEFAULT_START),
            $params->get('end', self::DEFAULT_END),
            $step
        );
    }

    public function applyUrlParams(UrlParams $params): void
    {
        $this->setEnd($params->get('end', self::DEFAULT_END));
        $this->setStart($params->get('start', self::DEFAULT_START));
        if ($step = $params->get('step')) {
            $this->step = (int) $step;
        } else {
            $this->step = null;
        }
    }

    public function applyToUrlParams(UrlParams $params)
    {
        $params->set('start', $this->start);
        $params->set('end', $this->end);
        $params->set('step', $this->step);
    }

    protected static function wantEpoch($time): int
    {
        if (is_int($time)) {
            return $time;
        }
        if (ctype_digit($time)) {
            return (int) $time;
        }

        $epoch = strtotime($time);
        if ($epoch === false) {
            throw new InvalidArgumentException(sprintf('Epoch or AT-style time expected, got "%s"', $time));
        }

        return $epoch;
    }

    // TODO: parse.
    public function __toString()
    {
        return ShellParameter::renderOptional('start', $this->start)
            . ShellParameter::renderOptional('end', $this->end)
            . ShellParameter::renderOptional('step', $this->step);
    }
}
