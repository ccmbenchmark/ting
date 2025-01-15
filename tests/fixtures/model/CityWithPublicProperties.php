<?php

namespace tests\fixtures\model;

class CityWithPublicProperties
{
    public int $id;
    public string $name;
    private ?CountryWithPublicProperties $country = null;

    public function setCountry(CountryWithPublicProperties $country): void
    {
        $this->country = $country;
    }
    
    public function getCountry(): ?CountryWithPublicProperties
    {
        return $this->country;
    }
}