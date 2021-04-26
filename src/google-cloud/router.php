<?php

/**
 * Determine the function source file to load.
 */
$documentRoot = __DIR__.'/../../../';
if ($functionSource = $_SERVER['FUNCTION_SOURCE'] ?? null) {
    if (0 !== strpos($functionSource, '/')) {
        // Make the path relative
        $relativeSource = $documentRoot.$functionSource;
        if (!file_exists($relativeSource)) {
            throw new RuntimeException(sprintf('Unable to load function from "%s"', $relativeSource));
        }
        require_once $_SERVER['SCRIPT_FILENAME'] = $relativeSource;
    } else {
        require_once $_SERVER['SCRIPT_FILENAME'] = $functionSource;
    }
} elseif (file_exists($defaultSource = $documentRoot.'index.php')) {
    // Default to "index.php" in the root of the application.
    require_once $_SERVER['SCRIPT_FILENAME'] = $defaultSource;
} else {
    throw new RuntimeException('Did not find your index.php. Please define environment variable "FUNCTION_SOURCE".');
}
