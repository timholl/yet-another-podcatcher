<?php

namespace App\Tracklist;

final class Chapter
{
    /**
     * Chapter start time in seconds
     *
     * @var int
     */
    private $start;

    /**
     * Chapter end time in seconds
     *
     * @var int
     */
    private $end;

    /**
     * Title
     *
     * @var string
     */
    private $title;

    public function __construct(int $start, int $end, string $title)
    {
        $this->start = $start;
        $this->end = $end;
        $this->title = $title;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
