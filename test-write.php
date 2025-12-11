<?php
$target = '/Users/user/Documents/siskeu-app/public/uploads/proof/test.txt';

// Coba tulis file manual
$result = file_put_contents($target, 'testing 123');

if ($result !== false) {
    echo "✔ Folder writable! (PHP bisa menulis file)";
} else {
    echo "✘ Folder TIDAK bisa ditulis!";
}
