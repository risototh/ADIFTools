<?php
function getConfiguration() : array {
    $overrides = [
        'qth::' => 'common/PWWLo',
    ];

    $shortOpts = 't:f:';
    $longOpts = array_keys($overrides);
    $cliParams = getopt($shortOpts, $longOpts);

    $config = parse_ini_file(__DIR__ . '/config.ini', true);
    foreach ($overrides as $name => $path) {
        $name = trim($name, ':');
        if (array_key_exists($name, $cliParams)) {
            setRecursive($config, $path, $cliParams[$name]);
        }
    }

    $env = [
        'cli' => $cliParams,
        'config' => $config,
        ];

    return $env;
}

/**
 * Sets an element of a multidimensional array from a string containing path for each dimension.
 *
 * @param array &$array The array to manipulate
 * @param string $path Path to array leg, keys delimited by /
 * @param mixed $value The value that is assigned to the element
 */
function setRecursive(array &$array, string $path, mixed $value)
{
    $path = explode('/', $path);
    $key = array_shift($path);
    if (empty($path)) {
        $array[$key] = $value;
    }
    else {
        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = array();
        }
        setRecursive($array[$key], implode('/', $path), $value);
    }
}