<?php
// Check PHP version and function availability
echo "<h3>PHP Environment Check</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "<hr>";

// Check if str_ends_with exists (PHP 8.0+)
if (function_exists('str_ends_with')) {
    echo "✅ str_ends_with() exists<br>";
} else {
    echo "❌ str_ends_with() NOT available (PHP < 8.0)<br>";
    echo "→ Need to add polyfill!<br>";
}

// Check if str_starts_with exists (PHP 8.0+)
if (function_exists('str_starts_with')) {
    echo "✅ str_starts_with() exists<br>";
} else {
    echo "❌ str_starts_with() NOT available (PHP < 8.0)<br>";
    echo "→ Need to add polyfill!<br>";
}
?>
