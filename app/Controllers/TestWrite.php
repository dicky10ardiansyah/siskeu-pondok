<?php

namespace App\Controllers;

class TestWrite extends BaseController
{
    public function index()
    {
        $target = WRITEPATH . '../public/uploads/proof/test.txt';

        $result = file_put_contents($target, 'testing 123');

        if ($result !== false) {
            return "✔ Folder writable! (PHP bisa menulis file)";
        } else {
            return "✘ Folder TIDAK bisa ditulis!";
        }
    }
}
