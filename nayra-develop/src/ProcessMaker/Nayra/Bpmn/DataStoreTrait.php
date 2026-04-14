<?php

namespace ProcessMaker\Nayra\Bpmn;

use ProcessMaker\Nayra\Contracts\Bpmn\ItemDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;

/**
 * Implementation of the behavior for a data store.
 */
trait DataStoreTrait
{
    use FlowElementTrait;

    private $data = [];

    /**
     * @var \ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface
     */
    private $process;

    /**
     * @var \ProcessMaker\Nayra\Contracts\Bpmn\ItemDefinitionInterface
     */
    private $itemSubject;

    /**
     * Get owner process.
     *
     * @return ProcessInterface
     */
    public function getOwnerProcess()
    {
        return $this->process;
    }

    /**
     * Get Process of the application.
     *
     * @param \ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface $process
     *
     * @return ProcessInterface
     */
    public function setOwnerProcess(ProcessInterface $process)
    {
        $this->process = $process;
        $this->getId();

        return $this;
    }

    /**
     * Get data from store.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getData($name = null, $default = null)
    {
        return $name === null ? $this->data : (isset($this->data[$name]) ? $this->data[$name] : $default);
    }

    /**
     * Set data of the store.
     *
     * @param array $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Put data to store.
     *
     * @param string $name
     * @param mixed $data
     *
     * @return $this
     */
    public function putData($name, $data)
    {
        $this->data[$name] = $data;

        return $this;
    }

    /**
     * Get the items that are stored or conveyed by the ItemAwareElement.
     *
     * @return ItemDefinitionInterface
     */
    public function getItemSubject()
    {
        return $this->itemSubject;
    }

    /**
     * Get data using dot notation.
     *
     * @param string $path Dot notation path (e.g., 'user.profile.name')
     * @param mixed $default Default value if path doesn't exist
     *
     * @return mixed
     */
    public function getDotData($path, $default = null)
    {
        $keys = explode('.', $path);
        $current = $this->data;

        // Navigate through the path
        foreach ($keys as $key) {
            // Handle numeric keys for arrays
            if (is_numeric($key)) {
                $key = (int) $key;
            }

            if (!isset($current[$key])) {
                return $default;
            }

            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Set data using dot notation.
     *
     * @param string $path Dot notation path (e.g., 'user.profile.name')
     * @param mixed $value Value to set
     *
     * @return $this
     */
    public function setDotData($path, $value)
    {
        $keys = explode('.', $path);
        $firstKey = $keys[0];
        $current = &$this->data;

        // Navigate to the parent of the target key
        for ($i = 0; $i < count($keys) - 1; $i++) {
            $key = $keys[$i];

            // Handle numeric keys for arrays
            if (is_numeric($key)) {
                $key = (int) $key;
            }

            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        // Set the final value
        $finalKey = $keys[count($keys) - 1];
        if (is_numeric($finalKey)) {
            $finalKey = (int) $finalKey;
        }

        $current[$finalKey] = $value;
        // Keep compatibility with putData method (required by PM Core)
        $this->putData($firstKey, $this->data[$firstKey]);

        return $this;
    }
}
