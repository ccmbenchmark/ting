<?php

namespace fastorm;

interface NotifyPropertyInterface
{
    public function addPropertyListener(PropertyListenerInterface $listener);
}
