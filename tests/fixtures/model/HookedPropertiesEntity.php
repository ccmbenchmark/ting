<?php

namespace tests\fixtures\model;

class HookedPropertiesEntity
{
    public string $hookGetOnly = 'default' {
        get => $this->hookGetOnly . ' (hooked on get)';
    }

    public string $hookSetOnly = 'default' {
        set(string $value) {
            $this->hookSetOnly = $value . ' (hooked on set)';
        }
    }

    public string $hookBoth = 'default' {
        get => $this->hookBoth . ' (hooked on get)';
        set(string $value) {
            $this->hookBoth = $value . ' (hooked on set)';
        }
    }
}