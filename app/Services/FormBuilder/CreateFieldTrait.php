<?php

namespace Coyote\Services\FormBuilder;

trait CreateFieldTrait
{
    /**
     * @param string $name
     * @param string $type
     * @param Form $parent
     * @param array $options
     * @return mixed
     */
    protected function makeField($name, $type = 'text', $parent = null, array $options = [])
    {
        $fieldType = $this->getFieldType($type);
        $options = $this->setupFieldOptions($options);

        return new $fieldType($name, $type, $parent, $options);
    }

    /**
     * @param array $options
     * @return array
     */
    protected function setupFieldOptions(array $options)
    {
        $default = ['theme' => $this->getTheme()];

        foreach ($default as $name => $value) {
            if (empty($options[$name])) {
                $options[$name] = $value;
            }
        }

        return $options;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getFieldType($type)
    {
        return __NAMESPACE__ . '\\Fields\\' . ucfirst(camel_case($type));
    }
}
