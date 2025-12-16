<?php

namespace tests\fixtures\ValueObject;

class WrongObject
{
    private $wrong;

    /**
     * @return mixed
     */
    public function getWrong()
    {
        return $this->wrong;
    }

    /**
     * @param mixed $wrong
     */
    public function setWrong($wrong): void
    {
        $this->wrong = $wrong;
    }
}
