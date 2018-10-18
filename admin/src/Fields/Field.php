<?php
namespace Formwork\Admin\Fields;

use Formwork\Data\DataSetter;
use LogicException;

class Field extends DataSetter
{
    /**
     * Field name
     *
     * @var string
     */
    protected $name;

    /**
     * Create a new Field instance
     *
     * @param string $name
     * @param array  $data
     */
    public function __construct($name, $data = array())
    {
        $this->name = $name;
        parent::__construct($data);
        if ($this->has('import')) {
            $this->importData();
        }
        if ($this->has('fields')) {
            $this->data['fields'] = new Fields($this->data['fields']);
        }
        Translator::translate($this);
    }

    /**
     * Return whether field is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->value());
    }

    /**
     * Get field name
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get field type
     *
     * @return string
     */
    public function type()
    {
        return $this->get('type');
    }

    /**
     * Get field label
     *
     * @return string
     */
    public function label()
    {
        return $this->get('label', $this->name());
    }

    /**
     * Get field placeholder label
     *
     * @return string
     */
    public function placeholder()
    {
        return $this->get('placeholder');
    }

    /**
     * Get field value
     */
    public function value()
    {
        return $this->get('value', $this->get('default'));
    }

    /**
     * Import data helper
     */
    protected function importData()
    {
        foreach ((array) $this->data['import'] as $key => $value) {
            if ($key === 'import') {
                throw new LogicException('Invalid key for import');
            }
            $callback = explode('::', $value, 2);
            if (!is_callable($callback)) {
                throw new LogicException('Invalid import callback "' . $value . '"');
            }
            $this->data[$key] = $callback();
        }
    }

    /**
     * __debugInfo() magic method
     *
     * @return array
     */
    public function __debugInfo()
    {
        $return['name'] = $this->name;
        if ($this->has('type')) {
            $return['type'] = $this->get('type');
        }
        if ($this->has('default')) {
            $return['default'] = $this->get('default');
        }
        if ($this->has('value')) {
            $return['value'] = $this->get('value');
        }
        if ($this->has('fields')) {
            $return['fields'] = $this->get('fields');
        }
        return $return;
    }
}
