<?php

namespace tests\fixtures\ValueObject;

class BouhWithNativeType
{
    private int $nb;

    private float $avg;

    private string $myName;

    private \DateTime $date;

    private \DateTimeImmutable $dateTimeImmutable;

    private bool $bool;

    public function getNb(): int
    {
        return $this->nb;
    }

    public function setNb(int $nb): void
    {
        $this->nb = $nb;
    }

    public function getAvg(): float
    {
        return $this->avg;
    }

    public function setAvg(float $avg): void
    {
        $this->avg = $avg;
    }

    public function getMyName(): string
    {
        return $this->myName;
    }

    public function setMyName(string $myName): void
    {
        $this->myName = $myName;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): void
    {
        $this->date = $date;
    }

    public function getDateTimeImmutable(): \DateTimeImmutable
    {
        return $this->dateTimeImmutable;
    }

    public function setDateTimeImmutable(\DateTimeImmutable $dateTimeImmutable): void
    {
        $this->dateTimeImmutable = $dateTimeImmutable;
    }

    public function isBool(): bool
    {
        return $this->bool;
    }

    public function setBool(bool $bool): void
    {
        $this->bool = $bool;
    }
}
