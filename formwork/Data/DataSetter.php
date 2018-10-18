<?php

namespace Formwork\Data;

class DataSetter extends DataGetter
{
    /**
     * Set a data value by key
     *
     * @param string $key
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
}
